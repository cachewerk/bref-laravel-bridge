<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Servable Assets
    |--------------------------------------------------------------------------
    |
    | Here you can configure list of public assets that should be servable
    | from your application's domain instead of only being servable via
    | the public S3 "asset" bucket or the AWS CloudFront CDN network.
    |
    */

    'assets' => [
        // 'favicon.ico',
        // 'robots.txt',
    ],

    /*
    |--------------------------------------------------------------------------
    | Shared Log Context
    |--------------------------------------------------------------------------
    |
    | In order to make debugging a little easier, the Lambda `X-Request-ID`
    | value can be added to the shared log context automatically.
    |
    */

    'request_context' => false,

    /*
    |--------------------------------------------------------------------------
    | Write logs as JSON
    |--------------------------------------------------------------------------
    |
    | By default, logs are written to CloudWatch as plain text.
    | If you prefer, you can have them logged as JSON objects instead.
    |
    */

    'logs_as_json' => false,

];
