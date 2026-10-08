<?php namespace App\Services\Mail;

use App\Mail\BaseMail;
use Carbon\Carbon;
use CustomFacades\Appearance;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Mailer\Transport\SendmailTransport;

class MailHelper
{
    protected $fallback;

    protected function recipients($to)
    {
        if (empty($to))
            return null;

        if ( ! is_array($to))
            $to = explode(';', $to);

        $to = array_filter($to, function($value) { return is_string($value); });
        $to = array_map('trim', $to);
        $to = array_filter($to, function($value) { return !empty($value); });

        return $to;
    }

    public function fallback(bool $value = true)
    {
        $this->fallback = $value;

        return $this;
    }

    public function send($to, $subject, $body, $attaches = [])
    {
        $recipients = $this->recipients($to);

        if (empty($recipients))
            return [
                'status' => false,
                'error'  => 'Empty recipients'
            ];

        $messeage = new BaseMail($subject, $body, $attaches);
        $messeage->to($recipients);

        if ($from = Appearance::getSetting('noreply_email')) {
            $name = Appearance::getSetting('from_name');
            $messeage->from($from, empty($name) ? null : $name);
        }

        try
        {
            Mail::send($messeage);
        }
        catch (ClientException $e) {
            $error = $e->getMessage();

            $response = $e->getResponse();

            if ( $response && $response->getStatusCode() == 422 )
                $this->fallback = FALSE;
        }
        catch (\Exception $e) {
            $error = $e->getMessage();
        }

        if (!empty($error) && $this->fallback) {
            $backupTransport = Mail::getFacadeRoot()->getSymfonyTransport();

            Mail::getFacadeRoot()->setSymfonyTransport(new SendmailTransport());

            try {
                Mail::send($messeage);
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }

            Mail::getFacadeRoot()->setSymfonyTransport($backupTransport);
        }

        if (config('tobuli.log_send_mail_template')) {
            $data = [
                Carbon::now(),
                $subject,
                implode(';', $recipients),
                empty($error) ? 'OK' : $error
            ];

            File::append(storage_path('logs/send_email_template.log'), implode(', ', $data) . "\n");
        }

        return [
            'status' => empty($error),
            'error'  => empty($error) ? NULL : $error
        ];
    }
}