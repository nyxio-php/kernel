<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Event;

use Nyxio\Event\Event;

class JobCompleted extends Event
{
    public const NAME = 'server.job.completed';

    public function __construct(public readonly string $job)
    {
    }
}
