<?php

namespace Arkonsoft\PsModule\CQRS\Tests\Fixtures;

final readonly class StubCommandWithoutAttribute
{
    public function __construct(public string $value) {}
}
