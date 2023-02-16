<?php

declare(strict_types=1);

namespace Vinelab\Tracing\Reporters;

use Illuminate\Queue\Queue;
use Illuminate\Support\Facades\Config;
use Zipkin\Reporters\SpanSerializer;
use Zipkin\Reporters\JsonV2Serializer;
use Zipkin\Reporter;
use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;

class QueueReporter implements Reporter
{
    /**
     * @var LoggerInterface|NullLogger
     */
    private $logger;

    /**
     * @var JsonV2Serializer|SpanSerializer
     */
    private $serializer;

    private $queue;

    /**
     * @param string $queue Queue Name
     * @param LoggerInterface|null $logger the logger for output
     * @param SpanSerializer|null $serializer
     */
    public function __construct(
        string $queue = "zipkin",
        LoggerInterface $logger = null,
        SpanSerializer $serializer = null
    ) {
        $this->queue = $queue;
        $this->logger = $logger ?? new NullLogger();
        $this->serializer = $serializer ?? new JsonV2Serializer();
    }

    public function report(array $spans): void
    {
        if (\count($spans) === 0) {
            return;
        }

        $payload = $this->serializer->serialize($spans);
        if (!$payload) {
            $this->logger->error(
                \sprintf('failed to encode spans with code %d', \json_last_error())
            );
            return;
        }

        \Illuminate\Support\Facades\Queue::pushRaw($payload, $this->queue);
    }
}
