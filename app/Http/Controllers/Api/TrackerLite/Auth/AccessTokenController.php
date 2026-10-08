<?php


namespace App\Http\Controllers\Api\TrackerLite\Auth;

use App\Console\Commands\ServerPassportCommand;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\Http\Controllers\AccessTokenController AS PassportAccessTokenController;
use Psr\Http\Message\ServerRequestInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Tobuli\Exceptions\ValidationException;
use Nyholm\Psr7\Response as Psr7Response;


class AccessTokenController extends PassportAccessTokenController
{
    /**
     * Authorize a client to access the user's account.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $request
     * @return \Illuminate\Http\Response
     */
    public function token(ServerRequestInterface $request)
    {
        $validator = Validator::make(request()->all(), [
            'imei' => 'required',
        ]);

        if ($validator && $validator->fails())
            throw new ValidationException($validator->errors());



        $client = $this->getClient();

        $request = $request->withParsedBody(array_merge(
            $request->getParsedBody(),
            [
                'grant_type' => 'tracker',
                'client_id' => $client->id,
                'client_secret' => $client->secret,
            ]
        ));

        return $this->convertResponse(
            $this->server->respondToAccessTokenRequest($request, new Psr7Response)
        );
    }

    /**
     * Authorize a client to access the user's account.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $request
     * @return \Illuminate\Http\Response
     */
    public function refresh(ServerRequestInterface $request)
    {
        $client = $this->getClient();

        $request = $request->withParsedBody(array_merge(
            $request->getParsedBody(),
            [
                'grant_type' => 'refresh_token',
                'client_id' => $client->id,
                'client_secret' => $client->secret,
            ]
        ));

        return $this->convertResponse(
            $this->server->respondToAccessTokenRequest($request, new Psr7Response)
        );
    }

    protected function getClient()
    {
        $client = ServerPassportCommand::getClientAppTrackerLite();

        if ($client)
            return $client;

        throw new AuthorizationException('TrackerLite API client not fount.');
    }

    /**
     * Convert a PSR7 response to a Illuminate Response.
     *
     * @param  \Psr\Http\Message\ResponseInterface  $psrResponse
     * @return \Illuminate\Http\Response
     */
    public function convertResponse($psrResponse)
    {
        return new Response(
            $psrResponse->getBody(),
            $psrResponse->getStatusCode(),
            $psrResponse->getHeaders()
        );
    }
}