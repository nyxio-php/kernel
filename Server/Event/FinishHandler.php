<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Event;

use Nyxio\Contract\Kernel\Server\Event\FinishHandlerInterface;
use Swoole\Http\Server;

class FinishHandler implements FinishHandlerInterface
{
    public function handle(Server $server, int $taskId, mixed $returnData): void
    {
    }
}
