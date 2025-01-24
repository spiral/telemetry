<?php

declare(strict_types=1);

namespace Spiral\Tests\Telemetry;

use Mockery as m;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Spiral\Core\BinderInterface;
use Spiral\Core\ContainerScope;
use Spiral\Core\InvokerInterface;
use Spiral\Core\ScopeInterface;
use Spiral\Telemetry\NullTracer;
use Spiral\Telemetry\Span;
use Spiral\Telemetry\SpanInterface;

final class NullTracerTest extends TestCase
{
    use m\Adapter\Phpunit\MockeryPHPUnitIntegration;

    #[RunInSeparateProcess]
    public function testFallbackRunScope(): void
    {
        $tracer = new NullTracer(
            $scope = m::mock(ScopeInterface::class),
        );

        $invoker = m::mock(InvokerInterface::class);

        $callable = static fn(): string => 'hello';

        $invoker->shouldReceive('invoke')
            ->once()
            ->with($callable)
            ->andReturn('hello');

        $scope->shouldReceive('runScope')
            ->withArgs(
                static fn(array $scope): bool =>
                $scope[SpanInterface::class] instanceof Span
                && $scope[SpanInterface::class]->getName() === 'foo',
            )
            ->andReturnUsing(static fn(array $scope, callable $callable) => $callable($invoker));

        self::assertSame('hello', $tracer->trace('foo', $callable, ['foo' => 'bar']));
    }

    #[RunInSeparateProcess]
    public function testWithScopedContainer(): void
    {
        $tracer = new NullTracer(
            $scope = m::mock(ScopeInterface::class),
        );

        $invoker = m::mock(InvokerInterface::class);
        $binder = m::mock(BinderInterface::class);
        $container = m::mock(ContainerInterface::class);
        $container->expects('get')
            ->with(InvokerInterface::class)
            ->andReturn($invoker);
        $container->expects('get')
            ->with(BinderInterface::class)
            ->andReturn($binder);

        $callable = static fn(): string => 'hello';

        $invoker->shouldReceive('invoke')
            ->once()
            ->with($callable)
            ->andReturn('hello');
        $binder->shouldReceive('bindSingleton')
            ->once();
        $binder->shouldReceive('removeBinding')
            ->with(SpanInterface::class);
        $scope->shouldNotReceive('runScope');

        ContainerScope::runScope($container, function () use ($tracer, $callable): void {
            self::assertSame('hello', $tracer->trace('foo', $callable, ['foo' => 'bar']));
        });
    }
}
