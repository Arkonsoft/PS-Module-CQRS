<?php

namespace Arkonsoft\PsModule\CQRS;

use Arkonsoft\PsModule\CQRS\Attribute\HandledBy;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class QueryBus
{
    /** @var callable(string): object */
    private mixed $resolveHandler;

    /**
     * @param callable(string): object $resolveHandler Callable that receives handler FQCN and returns handler instance
     */
    public function __construct(callable $resolveHandler)
    {
        $this->resolveHandler = $resolveHandler;
    }

    public function handle(object $query): mixed
    {
        $handlerClass = $this->getHandlerClass($query);
        $handler = ($this->resolveHandler)($handlerClass);

        if (!$handler instanceof HandlerInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Query handler %s must implement %s.',
                    $handlerClass,
                    HandlerInterface::class
                )
            );
        }

        return $handler->handle($query);
    }

    private function getHandlerClass(object $query): string
    {
        $reflection = new \ReflectionClass($query);
        $attributes = $reflection->getAttributes(HandledBy::class);

        if (count($attributes) !== 1) {
            throw new \RuntimeException(
                sprintf(
                    'Query %s must have exactly one %s attribute.',
                    $reflection->getName(),
                    HandledBy::class
                )
            );
        }

        $handledBy = $attributes[0]->newInstance();
        return $handledBy->handlerClass;
    }
}
