<?php

declare(strict_types=1);

namespace Nyxio\Kernel;

use Nyxio\Config\MemoryConfig;
use Nyxio\Container\Container;
use Nyxio\Contract\Config\ConfigInterface;
use Nyxio\Contract\Container\ContainerInterface;
use Nyxio\Contract\Kernel\Server\ServerEventHandlerInterface;
use Nyxio\Contract\Provider\ProviderDispatcherInterface;
use Nyxio\Provider\Dispatcher;
use Swoole\Http\Server;

class Application
{
    public function __construct(
        private readonly ConfigInterface $config = new MemoryConfig(),
        private readonly ContainerInterface $container = new Container()
    ) {
        $this->container->singleton(ConfigInterface::class, $config);
        $this->container->singleton(ProviderDispatcherInterface::class, Dispatcher::class);
    }

    /**
     * @return Application
     * @throws \ReflectionException
     */
    public function bootstrap(): static
    {
        $this->dispatchProviders();
        $this->bindServerEventHandler();

        return $this;
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function start(): void
    {
        $server = $this->container->get(Server::class);

        if (!$server instanceof Server) {
            throw new \RuntimeException(\sprintf('%s was not specified', Server::class));
        }

        $server->start();
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    private function dispatchProviders(): void
    {
        $dispatcher = $this->container->get(ProviderDispatcherInterface::class);

        if (!$dispatcher instanceof ProviderDispatcherInterface) {
            throw new \RuntimeException(\sprintf('%s was not specified', ProviderDispatcherInterface::class));
        }

        $dispatcher->dispatch($this->config->get('app.providers', []));
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    private function bindServerEventHandler(): void
    {
        if (!$this->container->hasSingleton(ServerEventHandlerInterface::class)) {
            throw new \RuntimeException(\sprintf('%s was not specified', ServerEventHandlerInterface::class));
        }

        /** @var ServerEventHandlerInterface $handler */
        $handler = $this->container->get(ServerEventHandlerInterface::class);
        $handler->handle();
    }
}
