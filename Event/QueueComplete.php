<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Event;

use Nyxio\Event\Event;
use Nyxio\Kernel\Server\Job\TaskData;

class QueueComplete extends Event
{
    public const NAME = 'kernel.job.queue.complete';

    public function __construct(public readonly TaskData $taskData)
    {
    }
}
