# laravel-opentelemetry

**Note**: OpenTelemetry is currently alpha/pre-release. This helper library is pre-pre-alpha.


Laravel OpenTelemetry helps integrate OpenTelemetry Traces into your Laravel application.

### Requirements

* Laravel 5+
* OpenTelemetry Collector w/ OLTP 

### Features

* Request attributes: Path, URL, Method, Secure, Client IP, UserAgent, Status

## Installation

```
composer require seanhood/laravel-opentelemetry
```

### Add the Middleware to your application

```php
// app/Http/Kernel.php

protected $middleware = [
    ...
    \SeanHood\LaravelOpenTelemetry\Middleware\Trace::class
];
```

## Config


### Publish config to config/laravel_opentelemetry.php
```
php artisan vendor:publish --provider="SeanHood\LaravelOpenTelemetry\LaravelOpenTelemetryServiceProvider"
```

### The basic options are:

* `'enable' => true`: Whether to enable LaravelOpenTelemetry

* `'oltp_endpoint' => 'http://localhost:4318'`: OLTP Endpoint to send spans to.

* `'service_name' => 'laravel-otel'`: The name of your application as you'd like to identify it in your traces.
