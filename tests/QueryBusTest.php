<?php

namespace Arkonsoft\PsModule\CQRS\Tests;

use Arkonsoft\PsModule\CQRS\QueryBus;
use Arkonsoft\PsModule\CQRS\Tests\Fixtures\StubQueryHandler;
use Arkonsoft\PsModule\CQRS\Tests\Fixtures\StubQueryWithHandler;
use Arkonsoft\PsModule\CQRS\Tests\Fixtures\StubQueryWithoutAttribute;
use PHPUnit\Framework\TestCase;

final class QueryBusTest extends TestCase
{
    public function testHandleResolvesHandlerFromAttributeAndReturnsHandlerResult(): void
    {
        $resolveHandler = fn(string $handlerClass): object => new $handlerClass();
        $bus = new QueryBus($resolveHandler);

        $query = new StubQueryWithHandler(42);
        $result = $bus->handle($query);

        $this->assertIsArray($result);
        $this->assertSame(42, $result['id']);
        $this->assertSame('query_ok', $result['result']);
    }

    public function testHandleCallsResolveHandlerWithCorrectHandlerClass(): void
    {
        $resolvedClasses = [];
        $resolveHandler = function (string $handlerClass) use (&$resolvedClasses): object {
            $resolvedClasses[] = $handlerClass;
            return new $handlerClass();
        };
        $bus = new QueryBus($resolveHandler);

        $query = new StubQueryWithHandler(1);
        $bus->handle($query);

        $this->assertSame([StubQueryHandler::class], $resolvedClasses);
    }

    public function testHandleThrowsWhenQueryHasNoHandledByAttribute(): void
    {
        $bus = new QueryBus(fn(string $class): object => new $class());
        $query = new StubQueryWithoutAttribute(1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must have exactly one');
        $this->expectExceptionMessage(StubQueryWithoutAttribute::class);

        $bus->handle($query);
    }

    public function testHandlePropagatesExceptionFromHandler(): void
    {
        $resolveHandler = fn(string $handlerClass): object => new class($handlerClass) {
            private string $handlerClass;

            public function __construct(string $handlerClass)
            {
                $this->handlerClass = $handlerClass;
            }

            public function handle(object $query): never
            {
                throw new \InvalidArgumentException('Query handler error: ' . $this->handlerClass);
            }
        };
        $bus = new QueryBus($resolveHandler);
        $query = new StubQueryWithHandler(1);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Query handler error');

        $bus->handle($query);
    }
}
