<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Job;

use Nyxio\Contract\Kernel\Server\Job\OptionsInterface;

class TaskData
{
    public function __construct(
        public readonly string $job,
        public readonly string $uuid,
        public readonly JobType $type = JobType::Queue,
        public readonly array $data = [],
        public readonly ?OptionsInterface $options = null,
    ) {
    }
}
