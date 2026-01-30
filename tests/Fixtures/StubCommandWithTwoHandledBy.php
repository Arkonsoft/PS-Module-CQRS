<?php

namespace Arkonsoft\PsModule\CQRS\Tests\Fixtures;

use Arkonsoft\PsModule\CQRS\Attribute\HandledBy;

#[HandledBy(StubCommandHandler::class)]
#[HandledBy(StubCommandHandler::class)]
final class StubCommandWithTwoHandledBy
{
    public function __construct(public string $value) {}
}
