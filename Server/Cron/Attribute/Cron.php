<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Cron\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Cron
{
    public function __construct(public readonly string $expression)
    {
    }
}
