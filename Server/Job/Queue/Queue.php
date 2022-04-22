<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Job\Queue;

use Nyxio\Contract\Kernel\Server\Job\DispatcherInterface;
use Nyxio\Contract\Kernel\Server\Job\OptionsInterface;
use Nyxio\Contract\Kernel\Server\Job\Queue\QueueInterface;
use Nyxio\Kernel\Server\Job\JobType;
use Nyxio\Kernel\Server\Job\TaskData;
use Ramsey\Uuid\Uuid;

class Queue implements QueueInterface
{
    public function __construct(private readonly DispatcherInterface $dispatcher)
    {
    }

    public function push(string $job, array $data = [], ?OptionsInterface $options = null): void
    {
        $this->dispatcher->dispatch(
            new TaskData(
                job:     $job,
                uuid:    Uuid::uuid4()->toString(),
                type:    JobType::Queue,
                data:    $data,
                options: $options
            )
        );
    }
}
