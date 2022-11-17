<?php

declare(strict_types=1);

namespace Spiral\Telemetry;

use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidFactory;
use Spiral\Core\ScopeInterface;
use Spiral\Logger\LogsInterface;

/**
 * @internal The component is under development.
 * Something may be changed in the future. We will stable it soon.
 * Feedback is welcome {@link https://github.com/spiral/framework/discussions/822}.
 */
final class LogTracerFactory implements TracerFactoryInterface
{
    public const LOG_CHANNEL = 'telemetry';

    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly ScopeInterface $scope,
        private readonly ClockInterface $clock,
        LogsInterface $logs,
        string $channel = self::LOG_CHANNEL
    ) {
        $this->logger = $logs->getLogger($channel);
    }

    public function make(array $context = []): TracerInterface
    {
        return new LogTracer(
            $this->scope,
            $this->clock,
            $this->logger,
            new UuidFactory()
        );
    }
}
