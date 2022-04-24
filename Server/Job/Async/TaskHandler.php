<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Job\Async;

use Nyxio\Contract\Container\ContainerInterface;
use Nyxio\Contract\Event\EventDispatcherInterface;
use Nyxio\Contract\Kernel\Server\Job\Async\OptionsInterface;
use Nyxio\Contract\Kernel\Server\Job\Async\TaskHandlerInterface;
use Nyxio\Kernel\Event\QueueComplete;
use Nyxio\Kernel\Event\QueueException;
use Nyxio\Kernel\Event\ScheduleComplete;
use Nyxio\Kernel\Event\ScheduleException;
use Nyxio\Kernel\Server\Job\BaseTaskHandler;
use Nyxio\Kernel\Server\Job\TaskData;
use Nyxio\Kernel\Server\Job\TaskType;
use Swoole\Http\Server;

class TaskHandler extends BaseTaskHandler implements TaskHandlerInterface
{
    public function __construct(
        protected ContainerInterface $container,
        protected readonly EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($container);
    }

    public function handle(Server $server, TaskData $taskData): void
    {
        $this->dispatch($server, $taskData);
    }

    private function dispatch(Server $server, TaskData $taskData, bool $isRetry = false): void
    {
        try {
            if (
                $isRetry === false
                && ($taskData->options instanceof OptionsInterface)
                && $taskData->options->getDelay() !== null
            ) {
                $server->after(
                    $taskData->options->getDelay(),
                    function () use ($taskData, $server) {
                        $this->invoke($server, $taskData);
                    }
                );

                return;
            }

            $this->invoke($server, $taskData);
        } catch (\Throwable $exception) {
            $this->retry($server, $taskData, $exception);
        }
    }

    private function invoke(Server $server, TaskData $taskData): void
    {
        try {
            /** @psalm-suppress InvalidArgument */
            $server->finish($this->invokeJob($taskData));

            switch ($taskData->type) {
                case TaskType::Queue:
                    $this->finishQueueJob($taskData);
                    break;
                case TaskType::Scheduled:
                    $this->finishScheduleJob($taskData);
                    break;
                default:
                    break;
            }
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

        $this->catchAsyncException($exception, $taskData);
    }


    private function catchAsyncException(\Throwable $exception, TaskData $taskData): void
    {
        switch ($taskData->type) {
            case TaskType::Queue:
                $this->eventDispatcher->dispatch(
                    QueueException::NAME,
                    new QueueException($taskData, $exception)
                );
                break;
            case TaskType::Scheduled:
                $this->eventDispatcher->dispatch(
                    ScheduleException::NAME,
                    new ScheduleException($taskData, $exception)
                );
                break;
            default:
                break;
        }
    }

    private function finishScheduleJob(TaskData $taskData): void
    {
        $this->eventDispatcher->dispatch(
            ScheduleComplete::NAME,
            new ScheduleComplete($taskData)
        );
    }

    private function finishQueueJob(TaskData $taskData): void
    {
        $this->eventDispatcher->dispatch(
            QueueComplete::NAME,
            new QueueComplete($taskData)
        );
    }
}
