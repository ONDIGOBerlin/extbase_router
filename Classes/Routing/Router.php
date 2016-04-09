<?php
namespace Ondigo\ExtbaseRouter\Routing;

use TYPO3\CMS\Core\SingletonInterface;

class Router implements SingletonInterface {

    const PATTERN_INTEGER = '[0-9]+';

    const PATTERN_ALPHANUMERIC = '[a-zA-Z_\-0-9]+';

    const PATTERN_UUID = '[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}';

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
     * @param string $url
     * @param string $controller
     * @param string $action
     * @param array $parameterSettings
     *
     * @return Router $this
     */
    public function get($url, $controller, $action, $parameterSettings = []) {
        $this->register($url, ['GET'], $controller, $action, $parameterSettings);

        return $this;
    }

    /**
     * register a new Route for HTTP Method POST
     *
     * @param string $url
     * @param string $controller
     * @param string $action
     * @param array $parameterSettings
     *
     * @return Router $this
     */
    public function post($url, $controller, $action, $parameterSettings = []) {
        $this->register($url, ['POST'], $controller, $action, $parameterSettings);

        return $this;
    }

    /**
     * register a new Route for HTTP Method PUT
     *
     * @param string $url
     * @param string $controller
     * @param string $action
     * @param array $parameterSettings
     *
     * @return Router $this
     */
    public function put($url, $controller, $action, $parameterSettings = []) {
        $this->register($url, ['PUT'], $controller, $action, $parameterSettings);

        return $this;
    }

    /**
     * register a new Route for HTTP Method PATCH
     *
     * @param string $url
     * @param string $controller
     * @param string $action
     * @param array $parameterSettings
     *
     * @return Router $this
     */
    public function patch($url, $controller, $action, $parameterSettings = []) {
        $this->register($url, ['PATCH'], $controller, $action, $parameterSettings);

        return $this;
    }

    /**
     * register a new Route for HTTP Method DELETE
     *
     * @param string $url
     * @param string $controller
     * @param string $action
     * @param array $parameterSettings
     *
     * @return Router $this
     */
    public function delete($url, $controller, $action, $parameterSettings = []) {
        $this->register($url, ['DELETE'], $controller, $action, $parameterSettings);

        return $this;
    }

    /**
     * register a new Route for HTTP Methods GET, POST, PUT, PATCH and DELETE
     *
     * @param string $url
     * @param string $controller
     * @param string $action
     * @param array $parameterSettings
     *
     * @return Router $this
     */
    public function any($url, $controller, $action, $parameterSettings = []) {
        $this->register($url, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], $controller, $action, $parameterSettings);

        return $this;
    }

    /**
     * register a new Route
     *
     * @param string $url
     * @param array $methods
     * @param string $controller
     * @param string $action
     * @param array $parameterSettings
     *
     * @return Router $this
     */
    protected function register($url, $methods, $controller, $action, $parameterSettings) {
        $this->routes[] = [
            'pattern' => $this->transform($url, $parameterSettings),
            'methods' => $methods,
            'controller' => $controller,
            'action' => $action
        ];

        return $this;
    }

    /**
     * matches the given $requestUri against all registered routes and returns either the first match or FALSE if no match was found
     *
     * @param $requestUri
     * @param $method
     *
     * @return array|bool
     */
    public function match($requestUri, $method) {
        foreach ($this->routes as $route) {
            $pattern = $route['pattern'];

            $matched = (bool)preg_match($pattern, $requestUri, $parameters);
            if ($matched && in_array(strtoupper($method), $route['methods'])) {
                foreach ($parameters as $k => $v) {
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

    /**
     * dispatches the given route
     *
     * @param $route
     *
     * @return string
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InfiniteLoopException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException
     */
    public function route($route) {
        $arguments = $route['parameters'];

        /** @var \TYPO3\CMS\Extbase\Mvc\Web\Request $request */
        $request = $this->objectManager->get('TYPO3\CMS\Extbase\Mvc\Web\Request');

        /** @var \TYPO3\CMS\Extbase\Mvc\Web\Response $response */
        $response = $this->objectManager->get('TYPO3\CMS\Extbase\Mvc\Web\Response');

        /** @var \TYPO3\CMS\Extbase\Mvc\Dispatcher $dispatcher */
        $dispatcher = $this->objectManager->get('TYPO3\CMS\Extbase\Mvc\Dispatcher');

        $request->setControllerObjectName($route['controller']);
        $request->setControllerActionName($route['action']);
        $request->setArguments($arguments);

        $dispatcher->dispatch($request, $response);
    }

    /**
     * transforms a URL pattern containing named parameters into a pattern usable with preg_match
     *
     * @param string $url
     * @param $parameterSettings
     *
     * @return string
     */
    protected function transform($url, $parameterSettings) {
        $url = rtrim($url, '/');

        // forward slashes have to be escaped for the regex to work, preg_quote however escapes our inner regex as well
        $pattern = str_replace('/', '\/', $url);
        $pattern = preg_replace_callback('/(:\w+)/i', function ($matches) use ($parameterSettings) {
            $parameterName = ltrim($matches[0], ':');
            $pattern = isset($parameterSettings[$parameterName]) ? $parameterSettings[$parameterName] : self::PATTERN_ALPHANUMERIC;

            return '(?P<' . $parameterName . '>' . $pattern . ')';
        }, $pattern);

        // add optional trailing slash matching
        $pattern = $pattern . '\/?$';

        // add regex delimiters
        $pattern = '/^' . $pattern . '/';

        return $pattern;
    }

}