<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Job\Schedule;

use Cron\CronExpression;
use Nyxio\Contract\Kernel\Server\Job\DispatcherInterface;
use Nyxio\Contract\Kernel\Server\Job\Schedule\ScheduleDispatcherInterface;
use Nyxio\Contract\Kernel\Server\Job\Schedule\ScheduledJobInterface;
use Nyxio\Helper\Attribute\ExtractAttribute;
use Nyxio\Kernel\Server\Job\JobType;
use Nyxio\Kernel\Server\Job\Schedule\Attribute\Schedule;
use Nyxio\Kernel\Server\Job\TaskData;
use Ramsey\Uuid\Uuid;
use Swoole\Http\Server;

/**
 * @codeCoverageIgnore
 */
class ScheduleDispatcher implements ScheduleDispatcherInterface
{
    public function __construct(
        private readonly Server $server,
        private readonly ExtractAttribute $extractAttribute,
        private readonly DispatcherInterface $dispatcher,
    ) {
    }

    public function launch(array $jobs): void
    {
        $currentDate = new \DateTime();

        foreach ($jobs as $job) {
            try {
                if (!\class_exists($job)) {
                    throw new \ReflectionException(\sprintf('Class %s doesn\'t exists', $job));
                }

                $reflection = new \ReflectionClass($job);

                if (!$reflection->implementsInterface(ScheduledJobInterface::class)) {
                    continue;
                }

                $scheduleAttribute = $this->extractAttribute->first($reflection, Schedule::class);

                if (!$scheduleAttribute instanceof Schedule) {
                    continue;
                }

                $cron = new CronExpression($scheduleAttribute->expression);

                echo \sprintf(
                    'Scheduled job %s is registered | %s' . \PHP_EOL,
                    $job,
                    $scheduleAttribute->expression,
                );

                $this->server->after(
                    $this->calculateNextRunInMilliseconds($currentDate, $cron->getNextRunDate()),
                    function () use ($cron, $job) {
                        $this->server->tick(
                            $this->calculateNextRunInMilliseconds(new \DateTime(), $cron->getNextRunDate()),
                            function () use ($job) {
                                /** @psalm-suppress InvalidArgument */
                                $this->dispatcher->dispatch(
                                    new TaskData(
                                        job: $job,
                                        uuid: Uuid::uuid4()->toString(),
                                        type: JobType::Scheduled
                                    )
                                );
                            }
                        );
                    }
                );
            } catch (\Throwable $exception) {
                echo \sprintf(
                    'Scheduled job %s is not registered (%s)' . \PHP_EOL,
                    $job,
                    $exception->getMessage()
                );

                continue;
            }
        }
    }

    private function calculateNextRunInMilliseconds(
        \DateTimeInterface $currentDate,
        \DateTimeInterface $nextRunDate
    ): int {
        return ($nextRunDate->getTimestamp() - $currentDate->getTimestamp()) * 1000;
    }
}
