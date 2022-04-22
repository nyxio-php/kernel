<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Event;

use Nyxio\Event\Event;
use Nyxio\Kernel\Server\Job\TaskData;

class ScheduleComplete extends Event
{
    public const NAME = 'kernel.job.schedule.complete';

    public function __construct(public readonly TaskData $taskData)
    {
    }
}
