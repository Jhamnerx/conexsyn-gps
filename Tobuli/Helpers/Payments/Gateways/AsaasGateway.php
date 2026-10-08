<?php

namespace Tobuli\Helpers\Payments\Gateways;

use App\Exceptions\PaymentsConfigurationException;
use App\Exceptions\PaymentsUnavailableException;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use InvalidArgumentException;
use Tobuli\Entities\Order;
use Tobuli\Entities\User;

class AsaasGateway extends PaymentGateway implements PaymentGatewayInterface
{
    private array $config;
    private string $baseUri;
    private Client $client;

    public function __construct()
    {
        $config = settings('payments.asaas');
        $this->config = $config;

        $this->baseUri = $this->isSandboxMode()
            ? 'https://sandbox.asaas.com/api/v3'
            : 'https://api.asaas.com/v3';

        $this->client = new Client([
            RequestOptions::HEADERS => ['access_token' => $config['api_key']]
        ]);

        parent::__construct();
    }

    /**
     * @see https://docs.asaas.com/reference/criar-nova-cobranca
     */
    public function pay($user, Order $order): RedirectResponse
    {
        $this->validatePaymentConditions($order);

        $customer = $this->getCustomer($user);

        $plan = $order->plan;

        $data = [
            'customer'      => $customer['id'],
            'billingType'   => 'UNDEFINED',
            'value'         => $plan->price,
            'dueDate'       => Carbon::now()->addDay()->format('Y-m-d'),
            'description'   => "$plan->title ($plan->duration_value $plan->duration_type)",
            'callback' => [
                'successUrl' => route('payments.success'),
            ],
        ];

        try {
            $response = $this->client->post($this->baseUri . '/payments', [
                RequestOptions::JSON => $data,
            ])->getBody()->getContents();
        } catch (RequestException $e) {
            $this->handleException($e);
        }

        $response = json_decode($response, true);

        $this->storeSubscription($user, $order, $response['id']);

        return Redirect::away($response['invoiceUrl']);
    }

    /**
     * @see https://docs.asaas.com/docs/criando-uma-assinatura
     */
    public function subscribe($user, Order $order)
    {
        $this->validatePaymentConditions($order);

        $cycle = $this->getSubscriptionCycle($order);

        if (!$cycle) {
            return $this->pay($user, $order);
        }

        $customer = $this->getCustomer($user);

        $plan = $order->plan;

        $data = [
            'customer'      => $customer['id'],
            'billingType'   => 'UNDEFINED',
            'value'         => $plan->price,
            'nextDueDate'   => Carbon::now()->format('Y-m-d'),
            'cycle'         => $cycle,
            'description'   => "$plan->title ($plan->duration_value $plan->duration_type)",
        ];

        try {
            $response = $this->client->post($this->baseUri . '/subscriptions', [
                RequestOptions::JSON => $data,
            ])->getBody()->getContents();
        } catch (RequestException $e) {
            $this->handleException($e);
        }

        $response = json_decode($response, true);

        $this->storeSubscription($user, $order, $response['id']);

        return view('front::Subscriptions.success')->with([
            'message' => trans('front.follow_payment_instructions_in_your_email')
        ]);
    }

    /**
     * @see https://docs.asaas.com/reference/listar-clientes
     * @see https://docs.asaas.com/reference/criar-novo-cliente
     */
    private function getCustomer(User $user): ?array
    {
        $client = $user->client;

        try {
            $response = $this->client->get($this->baseUri . '/customers', [
                RequestOptions::QUERY => array_filter([
                    'email'     => $user->email,
                    'cpfCnpj'   => $client->personal_code ?? null,
                ]),
            ])->getBody()->getContents();
        } catch (RequestException $e) {
            $this->handleException($e);
        }

        $response = json_decode($response, true);

        if (isset($response['data'][0])) {
            return $response['data'][0];
        }

        $this->validateUserConditions($user);

        try {
            $response = $this->client->post($this->baseUri . '/customers', [
                RequestOptions::JSON => array_filter([
                    'email'     => $user->email,
                    'cpfCnpj'   => $client->personal_code,
                    'name'      => trim($client->first_name . ' ' . $client->last_name) ?: $user->email,
                    'phone'     => $user->phone_number,
                    'address'   => $client->address,
                    'company'   => $user->company->name ?? null,
                ]),
            ])->getBody()->getContents();
        } catch (RequestException $e) {
            $this->handleException($e);
        }

        return json_decode($response, true);
    }

    public function payCallback(Request $request): Response
    {
        return new Response('Payments are processed by webhook.');
    }

    public function subscribeCallback(Request $request): Response
    {
        return $this->payCallback($request);
    }

    public function checkout(Order $order)
    {
        $this->validatePaymentConditions($order);

        return Redirect::route('payments.subscribe', [
            'order_id'  => $order->id,
            'gateway'   => $this->gatewayName(),
        ]);
    }

    /**
     * @see https://docs.asaas.com/docs/sobre-os-webhooks - General
     * @see https://docs.asaas.com/docs/webhook-para-cobrancas - Payments
     */
    public function webhook(Request $request): Response
    {
        $requestBody = $request->getContent();

        if ($request->headers->get('asaas-access-token') !== $this->config['access_token']) {
            return new Response('', 400);
        }

        $body = json_decode($requestBody, true);

        if (!$body || !isset($body['event'])) {
            $this->handleException(new InvalidArgumentException('Invalid response: ' . $requestBody));
        }

        $eventType = $body['event'];

        $sandbox = $this->isSandboxMode();

        if ($eventType === 'PAYMENT_CONFIRMED' && $sandbox) {
            return $this->webhookPaymentCompleted($body['payment']);
        }

        if ($eventType === 'PAYMENT_RECEIVED' && !$sandbox) {
            return $this->webhookPaymentCompleted($body['payment']);
        }

        return new Response();
    }

    private function webhookPaymentCompleted(array $data): Response
    {
        $id = $data['subscription'] ?? ($data['id'] ?? null);

        if ($id) {
            $this->activateSubscription($id);
        }

        return new Response();
    }

    /**
     * @see https://docs.asaas.com/reference/webhook-para-cobrancas-criar-ou-atualizar-configuracoes
     */
    public function isConfigCorrect(Request $request): bool
    {
        try {
            $response = $this->client->get($this->baseUri . '/webhooks')->getBody()->getContents();
        } catch (RequestException $e) {
            $statusCode = $e->getResponse()->getStatusCode();

            if ($statusCode === 401) {
                $msg = 'Wrong API key';
            } elseif ($statusCode !== 404) {
                $msg = $e->getMessage();
            }

            if (isset($msg)) {
                $this->handleException(new PaymentsConfigurationException($msg));
            }
        }

        $response = json_decode($response, true);

        if (empty($response['data'][0]['authToken']) || $response['data'][0]['authToken'] !== $this->config['access_token']) {
            $this->handleException(new PaymentsConfigurationException('Invalid Access token (webhook)'));
        }

        return true;
    }

    public function isSubscriptionRenewed($subscription): bool
    {
        $item = $this->getGatewaySubscription($subscription);

        if (!$item) {
            return false;
        }

        if ($item['status'] !== 'ACTIVE') {
            return false;
        }

        $subscriptionDate = Carbon::parse($subscription->expiration_date);
        $gatewayDate = empty($item['nextDueDate']) ? null : Carbon::parse($item['nextDueDate']);
        $planDurationInDays = $subscription->order->plan ? $subscription->order->plan->getDurationInDays() : null;

        return $this->compareDatesByPlan($subscriptionDate, $gatewayDate, $planDurationInDays);
    }

    public function isSubscriptionActive($subscription): bool
    {
        $item = $this->getGatewaySubscription($subscription);

        return $item && $item['status'] === 'ACTIVE';
    }

    /**
     * @see https://docs.asaas.com/reference/atualizar-assinatura-existente
     */
    public function cancelSubscription($subscription): bool
    {
        try {
            $this->client->put($this->baseUri . '/subscriptions/' . $subscription->gateway_id, [
                RequestOptions::JSON => ['status' => 'INACTIVE'],
            ]);
        } catch (RequestException $e) {
            return false;
        }

        return true;
    }

    /**
     * @see https://docs.asaas.com/reference/recuperar-uma-unica-assinatura
     */
    private function getGatewaySubscription($subscription): ?array
    {
        try {
            $response = $this->client->get($this->baseUri . '/subscriptions/' . $subscription->gateway_id)
                ->getBody()
                ->getContents();
        } catch (RequestException $e) {
            $this->handleException($e);
        }

        return json_decode($response, true);
    }

    private function getSubscriptionCycle(Order $order): ?string
    {
        $plan = $order->plan;
        $units = $plan->duration_type;
        $duration = $plan->duration_value;

        if ($units === 'days') {
            switch ($duration) {
                case 7:
                    return 'WEEKLY';
                case 14:
                    return 'BIWEEKLY';
                case 30:
                    return 'MONTHLY';
                case 365:
                    return 'YEARLY';
            }
        }

        if ($units === 'months') {
            switch ($duration) {
                case 1:
                    return 'MONTHLY';
                case 3:
                    return 'QUARTERLY';
                case 6:
                    return 'SEMIANNUALLY';
                case 12:
                    return 'YEARLY';
            }
        }

        if ($units === 'years' && $duration === 1) {
            return 'YEARLY';
        }

        return null;
    }

    private function validatePaymentConditions(Order $order)
    {
        if ($order->plan->price < 5) {
            throw new PaymentsUnavailableException(trans('validation.pay_provider_min_charge', [
                'pay_provider' => 'Asaas',
                'amount' => 'R$5'
            ]));
        }
    }

    private function validateUserConditions(User $user)
    {
        if (empty($user->client->personal_code)) {
            throw new PaymentsUnavailableException(
                trans('validation.required', [
                    'attribute' => trans('validation.attributes.personal_code')
                ])
            );
        }
    }

    private function isSandboxMode(): bool
    {
        return $this->config['environment'] === 'sandbox';
    }
}
