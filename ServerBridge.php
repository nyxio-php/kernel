<?php

declare(strict_types=1);

namespace Nyx\Kernel;

use Nyx\Contract\Kernel\Request\RequestHandlerInterface;
use Nyx\Contract\Server\HandlerInterface;

class ServerBridge
{
    public function __construct(
        private readonly RequestHandlerInterface $requestHandler,
        private readonly HandlerInterface $handler,
    ) {
    }

    public function request(): \Closure
    {
        return $this->handler->message($this->requestHandler);
    }
}
