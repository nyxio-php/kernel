<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Event;

use Nyxio\Event\Event;

class CronJobCompleted extends Event
{
    public const NAME = 'kernel.cron.completed';

    public function __construct(public readonly string $job)
    {
    }
}
