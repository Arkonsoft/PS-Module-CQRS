# PS Module CQRS

A lightweight CQRS (Command Query Responsibility Segregation) implementation for PrestaShop modules.

## Table of Contents

- [Description](#description)
- [Requirements](#requirements)
- [Recommended Dependencies](#recommended-dependencies)
- [Installation](#installation)
- [Usage](#usage)
  - [Basic Setup](#basic-setup)
  - [Creating Commands](#creating-commands)
  - [Creating Command Handlers](#creating-command-handlers)
  - [Creating Queries](#creating-queries)
  - [Creating Query Handlers](#creating-query-handlers)
  - [Using the Buses](#using-the-buses)
  - [Advanced Example: Order Processing](#advanced-example-order-processing)
- [Naming Conventions](#naming-conventions)
  - [Commands](#commands)
  - [Queries](#queries)
- [Error Handling](#error-handling)
- [Benefits](#benefits)
- [License](#license)
- [Support](#support)

## Description

This library provides a simple and efficient way to implement the CQRS pattern in PrestaShop modules. It includes `CommandBus` and `QueryBus` classes that automatically resolve and execute command/query handlers based on naming conventions.

## Requirements

- PHP >= 7.0
- PrestaShop >= 1.7.0
- **Required**: [Arkonsoft PS Module DI](https://packagist.org/packages/arkonsoft/ps-module-di) library



## Installation

```bash
composer require arkonsoft/ps-module-cqrs
```

The required dependency `arkonsoft/ps-module-di` will be automatically installed.

## Usage

### Basic Setup

First, include the CQRS classes in your PrestaShop module:

```php
<?php
// In your module's main class (e.g., mymodule.php)

if (!defined('_PS_VERSION_')) {
    exit;
}

use Arkonsoft\PsModule\CQRS\CommandBus;
use Arkonsoft\PsModule\CQRS\QueryBus;
use Arkonsoft\PsModule\DI\AutowiringContainer;

require_once __DIR__ . '/vendor/autoload.php';

class MyModule extends Module
{
    private $commandBus;
    private $queryBus;

    public function __construct()
    {
        $this->name = 'mymodule';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Your Name';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('My Module');
        $this->description = $this->l('Module with CQRS implementation');

        // Initialize CQRS buses
        $container = new AutowiringContainer();
        $this->commandBus = new CommandBus($container);
        $this->queryBus = new QueryBus($container);
        
        // Alternative: Get buses from DI container
        // $this->commandBus = $container->get(CommandBus::class);
        // $this->queryBus = $container->get(QueryBus::class);
    }
}
```

### Alternative: Using DI Container

You can also get the CQRS buses directly from the DI container:

```php
<?php
// Alternative approach - get buses from container
$container = new AutowiringContainer();

// Register buses in container (optional - AutowiringContainer can auto-resolve)
$container->set(CommandBus::class, function () use ($container) {
    return new CommandBus($container);
});

$container->set(QueryBus::class, function () use ($container) {
    return new QueryBus($container);
});

// Get buses from container
$this->commandBus = $container->get(CommandBus::class);
$this->queryBus = $container->get(QueryBus::class);
```



### Autoloading Configuration

If you're using Composer, make sure your `composer.json` includes PSR-4 autoloading for your module:

```json
{
    "autoload": {
        "psr-4": {
            "MyModule\\": "src/"
        }
    }
}
```

Then run:
```bash
composer dump-autoload
```

### Creating Commands

Create command classes that represent actions to be performed:

```php
<?php
// src/Application/Command/CreateProductCommand.php

namespace MyModule\Application\Command;

class CreateProductCommand
{
    private $name;
    private $price;
    private $categoryId;

    public function __construct($name, $price, $categoryId)
    {
        $this->name = $name;
        $this->price = $price;
        $this->categoryId = $categoryId;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function getCategoryId()
    {
        return $this->categoryId;
    }
}
```

### Creating Command Handlers

Create handlers that process the commands:

```php
<?php
// src/Application/Handler/CreateProductHandler.php

namespace MyModule\Application\Handler;

use MyModule\Application\Command\CreateProductCommand;

class CreateProductHandler
{
    public function handle(CreateProductCommand $command)
    {
        // Create new product
        $product = new Product();
        $product->name = $command->getName();
        $product->price = $command->getPrice();
        $product->id_category_default = $command->getCategoryId();
        
        if ($product->add()) {
            return $product->id;
        }
        
        throw new \Exception('Failed to create product');
    }
}
```

### Creating Queries

Create query classes that represent data retrieval requests:

```php
<?php
// src/Application/Query/GetProductByIdQuery.php

namespace MyModule\Application\Query;

class GetProductByIdQuery
{
    private $productId;

    public function __construct($productId)
    {
        $this->productId = $productId;
    }

    public function getProductId()
    {
        return $this->productId;
    }
}
```

### Creating Query Handlers

Create handlers that process the queries:

```php
<?php
// src/Application/Handler/GetProductByIdHandler.php

namespace MyModule\Application\Handler;

use MyModule\Application\Query\GetProductByIdQuery;

class GetProductByIdHandler
{
    public function handle(GetProductByIdQuery $query)
    {
        $product = new Product($query->getProductId());
        
        if (!Validate::isLoadedObject($product)) {
            throw new \Exception('Product not found');
        }
        
        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'category_id' => $product->id_category_default
        ];
    }
}
```

### Using the Buses

Now you can use the command and query buses in your module:

```php
<?php
// In your module's controller or hook methods

public function hookActionProductAdd($params)
{
    try {
        // Execute a command
        $command = new \MyModule\Application\Command\CreateProductCommand(
            'New Product',
            29.99,
            1
        );
        
        $productId = $this->commandBus->handle($command);
        
        // Execute a query
        $query = new \MyModule\Application\Query\GetProductByIdQuery($productId);
        $productData = $this->queryBus->handle($query);
        
        // Log or process the result
        PrestaShopLogger::addLog(
            'Product created: ' . json_encode($productData),
            1
        );
        
    } catch (\Exception $e) {
        PrestaShopLogger::addLog(
            'Error in CQRS operation: ' . $e->getMessage(),
            3
        );
    }
}
```

### Advanced Example: Order Processing

Here's a more complex example showing order processing:

```php
<?php
// src/Application/Command/ProcessOrderCommand.php

namespace MyModule\Application\Command;

class ProcessOrderCommand
{
    private $orderId;
    private $customerId;
    private $products;

    public function __construct($orderId, $customerId, array $products)
    {
        $this->orderId = $orderId;
        $this->customerId = $customerId;
        $this->products = $products;
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    public function getCustomerId()
    {
        return $this->customerId;
    }

    public function getProducts()
    {
        return $this->products;
    }
}

// src/Application/Handler/ProcessOrderHandler.php

namespace MyModule\Application\Handler;

use MyModule\Application\Command\ProcessOrderCommand;

class ProcessOrderHandler
{
    public function handle(ProcessOrderCommand $command)
    {
        // Validate order
        $order = new Order($command->getOrderId());
        if (!Validate::isLoadedObject($order)) {
            throw new \Exception('Invalid order');
        }

        // Process payment
        $paymentResult = $this->processPayment($order);
        
        // Update inventory
        $this->updateInventory($command->getProducts());
        
        // Send confirmation email
        $this->sendConfirmationEmail($order);
        
        return [
            'order_id' => $order->id,
            'status' => 'processed',
            'payment_result' => $paymentResult
        ];
    }

    private function processPayment($order)
    {
        // Payment processing logic
        return 'success';
    }

    private function updateInventory($products)
    {
        // Inventory update logic
    }

    private function sendConfirmationEmail($order)
    {
        // Email sending logic
    }
}
```

## Naming Conventions

The library uses the following naming conventions to automatically resolve handlers:

### Commands
- **Command class**: Must end with `Command` (e.g., `CreateProductCommand`)
- **Handler class**: Must end with `Handler` (e.g., `CreateProductHandler`)
- **Namespace structure**: 
  - Commands: `YourNamespace\Application\Command\`
  - Handlers: `YourNamespace\Application\Handler\`

### Queries
- **Query class**: Must end with `Query` (e.g., `GetProductByIdQuery`)
- **Handler class**: Must end with `Handler` (e.g., `GetProductByIdHandler`)
- **Namespace structure**:
  - Queries: `YourNamespace\Application\Query\`
  - Handlers: `YourNamespace\Application\Handler\`

## Error Handling

The library provides comprehensive error handling:

```php
try {
    $result = $this->commandBus->handle($command);
} catch (\InvalidArgumentException $e) {
    // Command class name doesn't end with 'Command'
    PrestaShopLogger::addLog('Invalid command: ' . $e->getMessage(), 3);
} catch (\RuntimeException $e) {
    // Handler class not found or failed to resolve
    PrestaShopLogger::addLog('Handler error: ' . $e->getMessage(), 3);
} catch (\Exception $e) {
    // Other errors from handler execution
    PrestaShopLogger::addLog('Execution error: ' . $e->getMessage(), 3);
}
```

## Benefits

- **Separation of Concerns**: Commands and queries are clearly separated
- **Testability**: Easy to unit test individual handlers
- **Maintainability**: Clear structure and naming conventions
- **Scalability**: Easy to add new commands and queries
- **PrestaShop Integration**: Designed specifically for PrestaShop modules
- **Dependency Injection**: Built on top of [Arkonsoft PS Module DI](https://packagist.org/packages/arkonsoft/ps-module-di) for automatic dependency resolution and injection

## License

Commercial - The terms of the license are subject to a proprietary agreement between the author (Arkonsoft) and the licensee.

## Support

For support and questions, please contact: info@arkonsoft.pl
