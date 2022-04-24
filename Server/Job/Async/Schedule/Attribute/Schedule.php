<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Job\Async\Schedule\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Schedule
{
    public function __construct(public readonly string $expression)
    {
    }
}
