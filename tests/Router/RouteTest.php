<?php
namespace Wandu\Router;

use Closure;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Wandu\Http\Psr\Stream\StringStream;
use Wandu\Router\Contracts\MiddlewareInterface;
use Wandu\Router\Responsifier\WanduResponsifier;
use function Wandu\Http\response;

class RouteTest extends TestCase
{
    public function testExecuteWithoutMiddlewares()
    {
        $route = new Route(TestRouteController::class, 'index');

        $request = $this->createRequest('GET', '/');

        ob_start();
        $response = $route->execute($request, null, new WanduResponsifier());
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertEquals('call!', $contents);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('[GET] index@TestRouteController', $response->getBody()->__toString());
    }

    public function testExecuteWithMiddleware()
    {
        $route = new Route(TestRouteController::class, 'index', [
            TestAuthSuccessMiddleware::class
        ]);

        $request = $this->createRequest('GET', '/');

        ob_start();
        $response = $route->execute($request, null, new WanduResponsifier());
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertEquals('call!', $contents);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('[GET] auth success; [GET] index@TestRouteController', $response->getBody()->__toString());
    }

    public function testExecuteWithPreventedMiddleware()
    {
        
        
        $route = new Route(TestRouteController::class, 'index', [
            TestAuthFailMiddleware::class
        ]);

        $request = $this->createRequest('GET', '/');

        ob_start();
        $response = $route->execute($request, null, new WanduResponsifier());
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertEquals('', $contents);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('Fail...', $response->getBody()->__toString());
    }
}

class TestRouteController
{
    public function index(ServerRequestInterface $request)
    {
        echo "call!";
        return "[{$request->getMethod()}] index@TestRouteController";
    }
}

class TestAuthSuccessMiddleware implements MiddlewareInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(ServerRequestInterface $request, Closure $next)
    {
        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = $next($request);
        $message = "[{$request->getMethod()}] auth success; " . $response->getBody()->__toString();

        return $response->withBody(new StringStream($message));
    }
}

class TestAuthFailMiddleware implements MiddlewareInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(ServerRequestInterface $request, Closure $next)
    {
        return response()->create("Fail...");
    }
}
