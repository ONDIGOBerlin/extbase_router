<?php
namespace Ondigo\ExtbaseRouter\Tests\Routing;

use Ondigo\ExtbaseRouter\Routing\Router;
use Ondigo\ExtbaseRouter\Tests\Fixture\FixtureController;
use TYPO3\CMS\Core\Tests\UnitTestCase;

class RouterTest extends UnitTestCase {

    public function testBasicRouteRegistration() {
        $routes = [
            ['routerMethod' => 'get', 'httpMethods' => ['GET'], 'route' => '/test/resource', 'action' => 'list'],
            ['routerMethod' => 'post', 'httpMethods' => ['POST'], 'route' => '/test/resource', 'action' => 'post'],
            ['routerMethod' => 'put', 'httpMethods' => ['PUT'], 'route' => '/test/resource', 'action' => 'put'],
            ['routerMethod' => 'patch', 'httpMethods' => ['PATCH'], 'route' => '/test/resource', 'action' => 'patch'],
            ['routerMethod' => 'delete', 'httpMethods' => ['DELETE'], 'route' => '/test/resource', 'action' => 'delete'],
            ['routerMethod' => 'any', 'httpMethods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], 'route' => '/test/resource', 'action' => 'any']
        ];

        foreach ($routes as $route) {
            $registeredRoute = $this->registerRouteAndAssertBasics($route['routerMethod'], $route['route'], $route['action'], $route['httpMethods']);

            $this->assertPatternMatchesOptionalTrailingSlash($registeredRoute['pattern'], $route['route']);
            $this->assertNotRegExp($registeredRoute['pattern'], '/test/sub/resource/');
            $this->assertNotRegExp($registeredRoute['pattern'], '/sub/resource/');
        }
    }

    public function testNamedParametersGetTransformedToCorrectRegex() {
        $registeredRoute = $this->registerRouteAndAssertBasics('GET', '/test/resource/:resourceId', 'get', ['GET']);

        $this->assertPatternMatchesOptionalTrailingSlash($registeredRoute['pattern'], '/test/resource/1234');
        $this->assertNotRegExp($registeredRoute['pattern'], '/test/resource/1234/56');
        $this->assertNotRegExp($registeredRoute['pattern'], '/resource/1234/123');
    }

    public function testMultipleNamedParametersGetTransformedToCorrectRegex() {
        $registeredRoute = $this->registerRouteAndAssertBasics('GET', '/test/images/:size/:imageId', 'get', ['GET']);

        $this->assertPatternMatchesOptionalTrailingSlash($registeredRoute['pattern'], '/test/images/200x200/1234');
        $this->assertNotRegExp($registeredRoute['pattern'], '/test/images/200x200');
        $this->assertNotRegExp($registeredRoute['pattern'], '/test/images/200x200/1234/56');
    }

    /**
     * asserts whether a given pattern matches uris with and without trailing slash
     *
     * @param $pattern
     * @param $test
     */
    protected function assertPatternMatchesOptionalTrailingSlash($pattern, $test) {
        $this->assertRegExp($pattern, rtrim($test, '/'));
        $this->assertRegExp($pattern, rtrim($test, '/') . '/');
    }

    /**
     * creates a new router object, registers a route and asserts whether the given
     * parameters have been processed correctly before returning the registered route
     *
     * @param string $routerMethod the method to be called on the router
     * @param string $route the URI
     * @param string $action the controller action
     * @param array $httpMethods
     *
     * @return array the registered route
     */
    protected function registerRouteAndAssertBasics($routerMethod, $route, $action, $httpMethods) {
        $router = new Router();
        $router->$routerMethod($route, FixtureController::class, $action);

        $routes = $this->getObjectAttribute($router, 'routes');
        $this->assertCount(1, $routes);
        $this->assertArrayHasKey(0, $routes);

        $registeredRoute = $routes[0];

        $this->assertArraySubset($httpMethods, $registeredRoute['methods']);
        $this->assertEquals(FixtureController::class, $registeredRoute['controller']);
        $this->assertEquals($action, $registeredRoute['action']);

        return $registeredRoute;
    }

}