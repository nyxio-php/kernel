<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Event;

use Nyxio\Contract\Kernel\Server\Event\TaskHandlerInterface;
use Nyxio\Contract\Kernel\Server\Job\Async;
use Nyxio\Contract\Kernel\Server\Job\Await;
use Nyxio\Kernel\Server\Job\TaskData;
use Swoole\Http\Server;

class TaskHandler implements TaskHandlerInterface
{
    public function __construct(
        private readonly Async\TaskHandlerInterface $asyncTaskHandler,
        private readonly Await\TaskHandlerInterface $awaitTaskHandler,
    ) {
    }

    public function handle(Server $server, int $taskId, int $reactorId, TaskData $taskData): mixed
    {
        if ($taskData->isAsync()) {
            $this->asyncTaskHandler->handle($server, $taskData);

            return null;
        }

        return $this->awaitTaskHandler->handle($server, $taskData);
    }
}
