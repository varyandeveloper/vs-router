<?php

namespace VS\Router;

/**
 * Interface RouterInterface
 * @package VS\Router
 * @author Varazdat Stepanyan
 *
 * @method RouterInterface get(string $pattern, $destination)
 * @method RouterInterface post(string $pattern, $destination)
 * @method RouterInterface put(string $pattern, $destination)
 * @method RouterInterface patch(string $pattern, $destination)
 * @method RouterInterface delete(string $pattern, $destination)
 */
interface RouterInterface
{
    /**
     * @param string $alias
     * @return RouterInterface
     */
    public function as(string $alias): RouterInterface;

    /**
     * @param string $prefix
     * @param string $controller
     * @return RouterInterface
     */
    public function CRUD(string $prefix, string $controller): RouterInterface;

    /**
     * @param string $prefix
     * @param string $controller
     * @return RouterInterface
     */
    public function REST(string $prefix, string $controller): RouterInterface;

    /**
     * @param string $prefix
     * @param string $controller
     * @param string $namespace
     * @return RouterInterface
     */
    public function AUTH(string $prefix, string $controller, string $namespace): RouterInterface;

    /**
     * @param string $alias
     * @param array $params
     * @return string
     */
    public function getByAlias(string $alias, array $params = []): string;

    /**
     * @param string $prefix
     * @param callable $callback
     * @return RouterInterface
     */
    public function prefix(string $prefix, callable $callback): RouterInterface;

    /**
     * @param string $namespace
     * @param \Closure $callback
     * @return RouterInterface
     */
    public function namespace(string $namespace, \Closure $callback): RouterInterface;

    /**
     * @param string $method
     * @param string $pattern
     * @param $destination
     * @return mixed
     */
    public function map(string $method, string $pattern, $destination);

    /**
     * @return RouteItemInterface
     */
    public function getRouteItem(): RouteItemInterface;

    /**
     * @return array
     */
    public function getRoutes(): array;
}