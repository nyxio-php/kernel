<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Provider;

use Nyxio\Contract\Container\ContainerInterface;
use Nyxio\Contract\Kernel\Server\ServerEventHandlerInterface;
use Nyxio\Contract\Provider\ProviderInterface;
use Nyxio\Contract\Queue\QueueInterface;
use Nyxio\Kernel\Server\Queue\Handler\FinishEventHandler;
use Nyxio\Kernel\Server\Queue\Handler\TaskEventHandler;
use Nyxio\Kernel\Server\Queue\Queue;

class QueueProvider implements ProviderInterface
{
    public function __construct(
        private readonly ServerEventHandlerInterface $eventHandler,
        private readonly ContainerInterface $container
    ) {
    }

    public function process(): void
    {
        $this->container->singleton(QueueInterface::class, Queue::class);

        $this->eventHandler->attach('task', TaskEventHandler::class);
        $this->eventHandler->attach('finish', FinishEventHandler::class);
    }
}
