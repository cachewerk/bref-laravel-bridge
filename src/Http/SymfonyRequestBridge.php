<?php

namespace CacheWerk\BrefLaravelBridge\Http;

use Bref\Context\Context;
use Bref\Event\Http\HttpRequestEvent;
use Bref\Event\Http\Psr7Bridge;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Request;

class SymfonyRequestBridge
{
    /**
     * Convert Bref HTTP event to Symfony request.
     *
     * @param  \Bref\Event\Http\HttpRequestEvent  $event
     * @param  \Bref\Context\Context  $context
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public static function convertRequest(HttpRequestEvent $event, Context $context): Request
    {
        $psr7Request = Psr7Bridge::convertRequest($event, $context);
        $httpFoundationFactory = new HttpFoundationFactory();
        $symfonyRequest = $httpFoundationFactory->createRequest($psr7Request);
        
        return $symfonyRequest;
    }
}
