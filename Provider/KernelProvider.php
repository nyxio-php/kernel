<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Provider;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyxio\Event;
use Nyxio\Http;
use Nyxio\Kernel;
use Nyxio\Provider;
use Nyxio\Routing;
use Nyxio\Validation;
use Nyxio\Contract;
use Psr\Http\Message;

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

        $this->bootstrap();
    }

    private function validation(): void
    {
        $this->container->singleton(
            Contract\Validation\RuleExecutorCollectionInterface::class,
            Validation\RuleExecutorCollection::class,
        );

        $this->container->singleton(
            Contract\Validation\Handler\RulesCheckerInterface::class,
            Validation\Handler\RulesChecker::class
        );

        $this->container->bind(
            Contract\Validation\Handler\ValidationInterface::class,
            Validation\Handler\Validation::class
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
    }

    private function bootstrap(): void
    {
        $this->container->get(Contract\Validation\RuleExecutorCollectionInterface::class)->register(Validation\DefaultRules::class);

        foreach ($this->config->get('http.groups', []) as $group) {
            $this->container->get(Contract\Routing\GroupCollectionInterface::class)->register($group);
        }

        $this->container->get(Contract\Kernel\Request\ActionCollectionInterface::class)
            ->create($this->config->get('http.actions', []));

    }
}
