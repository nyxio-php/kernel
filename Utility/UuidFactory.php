<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Utility;

use Nyxio\Contract\Kernel\Utility\UuidFactoryInterface;
use Ramsey\Uuid\Uuid;

class UuidFactory implements UuidFactoryInterface
{
    public function generate(): string
    {
        return Uuid::uuid4()->toString();
    }
}
