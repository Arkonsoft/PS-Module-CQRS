<?php

namespace Arkonsoft\PsModule\CQRS\Tests\Fixtures;

use Arkonsoft\PsModule\CQRS\Attribute\HandledBy;

#[HandledBy(StubCommandHandler::class)]
final class StubCommandWithHandler
{
    public function __construct(
        public string $value
    ) {}
}
