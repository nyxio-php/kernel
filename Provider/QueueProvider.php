<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Provider;

use Nyxio\Contract\Kernel\Server\ServerEventHandlerInterface;
use Nyxio\Contract\Provider\ProviderInterface;
use Nyxio\Kernel\Server\Queue\Handler\FinishEventHandler;
use Nyxio\Kernel\Server\Queue\Handler\TaskEventHandler;

class QueueProvider implements ProviderInterface
{
    public function __construct(private readonly ServerEventHandlerInterface $eventHandler)
    {
    }

    public function process(): void
    {
        $this->eventHandler->attach('task', TaskEventHandler::class);
        $this->eventHandler->attach('finish', FinishEventHandler::class);
    }
}
