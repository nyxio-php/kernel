<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Job;

enum TaskType: string
{
    case Scheduled = 'scheduled';
    case Queue = 'queue';
    case Await = 'await';
}
