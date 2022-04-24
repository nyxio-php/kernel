<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Job\Await;

use Nyxio\Contract\Kernel\Server\Job\Await\OptionsInterface;

class Options implements OptionsInterface
{
    public function __construct(public readonly float $timeout = 0.5)
    {
    }

    public function getTimeout(): float
    {
        return $this->timeout;
    }
}
