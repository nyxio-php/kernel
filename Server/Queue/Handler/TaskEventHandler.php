<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Queue\Handler;

use Nyxio\Contract\Container\ContainerInterface;
use Nyxio\Contract\Event\EventDispatcherInterface;
use Nyxio\Contract\Queue\OptionsInterface;
use Nyxio\Kernel\Event\JobCompleted;
use Nyxio\Kernel\Event\JobError;
use Nyxio\Kernel\Server\Queue\Options;
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

    public function handle(Server $server, int $taskId, int $reactorId, mixed $jobData): void
    {
        try {
            $job = $this->container->get($jobData['job']);

            $reflection = new \ReflectionClass($jobData['job']);

            if (!$reflection->hasMethod('handle')) {
                throw new \RuntimeException(\sprintf("Job %s doesn't have `handle` method", $jobData['job']));
            }

            /** @var OptionsInterface $options */
            $options = $jobData['options'];
            $handle = $reflection->getMethod('handle');

            if ($options->getDelay()) {
                $server->after($options->getDelay(), function () use (
                    $server,
                    $job,
                    $handle,
                    $options,
                    $jobData
                ) {
                    $this->execute($server, $job, $handle, $options, $jobData);
                });
            } else {
                $this->execute($server, $job, $handle, $options, $jobData);
            }
        } catch (\Throwable $exception) {
            echo \sprintf(
                "Task error (%s): \e[1m\033[91m%s\033[0m" . \PHP_EOL,
                $jobData['job'],
                $exception->getMessage()
            );

            $this->eventDispatcher->dispatch(JobError::NAME, new JobError($jobData['job'], $exception));
        }
    }

    protected function execute(
        Server $server,
        object $job,
        \ReflectionMethod $handle,
        OptionsInterface $options,
        array $jobData
    ): void {
        try {
            $handle->invokeArgs($job, $jobData['data']);

            $server->finish($jobData);

            $this->eventDispatcher->dispatch(JobCompleted::NAME, new JobCompleted($jobData['job']));
        } catch (\Throwable $exception) {
            $this->eventDispatcher->dispatch(JobError::NAME, new JobError($jobData['job'], $exception));

            if ($options->getRetryCount() === null) {
                return;
            }

            if ($options->getRetryCount() > 0) {
                $server->after(
                    $options->getRetryDelay() ?? 0,
                    function () use ($server, $job, $handle, $options, $jobData) {
                        $options = new Options(
                            $options->getRetryCount() - 1,
                            $options->getRetryDelay(),
                            $options->getDelay(),
                        );

                        $this->execute($server, $job, $handle, $options, $jobData);
                    }
                );
            }
        }
    }
}
