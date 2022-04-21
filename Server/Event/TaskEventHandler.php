<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Event;

use Nyxio\Contract\Container\ContainerInterface;
use Nyxio\Contract\Event\EventDispatcherInterface;
use Nyxio\Contract\Queue\OptionsInterface;
use Nyxio\Kernel\Event\CronJobCompleted;
use Nyxio\Kernel\Event\CronJobError;
use Nyxio\Kernel\Event\JobCompleted;
use Nyxio\Kernel\Event\JobError;
use Nyxio\Kernel\Server\WorkerData;
use Swoole\Http\Server;

/**
 * @codeCoverageIgnore
 */
class TaskEventHandler
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function handle(Server $server, int $taskId, int $reactorId, WorkerData $workerData): void
    {
        try {
            if (!\class_exists($workerData->job)) {
                throw new \ReflectionException(\sprintf('Class %s doesn\'t exists', $workerData->job));
            }

            $job = $this->container->get($workerData->job);

            $reflection = new \ReflectionClass($workerData->job);

            if (!$reflection->hasMethod('handle')) {
                throw new \RuntimeException(\sprintf("Job %s doesn't have `handle` method", $workerData->job));
            }

            $handle = $reflection->getMethod('handle');

            if ($workerData->options instanceof OptionsInterface) {
                $delay = $workerData->options->getDelay();

                if ($delay !== null) {
                    $server->after($delay, function () use ($server, $job, $handle, $workerData): void {
                        $this->execute($server, $job, $handle, $workerData);
                    });

                    return;
                }
            }

            $this->execute($server, $job, $handle, $workerData);
        } catch (\Throwable $exception) {
            if ($workerData->isCronJob) {
                $this->eventDispatcher->dispatch(CronJobError::NAME, new CronJobError($workerData->job, $exception));
            } else {
                $this->eventDispatcher->dispatch(JobError::NAME, new JobError($workerData->job, $exception));
            }
        }
    }

    protected function execute(
        Server $server,
        object $job,
        \ReflectionMethod $handle,
        WorkerData $workerData
    ): void {
        try {
            $handle->invokeArgs($job, $workerData->data);

            /** @psalm-suppress InvalidArgument  */
            $server->finish($workerData);

            if ($workerData->isCronJob) {
                $this->eventDispatcher->dispatch(CronJobCompleted::NAME, new CronJobCompleted($workerData->job));
            } else {
                $this->eventDispatcher->dispatch(JobCompleted::NAME, new JobCompleted($workerData->job));
            }
        } catch (\Throwable $exception) {
            if ($workerData->isCronJob) {
                $this->eventDispatcher->dispatch(CronJobError::NAME, new CronJobError($workerData->job, $exception));
            } else {
                $this->eventDispatcher->dispatch(JobError::NAME, new JobError($workerData->job, $exception));
            }

            if ($workerData->options instanceof OptionsInterface) {
                if ($workerData->options->getRetryCount() === null) {
                    return;
                }

                if ($workerData->options->getRetryCount() > 0) {
                    $retry = function () use ($server, $job, $handle, $workerData): void {
                        $workerData->options->decreaseRetryCount();

                        $this->execute($server, $job, $handle, $workerData);
                    };

                    $workerData->options->getRetryDelay()
                        ? $server->after($workerData->options->getRetryDelay(), $retry)
                        : $retry();
                }
            }
        }
    }
}
