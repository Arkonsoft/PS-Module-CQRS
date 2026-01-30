<?php

namespace Arkonsoft\PsModule\CQRS\Tests;

use Arkonsoft\PsModule\CQRS\CommandBus;
use Arkonsoft\PsModule\CQRS\HandlerInterface;
use Arkonsoft\PsModule\CQRS\Tests\Fixtures\StubCommandHandler;
use Arkonsoft\PsModule\CQRS\Tests\Fixtures\StubCommandWithHandler;
use Arkonsoft\PsModule\CQRS\Tests\Fixtures\StubCommandWithTwoHandledBy;
use Arkonsoft\PsModule\CQRS\Tests\Fixtures\StubCommandWithoutAttribute;
use PHPUnit\Framework\TestCase;

final class CommandBusTest extends TestCase
{
    public function testHandleResolvesHandlerFromAttributeAndReturnsHandlerResult(): void
    {
        $resolveHandler = fn(string $handlerClass): object => new $handlerClass();
        $bus = new CommandBus($resolveHandler);

        $command = new StubCommandWithHandler('test-value');
        $result = $bus->handle($command);

        $this->assertSame('handled:test-value', $result);
    }

    public function testHandleCallsResolveHandlerWithCorrectHandlerClass(): void
    {
        $resolvedClasses = [];
        $resolveHandler = function (string $handlerClass) use (&$resolvedClasses): object {
            $resolvedClasses[] = $handlerClass;
            return new $handlerClass();
        };
        $bus = new CommandBus($resolveHandler);

        $command = new StubCommandWithHandler('x');
        $bus->handle($command);

        $this->assertSame([StubCommandHandler::class], $resolvedClasses);
    }

    public function testHandleThrowsWhenCommandHasNoHandledByAttribute(): void
    {
        $bus = new CommandBus(fn(string $class): object => new $class());
        $command = new StubCommandWithoutAttribute('value');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must have exactly one');
        $this->expectExceptionMessage(StubCommandWithoutAttribute::class);

        $bus->handle($command);
    }

    public function testHandleThrowsWhenCommandHasMultipleHandledByAttributes(): void
    {
        $bus = new CommandBus(fn(string $class): object => new $class());
        $command = new StubCommandWithTwoHandledBy('value');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must have exactly one');
        $this->expectExceptionMessage(StubCommandWithTwoHandledBy::class);

        $bus->handle($command);
    }

    public function testHandleThrowsWhenHandlerDoesNotImplementHandlerInterface(): void
    {
        $resolveHandler = fn(string $handlerClass): object => new class() {
            public function execute(object $command): string
            {
                return 'wrong';
            }
        };
        $bus = new CommandBus($resolveHandler);
        $command = new StubCommandWithHandler('x');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement');
        $this->expectExceptionMessage(HandlerInterface::class);

        $bus->handle($command);
    }

    public function testHandlePropagatesExceptionFromHandler(): void
    {
        $resolveHandler = fn(string $handlerClass): object => new class($handlerClass) implements HandlerInterface {
            private string $handlerClass;

            public function __construct(string $handlerClass)
            {
                $this->handlerClass = $handlerClass;
            }

            public function handle($command): never
            {
                throw new \DomainException('Handler failed: ' . $this->handlerClass);
            }
        };
        $bus = new CommandBus($resolveHandler);
        $command = new StubCommandWithHandler('x');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Handler failed');

        $bus->handle($command);
    }
}
