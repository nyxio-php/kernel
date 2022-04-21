<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Event;

use Nyxio\Contract\Config\ConfigInterface;
use Nyxio\Contract\Kernel\Server\CronLauncherInterface;
use Swoole\Http\Server;

/**
 * @codeCoverageIgnore
 */
class WorkerStartEventHandler
{
    public function __construct(
        private readonly CronLauncherInterface $cronLauncher,
        private readonly ConfigInterface $config
    ) {
    }

    public function handle(Server $server, int $workerId): void
    {
        if ($workerId === 0) {
            $this->cronLauncher->launch($this->config->get('cron.jobs'));
        }
    }
}
