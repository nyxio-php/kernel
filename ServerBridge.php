<?php

declare(strict_types=1);

namespace Nyxio\Kernel;

use Nyxio\Contract\Kernel\Request\RequestHandlerInterface;
use Nyxio\Contract\Server\HandlerInterface;

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
