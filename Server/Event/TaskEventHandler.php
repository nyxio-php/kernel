<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Event;

use Nyxio\Contract\Container\ContainerInterface;
use Nyxio\Contract\Event\EventDispatcherInterface;
use Nyxio\Contract\Kernel\Server\Job\OptionsInterface;
use Nyxio\Kernel\Event\QueueException;
use Nyxio\Kernel\Event\ScheduleException;
use Nyxio\Kernel\Server\Job\JobType;
use Nyxio\Kernel\Server\Job\TaskData;
use Swoole\Http\Server;

/**
 * @codeCoverageIgnore
 */
class TaskEventHandler
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function handle(Server $server, int $taskId, int $reactorId, TaskData $taskData): void
    {
        try {
            $this->dispatch($server, $taskData);
        } catch (\Throwable $exception) {
            $this->catchException($exception, $taskData);
        }
    }

    private function dispatch(Server $server, TaskData $taskData, bool $isRetry = false): void
    {
        try {
            $job = $this->container->get($taskData->job);

            $reflection = new \ReflectionClass($taskData->job);

            if (!$reflection->hasMethod('handle')) {
                throw new \RuntimeException(
                    \sprintf("Job %s (%s) doesn't have `handle` method", $taskData->job, $taskData->uuid)
                );
            }

            $handle = $reflection->getMethod('handle');

            if (
                $isRetry === false
                && ($taskData->options instanceof OptionsInterface)
                && $taskData->options->getDelay() !== null
            ) {
                $server->after(
                    $taskData->options->getDelay(),
                    function () use ($job, $taskData, $handle, $server) {
                        $this->invoke($server, $handle, $job, $taskData);
                    }
                );

                return;
            }

            $this->invoke($server, $handle, $job, $taskData);
        } catch (\Throwable $exception) {
            $this->retry($server, $taskData, $exception);
        }
    }

    private function invoke(Server $server, \ReflectionMethod $handle, object $job, TaskData $taskData): void
    {
        try {
            $handle->invokeArgs($job, $taskData->data);
            /** @psalm-suppress InvalidArgument */
            $server->finish($taskData);
        } catch (\Throwable $exception) {
            $this->retry($server, $taskData, $exception);
        }
    }

    private function retry(Server $server, TaskData $taskData, \Throwable $exception): void
    {
        if (
            ($taskData->options instanceof OptionsInterface)
            && $taskData->options->getRetryCount() !== null
            && $taskData->options->getRetryCount() > 0
        ) {
            $taskData->options->decreaseRetryCount();

            if ($taskData->options->getRetryDelay() !== null) {
                $server->after($taskData->options->getRetryDelay(), function () use ($server, $taskData) {
                    $this->dispatch($server, $taskData, isRetry: true);
                });

                return;
            }

            $this->dispatch($server, $taskData, isRetry: true);

            return;
        }

        $this->catchException($exception, $taskData);
    }


    private function catchException(\Throwable $exception, TaskData $taskData): void
    {
        switch ($taskData->type) {
            case JobType::Queue:
                $this->eventDispatcher->dispatch(
                    QueueException::NAME,
                    new QueueException($taskData, $exception)
                );
                break;
            case JobType::Scheduled:
                $this->eventDispatcher->dispatch(
                    ScheduleException::NAME,
                    new ScheduleException($taskData, $exception)
                );
                break;
        }
    }
}
