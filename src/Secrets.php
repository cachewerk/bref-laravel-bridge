<?php

namespace CacheWerk\BrefLaravelBridge;

use Aws\Ssm\SsmClient;


use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;

class Secrets
{
    /**
     * Inject AWS SSM parameters into environment.
     *
     * @param  string  $path
     * @param  array  $parameters
     * @return void
     */
    public static function injectIntoEnvironment(string $path, array $parameters)
    {
        $parameters = \file_exists('/opt/extensions/AWSParametersAndSecretsLambdaExtension')
            ? self::resolveUsingLambdaExtension($path, $parameters)
            : self::resolveUsingAwsSdk($path, $parameters);

        $injected = [];

        foreach ($parameters as $key => $value) {
            $injected[] = isset($_ENV[$key]) ? "{$key} (overwritten)" : $key;
            $_ENV[$key] = $value;
        }

        if (! empty($injected)) {
            fwrite(STDERR, 'Injected runtime secrets: ' . implode(', ', $injected) . PHP_EOL);
        }
    }

    /**
     * Resolves secrets using Lambda extension.
     *
     * @param  string  $path
     * @param  array  $parameters
     * @return void
     */
    protected static function resolveUsingLambdaExtension(string $path, array $parameters): array
    {
        $httpFactory = new \Illuminate\Http\Client\Factory;
        $httpPool = new \Illuminate\Http\Client\Pool($httpFactory);

        foreach ($parameters as $secret) {
            $httpPool->as($secret)->withHeaders([
                'X-Aws-Parameters-Secrets-Token' => env('AWS_SESSION_TOKEN'),
            ])->get('http://localhost:2773/systemsmanager/parameters/get', [
                'name' => $path . $secret,
                'withDecryption' => 'true',
            ]);
        }

        return collect($httpPool->getRequests())
            ->map(fn (PendingRequest $request) => $request->getPromise()->wait())
            ->filter(fn (Response $response) => $response->ok())
            ->map(fn (Response $response) => $response->json('Parameter.Value'))
            ->toArray();
    }

    /**
     * Resolves secrets from AWS Systems Manager using SDK.
     *
     * @param  string  $path
     * @param  array  $parameters
     * @return void
     */
    protected static function resolveUsingAwsSdk(string $path, array $parameters): array
    {
        $ssm = new SsmClient([
            'version' => 'latest',
            'region' => $_ENV['AWS_REGION'] ?? $_ENV['AWS_DEFAULT_REGION'],
        ]);

        $response = $ssm->getParameters([
            'Names' => array_map(fn ($name) => trim($path) . trim($name), $parameters),
            'WithDecryption' => true,
        ]);

        return collect($response['Parameters'] ?? [])
            ->mapWithKeys(fn (array $secret) => [trim(strrchr($secret['Name'], '/'), '/') => $secret['Value']])
            ->toArray();
    }
}
