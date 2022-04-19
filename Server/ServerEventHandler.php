<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server;

use Nyxio\Contract\Container\ContainerInterface;
use Nyxio\Contract\Kernel\Server\ServerEventHandlerInterface;
use Swoole\Http\Server;

class ServerEventHandler implements ServerEventHandlerInterface
{
    public function __construct(
        private readonly Server $server,
        private readonly ContainerInterface $container
    ) {
    }

    public function attach(string $event, string $class): static
    {
        $this->server->on($event, [$this->container->get($class), 'handle']);

        return $this;
    }
}
