<?php

namespace Arkonsoft\PsModule\CQRS\Tests\Fixtures;

use Arkonsoft\PsModule\CQRS\HandlerInterface;

final class StubQueryHandler implements HandlerInterface
{
    /** @return array{id: int, result: string} */
    public function handle($query): array
    {
        assert($query instanceof StubQueryWithHandler);
        return ['id' => $query->id, 'result' => 'query_ok'];
    }
}
