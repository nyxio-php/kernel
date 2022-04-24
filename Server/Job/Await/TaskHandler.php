<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Job\Await;

use Nyxio\Contract\Kernel\Server\Job\Await\TaskHandlerInterface;
use Nyxio\Kernel\Server\Job\BaseTaskHandler;
use Nyxio\Kernel\Server\Job\TaskData;
use Swoole\Http\Server;

class TaskHandler extends BaseTaskHandler implements TaskHandlerInterface
{
    /**
     * @throws \ReflectionException
     */
    public function handle(Server $server, TaskData $taskData): mixed
    {
        return $this->invokeJob($taskData);
    }
}
