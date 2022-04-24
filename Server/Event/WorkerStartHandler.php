<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Event;

use Nyxio\Contract\Config\ConfigInterface;
use Nyxio\Contract\Kernel\Server\Event\WorkerStartHandlerInterface;
use Nyxio\Contract\Kernel\Server\Job\Async\Schedule\ScheduleDispatcherInterface;
use Swoole\Http\Server;

class WorkerStartHandler implements WorkerStartHandlerInterface
{
    public function __construct(
        private readonly ScheduleDispatcherInterface $scheduleDispatcher,
        private readonly ConfigInterface $config,
    ) {
    }

    public function handle(Server $server, int $workerId): void
    {
        if ($workerId === 0) {
            $this->scheduleDispatcher->launch($this->config->get('schedule.jobs', []));
        }
    }
}
