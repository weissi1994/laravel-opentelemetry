<?php

namespace SeanHood\LaravelOpenTelemetry\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\SDK\Trace\Tracer;
use OpenTelemetry\SDK\Trace\Span;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;


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
        $carrier = TraceContextPropagator::getInstance()->extract($request->header());
        $root = $this->tracer->spanBuilder(strtoupper($request->method()).'_'.$request->path())
            ->setParent($carrier)
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->startSpan();
        $scope = $root->activate();        
        $response = $next($request);

        $this->setSpanStatus($root, $response->status());
        $this->addConfiguredTags($root, $request, $response);
        $root->setAttribute('component', 'http');
        
        $root->end();
        $scope->detach();
        return $response;
    }

    private function setSpanStatus(Span $span, int $httpStatusCode)
    {

        if($httpStatusCode >= 500 && $httpStatusCode < 600) {
            $span->setAttribute('http.status_class', '5xx');
        } elseif($httpStatusCode >= 400 && $httpStatusCode < 500) {
            $span->setAttribute('http.status_class', '4xx');
        } elseif($httpStatusCode >= 300 && $httpStatusCode < 400) {
            $span->setAttribute('http.status_class', '3xx');
        } elseif($httpStatusCode >= 200 && $httpStatusCode < 300) {
            $span->setAttribute('http.status_class', '2xx');
        } else {
            $span->setAttribute('http.status_class', 'other');
        }
        $span->setStatus($httpStatusCode < 500 ? StatusCode::STATUS_OK : StatusCode::STATUS_ERROR);
        $span->setAttribute('http.status_code', $httpStatusCode);
    }

    private function addConfiguredTags(Span $span, Request $request, $response)
    {
        $configurationKey = 'laravel_opentelemetry.tags.';

        if(config($configurationKey.'host')) {
            $span->setAttribute('http.host', $request->getSchemeAndHttpHost());
        }
        
        if(config($configurationKey.'path')) {
            $span->setAttribute('http.route', $request->path());
        }

        if(config($configurationKey.'url')) {
            $span->setAttribute('http.url', $request->fullUrl());
        } 
        
        if(config($configurationKey.'method')) {
            $span->setAttribute('http.method', $request->method());
        }

        if(config($configurationKey.'secure')) {
            $span->setAttribute('http.secure', $request->secure());
        }

        if(config($configurationKey.'ip')) {
            $span->setAttribute('http.ip', $request->ip());
        }

        if(config($configurationKey.'ua')) {
            $span->setAttribute('http.ua', $request->userAgent());
        }

        if(config($configurationKey.'user') && $request->user()) {
            $span->setAttribute('http.user', $request->user()->email);
        }
    
        if(config($configurationKey.'headers')) {
            $headers = collect($request->header())->transform(function ($item) {
                return $item[0];
            });
            $span->setAttribute('http.headers', $headers);
        }
        
        if(config($configurationKey.'payload') && $request->getContent()) {
            $span->setAttribute('http.payload', $request->getContent());
        }
        
        if(config($configurationKey.'payload') && $response->getContent()) {
            $span->setAttribute('response.payload', $response->getContent());
        }
        
    }
}
