<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Job;

use Nyxio\Contract\Container\ContainerInterface;

abstract class BaseTaskHandler
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    /**
     * @param TaskData $taskData
     * @return mixed
     * @throws \ReflectionException
     */
    protected function invokeJob(TaskData $taskData): mixed
    {
        if (!\class_exists($taskData->job)) {
            throw new \ReflectionException(\sprintf("Class %s doesn't exists", $taskData->job));
        }

        $jobObject = $this->container->get($taskData->job);

        $reflection = new \ReflectionClass($taskData->job);

        if (!$reflection->hasMethod('handle')) {
            throw new \ReflectionException(
                \sprintf("Job %s (%s) doesn't have `handle` method", $taskData->job, $taskData->uuid)
            );
        }

        return $reflection->getMethod('handle')->invokeArgs($jobObject, $taskData->data);
    }
}
