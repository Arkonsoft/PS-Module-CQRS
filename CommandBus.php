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
 * @copyright 2025 Arkonsoft
 * @license Commercial - The terms of the license are subject to a proprietary agreement between the author (Arkonsoft) and the licensee
 */

namespace Arkonsoft\PsModule\CQRS;

use Arkonsoft\PsModule\DI\AutowiringContainerInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CommandBus
{
    /**
     * @var AutowiringContainerInterface
     */
    private $container;

    public function __construct(
        AutowiringContainerInterface $container
    ) {
        $this->container = $container;
    }

    public function handle($command)
    {
        $handlerClass = $this->resolveHandlerClass($command);

        if (!class_exists($handlerClass)) {
            throw new \RuntimeException("Handler class not found: $handlerClass");
        }

        return $this->container->get($handlerClass)->handle($command);
    }

    private function resolveHandlerClass($command)
    {
        $commandClass = get_class($command);

        $commandKeyword = 'Command';

        if (substr($commandClass, -strlen($commandKeyword)) !== $commandKeyword) {
            throw new \InvalidArgumentException("Command class name must end with 'Command'");
        }

        // Replace the namespace segment 'Command' with 'Handler'
        $handlerClass = str_replace(
            '\\' . $commandKeyword . '\\',
            '\\Handler\\',
            $commandClass
        );

        // Replace the suffix 'Command' with 'Handler'
        $handlerClass = preg_replace('/Command$/', 'Handler', $handlerClass);

        if ($handlerClass === null) {
            throw new \RuntimeException("Failed to resolve handler class for command: $commandClass");
        }

        return $handlerClass;
    }
}
