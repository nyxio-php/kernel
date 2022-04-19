<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Provider;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyxio\Contract;
use Nyxio\Contract\Kernel\Server\ServerEventHandlerInterface;
use Nyxio\Event;
use Nyxio\Http;
use Nyxio\Kernel;
use Nyxio\Kernel\Server\Http\ServerEventHandler;
use Nyxio\Provider;
use Nyxio\Routing;
use Nyxio\Validation;
use Psr\Http\Message;
use Swoole\Http\Server;

class KernelProvider implements Contract\Provider\ProviderInterface
{
    public function __construct(
        private readonly Contract\Container\ContainerInterface $container,
        private readonly Contract\Config\ConfigInterface $config
    ) {
    }

    public function process(): void
    {
        $this->kernel();
        $this->http();
        $this->routing();
        $this->validation();
        $this->server();

        $this->bootstrap();
    }

    private function validation(): void
    {
        $this->container->singleton(
            Contract\Validation\RuleExecutorCollectionInterface::class,
            Validation\RuleExecutorCollection::class,
        );

        $this->container->singleton(
            Contract\Validation\RulesCheckerInterface::class,
            Validation\RulesChecker::class
        );

        $this->container->bind(
            Contract\Validation\ValidationInterface::class,
            Validation\Validation::class
        );
    }

    private function routing(): void
    {
        $this->container->singleton(Contract\Routing\GroupCollectionInterface::class, Routing\GroupCollection::class);
        $this->container->bind(Contract\Routing\UriMatcherInterface::class, Routing\UriMatcher::class);
    }

    // PSR-17: Factories
    private function http(): void
    {
        $this->container->singleton(Message\UriFactoryInterface::class, Http\Factory\UriFactory::class);
        $this->container->singleton(Message\StreamFactoryInterface::class, Psr17Factory::class);
        $this->container->singleton(Message\UploadedFileFactoryInterface::class, Psr17Factory::class);
        $this->container->singleton(Message\ServerRequestFactoryInterface::class, Http\Factory\RequestFactory::class);
        $this->container->singleton(Message\ResponseFactoryInterface::class, Http\Factory\ResponseFactory::class);
    }

    private function kernel(): void
    {
        $this->container->singleton(Kernel\Application::class);

        $this->container->singleton(
            Contract\Kernel\Request\ActionCollectionInterface::class,
            Kernel\Request\ActionCollection::class
        );

        $this->container
            ->singleton(
                Contract\Kernel\Exception\Transformer\ExceptionTransformerInterface::class,
                Kernel\Exception\Transformer\ExceptionTransformer::class
            )
            ->addArgument('debug', $this->config->get('app.debug', false));

        $this->container->singleton(
            Contract\Kernel\Request\RequestHandlerInterface::class,
            Kernel\Request\RequestHandler::class
        );

        $this->container->singleton(Contract\Provider\ProviderDispatcherInterface::class, Provider\Dispatcher::class);
        $this->container->singleton(Contract\Event\EventDispatcherInterface::class, Event\Dispatcher::class);
        $this->container->singleton(Contract\Kernel\Text\MessageInterface::class, Kernel\Text\Message::class);
        $this->container->singleton(Contract\Queue\QueueInterface::class, Kernel\Server\Queue\Queue::class);
    }

    private function bootstrap(): void
    {
        $this->container->get(Contract\Validation\RuleExecutorCollectionInterface::class)->register(
            Validation\Helper\DefaultRules::class);

        foreach ($this->config->get('http.groups', []) as $group) {
            $this->container->get(Contract\Routing\GroupCollectionInterface::class)->register($group);
        }

        $this->container->get(Contract\Kernel\Request\ActionCollectionInterface::class)
            ->create($this->config->get('http.actions', []));

    }

    private function server(): void
    {
        $this->container->singletonFn(Server::class, function () {
            $server = new Server(
                $this->config->get('server.host', '127.0.0.1'),
                $this->config->get('server.port', 9501)
            );

            $server->set($this->config->get('server.options', []));

            return $server;
        });

        $this->container->singleton(ServerEventHandlerInterface::class, ServerEventHandler::class);
    }
}
