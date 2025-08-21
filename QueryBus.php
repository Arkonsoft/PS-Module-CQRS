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

class QueryBus
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

    public function handle($query)
    {
        $handlerClass = $this->resolveHandlerClass($query);

        if (!class_exists($handlerClass)) {
            throw new \RuntimeException("Handler class not found: $handlerClass");
        }

        return $this->container->get($handlerClass)->handle($query);
    }

    private function resolveHandlerClass($query)
    {
        $queryClass = get_class($query);

        $queryKeyword = 'Query';

        if (substr($queryClass, -strlen($queryKeyword)) !== $queryKeyword) {
            throw new \InvalidArgumentException("Query class name must end with 'Query'");
        }

        // Replace the namespace segment 'Query' with 'Handler'
        $handlerClass = str_replace(
            '\\' . $queryKeyword . '\\',
            '\\Handler\\',
            $queryClass
        );

        // Replace the suffix 'Query' with 'Handler'
        $handlerClass = preg_replace('/Query$/', 'Handler', $handlerClass);

        if ($handlerClass === null) {
            throw new \RuntimeException("Failed to resolve handler class for query: $queryClass");
        }

        return $handlerClass;
    }
}