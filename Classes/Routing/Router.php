<?php
namespace Ondigo\ExtbaseRouter\Routing;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Router implements SingletonInterface {

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $routes = [];

    public function __construct() {
        $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
    }

    /**
     * register a new Route for HTTP Method GET
     *
     * @param $url
     * @param $controller
     * @param $action
     * @return $this
     */
    public function get($url, $controller, $action) {
        $this->register($url, 'GET', $controller, $action);

        return $this;
    }

    /**
     * register a new Route for HTTP Method POST
     *
     * @param $url
     * @param $controller
     * @param $action
     * @return $this
     */
    public function post($url, $controller, $action) {
        $this->register($url, 'POST', $controller, $action);

        return $this;
    }

    /**
     * register a new Route for HTTP Method PUT
     *
     * @param $url
     * @param $controller
     * @param $action
     * @return $this
     */
    public function put($url, $controller, $action) {
        $this->register($url, 'PUT', $controller, $action);

        return $this;
    }

    /**
     * register a new Route for HTTP Method DELETE
     *
     * @param $url
     * @param $controller
     * @param $action
     * @return $this
     */
    public function delete($url, $controller, $action) {
        $this->register($url, 'DELETE', $controller, $action);

        return $this;
    }

    /**
     * register a new Route
     *
     * @param $url
     * @param $method
     * @param $controller
     * @param $action
     * @return $this
     */
    public function register($url, $method, $controller, $action) {
        list($vendorName, $extensionName, , $controllerName) = explode('\\', $controller);

        $this->routes[] = [
            'pattern' => $this->transform($url),
            'method' => $method,
            'handler' => [
                'vendorName' => $vendorName,
                'extensionName' => GeneralUtility::camelCaseToLowerCaseUnderscored($extensionName),
                'controllerName' => substr($controllerName, 0, -10), // remove the 'Controller' from the end of the class name
                'action' => $action
            ]
        ];

        return $this;
    }

    public function match($requestUri, $method) {
        foreach ($this->routes as $route) {
            $pattern = $route['pattern'];

            preg_match($pattern, $requestUri, $parameters);
            if (!empty($parameters) && strtoupper($route['method']) === strtoupper($method)) {
                foreach($parameters as $k => $v) {
                    if (is_int($k)) {
                        unset($parameters[$k]);
                    }
                }

                $route += ['parameters' => $parameters];
                return $route;
            }
        }

        return FALSE;
    }

    public function route($route) {
        $handler = $route['handler'];
        $arguments = $route['parameters'];

        /** @var \TYPO3\CMS\Extbase\Mvc\Web\Request $request */
        $request = $this->objectManager->get('TYPO3\CMS\Extbase\Mvc\Web\Request');

        /** @var \TYPO3\CMS\Extbase\Mvc\Web\Response $response */
        $response = $this->objectManager->get('TYPO3\CMS\Extbase\Mvc\Web\Response');

        /** @var \TYPO3\CMS\Extbase\Mvc\Dispatcher $dispatcher */
        $dispatcher = $this->objectManager->get('TYPO3\CMS\Extbase\Mvc\Dispatcher');

        $request->setControllerVendorName($handler['vendorName']);
        $request->setControllerExtensionName($handler['extensionName']);
        $request->setControllerName($handler['controllerName']);
        $request->setControllerActionName($handler['action']);

        $request->setArguments($arguments);

        $dispatcher->dispatch($request, $response);
        $content = $response->getContent();

        return $content;
    }

    /**
     * transforms a URL pattern containing named parameters into a pattern usable with preg_match
     *
     * @param string $url
     * @return string
     */
    protected function transform($url) {
        $url = rtrim($url, '/');

        // forward slashes have to be escaped for the regex to work, preg_quote however escapes our inner regex as well
        $pattern = str_replace('/', '\/', $url);
        $pattern = preg_replace_callback('/(:\w+)/i', function($matches) {
            $parameterName = ltrim($matches[0],':');
            return '(?P<'.$parameterName.'>\w+)';
        }, $pattern);

        // add optional trailing slash matching
        $pattern = $pattern . '\/?';

        // add regex delimiters
        $pattern = '/^' . $pattern . '/';

        return $pattern;
    }

}