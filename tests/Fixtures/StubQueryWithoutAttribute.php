<?php

namespace Arkonsoft\PsModule\CQRS\Tests\Fixtures;

final readonly class StubQueryWithoutAttribute
{
    public function __construct(public int $id) {}
}
