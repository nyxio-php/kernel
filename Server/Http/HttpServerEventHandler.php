<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Http;

use Nyxio\Contract\Kernel\Server\ServerEventHandlerInterface;
use Nyxio\Kernel\Server\Http\Event\RequestHandler;
use Swoole\Server;

class HttpServerEventHandler implements ServerEventHandlerInterface
{
    public function __construct(private readonly Server $server, private readonly RequestHandler $requestHandler)
    {
    }

    public function handle(): void
    {
        $this->server->on('request', [$this->requestHandler, 'handle']);
    }
}
