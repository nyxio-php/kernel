<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Job;

use Nyxio\Contract\Kernel\Server\Job\Async;
use Nyxio\Contract\Kernel\Server\Job\Await;

class TaskData
{
    public function __construct(
        public readonly string $job,
        public readonly string $uuid,
        public readonly TaskType $type,
        public readonly array $data = [],
        public readonly Await\OptionsInterface|Async\OptionsInterface|null $options = null,
    ) {
    }

    public function isAsync(): bool
    {
        return \in_array($this->type, [TaskType::Scheduled, TaskType::Queue], true);
    }
}
