<?php
namespace SeanHood\LaravelOpenTelemetry\Models;

use Illuminate\Database\Eloquent\Model;
use SeanHood\LaravelOpenTelemetry\Observers\ModelObserver;

class TraceableModel extends Model {
    private $scopes;

    public function getLatestScope() {
        return array_pop($this->scopes);
    }

    public function addTracingScope($scope) {
        $this->scopes[] = $scope;
    }

    protected static function boot()
    {
        parent::boot();

        self::observe(ModelObserver::class);
    }
}
