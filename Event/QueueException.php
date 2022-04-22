<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Event;

use Nyxio\Event\Event;
use Nyxio\Kernel\Server\Job\TaskData;

class QueueException extends Event
{
    public const NAME = 'kernel.job.queue.exception';

    public function __construct(public readonly TaskData $taskData, public readonly \Throwable $exception)
    {
    }
}
