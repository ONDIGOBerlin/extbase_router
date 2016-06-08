> This Extension allows the registration of endpoints without requiring any pages or plugins in the TYPO3 Backend. 

To accomplish this, the Extension hooks into the TYPO3 bootstrap process - at a point where the FrontendUser is initialized, but before any attempt to resolve an actual page is made. The requested URL is then matched against all registered routes and in case a match is found the request gets intercepted and rerouted to the specified controller action.

**A page that would normally be accessed by `/example` can never be accessed if a route matching `/example` was registered! Routes are not automatically scoped or namespaced and you should therefore carefully consider the implications your routes may have!**

Usage
==========

In order to have routes registered before any matching takes place, route configuration should happen in the ext_localconf.php of your extension.

```php
/** 
 * @var \Ondigo\ExtbaseRouter\Routing\Router $router 
 */
$router = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Ondigo\ExtbaseRouter\Routing\Router::class);
    
$router->get('/api/resource', \Vendor\Extension\Controller\ResourceController::class, 'list');
```

All requests matching `/api/resource` with HTTP Method GET are then routed directly to `ResourceController->listAction`

*At the moment your controller is required to set the appropriate headers and output all data. 
This Router does nothing except for eliminating the overhead caused by TYPO3 when rendering a single plugin where the normal page layout isn't needed.*

Configuration
=========

The only configuration necessary is if you want to provide multiple Languages for API endpoints.
These settings are done system wide and can not be set by another extension.

To configure the supported languages, use the TYPO3 Extension Manager and set the appropriate values for the Extbase Router extension:

###Accept-Language Header

default: `X-Accept-Language`

*With this option you can specify which Header field will be used to request a particular language. When making a Request, the value of this field should be one of the languageKeys defined in the **Supported Languages** field*

###Supported Languages

*This should be a comma separated list of languageKey=LanguageUid mappings. For example "de=0,en=2"*


API
=========

```php
get($url, $controller, $action, $parameterSettings)
```

```php
post($url, $controller, $action, $parameterSettings)
```

```php
put($url, $controller, $action, $parameterSettings)
```

```php
patch($url, $controller, $action, $parameterSettings)
```

```php
delete($url, $controller, $action, $parameterSettings)
```

```php
any($url, $controller, $action, $parameterSettings)
```

all of the above methods are chainable to allow easy ordering and grouping of routes.


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
 * @param int $resourceId
 */
public function getAction($resourceId) {
    ...
}
```

Passing `$parameterSettings` when registering a new route allows different patterns to be specified for matching. By default every named parameter is matched against `[a-zA-Z_\-0-9]+`.

```php
$router->get('/api/resource/:resourceId', $controller, $action, [
	'resourceId' => Router::PATTERN_INTEGER
]);
```

Patterns to match Integer and UUID values are available as constants, 
other patterns can be passed as any regular expression capturing group. `([0-9]+x[0-9]+)` for example can be used to match values like `200x200`.