<?php

namespace Arkonsoft\PsModule\CQRS\Tests\Fixtures;

final class StubCommandHandler
{
    public function handle(StubCommandWithHandler $command): string
    {
        return 'handled:' . $command->value;
    }
}
