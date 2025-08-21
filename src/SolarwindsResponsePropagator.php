<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Propagation\Solarwinds;

use OpenTelemetry\API\Behavior\LogsMessagesTrait;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\Context\Propagation\ArrayAccessGetterSetter;
use OpenTelemetry\Context\Propagation\PropagationSetterInterface;

/**
 * Provides a ResponsePropagator implementation for Response Baggage.
 */
final class SolarwindsResponsePropagator implements ResponsePropagator
{
    use LogsMessagesTrait;
    const IS_SAMPLED = '01';
    const NOT_SAMPLED = '00';
    const SUPPORTED_VERSION = '00';
    const X_TRACE = 'X-Trace';
    const X_TRACE_OPTIONS_RESPONSE = 'X-Trace-Options-Response';

    public const FIELDS = [
        self::X_TRACE,
        self::X_TRACE_OPTIONS_RESPONSE,
    ];
    public function fields(): array
    {
        return self::FIELDS;
    }

    public function inject(&$carrier, ?PropagationSetterInterface $setter = null, ?ContextInterface $context = null): void
    {
        $setter = $setter ?? ArrayAccessGetterSetter::getInstance();
        $context = $context ?? Context::getCurrent();
        $spanContext = Span::fromContext($context)->getContext();

        if (!$spanContext->isValid()) {
            return;
        }

        $traceId = $spanContext->getTraceId();
        $spanId = $spanContext->getSpanId();

        $samplingFlag = $spanContext->isSampled() ? self::IS_SAMPLED : self::NOT_SAMPLED;

        $header = self::SUPPORTED_VERSION . '-' . $traceId . '-' . $spanId . '-' . $samplingFlag;
        $setter->set($carrier, self::X_TRACE, $header);

        $traceState = $spanContext->getTraceState();
        if ($traceState !== null) {
            $this->logDebug('1.....');
            $xtrace_options_response = $traceState->get("xtrace_options_response");
            $this->logDebug('2.....');
            if ($xtrace_options_response !== null) {
                $this->logDebug('3.....');
                $replaced = str_replace('....', ',', $xtrace_options_response);
                $this->logDebug('4.....');
                $final = str_replace('####', '=', $replaced);
                $this->logDebug('5.....');
                $setter->set($carrier, self::X_TRACE_OPTIONS_RESPONSE, $final);
                $this->logDebug('6.....');
            }
            $this->logDebug('7.....');
        }
        $this->logDebug('8.....');
    }
}
