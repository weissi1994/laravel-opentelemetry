<?php

namespace SeanHood\LaravelOpenTelemetry\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenTelemetry\Context\Context;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\SDK\Trace\Tracer;
use OpenTelemetry\API\Trace\Span;

/**
 * Trace an incoming HTTP request
 */
class Trace
{
    /**
     * @var Tracer $tracer OpenTelemetry Tracer
     */
    private $tracer;

    public function __construct(Tracer $tracer)
    {
        $this->tracer = $tracer;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $span = $this->tracer->spanBuilder('http_'.strtolower($request->method()).'_'.strtolower($request->path()))->startSpan();
        $scope = $span->activate();
        $response = $next($request);

        $this->setSpanStatus($span, $response->status());
        $this->addConfiguredTags($span, $request, $response);
        $span->setAttribute('response.status', $response->status());
        
        $span->end();
        $scope->detach();

        return $response;
    }

    private function setSpanStatus(Span $span, int $httpStatusCode)
    {

        if($httpStatusCode >= 500 && $httpStatusCode < 600) {
            $span->setAttribute('status_class', '5xx');
        } elseif($httpStatusCode >= 400 && $httpStatusCode < 500) {
            $span->setAttribute('status_class', '4xx');
        } elseif($httpStatusCode >= 300 && $httpStatusCode < 400) {
            $span->setAttribute('status_class', '3xx');
        } elseif($httpStatusCode >= 200 && $httpStatusCode < 300) {
            $span->setAttribute('status_class', '2xx');
        } else {
            $span->setAttribute('status_class', 'other');
        }

        $span->setAttribute('status_code', $httpStatusCode);
    }

    private function addConfiguredTags(Span $span, Request $request, $response)
    {
        $configurationKey = 'laravel_opentelemetry.tags.';

        if(config($configurationKey.'path')) {
            $span->setAttribute('request.path', $request->path());
        }

        if(config($configurationKey.'url')) {
            $span->setAttribute('request.url', $request->fullUrl());
        } 
        
        if(config($configurationKey.'method')) {
            $span->setAttribute('request.method', $request->method());
        }

        if(config($configurationKey.'secure')) {
            $span->setAttribute('request.secure', $request->secure());
        }

        if(config($configurationKey.'ip')) {
            $span->setAttribute('request.ip', $request->ip());
        }

        if(config($configurationKey.'ua')) {
            $span->setAttribute('request.ua', $request->userAgent());
        }

        if(config($configurationKey.'user') && $request->user()) {
            $span->setAttribute('request.user', $request->user()->email);
        }
    
        if(config($configurationKey.'headers')) {
            $headers = collect($request->header())->transform(function ($item) {
                return $item[0];
            });
            $span->setAttribute('request.headers', $headers);
        }
        
        if(config($configurationKey.'payload') && $request->getContent()) {
            $span->setAttribute('request.payload', $request->getContent());
        }
        
        if(config($configurationKey.'payload') && $response->getContent()) {
            $span->setAttribute('response.payload', $response->getContent());
        }
        
    }
}
