<?php

namespace App\Services\Mail;

use Curl;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class GpswoxMailerTransport implements TransportInterface
{
    private const BASE_URL = 'http://localhost';

    private string $multipartBoundary;

    public function __construct(private Curl $client, string $apiKey)
    {
        $this->client->options['CURLOPT_RETURNTRANSFER'] = true;
        $this->client->headers['Authorization'] = $apiKey;

        $this->multipartBoundary = '-------------' . uniqid();
    }

    /**
     * {@inheritdoc}
     */
    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        if (!$message instanceof Email) {
            throw new \InvalidArgumentException('Invalid email message.');
        }

        $data = $this->getData($message);
        $attachments = $this->getAttachments($message);

        if (count($attachments) > 0) {
            $data['attachments'] = $attachments;
        }

        $data = $this->generateMultipartContents($data, $this->multipartBoundary);

        $this->post($data);

        return new SentMessage($message, $envelope ?? new Envelope($message->getFrom(), $message->getTo()));
    }

    private function generateMultipartContents(array $data, string $boundary, string $prefix = ''): string
    {
        $contents = '';

        foreach ($data as $key => $datum) {
            if ($prefix && is_numeric($key)) {
                $key = '';
            }

            $name = $prefix ? $prefix . '[' . $key . ']' : $key;

            if (isset($datum['__file'])) {
                $contents .= '--' . $boundary . "\r\n";
                // "filename" attribute is not essential; server-side scripts may use it
                $contents .= 'Content-Disposition: form-data; name="' . $name . '";' .
                    ' filename="' . $datum['filename'] . '"' . "\r\n";
                // this is, again, informative only; good practice to include though
                if (isset($datum['type'])) {
                    $contents .= 'Content-Type: ' . $datum['type'] . "\r\n";
                }
                // this end-line must be here to indicate end of headers
                $contents .= "\r\n";
                // the file itself (note: there's no encoding of any kind)
                $contents .= $datum['contents'] . "\r\n";
            } elseif (is_array($datum)) {
                $contents .= $this->generateMultipartContents($datum, $boundary, $name);
            } else {
                $contents .= "--" . $boundary . "\r\n";
                $contents .= 'Content-Disposition: form-data; name="' . $name . '"';
                $contents .= "\r\n\r\n";
                $contents .= "$datum\r\n";
            }
        }

        if (!$prefix) {
            $contents .= "--" . $boundary . "--\r\n";
        }

        return $contents;
    }

    private function getData(Email $email): array
    {
        $data = [
            'subject' => $email->getSubject(),
            'body' => $this->getContents($email),
        ];

        if ($from = $this->getFrom($email)) {
            $data['from'] = $from;
        }

        if ($replyTo = $this->getReplyTo($email)) {
            $data['reply_to'] = $replyTo;
        }

        $recipients = $this->getRecipients($email);
        $data['to'] = $recipients['to'];

        if (isset($recipients['cc'])) {
            $data['cc'] = $recipients['cc'];
        }

        if (isset($recipients['bcc'])) {
            $data['bcc'] = $recipients['bcc'];
        }

        return $data;
    }

    private function getRecipients(Email $email): array
    {
        $setter = fn (array $addresses) => array_map(fn($address) => $address->getAddress(), $addresses);

        $recipients = ['to' => $setter($email->getTo())];

        if ($cc = $email->getCc()) {
            $recipients['cc'] = $setter($cc);
        }

        if ($bcc = $email->getBcc()) {
            $recipients['bcc'] = $setter($bcc);
        }

        return $recipients;
    }

    private function getFrom(Email $email)
    {
        $from = $email->getFrom();

        return $from ? ['address' => $from[0]->getAddress(), 'name' => $from[0]->getName()] : null;
    }

    private function getReplyTo(Email $email)
    {
        $replyTo = $email->getReplyTo();

        return $replyTo ? ['address' => $replyTo[0]->getAddress(), 'name' => $replyTo[0]->getName()] : null;
    }

    private function getContents(Email $email): string
    {
        return $email->getHtmlBody() ?? $email->getTextBody() ?? '';
    }

    private function getAttachments(Email $email): array
    {
        $attachments = [];

        foreach ($email->getAttachments() as $attachment) {
            $attachments[] = [
                '__file'    => true,
                'filename'  => $attachment->getPreparedHeaders()->getHeaderParameter('Content-Disposition', 'filename'),
                'type'      => $attachment->getMediaType() . '/' . $attachment->getMediaSubtype(),
                'contents'  => $attachment->getBody(),
            ];
        }

        return $attachments;
    }

    private function post($payload)
    {
        $this->client->headers['Content-Type'] = 'multipart/form-data; boundary=' . $this->multipartBoundary;
        $this->client->headers['Content-Length'] = strlen($payload);

        $response = $this->client->post(self::BASE_URL . '/api/send-email', $payload, 'multipart/form-data');

        $statusCode = $response->headers['Status-Code'];
        $message = $response->body;

        if ($statusCode === '200') {
            return $message;
        }

        if ($statusCode === '422') {
            $message = $this->format422Response($message);
        }

        throw new \RuntimeException($message);
    }

    private function format422Response(string $json): string
    {
        $data = json_decode($json, true);

        if (!$data || empty($data['data'])) {
            return $json;
        }

        $message = '';

        foreach ($data['data'] as $field) {
            $message .= implode('', $field);
        }

        return $message;
    }

    public function __toString(): string
    {
        return 'gpswox';
    }
}