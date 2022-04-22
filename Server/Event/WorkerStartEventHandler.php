<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Event;

use Nyxio\Contract\Config\ConfigInterface;
use Nyxio\Contract\Kernel\Server\Job\Schedule\ScheduleDispatcherInterface;
use Swoole\Http\Server;

/**
 * @codeCoverageIgnore
 */
class WorkerStartEventHandler
{
    public function __construct(
        private readonly ScheduleDispatcherInterface $scheduleDispatcher,
        private readonly ConfigInterface $config
    ) {
    }

    public function handle(Server $server, int $workerId): void
    {
        if ($workerId === 0) {
            $this->scheduleDispatcher->launch($this->config->get('schedule.jobs', []));
        }
    }
}
