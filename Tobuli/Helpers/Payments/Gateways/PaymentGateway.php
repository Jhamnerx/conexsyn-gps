<?php

namespace Tobuli\Helpers\Payments\Gateways;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Tobuli\Entities\Order;
use Tobuli\Entities\Subscription;

abstract class PaymentGateway
{
    private $logger = null;

    public function __construct()
    {
        if (config('tobuli.payments_error_log')) {
            $this->logger = (new Logger('payments'))
                ->pushHandler(new StreamHandler(storage_path('logs/payments.log')));
        }
    }

    public function storeConfig($request, $gateway)
    {
        settings('payments.gateways.' . $gateway, $request->active ? 1 : 0);
        settings('payments.' . $gateway,
            array_merge(settings('payments.' . $gateway), $request->except('_token', 'active'))
        );
    }

    public function renewSubscription($subscription, $expirationDate = null)
    {
        $subscription->renew($expirationDate);
    }

    public function isSubscriptionRenewed($subscription)
    {
        return $this->isSubscriptionActive($subscription);
    }

    public function getSubscriptionEnd($subscription)
    {
        return $subscription->expiration_date;
    }

    public function webhook(Request $request)
    {
        return response('', 404);
    }

    protected function gatewayName()
    {
        $name = (new \ReflectionClass($this))->getShortName();
        $name = str_replace('Gateway', '', $name);

        return Str::snake($name);
    }

    protected function storeSubscription($user, Order $order, $gateway_id)
    {
        $subscription = Subscription::create([
            'user_id'    => $user->id,
            'gateway'    => $this->gatewayName(),
            'gateway_id' => $gateway_id,
            'order_id'   => $order->id,
        ]);

        return $subscription;
    }

    protected function activateSubscription($gateway_id, $options = [])
    {
        $subscription = Subscription::where('gateway_id', $gateway_id)->first();

        if ( ! $subscription) {
            $this->handleException(new Exception('Subscription not found for activation!'));
        }

        $this->cancelSiblings($subscription);

        $subscription->activateEntity($options);
    }

    protected function cancelSiblings($subscription)
    {
        $siblingSubscriptions = Subscription::active()
            ->where('id', '!=', $subscription->id)
            ->where('user_id', $subscription->user_id)
            ->whereHas('order', function($query) use ($subscription) {
                $query->where('entity_id', $subscription->order->entity_id);
                $query->where('entity_type', $subscription->order->entity_type);
            })
            ->get();

        foreach ($siblingSubscriptions as $siblingSubscription) {
            try {
                $siblingSubscription->cancel();
            } catch (Exception $e) {}
        }
    }

    /**
     * Compares two dates and checks if they make period with length of plan duration
     */
    protected function compareDatesByPlan(
        Carbon $expirationDateByPlan,
        Carbon $gatewayDate,
        ?int $planDurationInDays,
        int $maxDiffPercentage = 30
    ): bool {
        $diffPercentage = $this->getDatesDiffByPlan($expirationDateByPlan, $gatewayDate, $planDurationInDays);

        if ($diffPercentage === null) {
            return $gatewayDate->gt($expirationDateByPlan);
        }

        return $diffPercentage >= $maxDiffPercentage;
    }

    /**
     * Compares two dates and checks if they make period with length of plan duration
     */
    protected function getDatesDiffByPlan(
        Carbon $expirationDate,
        Carbon $compareDate,
        ?int $planDurationInDays
    ): ?float {
        if (empty($planDurationInDays)) {
            return null;
        }

        $planDurationInMinutes = $planDurationInDays * 24 * 60;
        $diffInMinutes = $expirationDate->diffInMinutes($compareDate, false);
        $diffPercentage = ($diffInMinutes / $planDurationInMinutes) * 100;

        return $diffPercentage;
    }

    protected function handleException(Exception $exception, $logMessage = null)
    {
        if ($this->logger) {
            if ($logMessage === null) {
                $logMessage = $exception->getMessage();
            } else {
                $logMessage = is_string($logMessage) ? $logMessage : json_encode($logMessage);
            }

            $this->logger->error($logMessage, [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        }

        throw $exception;
    }
}