<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Propagation\Solarwinds;

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

//        $responseBaggage = ResponseBaggage::fromContext($context);
//        if($responseBaggage->isEmpty()) {
//            return;
//        }
//        foreach ($responseBaggage->getAll() as $key => $value) {
//            $setter->set($carrier, $key, (string)$value->getValue());
//        }
    }
}
