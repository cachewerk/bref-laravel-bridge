<?php

namespace CacheWerk\BrefLaravelBridge;

use Aws\Ssm\SsmClient;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class Secrets
{
    protected const LAMDA_EXTENSION_BINARY_PATH = '/opt/extensions/AWSParametersAndSecretsLambdaExtension';

    /**
     * Inject AWS SSM parameters into environment.
     *
     * @param  string  $path
     * @param  array  $parameters
     * @return void
     */
    public static function injectIntoEnvironment(string $path, array $parameters)
    {
        $resolved = \file_exists(self::LAMDA_EXTENSION_BINARY_PATH)
            ? self::resolveUsingLambdaExtension($path, $parameters)
            : self::resolveUsingAwsSdk($path, $parameters);

        $injected = [];

        foreach ($resolved as $key => $value) {
            $injected[] = isset($_ENV[$key]) ? "{$key} (overwritten)" : $key;
            $_ENV[$key] = $value;
        }

        if (! empty($injected)) {
            fwrite(STDERR, 'Injected runtime secrets: ' . implode(', ', $injected) . PHP_EOL);
        }
    }

    protected static function resolveUsingLambdaExtension(string $path, array $parameters): array
    {
        $responses = Http::pool(
            fn (Pool $pool) => collect($parameters)->map(
                fn (string $secret) => $pool->as($secret)->withHeaders([
                    'X-Aws-Parameters-Secrets-Token' => env('AWS_SESSION_TOKEN'),
                ])->get('http://localhost:2773/systemsmanager/parameters/get', [
                    'name' => $path . $secret,
                    'withDecryption' => 'true',
                ])
            )
        );

        return collect($responses)
            ->filter(fn (Response $response) => $response->ok())
            ->map(fn (Response $response) => $response->json('Parameter.Value'))
            ->toArray();
    }

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
