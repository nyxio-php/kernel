<?php

declare(strict_types=1);

namespace Nyx\Kernel\Provider;

use Nyx\Contract\Container\ContainerInterface;
use Nyx\Contract\Provider\ProviderInterface;
use Nyx\Contract\Server\HandlerInterface;
use Nyx\Server\WorkermanHandler;

class WorkermanProvider implements ProviderInterface
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function process(): void
    {
        $this->container->singleton(HandlerInterface::class, WorkermanHandler::class);
    }
}
