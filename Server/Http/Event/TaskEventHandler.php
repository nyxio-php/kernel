<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Http\Event;

use Swoole\Http\Server;

/**
 * @codeCoverageIgnore
 */
class TaskEventHandler
{
    public function handle(Server $server, int $task_id, int $reactorId, mixed $data): void
    {

    }
}
