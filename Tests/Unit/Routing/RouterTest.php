<?php
namespace Ondigo\ExtbaseRouter\Tests\Routing;

use Ondigo\ExtbaseRouter\Routing\Router;
use Ondigo\ExtbaseRouter\Tests\Fixture\FixtureController;
use TYPO3\CMS\Core\Tests\UnitTestCase;

class RouterTest extends UnitTestCase {

    public function testBasicRouteRegistration() {
        $routes = array(
            array('method' => 'GET', 'route' => '/test/resource', 'action' => 'list'),
            array('method' => 'POST', 'route' => '/test/resource', 'action' => 'post'),
            array('method' => 'PUT', 'route' => '/test/resource', 'action' => 'put'),
            array('method' => 'DELETE', 'route' => '/test/resource', 'action' => 'delete')
        );

        foreach ($routes as $route) {
            $registeredRoute = $this->registerRouteAndAssertBasics($route['method'], $route['route'], $route['action']);

            $this->assertPatternMatchesOptionalTrailingSlash($registeredRoute['pattern'], $route['route']);
            $this->assertNotRegExp($registeredRoute['pattern'], '/test/sub/resource/');
            $this->assertNotRegExp($registeredRoute['pattern'], '/sub/resource/');
        }
    }

    public function testNamedParametersGetTransformedToCorrectRegex() {
        $registeredRoute = $this->registerRouteAndAssertBasics('GET', '/test/resource/:resourceId', 'get');

        $this->assertPatternMatchesOptionalTrailingSlash($registeredRoute['pattern'], '/test/resource/1234');
        $this->assertNotRegExp($registeredRoute['pattern'], '/test/resource/1234/56');
        $this->assertNotRegExp($registeredRoute['pattern'], '/resource/1234/123');
    }

    public function testMultipleNamedParametersGetTransformedToCorrectRegex() {
        $registeredRoute = $this->registerRouteAndAssertBasics('GET', '/test/images/:size/:imageId', 'get');

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
     * creates a new router object, registers a route and asserts whether the given parameters have been processed correctly
     *
     * @param string $method the http method
     * @param string $route the URI
     * @param string $action the controller action
     *
     * @return array the registered route
     */
    protected function registerRouteAndAssertBasics($method, $route, $action) {
        $method = strtolower($method);

        $router = new Router();
        $router->$method($route, FixtureController::class, $action);

        $routes = $this->getObjectAttribute($router, 'routes');
        $this->assertCount(1, $routes);
        $this->assertArrayHasKey(0, $routes);

        $registeredRoute = $routes[0];

        $this->assertEquals(strtoupper($method), $registeredRoute['method']);
        $this->assertEquals(FixtureController::class, $registeredRoute['controller']);
        $this->assertEquals($action, $registeredRoute['action']);

        return $registeredRoute;
    }

}