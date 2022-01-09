<?php

declare(strict_types=1);

namespace Nyx\Kernel\Provider;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyx\Event;
use Nyx\Http;
use Nyx\Kernel;
use Nyx\Provider;
use Nyx\Routing;
use Nyx\Validation;
use Psr\Http\Message;

class KernelProvider implements \Nyx\Contract\Provider\ProviderInterface
{
    public function __construct(
        private readonly \Nyx\Contract\Container\ContainerInterface $container,
        private readonly \Nyx\Contract\Config\ConfigInterface $config
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
            \Nyx\Contract\Validation\RuleExecutorCollectionInterface::class,
            Validation\RuleExecutorCollection::class,
        );

        $this->container->singleton(
            \Nyx\Contract\Validation\Handler\RulesCheckerInterface::class,
            Validation\Handler\RulesChecker::class
        );

        $this->container->bind(
            \Nyx\Contract\Validation\Handler\ValidatorCollectionInterface::class,
            Validation\Handler\ValidatorCollection::class
        );
    }

    private function routing(): void
    {
        $this->container->singleton(\Nyx\Contract\Routing\GroupCollectionInterface::class, Routing\GroupCollection::class);
        $this->container->bind(\Nyx\Contract\Routing\UriMatcherInterface::class, Routing\UriMatcher::class);
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
            \Nyx\Contract\Kernel\Request\ActionCollectionInterface::class,
            Kernel\Request\ActionCollection::class
        );

        $this->container
            ->singleton(
                \Nyx\Contract\Kernel\Exception\Transformer\ExceptionTransformerInterface::class,
                Kernel\Exception\Transformer\ExceptionTransformer::class
            )
            ->addArgument('debug', $this->config->get('app.debug', false));

        $this->container->singleton(
            \Nyx\Contract\Kernel\Request\RequestHandlerInterface::class,
            Kernel\Request\RequestHandler::class
        );

        $this->container->singleton(\Nyx\Contract\Provider\ProviderDispatcherInterface::class, Provider\Dispatcher::class);
        $this->container->singleton(\Nyx\Contract\Event\EventDispatcherInterface::class, Event\Dispatcher::class);
    }

    private function bootstrap(): void
    {
        $this->container->get(\Nyx\Contract\Validation\RuleExecutorCollectionInterface::class)->register(Validation\DefaultRules::class);

        foreach ($this->config->get('http.groups', []) as $group) {
            $this->container->get(\Nyx\Contract\Routing\GroupCollectionInterface::class)->register($group);
        }

        $this->container->get(\Nyx\Contract\Kernel\Request\ActionCollectionInterface::class)
            ->create($this->config->get('http.actions', []));

    }
}
