<?php

declare(strict_types=1);

namespace Nyxio\Kernel;

use Nyxio\Config\MemoryConfig;
use Nyxio\Container\Container;
use Nyxio\Contract\Config\ConfigInterface;
use Nyxio\Contract\Container\ContainerInterface;
use Nyxio\Contract\Provider\ProviderDispatcherInterface;
use Nyxio\Provider\Dispatcher;

class Application
{
    private ?ServerBridge $serverBridge;

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

        $server = $this->container->get(ServerBridge::class);

        if (!$server instanceof ServerBridge) {
            throw new \RuntimeException(\sprintf('%s was not specified', ServerBridge::class));
        }

        $this->serverBridge = $server;

        return $this;
    }

    public function request(): \Closure
    {
        return $this->serverBridge->request();
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
}
