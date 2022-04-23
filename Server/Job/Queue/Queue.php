<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Job\Queue;

use Nyxio\Contract\Kernel\Server\Job\DispatcherInterface;
use Nyxio\Contract\Kernel\Server\Job\OptionsInterface;
use Nyxio\Contract\Kernel\Server\Job\Queue\QueueInterface;
use Nyxio\Contract\Kernel\Utility\UuidFactoryInterface;
use Nyxio\Kernel\Server\Job\JobType;
use Nyxio\Kernel\Server\Job\TaskData;

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
                type:    JobType::Queue,
                data:    $data,
                options: $options
            )
        );
    }
}
