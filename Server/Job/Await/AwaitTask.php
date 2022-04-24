<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Job\Await;

use Nyxio\Contract\Kernel\Server\Job\Await\OptionsInterface;
use Nyxio\Contract\Kernel\Server\Job\Await\AwaitTaskInterface;
use Nyxio\Contract\Kernel\Server\Job\DispatcherInterface;
use Nyxio\Contract\Kernel\Utility\UuidFactoryInterface;
use Nyxio\Kernel\Server\Job\TaskData;
use Nyxio\Kernel\Server\Job\TaskType;

class AwaitTask implements AwaitTaskInterface
{
    public function __construct(
        private readonly DispatcherInterface $dispatcher,
        private readonly UuidFactoryInterface $uuidFactory
    ) {
    }

    public function run(string $job, array $data = [], OptionsInterface $options = null): mixed
    {
        return $this->dispatcher->dispatch(
            new TaskData(
                job:     $job,
                uuid:    $this->uuidFactory->generate(),
                type:    TaskType::Await,
                data:    $data,
                options: $options,
            )
        );
    }
}
