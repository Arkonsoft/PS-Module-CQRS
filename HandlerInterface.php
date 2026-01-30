<?php

namespace Arkonsoft\PsModule\CQRS;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Interface that every command and query handler must implement.
 * The bus will not call handle() on objects that do not implement this interface.
 */
interface HandlerInterface
{
    /**
     * @param object $message Command or query instance
     * @return mixed Handler result
     */
    public function handle(object $message): mixed;
}
