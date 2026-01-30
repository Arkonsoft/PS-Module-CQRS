<?php

namespace Arkonsoft\PsModule\CQRS\Tests\Fixtures;

use Arkonsoft\PsModule\CQRS\HandlerInterface;

final class StubCommandHandler implements HandlerInterface
{
    public function handle(object $command): string
    {
        assert($command instanceof StubCommandWithHandler);
        return 'handled:' . $command->value;
    }
}
