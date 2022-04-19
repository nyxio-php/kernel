<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Queue\Handler;

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
