<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Request;

use Nyxio\Contract\Container\ContainerInterface;
use Nyxio\Contract\Kernel\Request\ActionCollectionInterface;
use Nyxio\Contract\Routing\GroupCollectionInterface;
use Nyxio\Helper\Attribute\ExtractAttribute;
use Nyxio\Routing\Attribute\Middleware;
use Nyxio\Routing\Attribute\Route;
use Nyxio\Routing\Attribute\RouteGroup;
use Nyxio\Routing\Group;
use Nyxio\Validation\Attribute\Validation;

use function Nyxio\Helper\Reflection\getMethodParametersNames;
use function Nyxio\Helper\Url\joinUri;

class ActionCollection implements ActionCollectionInterface
{
    /**
     * @var Action[]
     */
    private array $actions = [];

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ExtractAttribute $extractAttribute,
        private readonly GroupCollectionInterface $groupCollection,
    ) {
    }

    /**
     * @return Action[]
     */
    public function all(): array
    {
        return $this->actions;
    }

    /**
     * @param string[] $actions
     * @return void
     * @throws \ReflectionException
     */
    public function create(array $actions): void
    {
        foreach ($actions as $action) {
            $this->container->singleton($action);
            $reflectionClass = new \ReflectionClass($action);

            $route = $this->extractAttribute->first($reflectionClass, Route::class);

            if (!$route instanceof Route) {
                continue;
            }

            $middlewares = [];
            $validations = [];
            $uriPrefix = [];

            $routeGroups = $this->getRouteGroupsNames($reflectionClass);

            foreach ($routeGroups as $routeGroup) {
                $rootGroup = $this->groupCollection->get($routeGroup);

                if ($rootGroup === null) {
                    continue;
                }

                foreach ($this->getRouteGroups($rootGroup) as $group) {
                    $middlewares[] = $group->middlewares;
                    $validations[] = $group->validations;

                    $route->appendFields($group->getFields());

                    if ($group->prefix) {
                        $uriPrefix[] = $group->prefix;
                    }
                }
            }

            if (!empty($uriPrefix)) {
                $route->addPrefix(joinUri(...$uriPrefix));
            }

            $middlewares = \array_map(
                fn($middleware) => $this->container->get($middleware),
                array_merge(...$middlewares)
            );

            $validations = \array_map(
                fn($validation) => $this->container->get($validation),
                \array_merge(...$validations)
            );

            $handle = $reflectionClass->getMethod('handle');

            $this->actions[$action] = new Action(
                instance:           $this->container->get($action),
                handleMethod:       $handle,
                handleMethodParams: getMethodParametersNames($handle),
                middlewares:        array_merge($middlewares, $this->getMiddlewares($reflectionClass)),
                validations:        array_merge($validations, $this->getValidations($reflectionClass)),
                route:              $route,
            );
        }
    }

    private function getRouteGroups(Group $group): array
    {
        if ($group->parent !== null) {
            return \array_merge($this->getRouteGroups($group->parent), [$group]);
        }

        return [$group];
    }

    private function getMiddlewares(\ReflectionClass $actionReflection): array
    {
        return \array_map(
            fn(Middleware $middleware) => $this->container->get($middleware->name),
            $this->extractAttribute->all($actionReflection, Middleware::class, true)
        );
    }

    private function getRouteGroupsNames(\ReflectionClass $actionReflection): array
    {
        return \array_map(
            static fn(RouteGroup $group) => $group->name,
            $this->extractAttribute->all($actionReflection, RouteGroup::class, true)
        );
    }

    private function getValidations(\ReflectionClass $actionReflection): array
    {
        return \array_map(
            fn(Validation $validation) => $this->container->get($validation->name),
            $this->extractAttribute->all($actionReflection, Validation::class, true)
        );
    }
}
