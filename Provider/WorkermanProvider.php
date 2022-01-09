<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Provider;

use Nyxio\Contract\Container\ContainerInterface;
use Nyxio\Contract\Provider\ProviderInterface;
use Nyxio\Contract\Server\HandlerInterface;
use Nyxio\Server\WorkermanHandler;

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
