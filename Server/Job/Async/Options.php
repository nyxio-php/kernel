<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Job\Async;

use Nyxio\Contract\Kernel\Server\Job\Async\OptionsInterface;

class Options implements OptionsInterface
{
    public function __construct(
        private ?int $retryCount = null,
        private readonly ?int $retryDelay = null,
        private readonly ?int $delay = null,
        private ?\Closure $finishCallback = null,
    ) {
        if ($this->delay !== null && $this->delay <= 0) {
            throw new \InvalidArgumentException('Delay cannot be less or equals zero');
        }

        if ($this->retryDelay !== null && $this->retryDelay <= 0) {
            throw new \InvalidArgumentException('Retry delay cannot be less or equals zero');
        }

        if ($this->retryCount !== null && $this->retryCount < 0) {
            throw new \InvalidArgumentException('Retry delay cannot be less than zero');
        }
    }

    public function getFinishCallback(): ?\Closure
    {
        return $this->finishCallback;
    }

    public function getDelay(): ?int
    {
        return $this->delay;
    }

    public function getRetryCount(): ?int
    {
        return $this->retryCount;
    }

    public function decreaseRetryCount(): static
    {
        if ($this->retryCount === null || $this->retryCount === 0) {
            return $this;
        }

        --$this->retryCount;

        return $this;
    }

    public function getRetryDelay(): ?int
    {
        return $this->retryDelay;
    }

    public function resetFinishCallback(): static
    {
        $this->finishCallback = null;

        return $this;
    }
}
