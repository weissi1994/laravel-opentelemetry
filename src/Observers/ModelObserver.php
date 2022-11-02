<?php

namespace SeanHood\LaravelOpenTelemetry\Observers;

use SeanHood\LaravelOpenTelemetry\Models\TraceableModel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenTelemetry\Context\Context;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\SDK\Trace\Tracer;
use OpenTelemetry\SDK\Trace\Span;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;

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
  
    private function startTrace(TraceableModel $model, $operation)
    {
        $this->spanScope = $this->span->activate();
        $span->setAttribute('model', $model);
        $span->setAttribute('operation', $operation);
    }

    private function endTrace(TraceableModel $model)
    {
        $this->span->end();
        $this->spanScope->detach();
    }

    public function creating(TraceableModel $model) {
        $this->startTrace($model, 'create');
    }
    public function created(TraceableModel $model) {
        $this->endTrace($model);
    }

    public function updating(TraceableModel $model) {
        $this->startTrace($model, 'update');
    }
    public function updated(TraceableModel $model) {
        $this->endTrace($model);
    }

    public function deleting(TraceableModel $model) {
        $this->startTrace($model, 'delete');
    }
    public function deleted(TraceableModel $model) {
        $this->endTrace($model);
    }

    public function restoring(TraceableModel $model) {
        $this->startTrace($model, 'restore');
    }
    public function restored(TraceableModel $model) {
        $this->endTrace($model);
    }
}
