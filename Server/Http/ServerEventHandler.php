<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Http;

use Nyxio\Contract\Config\ConfigInterface;
use Nyxio\Contract\Kernel\Server\ServerEventHandlerInterface;
use Nyxio\Kernel\Server\Http\Event\FinishEventHandler;
use Nyxio\Kernel\Server\Http\Event\RequestEventHandler;
use Nyxio\Kernel\Server\Http\Event\TaskEventHandler;
use Swoole\Http\Server;

class ServerEventHandler implements ServerEventHandlerInterface
{
    public function __construct(
        private readonly Server $server,
        private readonly RequestEventHandler $requestEventHandler,
        private readonly TaskEventHandler $taskEventHandler,
        private readonly FinishEventHandler $finishEventHandler,
        private readonly ConfigInterface $config,
    ) {
    }

    public function handle(): void
    {
        $this->server->on('request', [$this->requestEventHandler, 'handle']);

        if ($this->config->get('app.queue.enable', false) === true) {
            $this->server->on('task', [$this->taskEventHandler, 'handle']);
            $this->server->on('finish', [$this->finishEventHandler, 'handle']);
        }
    }
}
