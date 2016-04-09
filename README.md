> This Extension allows the registration of endpoints without requiring any pages or plugins in the TYPO3 Backend. 

To accomplish this, the Extension hooks into the TYPO3 bootstrap process - at a point where the FrontendUser is initialized, but before any attempt to resolve an actual page is made. The requested URL is then matched against all registered routes and in case a match is found the request gets intercepted and rerouted to the specified controller action.

**A page that would normally be accessed by `/example` can never be accessed if a route matching `/example` was registered! Routes are not automatically scoped or namespaced and you should therefore carefully consider the implications your routes may have!**

Usage
==========

In order to have routes registered before any matching takes place, route configuration should happen in the ext_localconf.php of your extension.

```php
/** @var \Ondigo\ExtbaseRouter\Routing\Router $router */
$router = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Ondigo\ExtbaseRouter\Routing\Router::class);
    
$router->get('/api/resource', \Vendor\Extension\Controller\ResourceController::class, 'list');
```

All requests matching `/api/resource` with HTTP Method GET are then routed directly to `ResourceController->listAction`

*At the moment your controller is required to set the appropriate headers and output all data. 
This Router does nothing except for eliminating the overhead caused by TYPO3 when rendering a single plugin where the normal page layout isn't needed.*

API
=========

```php
get($url, $controller, $action)
```

```php
post($url, $controller, $action)
```

```php
put($url, $controller, $action)
```

```php
patch($url, $controller, $action)
```

```php
delete($url, $controller, $action)
```

```php
any($url, $controller, $action)
```

To allow ordering and grouping of routes all of the above methods are chainable like this:

```php
/** @var \Ondigo\ExtbaseRouter\Routing\Router $router */
$router
    ->get('/api/resource', \Vendor\ExtensionName\Controller\ResourceController::class, 'list')
    ->post('/api/resource', \Vendor\ExtensionName\Controller\ResourceController::class, 'create')
    ->put('/api/resource/:resourceId', \Vendor\ExtensionName\Controller\ResourceController::class, 'update')
```

Specifying Parameters
==========

It's possible to specify named parameters in a route like this:

```php
$router->get('/api/resource/:resourceId', \Vendor\ExtensionName\Controller\ResourceController::class, 'get')
```

where `:resourceId` specifies the variable part of the URL.

Named parameters are recognized by a colon `:` in the beginning. 
In order to pass the parameter to the called action, the parameter names must match!

```php
/**
 * @param string $resourceId
 */
public function getAction($resourceId) {
    ...
}
```

Currently it's not possible to match parameters by specific patterns. Every named parameter is simply matched with `[a-zA-Z_\-0-9]+`.