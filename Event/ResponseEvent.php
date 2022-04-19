<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Event;

use Nyxio\Event\Event;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ResponseEvent extends Event
{
    public const NAME = 'response';

    public function __construct(
        public readonly ResponseInterface $response,
        public readonly ?ServerRequestInterface $request = null,
    ) {
    }
}
