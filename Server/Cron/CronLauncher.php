<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Cron;

use Cron\CronExpression;
use Nyxio\Contract\Kernel\Server\CronLauncherInterface;
use Nyxio\Helper\Attribute\ExtractAttribute;
use Nyxio\Kernel\Server\Cron\Attribute\Cron;
use Nyxio\Kernel\Server\WorkerData;
use Swoole\Http\Server;

class CronLauncher implements CronLauncherInterface
{
    public function __construct(
        private readonly Server $server,
        private readonly ExtractAttribute $extractAttribute,
    ) {
    }

    public function launch(array $jobs): void
    {
        $currentDate = new \DateTime();
        foreach ($jobs as $job) {
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
