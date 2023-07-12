<?php

namespace Ajaxy\Logger;

interface HandlerInterface
{
    const ERROR = -1;
    const CLOSED = 2;
    const READY = 1;
    const PENDING = 0;

    public function handle($tag, array $record);

    public function getErrors();

    public function getStatus();

    public function getBubble();

    public function end();
}
