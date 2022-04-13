<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Provider;

use Nyxio\Contract\Config\ConfigInterface;
use Nyxio\Contract\Container\ContainerInterface;
use Nyxio\Contract\Provider\ProviderInterface;
use Swoole\Http\Server as HttpServer;
use Swoole\Server;

class HttpServerProvider implements ProviderInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ConfigInterface $config,
    ) {
    }

    public function process(): void
    {
        $this->container->singletonFn(Server::class, function () {
            return new HttpServer(
                $this->config->get('server.host', '127.0.0.1'), $this->config->get('server.port', 9501)
            );
        });
    }


}
