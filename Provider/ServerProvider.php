<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Provider;

use Nyxio\Contract\Config\ConfigInterface;
use Nyxio\Contract\Container\ContainerInterface;
use Nyxio\Contract\Provider\ProviderInterface;
use Nyxio\Contract\Server\ServerProtocol;
use Swoole\Server;

class ServerProvider implements ProviderInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ConfigInterface $config,
    ) {
    }

    public function process(): void
    {
        $this->container->singletonFn(Server::class, function () {
            $protocol = ServerProtocol::tryFrom(
                    $this->config->get('server.protocol', ServerProtocol::HTTP->value)
                ) ?? ServerProtocol::HTTP;

            $host = $this->config->get('server.host', '127.0.0.1');
            $port = $this->config->get('server.port', 9501);

            $server = match ($protocol) {
                default => new \Swoole\Http\Server($host, $port),
                ServerProtocol::WebSocket => new \Swoole\WebSocket\Server($host, $port),
            };

            $server->set($this->config->get('server.options'));

            return $server;
        });
    }
}
