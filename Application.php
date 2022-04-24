<?php

declare(strict_types=1);

namespace Nyxio\Kernel;

use Nyxio\Config\MemoryConfig;
use Nyxio\Container\Container;
use Nyxio\Contract\Config\ConfigInterface;
use Nyxio\Contract\Container\ContainerInterface;
use Nyxio\Contract\Kernel\Request\ActionCollectionInterface;
use Nyxio\Contract\Provider\ProviderDispatcherInterface;
use Nyxio\Contract\Routing\GroupCollectionInterface;
use Nyxio\Kernel\Server\Starter;
use Nyxio\Provider\Dispatcher;

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
     * @return static
     * @throws \ReflectionException
     */
    public function bootstrap(): static
    {
        $this->dispatchProviders();
        $this->httpBootstrap();

        return $this;
    }

    /**
     * @return bool
     * @throws \ReflectionException
     */
    public function start(): bool
    {
        $starter = $this->container->get(Starter::class);

        if (!$starter instanceof Starter) {
            throw new \RuntimeException(\sprintf('%s was not specified', Starter::class));
        }

        return $starter->start();
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
    private function httpBootstrap(): void
    {
        $groupCollection = $this->container->get(GroupCollectionInterface::class);
        $actionCollection = $this->container->get(ActionCollectionInterface::class);

        if (!$groupCollection instanceof GroupCollectionInterface) {
            throw new \RuntimeException(\sprintf('%s was not specified', GroupCollectionInterface::class));
        }

        if (!$actionCollection instanceof ActionCollectionInterface) {
            throw new \RuntimeException(\sprintf('%s was not specified', ActionCollectionInterface::class));
        }

        foreach ($this->config->get('http.groups', []) as $group) {
            $groupCollection->register($group);
        }

        $actionCollection->create($this->config->get('http.actions', []));
    }
}
