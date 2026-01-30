<?php

namespace Arkonsoft\PsModule\CQRS\Tests\Fixtures;

final class StubQueryHandler
{
    /** @return array{id: int, result: string} */
    public function handle(StubQueryWithHandler $query): array
    {
        return ['id' => $query->id, 'result' => 'query_ok'];
    }
}
