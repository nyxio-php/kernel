<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Http\Event;

use Swoole\Http\Server;

/**
 * @codeCoverageIgnore
 */
class FinishEventHandler
{
    public function handle(Server $server, int $taskId, mixed $data): void
    {
    }
}
