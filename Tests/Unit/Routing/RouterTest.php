<?php
namespace Ondigo\ExtbaseRouter\Tests\Routing;

use Ondigo\ExtbaseRouter\Routing\Router;
use Ondigo\ExtbaseRouter\Tests\Fixture\FixtureController;
use TYPO3\CMS\Core\Tests\UnitTestCase;

class RouterTest extends UnitTestCase {

    protected $httpMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * @var \Ondigo\ExtbaseRouter\Routing\Router
     */
    protected $router;

    public function setUp() {
        $this->router = new Router();
    }

    /**
     * test route registration via the ->get() method
     */
    public function testGetRoute() {
        $url = '/api/resource';
        $action = 'list';
        $controller = FixtureController::class;
        $method = 'GET';

        $this->router->get($url, $controller, $action);
        $this->assertRouteRegistration($url, $method, $controller, $action);
        $this->assertRouteDoesNotMatch($url, $this->filterHttpMethods($method));
    }

    /**
     * test route registration via the ->post() method
     */
    public function testPostRoute() {
        $url = '/api/resource';
        $action = 'create';
        $controller = FixtureController::class;
        $method = 'POST';

        $this->router->post($url, $controller, $action);
        $this->assertRouteRegistration($url, $method, $controller, $action);
        $this->assertRouteDoesNotMatch($url, $this->filterHttpMethods($method));
    }

    /**
     * test route registration via the ->put() method
     */
    public function testPutRoute() {
        $url = '/api/resource';
        $action = 'update';
        $controller = FixtureController::class;
        $method = 'PUT';

        $this->router->put($url, $controller, $action);
        $this->assertRouteRegistration($url, $method, $controller, $action);
        $this->assertRouteDoesNotMatch($url, $this->filterHttpMethods($method));
    }

    /**
     * test route registration via the ->patch() method
     */
    public function testPatchRoute() {
        $url = '/api/resource';
        $action = 'update';
        $controller = FixtureController::class;
        $method = 'PATCH';

        $this->router->patch($url, $controller, $action);
        $this->assertRouteRegistration($url, $method, $controller, $action);
        $this->assertRouteDoesNotMatch($url, $this->filterHttpMethods($method));
    }

    /**
     * test route registration via the ->delete() method
     */
    public function testDeleteRoute() {
        $url = '/api/resource';
        $action = 'remove';
        $controller = FixtureController::class;
        $method = 'DELETE';

        $this->router->delete($url, $controller, $action);
        $this->assertRouteRegistration($url, $method, $controller, $action);
        $this->assertRouteDoesNotMatch($url, $this->filterHttpMethods($method));
    }

    /**
     * test route registration via the ->any() method
     */
    public function testAnyRoute() {
        $url = '/api/resource';
        $action = 'any';
        $controller = FixtureController::class;

        $this->router->any($url, FixtureController::class, $action);

        foreach ($this->httpMethods as $method) {
            $this->assertRouteRegistration($url, $method, $controller, $action);
        }
    }

    /**
     * test matching a parameter with the UUID pattern
     */
    public function testNamedParameterWithUuidMatching() {
        $action = 'get';
        $controller = FixtureController::class;
        $method = 'GET';

        $this->router->get('/api/resource/:uuid', $controller, $action, [
            'uuid' => Router::PATTERN_UUID
        ]);

        $this->assertRouteRegistration('/api/resource/a86b0e59-23f9-4ae2-a752-be11aa165dbc', $method, $controller, $action);
        $this->assertRouteDoesNotMatch('/api/resource/abcdef', [$method]);
        $this->assertRouteDoesNotMatch('/api/resource/a86b0e59-a23f9-4ae2-a752-b1aa165dbc', [$method]);
    }

    /**
     * test matching a parameter with the INTEGER pattern
     */
    public function testNamedParameterWithIntegerMatching() {
        $action = 'get';
        $controller = FixtureController::class;
        $method = 'GET';

        $this->router->get('/api/resource/:id', $controller, $action, [
            'id' => Router::PATTERN_INTEGER
        ]);

        $this->assertRouteRegistration('/api/resource/1234', $method, $controller, $action);
        $this->assertRouteDoesNotMatch('/api/resource/abcdef', [$method]);
    }

    /**
     * test matching a parameter with a custom regex
     */
    public function testNamedParametersWithCustomMatching() {
        $action = 'get';
        $controller = FixtureController::class;
        $method = 'GET';

        $this->router->get('/api/images/:size/:imageId', $controller, $action, [
            'size' => '[0-9]+x[0-9]+'
        ]);

        $this->assertRouteRegistration('/api/images/200x200/1234', $method, $controller, $action);
        $this->assertRouteDoesNotMatch('/api/images/200x/1234', [$method]);
        $this->assertRouteDoesNotMatch('/api/images/200', [$method]);
    }

    /**
     * helper function to easily create a list of HTTP Methods excluding the one given
     *
     * @param $method
     *
     * @return mixed
     */
    protected function filterHttpMethods($method) {
        return array_filter($this->httpMethods, function($m) use ($method) {
            return $m !== $method;
        });
    }

    /**
     * matches a given url and verifies the routes configuration
     *
     * @param $url
     * @param $method
     * @param $controller
     * @param $action
     */
    protected function assertRouteRegistration($url, $method, $controller, $action) {
        $match = $this->router->match($url, $method);

        $this->assertPatternMatchesOptionalTrailingSlash($match['pattern'], $url);

        $this->assertNumberRoutesRegistered(1);
        $this->assertArraySubset([
            'controller' => $controller,
            'action' => $action
        ], $match);
        $this->assertRouteMatches($url, [$method]);
    }

    /**
     * tests whether a given pattern matches urls with and without trailing slash
     *
     * @param $pattern
     * @param $test
     */
    protected function assertPatternMatchesOptionalTrailingSlash($pattern, $test) {
        $this->assertRegExp($pattern, rtrim($test, '/'));
        $this->assertRegExp($pattern, rtrim($test, '/') . '/');
    }

    /**
     * run right after setUp but before any test this is to make sure that the test will run with a fresh router instance
     */
    protected function assertPreConditions() {
        parent::assertPreConditions();
        $routes = $this->getObjectAttribute($this->router, 'routes');
        $this->assertCount(0, $routes);
    }

    /**
     * assert whether the number of routes registered on the current instance matches the given $count
     *
     * @param $count
     */
    protected function assertNumberRoutesRegistered($count) {
        $routes = $this->getObjectAttribute($this->router, 'routes');
        $this->assertCount($count, $routes);
    }

    /**
     * assert whether a url does not have any matches for the given $methods
     *
     * @param $url
     * @param $methods
     */
    protected function assertRouteDoesNotMatch($url, $methods) {
        foreach ($methods as $method) {
            $this->assertFalse($this->router->match($url, $method), "Route matched $method even though it shouldn't have");
        }
    }

    /**
     * assert whether a url has a match for every one of the given $methods
     *
     * @param $url
     * @param $methods
     */
    protected function assertRouteMatches($url, $methods) {
        foreach ($methods as $method) {
            $this->assertNotFalse($this->router->match($url, $method), "Route didn't match $method even though it should've");
        }
    }
}