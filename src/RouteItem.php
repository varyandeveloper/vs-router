<?php

namespace VS\Router;

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
     * RouteItem constructor.
     * @param string $ctrl
     * @param string $method
     * @param array $params
     */
    public function __construct(string $ctrl, string $method, array $params)
    {
        $this->setController($ctrl);
        $this->setMethodName($method);
        $this->setParams($params);
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
}