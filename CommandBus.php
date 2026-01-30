<?php

/**
 *  NOTICE OF LICENSE
 *
 * This file is licensed under the Software License Agreement.
 *
 * With the purchase or the installation of the software in your application
 * you accept the license agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 * @author Arkonsoft
 * @copyright 2026 Arkonsoft
 * @license Commercial - The terms of the license are subject to a proprietary agreement between the author (Arkonsoft) and the licensee
 */

namespace Arkonsoft\PsModule\CQRS;

use Arkonsoft\PsModule\CQRS\Attribute\HandledBy;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CommandBus
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

    public function handle(object $command): mixed
    {
        $handlerClass = $this->getHandlerClass($command);
        $handler = ($this->resolveHandler)($handlerClass);
        return $handler->handle($command);
    }

    private function getHandlerClass(object $command): string
    {
        $reflection = new \ReflectionClass($command);
        $attributes = $reflection->getAttributes(HandledBy::class);

        if (count($attributes) !== 1) {
            throw new \RuntimeException(
                sprintf(
                    'Command %s must have exactly one %s attribute.',
                    $reflection->getName(),
                    HandledBy::class
                )
            );
        }

        $handledBy = $attributes[0]->newInstance();
        return $handledBy->handlerClass;
    }
}
