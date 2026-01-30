# PS Module CQRS

A lightweight CQRS (Command Query Responsibility Segregation) implementation for PrestaShop modules. Uses PHP 8 attributes to bind commands and queries to their handlers — no registry or convention-based resolution.

## Table of Contents

- [Description](#description)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
  - [Basic Setup](#basic-setup)
  - [The HandledBy Attribute](#the-handledby-attribute)
  - [Creating Commands and Handlers](#creating-commands-and-handlers)
  - [Creating Queries and Handlers](#creating-queries-and-handlers)
  - [Using the Buses](#using-the-buses)
- [Handler Resolution (callable)](#handler-resolution-callable)
- [Error Handling](#error-handling)
- [Migration from Convention-Based Binding](#migration-from-convention-based-binding)
- [License](#license)
- [Support](#support)

## Description

This library provides a simple way to implement the CQRS pattern in PrestaShop modules. It includes `CommandBus` and `QueryBus` classes that resolve handlers at runtime by reading the `#[HandledBy(HandlerClass::class)]` attribute from the command or query class. There is no registry, no builder, and no naming convention — you pass a **callable** that creates handler instances (e.g. from your DI container or `new $class()`).

## Requirements

- PHP >= 8.1
- PrestaShop >= 8.0.0

The library does **not** require any specific DI container. You provide a callable that resolves handler class names to instances.

## Installation

```bash
composer require arkonsoft/ps-module-cqrs
```

## Usage

### Basic Setup

In your module's main class, create the buses by passing a **callable** that receives the handler class name (string) and returns the handler instance:

```php
<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use Arkonsoft\PsModule\CQRS\CommandBus;
use Arkonsoft\PsModule\CQRS\QueryBus;

require_once __DIR__ . '/vendor/autoload.php';

class MyModule extends Module
{
    private CommandBus $commandBus;
    private QueryBus $queryBus;

    public function __construct()
    {
        // ... module setup ...

        $resolveHandler = fn(string $handlerClass): object => $this->getContainer()->get($handlerClass);
        $this->commandBus = new CommandBus($resolveHandler);
        $this->queryBus = new QueryBus($resolveHandler);
    }

    private function getContainer(): object
    {
        // Your DI container (e.g. PSR-11, arkonsoft/ps-module-di, etc.)
        static $container = null;
        if ($container === null) {
            $container = new \Some\Container();
        }
        return $container;
    }
}
```

### The HandledBy Attribute

Put the **HandledBy** attribute on the **command** or **query** class (not on the handler). It points to the handler class that will process that command or query.

- **Command class**: `#[HandledBy(YourCommandHandler::class)]`
- **Query class**: `#[HandledBy(YourQueryHandler::class)]`

The bus reads this attribute at runtime when you call `handle()`, so there is no registry to build or namespaces to scan.

### Creating Commands and Handlers

**Command** — add the attribute with the handler class:

```php
<?php
// src/Application/Command/CreateProductCommand.php

namespace MyModule\Application\Command;

use Arkonsoft\PsModule\CQRS\Attribute\HandledBy;
use MyModule\Application\Handler\CreateProductHandler;

#[HandledBy(CreateProductHandler::class)]
final readonly class CreateProductCommand
{
    public function __construct(
        public string $name,
        public float $price,
        public int $categoryId,
    ) {}
}
```

**Handler** — no attribute; just implement `handle(Command $command)`:

```php
<?php
// src/Application/Handler/CreateProductHandler.php

namespace MyModule\Application\Handler;

use MyModule\Application\Command\CreateProductCommand;

final class CreateProductHandler
{
    public function handle(CreateProductCommand $command): int
    {
        $product = new \Product();
        $product->name = $command->name;
        $product->price = $command->price;
        $product->id_category_default = $command->categoryId;
        $product->add();
        return (int) $product->id;
    }
}
```

### Creating Queries and Handlers

**Query** — add the attribute with the handler class:

```php
<?php
// src/Application/Query/GetProductByIdQuery.php

namespace MyModule\Application\Query;

use Arkonsoft\PsModule\CQRS\Attribute\HandledBy;
use MyModule\Application\Handler\GetProductByIdHandler;

#[HandledBy(GetProductByIdHandler::class)]
final readonly class GetProductByIdQuery
{
    public function __construct(public int $productId) {}
}
```

**Handler** — no attribute; just implement `handle(Query $query)`:

```php
<?php
// src/Application/Handler/GetProductByIdHandler.php

namespace MyModule\Application\Handler;

use MyModule\Application\Query\GetProductByIdQuery;

final class GetProductByIdHandler
{
    public function handle(GetProductByIdQuery $query): array
    {
        $product = new \Product($query->productId);
        if (!\Validate::isLoadedObject($product)) {
            throw new \RuntimeException('Product not found');
        }
        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->price,
        ];
    }
}
```

### Using the Buses

```php
// Execute a command
$command = new \MyModule\Application\Command\CreateProductCommand('New Product', 29.99, 1);
$productId = $this->commandBus->handle($command);

// Execute a query
$query = new \MyModule\Application\Query\GetProductByIdQuery($productId);
$productData = $this->queryBus->handle($query);
```

## Handler Resolution (callable)

The constructor of `CommandBus` and `QueryBus` accepts a **callable** with signature `(string $handlerClass): object`. The bus calls it with the handler FQCN (from the `HandledBy` attribute) and uses the returned instance to call `handle($command)` or `handle($query)`.

Examples:

**With a PSR-11 or custom container:**

```php
$resolveHandler = fn(string $handlerClass): object => $container->get($handlerClass);
$this->commandBus = new CommandBus($resolveHandler);
$this->queryBus = new QueryBus($resolveHandler);
```

**Simple factory (no DI):**

```php
$resolveHandler = fn(string $handlerClass): object => new $handlerClass();
$this->commandBus = new CommandBus($resolveHandler);
$this->queryBus = new QueryBus($resolveHandler);
```

There is no `HandlerResolverInterface` or resolver class in the library — you pass the callable directly.

## Error Handling

- If a command or query class does not have exactly one `#[HandledBy(...)]` attribute, the bus throws a `\RuntimeException`.
- Any exception thrown by your handler propagates from `handle()`.

```php
try {
    $result = $this->commandBus->handle($command);
} catch (\RuntimeException $e) {
    // Missing or invalid HandledBy attribute, or handler resolution failed
    PrestaShopLogger::addLog('CQRS error: ' . $e->getMessage(), 3);
} catch (\Exception $e) {
    // Handler execution error
    PrestaShopLogger::addLog('Handler error: ' . $e->getMessage(), 3);
}
```

## Migration from Convention-Based Binding

If you used an older version that resolved handlers by convention (e.g. `CreateProductCommand` → `CreateProductHandler` in a `Handler` namespace):

1. Add `#[HandledBy(YourHandler::class)]` to each command and query class.
2. Replace the container in the bus constructor with a **callable**:  
   `new CommandBus(fn(string $class) => $container->get($class))`  
   (and the same for `QueryBus`).
3. Remove any dependency on a specific DI library from this package; your module still uses its own container inside the callable.

No registry, builder, or handler list is required.

## License

Commercial - The terms of the license are subject to a proprietary agreement between the author (Arkonsoft) and the licensee.

## Support

For support and questions, please contact: info@arkonsoft.pl
