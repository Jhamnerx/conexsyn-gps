<?php

namespace App\Services\Mail;


use Curl;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Mail\MailManager as IlluminateMailManager;
use Illuminate\Support\Arr;

class MailManager extends IlluminateMailManager
{
    /**
     * Create an instance of the Sendgrid Transport driver.
     */
    protected function createSendgridTransport()
    {
        $config = $this->app['config']->get('services.sendgrid', []);

        $client = new HttpClient(Arr::get($config, 'guzzle', []));

        return new SendgridTransport($client, $config['secret']);
    }

    /**
     * Create an instance of the Postmark Transport driver.
     *
     * @return \App\Services\Mail\PostmarkTransport
     */
    protected function createPostmarkTransport(array $config)
    {
        $config = $this->app['config']->get('services.postmark', []);

        $client = new HttpClient(Arr::get($config, 'guzzle', []));

        return new PostmarkTransport($client, $config['secret']);
    }

    protected function createGpswoxMailerTransport(): GpswoxMailerTransport
    {
        $config = $this->app['config']->get('services.gpswoxmailer', []);

        return new GpswoxMailerTransport(new Curl(), $config['api_key']);
    }

    public function createGmailTransport(): GmailTransport
    {
        $config = $this->app['config']->get('services.gmail', []);

        $client = new HttpClient(Arr::get($config, 'guzzle', []));

        return new GmailTransport($client, $config);
    }
}
