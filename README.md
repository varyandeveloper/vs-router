# vs/router

## Description
An open source PHP ^7.2 Router object to make routing easier


## Features

* Supported request method (You can add any new method that you need)
    * GET
    * POST
    * PUT
    * PATCH
    * DELETE
* Prefixes (nested level)
* Namespaces (nested level)   
* Naming
* No limit for segments
* Supported dynamic segments (You can add any new segment that you need)
    * (n) => /[0-9]/
    * (s) => /[A-Za-z0-9]/
* Supported destination types    
    * String => "ControllerName.ActionName",
    * Closure => function (){} (if pattern will contain dynamic segments function will inject them automatically like ($S1,$S2,...$Sn))
    * array => ["ControllerName", "ActionName"],
    * array (associative) => [
        "controller" => "ControllerName",
        "method" => "ActionName"
    ]


## Installation

* Add line "vs/router": "dev-master" into your composer file
* Change/Add "minimum-stability" of your composer file to "dev"
* Run composer install/update command on your terminal

## Examples

### Method definition

```php
/**
 * @var \VS\Router\RouterInterface $router
*/
$router = \VS\General\DIFactory::injectClass(\VS\Router\Router::class);

$router->get('/', function(){
    // code for defined route gouse here
})->post('/login', 'ControllerName.actionName')
  ->delete(sprintf('/user/%s', \VS\Router\RouterConstants::NUMBER_ARGUMENT_ALIAS), function($id){
    // code for delete user with ID $id gouse here
  });

```