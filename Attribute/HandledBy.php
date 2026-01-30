<?php

/**
 *  NOTICE OF LICENSE
 *
 * This file is licensed under the Software License Agreement.
 *
 * With the purchase or the installation of the software in your application
 * you accept the license agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 * @author Arkonsoft
 * @copyright 2026 Arkonsoft
 * @license Commercial - The terms of the license are subject to a proprietary agreement between the author (Arkonsoft) and the licensee
 */

namespace Arkonsoft\PsModule\CQRS\Attribute;

use Attribute;

if (!defined('_PS_VERSION_')) {
    exit;
}

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class HandledBy
{
    public function __construct(
        public string $handlerClass
    ) {}
}
