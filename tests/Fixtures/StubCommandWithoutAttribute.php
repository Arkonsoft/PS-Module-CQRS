<?php

namespace Arkonsoft\PsModule\CQRS\Tests\Fixtures;

final class StubCommandWithoutAttribute
{
    public function __construct(public string $value) {}
}
