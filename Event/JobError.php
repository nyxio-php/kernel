<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Event;

use Nyxio\Event\Event;

class JobError extends Event
{
    public const NAME = 'kernel.job.error';

    public function __construct(public readonly string $job, public readonly \Throwable $exception)
    {
    }
}
