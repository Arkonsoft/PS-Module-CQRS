<?php

namespace Arkonsoft\PsModule\CQRS\Attribute;

use Attribute;

if (!defined('_PS_VERSION_')) {
    exit;
}

#[Attribute(Attribute::TARGET_CLASS)]
final class HandledBy
{
    public function __construct(
        public string $handlerClass
    ) {}
}
