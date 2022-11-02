<?php
namespace SeanHood\LaravelOpenTelemetry;

use Illuminate\Support\ServiceProvider;

use OpenTelemetry\Contrib\OtlpHttp\Exporter as OTLPExporter;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use OpenTelemetry\API\Common\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\Contrib\Otlp\MetricExporter;
use OpenTelemetry\SDK\Common\Export\Http\PsrTransportFactory;
use OpenTelemetry\SDK\Common\Time\ClockFactory;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\Sampler\ParentBased;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessorBuilder;
use OpenTelemetry\SDK\Trace\TracerProvider;

/**
 * LaravelOpenTelemetryServiceProvider injects a configured OpenTelemetry Tracer into
 * the Laravel service container, so that instrumentation is traceable.
 */
class LaravelOpenTelemetryServiceProvider extends ServiceProvider
{
    /**
     * Publishes configuration file.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/laravel_opentelemetry.php' => config_path('laravel_opentelemetry.php')
        ]);

        $this->mergeConfigFrom(
            __DIR__.'/config/laravel_opentelemetry.php',
            'laravel_opentelemetry'
        );
    }
    
    /**
     * Make config publishment optional by merging the config from the package.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Tracer::class, function () {
            return $this->initOpenTelemetry();
        });
    }

    /**
     * Initialize an OpenTelemetry Tracer with the exporter
     * specified in the application configuration.
     * 
     * @return Tracer|null A configured Tracer, or null if tracing hasn't been enabled.
     */
    private function initOpenTelemetry(): Tracer
    {
        if(!config('laravel_opentelemetry.enable')) {
            return null;
        }
    
        $resource = ResourceInfoFactory::defaultResource();
        
        $spanExporter = OTLPExporter::fromConnectionString(
            config('laravel_opentelemetry.oltp_endpoint'),
            config('laravel_opentelemetry.service_name')
        );

        $reader = new ExportingReader(
            new MetricExporter(
                (new PsrTransportFactory(new Client(), new HttpFactory(), new HttpFactory()))
                    ->create('http://collector:4318/v1/metrics', 'application/x-protobuf')
            ),
            ClockFactory::getDefault()
        );

        $meterProvider = MeterProvider::builder()
            ->setResource($resource)
            ->addReader($reader)
            ->build();

        $tracerProvider = TracerProvider::builder()
            ->addSpanProcessor(
                (new BatchSpanProcessorBuilder($spanExporter))
                    ->setMeterProvider($meterProvider)
                    ->build()
            )
            ->setResource($resource)
            ->setSampler(new ParentBased(new AlwaysOnSampler()))
            ->build();
        // $provider = new TracerProvider(
        //     new BatchSpanProcessor($exporter),
        //     ClockFactory::getDefault()
        // );
        Sdk::builder()
            ->setTracerProvider($tracerProvider)
            ->setMeterProvider($meterProvider)
            ->setPropagator(TraceContextPropagator::getInstance())
            ->setAutoShutdown(true)
            ->buildAndRegisterGlobal();

        $instrumentation = new CachedInstrumentation('example');
        $tracer = $instrumentation->tracer();

        return $tracer; //provider->getTracer('io.opentelemetry.contrib.php');
    }
}
