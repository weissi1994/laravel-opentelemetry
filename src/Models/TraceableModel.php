<?php
namespace SeanHood\LaravelOpenTelemetry\Models;

use Illuminate\Database\Eloquent\Model;
use SeanHood\LaravelOpenTelemetry\Observers\ModelObserver;

class TraceableModel extends Model {
    protected static function boot()
    {
        parent::boot();

        self::observe(ModelObserver::class);
    }
}
