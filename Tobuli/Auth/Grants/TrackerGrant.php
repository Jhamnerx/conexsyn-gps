<?php

namespace Tobuli\Auth\Grants;


use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\PasswordGrant;
use Psr\Http\Message\ServerRequestInterface;
use Tobuli\Entities\Device;

class TrackerGrant extends PasswordGrant
{
    /**
     * @param ServerRequestInterface $request
     *
     * @return Device
     * @throws OAuthServerException
     */
    protected function validateUser(ServerRequestInterface $request, ClientEntityInterface $client)
    {
        $imei = $this->getRequestParameter('imei', $request);

        if (!\is_string($imei)) {
            throw OAuthServerException::invalidRequest('imei');
        }

        $device = Device::where('imei', $imei)->first();

        if ($device instanceof Device === false) {
            throw OAuthServerException::invalidCredentials();
        }

        return $device;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return 'tracker';
    }
}