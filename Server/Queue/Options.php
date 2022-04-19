<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Queue;

use Nyxio\Contract\Queue\OptionsInterface;

class Options implements OptionsInterface
{
    public function __construct(
        private readonly ?int $retryCount = null,
        private readonly ?int $retryDelay = null,
        private readonly ?int $delay = null,
    ) {
    }

    public function getDelay(): ?int
    {
        return $this->delay;
    }

    public function getRetryCount(): ?int
    {
        return $this->retryCount;
    }

    public function getRetryDelay(): ?int
    {
        return $this->retryDelay;
    }
}
