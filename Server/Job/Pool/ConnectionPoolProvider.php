<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Job\Pool;

use Nyxio\Contract\Kernel\Server\Job\Pool\ConnectionPoolProviderInterface;

class ConnectionPoolProvider implements ConnectionPoolProviderInterface
{
    /**
     * @var \Closure[]
     */
    private array $connectionClosures = [];

    public function register(string $key, \Closure $closure): static
    {
        $this->connectionClosures[$key] = $closure;

        return $this;
    }

    public function getAllRegisterClosures(): array
    {
        return $this->connectionClosures;
    }
}
