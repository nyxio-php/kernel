<?php

declare(strict_types=1);

namespace Nyx\Kernel\Request;

use Nyx\Contract\Http\MiddlewareInterface;
use Nyx\Routing\Attribute\Route;

class ActionCache
{
    /**
     * @param object $instance
     * @param \ReflectionMethod $handleMethod
     * @param array $handleMethodParams
     * @param MiddlewareInterface[] $middlewares
     * @param MiddlewareInterface[] $validations
     * @param Route $route
     */
    public function __construct(
        public readonly object $instance,
        public readonly \ReflectionMethod $handleMethod,
        public readonly array $handleMethodParams,
        public readonly array $middlewares,
        public readonly array $validations,
        public readonly Route $route
    ) {
    }
}
