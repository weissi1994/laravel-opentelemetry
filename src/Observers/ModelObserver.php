<?php

namespace SeanHood\LaravelOpenTelemetry\Observers;

use Illuminate\Database\Eloquent\Model;
use OpenTelemetry\SDK\Trace\Span;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\SDK\Trace\Tracer;

class ModelObserver {
    /**
     * @var Tracer $tracer OpenTelemetry Tracer
     */
    private $tracer;
    /**
     * @var Span span
     */
    private $span;
    /**
     * @var SpanScope spanScope
     */
    private $spanScope;

    public function __construct(Tracer $tracer)
    {
        $this->tracer = $tracer;
        $parent = TraceContextPropagator::getInstance()->extract($request->header());
        $spanBuilder = $this->tracer->spanBuilder('DB Query');
        if ($parent != null) {
            $spanBuilder->setParent($parent);
        }
        $this->span = $spanBuilder->startSpan();
    }
  
    private function startTrace(Model $model, $operation)
    {
        $this->spanScope = $this->span->activate();
        $span->setAttribute('model', $model);
        $span->setAttribute('operation', $operation);
    }

    private function endTrace(Model $model)
    {
        $this->span->end();
        $this->spanScope->detach();
    }

    public function creating(Model $model) {
        $this->startTrace($model, 'create');
    }
    public function created(TraceableModel $model) {
        $this->endTrace($model);
    }

    public function updating(Model $model) {
        $this->startTrace($model, 'update');
    }
    public function updated(Model $model) {
        $this->endTrace($model);
    }

    public function deleting(Model $model) {
        $this->startTrace($model, 'delete');
    }
    public function deleted(Model $model) {
        $this->endTrace($model);
    }

    public function restoring(Model $model) {
        $this->startTrace($model, 'restore');
    }
    public function restored(Model $model) {
        $this->endTrace($model);
    }
}
