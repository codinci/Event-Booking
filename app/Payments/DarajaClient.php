<?php

namespace App\Payments;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class DarajaClient
{
    /**
     * An authenticated request to the Daraja sandbox.
     *
     * @param  array<string, mixed>  $config  Needs `key` and `secret`.
     */
    public static function request(array $config): PendingRequest
    {
        $baseUrl = rtrim((string) config('payments.daraja_base_url'), '/');

        $token = Http::baseUrl($baseUrl)->timeout(8)
            ->withBasicAuth($config['key'], $config['secret'])
            ->get('/oauth/v1/generate', ['grant_type' => 'client_credentials'])
            ->throw()->json('access_token');

        if (! is_string($token) || $token === '') {
            throw new ConnectionException('Daraja did not return an access token.');
        }

        return Http::baseUrl($baseUrl)->timeout(8)->withToken($token);
    }

    /** The public base URL Daraja should call back to. */
    public static function callbackBase(): string
    {
        return rtrim(config('payments.daraja_callback_url') ?: config('app.url'), '/');
    }
}
