<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Event;

use Nyxio\Event\Event;

class CronJobError extends Event
{
    public const NAME = 'kernel.cron.error';

    public function __construct(public readonly string $job, public readonly \Throwable $exception)
    {
    }
}
