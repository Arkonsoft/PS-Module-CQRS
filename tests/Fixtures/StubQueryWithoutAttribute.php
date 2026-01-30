<?php

namespace Arkonsoft\PsModule\CQRS\Tests\Fixtures;

final class StubQueryWithoutAttribute
{
    public function __construct(public int $id) {}
}
