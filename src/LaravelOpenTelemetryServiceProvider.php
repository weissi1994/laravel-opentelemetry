<?php
namespace SeanHood\LaravelOpenTelemetry;

use Illuminate\Support\ServiceProvider;

use OpenTelemetry\Contrib\OtlpHttp\Exporter as OTLPExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\Tracer;

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
        
        $exporter = OTLPExporter::fromConnectionString(
            config('laravel_opentelemetry.oltp_endpoint'),
            config('laravel_opentelemetry.service_name')
        );

        $provider = new TracerProvider(
          new SimpleSpanProcessor($exporter)
        );

        return $provider->getTracer('io.opentelemetry.contrib.php');
    }
}
