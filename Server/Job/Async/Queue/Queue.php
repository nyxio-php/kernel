<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Job\Async\Queue;

use Nyxio\Contract\Kernel\Server\Job\Async\OptionsInterface;
use Nyxio\Contract\Kernel\Server\Job\Async\Queue\QueueInterface;
use Nyxio\Contract\Kernel\Server\Job\DispatcherInterface;
use Nyxio\Contract\Kernel\Utility\UuidFactoryInterface;
use Nyxio\Kernel\Server\Job\TaskData;
use Nyxio\Kernel\Server\Job\TaskType;

class Queue implements QueueInterface
{
    public function __construct(
        private readonly DispatcherInterface $dispatcher,
        private readonly UuidFactoryInterface $uuidFactory,
    ) {
    }

    public function push(string $job, array $data = [], ?OptionsInterface $options = null): void
    {
        $this->dispatcher->dispatch(
            new TaskData(
                job:     $job,
                uuid:    $this->uuidFactory->generate(),
                type:    TaskType::Queue,
                data:    $data,
                options: $options
            )
        );
    }
}
