<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Event;

use Nyxio\Contract\Event\EventDispatcherInterface;
use Nyxio\Kernel\Event\QueueComplete;
use Nyxio\Kernel\Event\ScheduleComplete;
use Nyxio\Kernel\Server\Job\JobType;
use Nyxio\Kernel\Server\Job\TaskData;
use Swoole\Http\Server;

/**
 * @codeCoverageIgnore
 */
class FinishEventHandler
{
    public function __construct(private readonly EventDispatcherInterface $eventDispatcher)
    {
    }

    public function handle(Server $server, int $taskId, TaskData $taskData): void
    {
        switch ($taskData->type) {
            case JobType::Queue:
                $this->finishQueueJob($taskData);
                break;
            case JobType::Scheduled:
                $this->finishScheduleJob($taskData);
                break;
        }
    }


    private function finishScheduleJob(TaskData $taskData): void
    {
        $this->eventDispatcher->dispatch(
            ScheduleComplete::NAME,
            new ScheduleComplete($taskData)
        );
    }

    private function finishQueueJob(TaskData $taskData): void
    {
        $this->eventDispatcher->dispatch(
            QueueComplete::NAME,
            new QueueComplete($taskData)
        );
    }
}
