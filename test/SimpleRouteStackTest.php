<?php

declare(strict_types=1);

namespace LaminasTest\Router;

use ArrayIterator;
use Laminas\Router\Exception\InvalidArgumentException;
use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\Http\Chain;
use Laminas\Router\Http\Hostname;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Method;
use Laminas\Router\Http\Placeholder;
use Laminas\Router\Http\Regex;
use Laminas\Router\Http\Scheme;
use Laminas\Router\Http\Segment;
use Laminas\Router\RouteMatch;
use Laminas\Router\RoutePluginManager;
use Laminas\Router\SimpleRouteStack;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;

final class SimpleRouteStackTest extends TestCase
{
    public function testSetRoutePluginManager()
    {
        $routes = new RoutePluginManager(new ServiceManager());
        $stack  = new SimpleRouteStack();
        $stack->setRoutePluginManager($routes);

        $this->assertEquals($routes, $stack->getRoutePluginManager());
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAddRoutesWithInvalidArgument()
    {
        $stack = new SimpleRouteStack();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('addRoutes expects an array or Traversable set of routes');
        $stack->addRoutes('foo');
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAddRoutesAsArray()
    {
        $stack = new SimpleRouteStack();
        $stack->addRoutes([
            'foo' => new TestAsset\DummyRoute(),
        ]);

        $this->assertInstanceOf(RouteMatch::class, $stack->match(new Request()));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAddRoutesAsTraversable()
    {
        $stack = new SimpleRouteStack();
        $stack->addRoutes(new ArrayIterator([
            'foo' => new TestAsset\DummyRoute(),
        ]));

        $this->assertInstanceOf(RouteMatch::class, $stack->match(new Request()));
    }

    public function testSetRoutesWithInvalidArgument()
    {
        $stack = new SimpleRouteStack();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('addRoutes expects an array or Traversable set of routes');
        $stack->setRoutes('foo');
    }

    public function testSetRoutesAsArray()
    {
        $stack = new SimpleRouteStack();
        $stack->setRoutes([
            'foo' => new TestAsset\DummyRoute(),
        ]);

        $this->assertInstanceOf(RouteMatch::class, $stack->match(new Request()));

        $stack->setRoutes([]);

        $this->assertNull($stack->match(new Request()));
    }

    public function testSetRoutesAsTraversable()
    {
        $stack = new SimpleRouteStack();
        $stack->setRoutes(new ArrayIterator([
            'foo' => new TestAsset\DummyRoute(),
        ]));

        $this->assertInstanceOf(RouteMatch::class, $stack->match(new Request()));

        $stack->setRoutes(new ArrayIterator([]));

        $this->assertNull($stack->match(new Request()));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testremoveRouteAsArray()
    {
        $stack = new SimpleRouteStack();
        $stack->addRoutes([
            'foo' => new TestAsset\DummyRoute(),
        ]);

        $this->assertEquals($stack, $stack->removeRoute('foo'));
        $this->assertNull($stack->match(new Request()));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    /**
     * @throws ContainerExceptionInterface
     */
    public function testAddRouteAsArrayWithoutOptions()
    {
        $stack = new SimpleRouteStack();
        $stack->addRoute('foo', [
            'type' => TestAsset\DummyRoute::class,
        ]);

        $this->assertInstanceOf(RouteMatch::class, $stack->match(new Request()));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAddRouteAsArrayWithOptions()
    {
        $stack = new SimpleRouteStack();
        $stack->addRoute('foo', [
            'type'    => TestAsset\DummyRoute::class,
            'options' => [],
        ]);

        $this->assertInstanceOf(RouteMatch::class, $stack->match(new Request()));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAddRouteAsArrayWithoutType()
    {
        $stack = new SimpleRouteStack();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "type" option');
        $stack->addRoute('foo', []);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAddRouteAsArrayWithPriority()
    {
        $stack = new SimpleRouteStack();

        $stack->addRoute('foo', [
            'type'     => TestAsset\DummyRouteWithParam::class,
            'priority' => 2,
        ])->addRoute('bar', [
            'type'     => TestAsset\DummyRoute::class,
            'priority' => 1,
        ]);

        $this->assertEquals('bar', $stack->match(new Request())->getParam('foo'));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAddRouteWithPriority()
    {
        $stack = new SimpleRouteStack();

        $route = new TestAsset\DummyRouteWithParam();
        $route->setPriority(2);
        $stack->addRoute('baz', $route);

        $stack->addRoute('foo', [
            'type'     => TestAsset\DummyRoute::class,
            'priority' => 1,
        ]);

        $this->assertEquals('bar', $stack->match(new Request())->getParam('foo'));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAddRouteAsTraversable()
    {
        $stack = new SimpleRouteStack();
        $stack->addRoute('foo', new ArrayIterator([
            'type' => TestAsset\DummyRoute::class,
        ]));

        $this->assertInstanceOf(RouteMatch::class, $stack->match(new Request()));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAssemble()
    {
        $stack = new SimpleRouteStack();
        $stack->addRoute('foo', new TestAsset\DummyRoute());
        $this->assertEquals('', $stack->assemble([], ['name' => 'foo']));
    }

    public function testAssembleWithoutNameOption()
    {
        $stack = new SimpleRouteStack();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "name" option');
        $stack->assemble();
    }

    public function testAssembleNonExistentRoute()
    {
        $stack = new SimpleRouteStack();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route with name "foo" not found');
        $stack->assemble([], ['name' => 'foo']);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testDefaultParamIsAddedToMatch()
    {
        $stack = new SimpleRouteStack();
        $stack->addRoute('foo', new TestAsset\DummyRoute());
        $stack->setDefaultParam('foo', 'bar');

        $this->assertEquals('bar', $stack->match(new Request())->getParam('foo'));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testDefaultParamDoesNotOverrideParam()
    {
        $stack = new SimpleRouteStack();
        $stack->addRoute('foo', new TestAsset\DummyRouteWithParam());
        $stack->setDefaultParam('foo', 'baz');

        $this->assertEquals('bar', $stack->match(new Request())->getParam('foo'));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testDefaultParamIsUsedForAssembling()
    {
        $stack = new SimpleRouteStack();
        $stack->addRoute('foo', new TestAsset\DummyRouteWithParam());
        $stack->setDefaultParam('foo', 'bar');

        $this->assertEquals('bar', $stack->assemble([], ['name' => 'foo']));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testDefaultParamDoesNotOverrideParamForAssembling()
    {
        $stack = new SimpleRouteStack();
        $stack->addRoute('foo', new TestAsset\DummyRouteWithParam());
        $stack->setDefaultParam('foo', 'baz');

        $this->assertEquals('bar', $stack->assemble(['foo' => 'bar'], ['name' => 'foo']));
    }

    public function testFactory()
    {
        $tester = new FactoryTester($this);
        $tester->testFactory(
            SimpleRouteStack::class,
            [],
            [
                'route_plugins'  => new RoutePluginManager(new ServiceManager()),
                'routes'         => [],
                'default_params' => [],
            ]
        );
    }

    public function testGetRoutes()
    {
        $stack = new SimpleRouteStack();
        $this->assertInstanceOf('Traversable', $stack->getRoutes());
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testGetRouteByName()
    {
        $stack = new SimpleRouteStack();
        $route = new TestAsset\DummyRoute();
        $stack->addRoute('foo', $route);

        $this->assertEquals($route, $stack->getRoute('foo'));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testHasRoute()
    {
        $stack = new SimpleRouteStack();
        $this->assertFalse($stack->hasRoute('foo'));

        $stack->addRoute('foo', new TestAsset\DummyRoute());
        $this->assertTrue($stack->hasRoute('foo'));
    }

    /** @return array<class-string, array{0: array, 1: int}> */
    public static function routeTypeProvider(): array
    {
        $routePlugins = new RoutePluginManager(new ServiceManager());
        return [
            Chain::class       => [
                [
                    'type'     => Chain::class,
                    'priority' => 1,
                    'options'  => [
                        'routes'        => [],
                        'route_plugins' => $routePlugins,
                    ],
                ],
                1,
            ],
            Hostname::class    => [
                [
                    'type'     => Hostname::class,
                    'options'  => [
                        'route'    => 'www.example.com',
                        'defaults' => [
                            'controller' => 'SomeController',
                            'action'     => 'index',
                        ],
                    ],
                    'priority' => 5,
                ],
                5,
            ],
            Literal::class     => [
                [
                    'type'     => Literal::class,
                    'options'  => [
                        'route'    => '/blah',
                        'defaults' => [
                            'controller' => 'SomeController',
                            'action'     => 'index',
                        ],
                    ],
                    'priority' => 10,
                ],
                10,
            ],
            Method::class      => [
                [
                    'type'     => Method::class,
                    'options'  => [
                        'route' => '/duck',
                        'verb'  => 'QUACK',
                    ],
                    'priority' => 20,
                ],
                20,
            ],
            Placeholder::class => [
                [
                    'type'     => Placeholder::class,
                    'options'  => [],
                    'priority' => 30,
                ],
                30,
            ],
            Regex::class       => [
                [
                    'type'     => Regex::class,
                    'options'  => [
                        'regex' => '/(?<foo>[^/]+)',
                        'spec'  => '/%foo%',
                    ],
                    'priority' => 40,
                ],
                40,
            ],
            Scheme::class      => [
                [
                    'type'     => Scheme::class,
                    'options'  => [
                        'scheme' => 'carrots',
                    ],
                    'priority' => 50,
                ],
                50,
            ],
            Segment::class     => [
                [
                    'type'     => Segment::class,
                    'options'  => [
                        'route' => '/mushrooms',
                    ],
                    'priority' => 60,
                ],
                60,
            ],
        ];
    }

    /**
     * @throws ContainerExceptionInterface
     */
    #[DataProvider('routeTypeProvider')]
    public function testSimpleRouteStackSetsPriorityForAllKnownRouteTypes(array $routeSpec, int $expectedPriority): void
    {
        $router = new SimpleRouteStack();
        $router->addRoute('name', $routeSpec);

        $route = $router->getRoute('name');
        self::assertNotNull($route);
        self::assertEquals($expectedPriority, $route->getPriority());
    }
}
