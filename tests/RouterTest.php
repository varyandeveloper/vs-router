<?php

/**
 * Class RouterTest
 * @author Varazdat Stepanyan
 */
class RouterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \VS\Router\RouterInterface $Router
     */
    protected $Router;

    public function setUp()
    {
        $this->Router = new \VS\Router\Router(new \VS\Url\Url(), new \VS\Request\Request());
        $this->Router->reset();
    }

    public function testRequestRoutesRegistration()
    {
        $this->Router->get('/', 'IndexController');
        $this->Router->post('/login', 'IndexController.login');

        $putRoute = sprintf('/user/%s/edit', \VS\Router\RouterConstants::NUMBER_ARGUMENT_ALIAS);
        $this->Router->put($putRoute, 'IndexController.update');
        $this->Router->post('/auth/register', 'AuthController.register');

        $deleteRoute = sprintf('/user/%s', \VS\Router\RouterConstants::NUMBER_ARGUMENT_ALIAS);
        $this->Router->delete($deleteRoute, 'UserController.delete');

        $patchRoute = sprintf('/office/user/%s/edit', \VS\Router\RouterConstants::NUMBER_ARGUMENT_ALIAS);
        $this->Router->patch($patchRoute, 'OfficeUserController.updateFew');

        $excepted = [
            'GET' => [
                1 => [
                    '/' => 'IndexController'
                ]
            ],
            'POST' => [
                1 => [
                    '/login' => 'IndexController.login'
                ],
                2 => [
                    '/auth/register' => 'AuthController.register'
                ]
            ],
            'PUT' => [
                3 => [
                    $putRoute => 'IndexController.update'
                ]
            ],
            'DELETE' => [
                2 => [
                    $deleteRoute => 'UserController.delete'
                ]
            ],
            'PATCH' => [
                4 => [
                    $patchRoute => 'OfficeUserController.updateFew'
                ]
            ]
        ];

        $actual = $this->Router->getRoutes();

        $this->assertEquals($excepted, $actual);
    }

    public function testRoutesPrefix()
    {
        $this->Router->prefix('my-prefix', function (\VS\Router\RouterInterface $router) {

            // now all routes defined inside function will be prefixed with [my-prefix] prefix

            $router->get('/test', 'TestController.test');

            // nested prefixing
            $router->prefix('done', function (\VS\Router\RouterInterface $router){
                $router->post('/by/me', 'DoneController');
            });

        });

        // route defined out off closure will not be prefixed
        $this->Router->get('/hey-there', 'HeyThereController.tested');

        $excepted = [
            'GET' => [
                1 => [
                    '/hey-there' => 'HeyThereController.tested'
                ],
                2 => [
                    '/my-prefix/test' => 'TestController.test'
                ]
            ],
            'POST' => [
                4 => [
                    '/my-prefix/done/by/me' => 'DoneController'
                ]
            ]
        ];

        $actual = $this->Router->getRoutes();

        $this->assertEquals($excepted, $actual);
    }

    public function testRoutesNamespace()
    {
        $this->Router->namespace('Auth', function (\VS\Router\RouterInterface $router){
            // all destinations (controller names) will be under [Auth] namespace

            $router->post('/login', 'LoginController.login');

            $router->namespace('Other', function (\VS\Router\RouterInterface $router){
                $router->get('/tested', 'TestedCtrl');
            });
        });

        $excepted = [
            'POST' => [
                1 => [
                    '/login' => 'Auth\\LoginController.login'
                ]
            ],
            'GET' => [
                1 => [
                    '/tested' => 'Auth\\Other\\TestedCtrl'
                ]
            ]
        ];
        $actual = $this->Router->getRoutes();

        $this->assertEquals($excepted, $actual);
    }

    public function testRoutesNaming()
    {
        $this->Router->get('/', 'IndexController.index')->as('home');

        $excepted = '/';
        $actual = $this->Router->getByAlias('home');

        $this->assertEquals($excepted, $actual);
    }

    public function testRESTRoutes()
    {
        $this->Router->REST('user', 'UserController');
        $argument = sprintf('/%s', \VS\Router\RouterConstants::NUMBER_ARGUMENT_ALIAS);

        $excepted = $this->Router->getRoutes();
        $actual = [
            'GET' => [
                1 => [
                    '/user' => 'UserController.list',
                ],
                2 => [
                    "/user{$argument}" => 'UserController.one'
                ],
            ],
            'POST' => [
                1 => [
                    '/user' => 'UserController.save',
                ],
            ],
            'PUT' => [
                2 => [
                    "/user{$argument}" => 'UserController.replace',
                ],
            ],
            'PATCH' => [
                2 => [
                    "/user$argument" => 'UserController.replaceFew',
                ],
            ],
            'DELETE' => [
                2 => [
                    "/user$argument" => 'UserController.remove',
                ],
            ]
        ];

        $this->assertEquals($excepted, $actual);
    }

    public function testAddNewMethodsDynamically()
    {
        $methods = ['cli'];

        \VS\Router\RouterConstants::setAllowedMethods($methods);

        $this->Router->cli('/test', function (){
            // logic here for cli route
        });

        $excepted = [
            'CLI' => [
                1 => [
                    '/test' => function(){}
                ]
            ]
        ];

        $actual = $this->Router->getRoutes();

        $this->assertEquals($excepted, $actual);
        $this->expectException(BadMethodCallException::class);
        $this->Router->options('/done', 'TestCtrl');
    }

    public function testRulesMethod()
    {
        $argument = sprintf('/%s', \VS\Router\RouterConstants::NUMBER_ARGUMENT_ALIAS);
        $this->Router->rules([
            'prefix' => 'categories',
            'namespace' => 'Category\Controller',
        ], function ($router) use ($argument) {
            $router->REST('', 'TestController');
            $router->get("{$argument}/sub-categories", 'TestController.subCategories');
        });

        $actual = $this->Router->getRoutes();

        $expected = [
            'GET' => [
                1 => [
                    '/categories' => 'Category\Controller\TestController.list',
                ],
                2 => [
                    "/categories{$argument}" => 'Category\Controller\TestController.one',
                    "{$argument}/sub-categories" => 'Category\Controller\TestController.subCategories',
                ],
            ],
            'POST' => [
                1 => [
                    '/categories' => 'Category\Controller\TestController.save',
                ],
            ],
            'PUT' => [
                2 => [
                    "/categories{$argument}" => 'Category\Controller\TestController.replace',
                ],
            ],
            'PATCH' => [
                2 => [
                    "/categories$argument" => 'Category\Controller\TestController.replaceFew',
                ],
            ],
            'DELETE' => [
                2 => [
                    "/categories$argument" => 'Category\Controller\TestController.remove',
                ],
            ]
        ];

        $this->assertEquals($expected, $actual);
    }
}