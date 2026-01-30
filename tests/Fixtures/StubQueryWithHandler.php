<?php

namespace Arkonsoft\PsModule\CQRS\Tests\Fixtures;

use Arkonsoft\PsModule\CQRS\Attribute\HandledBy;

#[HandledBy(StubQueryHandler::class)]
final readonly class StubQueryWithHandler
{
    public function __construct(
        public int $id
    ) {}
}
