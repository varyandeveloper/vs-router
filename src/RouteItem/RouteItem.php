<?php

namespace VS\Router\RouteItem;

use VS\Router\MiddlewareInterface;

/**
 * Class RouteItem
 * @package VS\Router
 * @author Varazdat Stepanyan
 */
class RouteItem implements RouteItemInterface
{
    const DEFAULT_METHOD = 'index';
    const DEFAULT_PARAMS = [];

    /**
     * @var string $_controller
     */
    private $_controller;
    /**
     * @var string $_methodName
     */
    private $_methodName;
    /**
     * @var array $_params
     */
    private $_params;
    /**
     * @var MiddlewareInterface[] $_middleware
     */
    private $_middleware = [];

    /**
     * RouteItem constructor.
     * @param string $ctrl
     * @param string $method
     * @param array $params
     * @param array $middleware
     */
    public function __construct(string $ctrl, string $method, array $params, array $middleware = [])
    {
        $this->setController($ctrl);
        $this->setMethodName($method);
        $this->setParams($params);
        $this->setMiddleware($middleware);
    }

    /**
     * @return null|string
     */
    public function getNamespace(): ?string
    {
        $partials = explode('\\', $this->getController());
        array_pop($partials);
        return implode('\\', $partials);
    }

    /**
     * @return string
     */
    public function getController(): string
    {
        return $this->_controller;
    }

    /**
     * @param string|object $controller
     * @return void
     */
    protected function setController($controller): void
    {
        $this->_controller = $controller;
    }

    /**
     * @return string
     */
    public function getMethodName(): string
    {
        return $this->_methodName ?? static::DEFAULT_METHOD;
    }

    /**
     * @param string $methodName
     * @return void
     */
    protected function setMethodName(string $methodName): void
    {
        $this->_methodName = $methodName;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->_params ?? static::DEFAULT_PARAMS;
    }

    /**
     * @param array $params
     * @return void
     */
    protected function setParams(array $params): void
    {
        $this->_params = $params;
    }

    /**
     * @return MiddlewareInterface[]
     */
    public function getMiddleware(): array
    {
        return $this->_middleware;
    }

    /**
     * @param array $middleware
     */
    protected function setMiddleware(array $middleware)
    {
        $this->_middleware = $middleware;
    }
}