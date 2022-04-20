<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Http;

use Nyxio\Contract\Queue\OptionsInterface;

class WorkerData
{
    public function __construct(
        public readonly string $job,
        public readonly array $data = [],
        public readonly ?OptionsInterface $options = null
    ) {
    }
}
