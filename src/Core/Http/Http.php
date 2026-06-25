<?php
namespace HexaGen\Core\Http;

use HexaGen\Core\Support\Traits\Macroable;

class Http
{
    use Macroable;

    public static function withHeaders(array $headers): PendingRequest
    {
        return (new PendingRequest())->withHeaders($headers);
    }

    public static function withToken(string $token, string $type = 'Bearer'): PendingRequest
    {
        return (new PendingRequest())->withToken($token, $type);
    }

    public static function withBasicAuth(string $username, string $password): PendingRequest
    {
        return (new PendingRequest())->withBasicAuth($username, $password);
    }

    public static function baseUrl(string $url): PendingRequest
    {
        return (new PendingRequest())->baseUrl($url);
    }

    public static function timeout(int $seconds): PendingRequest
    {
        return (new PendingRequest())->timeout($seconds);
    }

    public static function retry(int $times, int $sleepMs = 100): PendingRequest
    {
        return (new PendingRequest())->retry($times, $sleepMs);
    }

    public static function asJson(): PendingRequest
    {
        return (new PendingRequest())->asJson();
    }

    public static function asForm(): PendingRequest
    {
        return (new PendingRequest())->asForm();
    }

    public static function acceptJson(): PendingRequest
    {
        return (new PendingRequest())->acceptJson();
    }

    public static function withoutVerifying(): PendingRequest
    {
        return (new PendingRequest())->withoutVerifying();
    }

    public static function get(string $url, array $query = []): HttpResponse
    {
        return (new PendingRequest())->get($url, $query);
    }

    public static function post(string $url, array $data = []): HttpResponse
    {
        return (new PendingRequest())->post($url, $data);
    }

    public static function postJson(string $url, array $data = []): HttpResponse
    {
        return (new PendingRequest())->postJson($url, $data);
    }

    public static function put(string $url, array $data = []): HttpResponse
    {
        return (new PendingRequest())->put($url, $data);
    }

    public static function patch(string $url, array $data = []): HttpResponse
    {
        return (new PendingRequest())->patch($url, $data);
    }

    public static function delete(string $url, array $data = []): HttpResponse
    {
        return (new PendingRequest())->delete($url, $data);
    }
}
