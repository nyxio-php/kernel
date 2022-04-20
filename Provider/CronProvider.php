<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Provider;

use Nyxio\Contract\Config\ConfigInterface;
use Nyxio\Contract\Provider\ProviderInterface;
use Nyxio\Contract\Queue\QueueInterface;
use Nyxio\Kernel\Server\Cron\CronJob;

class CronProvider implements ProviderInterface
{
    public function __construct(private readonly QueueInterface $queue, private readonly ConfigInterface $config)
    {
    }

    public function process(): void
    {
        $this->queue->push(CronJob::class, ['jobs' => $this->config->get('cron.jobs')]);
    }
}
