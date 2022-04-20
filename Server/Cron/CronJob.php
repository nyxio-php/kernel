<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Cron;

use Cron\CronExpression;
use Nyxio\Contract\Config\ConfigInterface;
use Nyxio\Helper\Attribute\ExtractAttribute;
use Nyxio\Kernel\Server\Cron\Attribute\Cron;
use Nyxio\Kernel\Server\Http\WorkerData;
use Swoole\Http\Server;

class CronJob
{
    private array $jobs;

    public function __construct(
        private readonly Server $server,
        private readonly ConfigInterface $config,
        private readonly ExtractAttribute $extractAttribute,
    ) {
        $this->jobs = $this->config->get('cron.jobs');
    }

    public function handle(): void
    {
        $currentDate = new \DateTime();
        foreach ($this->jobs as $job) {
            try {
                $reflection = new \ReflectionClass($job);
                $cronAttribute = $this->extractAttribute->first($reflection, Cron::class);

                if (!$cronAttribute instanceof Cron) {
                    continue;
                }

                echo \sprintf('Cron job %s is registered (5s)' . \PHP_EOL, $job, $cronAttribute->expression);

                $cron = new CronExpression($cronAttribute->expression);

                $this->server->after(
                    $this->calculateNextRunInMilliseconds($currentDate, $cron->getNextRunDate()),
                    function () use ($job, $cron) {
                        $workerData = new WorkerData($job);
                        $this->server->task(
                            $workerData,
                            null,
                            function () use ($workerData, $cron) {
                                $this->server->tick(
                                    $this->calculateNextRunInMilliseconds(
                                        currentDate: new \DateTime(),
                                        nextRunDate: $cron->getNextRunDate()
                                    ),
                                    function () use ($workerData) {
                                        $this->server->task($workerData);
                                    }
                                );
                            }
                        );
                    }
                );
            } catch (\Throwable) {
                continue;
            }
        }
    }

    private function calculateNextRunInMilliseconds(
        \DateTimeInterface $currentDate,
        \DateTimeInterface $nextRunDate
    ): int {
        return ($nextRunDate->getTimestamp() - $currentDate->getTimestamp()) * 100;
    }
}
