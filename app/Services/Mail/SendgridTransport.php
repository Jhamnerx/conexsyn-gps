<?php

namespace App\Services\Mail;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Stringable;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\Part\DataPart;

/**
 * @link https://github.com/s-ichikawa/laravel-sendgrid-driver/blob/master/src/Transport/SendgridTransport.php
 */
class SendgridTransport extends AbstractTransport implements Stringable
{
    /**
     * https://docs.sendgrid.com/api-reference/mail-send/mail-send
     */
    private const BASE_URL = 'https://api.sendgrid.com/v3/mail/send';

    /**
     * @deprecated use REQUEST_BODY_PARAMETER instead
     */
    const SMTP_API_NAME = 'sendgrid/request-body-parameter';
    const REQUEST_BODY_PARAMETER = 'sendgrid/request-body-parameter';

    private array $attachments = [];
    private int $numberOfRecipients = 0;

    public function __construct(
        private ClientInterface $client,
        private string $apiKey,
        private ?string $endpoint = null
    ) {
        $this->endpoint = $endpoint ?? self::BASE_URL;

        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $origMessage = $message->getOriginalMessage();

        if (!$origMessage instanceof Message) {
            throw new \InvalidArgumentException('Invalid email message');
        }

        $email = MessageConverter::toEmail($origMessage);

        $data = [
            'personalizations' => $this->getPersonalizations($email),
            'from' => $this->getFrom($email),
            'subject' => $email->getSubject(),
        ];

        if ($contents = $this->getContents($email)) {
            $data['content'] = $contents;
        }

        if ($reply_to = $this->getReplyTo($email)) {
            $data['reply_to'] = $reply_to;
        }

        $attachments = $this->getAttachments($email);
        if (count($attachments) > 0) {
            $data['attachments'] = $attachments;
        }

        $data = $this->setParameters($email, $data);

        $payload = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $data,
        ];

        $response = $this->post($payload);

        $messageId = $response->getHeaderLine('X-Message-Id');

        $message->setMessageId($messageId);

        $message->getOriginalMessage()
            ->getHeaders()
            ->addTextHeader('X-Sendgrid-Message-Id', $messageId);
    }

    private function getPersonalizations(Email $email): array
    {
        $personalization['to'] = $this->setAddress($email->getTo());

        if (count($email->getCc()) > 0) {
            $personalization['cc'] = $this->setAddress($email->getCc());

        }

        if (count($email->getBcc()) > 0) {
            $personalization['bcc'] = $this->setAddress($email->getBcc());

        }

        return [$personalization];
    }

    /**
     * @param Address[] $addresses
     */
    private function setAddress(array $addresses): array
    {
        $recipients = [];
        foreach ($addresses as $address) {
            $recipient = ['email' => $address->getAddress()];
            if ($address->getName() !== '') {
                $recipient['name'] = $address->getName();
            }
            $recipients[] = $recipient;
        }
        return $recipients;
    }

    private function getFrom(Email $email): array
    {
        if (count($email->getFrom()) > 0) {
            foreach ($email->getFrom() as $from) {
                return ['email' => $from->getAddress(), 'name' => $from->getName()];
            }
        }
        return [];
    }

    private function getContents(Email $email): array
    {
        $contents = [];
        if (!is_null($email->getTextBody())) {
            $contents[] = [
                'type' => 'text/plain',
                'value' => $email->getTextBody(),
            ];
        }

        if (!is_null($email->getHtmlBody())) {
            $contents[] = [
                'type' => 'text/html',
                'value' => $email->getHtmlBody(),
            ];
        }

        return $contents;
    }

    private function getReplyTo(Email $email): ?array
    {
        if (count($email->getReplyTo()) > 0) {
            $replyTo = $email->getReplyTo()[0];
            return [
                'email' => $replyTo->getAddress(),
                'name' => $replyTo->getName(),
            ];
        }
        return null;
    }

    private function getAttachments(Email $email): array
    {
        $attachments = [];
        foreach ($email->getAttachments() as $attachment) {
            $filename = $this->getAttachmentName($attachment);
            if ($filename === self::REQUEST_BODY_PARAMETER) {
                continue;
            }

            $attachments[] = [
                'content' => base64_encode($attachment->getBody()),
                'filename' => $this->getAttachmentName($attachment),
                'type' => $this->getAttachmentContentType($attachment),
                'disposition' => $attachment->getHeaders()->getHeaderBody('Content-Disposition'),
                'content_id' => $attachment->getContentId(),
            ];
        }
        return $attachments;
    }

    private function getAttachmentName(DataPart $dataPart): string
    {
        return $dataPart->getPreparedHeaders()->getHeaderParameter('Content-Disposition', 'filename');
    }

    private function getAttachmentContentType(Datapart $dataPart): string
    {
        return $dataPart->getMediaType() . '/' . $dataPart->getMediaSubtype();
    }

    private function setParameters(Email $email, array $data): array
    {
        $smtpApi = [];
        foreach ($email->getAttachments() as $attachment) {
            $name = $attachment->getPreparedHeaders()->getHeaderParameter('Content-Disposition', 'filename');
            if ($name === self::REQUEST_BODY_PARAMETER) {
                $smtpApi = self::decode($attachment->getBody());
            }
        }

        if (count($smtpApi) < 1) {
            return $data;
        }

        foreach ($smtpApi as $key => $val) {
            switch ($key) {
                case 'api_key':
                    $this->apiKey = $val;
                    continue 2;
                case 'personalizations':
                    $this->setPersonalizations($data, $val);
                    continue 2;
                case 'attachments':
                    $val = array_merge($this->attachments, $val);
                    break;
            }
            Arr::set($data, $key, $val);
        }

        return $data;
    }

    public static function decode($strParams): array
    {
        if (!is_string($strParams)) {
            return (array)$strParams;
        }
        $params = json_decode($strParams, true);
        return is_array($params) ? $params : [];
    }

    /**
     * @param array $data
     * @param array $personalizations
     * @return void
     */
    private function setPersonalizations(array &$data, array $personalizations): void
    {
        foreach ($personalizations as $index => $params) {
            foreach ($params as $key => $val) {
                if (in_array($key, ['to', 'cc', 'bcc'])) {
                    Arr::set($data, 'personalizations.' . $index . '.' . $key, $val);
                    ++$this->numberOfRecipients;
                } else {
                    Arr::set($data, 'personalizations.' . $index . '.' . $key, $val);
                }
            }
        }
    }

    /**
     * @throws ClientException|GuzzleException
     */
    private function post(array $payload): ResponseInterface
    {
        return $this->client->request('POST', $this->endpoint, $payload);
    }

    public function __toString(): string
    {
        return 'sendgrid';
    }
}