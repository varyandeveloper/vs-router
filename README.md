# vs-router

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