<?php

namespace SeanHood\LaravelOpenTelemetry;

use SeanHood\LaravelOpenTelemetry\Observers\ModelObserver;

trait ObservantTrait
{
    public static function bootObservantTrait()
    {
        static::observe(new ModelObserver());
    }
}
