<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Queue\Handler;

use Nyxio\Contract\Container\ContainerInterface;
use Nyxio\Contract\Queue\OptionsInterface;
use Nyxio\Kernel\Server\Queue\Options;
use Swoole\Http\Server;

/**
 * @codeCoverageIgnore
 */
class TaskEventHandler
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function handle(Server $server, int $taskId, int $reactorId, mixed $data): void
    {
        try {
            $job = $this->container->get($data['job']);

            $reflection = new \ReflectionClass($data['job']);

            if (!$reflection->hasMethod('handle')) {
                throw new \RuntimeException(\sprintf("Job %s doesn't have `handle` method", $data['job']));
            }

            $handle = $reflection->getMethod('handle');

            $this->execute($server, $job, $handle, $data['options'], $data['data']);

            $server->finish($data);
        } catch (\Throwable $exception) {
            echo \sprintf(
                "Task error (%s): \e[1m\033[91m%s\033[0m" . \PHP_EOL,
                $data['job'],
                $exception->getMessage()
            );
        }
    }

    protected function execute(
        Server $server,
        object $job,
        \ReflectionMethod $handle,
        OptionsInterface $options,
        array $data = []
    ): void {
        try {
            $handle->invokeArgs($job, $data['data']);
        } catch (\Throwable $exception) {
            if ($options->getRetryCount() === null) {
                return;
            }

            if ($options->getRetryCount() > 0) {
                $server->after(
                    $options->getRetryDelay() ?? 0,
                    function () use ($server, $job, $handle, $options, $data) {
                        $options = new Options(
                            $options->getRetryCount() - 1,
                            $options->getRetryDelay(),
                            $options->getDelay(),
                        );

                        $this->execute($server, $job, $handle, $options, $data);
                    }
                );
            }
        }
    }
}
