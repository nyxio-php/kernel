<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Job;

enum JobType: string
{
    case Scheduled = 'scheduled';
    case Queue = 'queue';
}
