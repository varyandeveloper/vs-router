<?php

namespace VS\Router;

use VS\Request\Request;

/**
 * Interface MiddlewareInterface
 * @package VS\Framework\Router
 */
interface MiddlewareInterface
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function before(Request $request);

    /**
     * @param $response
     * @return mixed
     */
    public function after($response);
}