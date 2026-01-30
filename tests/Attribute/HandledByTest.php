<?php

namespace Arkonsoft\PsModule\CQRS\Tests\Attribute;

use Arkonsoft\PsModule\CQRS\Attribute\HandledBy;
use Arkonsoft\PsModule\CQRS\Tests\Fixtures\StubCommandHandler;
use PHPUnit\Framework\TestCase;

final class HandledByTest extends TestCase
{
    public function testAttributeStoresHandlerClass(): void
    {
        $handlerClass = StubCommandHandler::class;
        $attribute = new HandledBy($handlerClass);

        $this->assertSame($handlerClass, $attribute->handlerClass);
    }

    public function testAttributeCanBeInstantiatedWithArbitraryString(): void
    {
        $fqcn = 'Some\\Namespace\\SomeHandler';
        $attribute = new HandledBy($fqcn);

        $this->assertSame($fqcn, $attribute->handlerClass);
    }
}
