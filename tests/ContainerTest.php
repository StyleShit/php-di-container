<?php

use StyleShit\Container;
use Tests\Mocks\A;
use Tests\Mocks\B;
use Tests\Mocks\C;
use Tests\Mocks\Contract;
use Tests\Mocks\ContractImpl;
use Tests\Mocks\D;

require_once __DIR__.'/../src/Container.php';
require_once __DIR__.'/Mocks/MockClasses.php';

it('should throw when binding invalid abstract', function () {
    // Arrange.
    $container = new Container();

    // Act & Assert.
    expect(function () use ($container) {
        $container->bind(null);
    })->toThrow(\InvalidArgumentException::class, 'Abstract must be a string');
});

it('should automatically create a default concrete resolver if not supplied', function () {
    // Arrange.
    $container = new Container();

    // Act.
    $container->bind(D::class);

    // Assert.
    expect($container->make(D::class))->toEqual(new D());
});

it('should bind an abstract to concrete using resolver function', function () {
    // Arrange.
    $container = new Container();

    // Act.
    $container->bind(D::class, function (Container $container, $args) {
        return $args;
    });

    // Assert.
    expect($container->make(D::class, ['test' => 123]))->toEqual(['test' => 123]);
});

it('should bind an abstract to concrete using class string', function () {
    // Arrange.
    $container = new Container();

    // Act.
    $container->bind(Contract::class, ContractImpl::class);

    // Assert.
    expect($container->make(Contract::class, ['name' => 'test']))->toEqual(new ContractImpl('test'));
});

it('should resolve concrete automatically if not bound', function () {
    // Arrange.
    $container = new Container();

    // Act & Assert.
    expect($container->make(D::class))->toEqual(new D());
});

it('should determine if an abstract is bound', function () {
    // Arrange.
    $container = new Container();

    $container->bind(D::class);

    // Act & Assert.
    expect($container->has(D::class))->toBeTrue();
    expect($container->has(Contract::class))->toBeFalse();
});

it('should throw when making unbound interface', function () {
    // Arrange.
    $container = new Container();

    // Act & Assert.
    expect(function () use ($container) {
        $container->make(Contract::class);
    })->toThrow(\InvalidArgumentException::class, 'Interface `Tests\Mocks\Contract::class` is not bound to a concrete');
});

it('should throw when making invalid abstract', function () {
    // Arrange.
    $container = new Container();

    // Act & Assert.
    expect(function () use ($container) {
        $container->make('non-existing-abstract');
    })->toThrow(\InvalidArgumentException::class, 'Abstract `non-existing-abstract::class` not found');
});

it('should make concrete with args', function () {
    // Arrange.
    $container = new Container();

    // Act.
    $d = $container->make(D::class, [
        'name' => 'test',
    ]);

    // Assert.
    expect($d)->toEqual(new D('test'));
});

it('should make concrete with args when using the default resolver', function () {
    // Arrange.
    $container = new Container();
    $container->bind(D::class);

    // Act.
    $d = $container->make(D::class, [
        'name' => 'test',
    ]);

    // Assert.
    expect($d)->toEqual(new D('test'));
});

it('should auto-wire class dependencies', function () {
    // Arrange.
    $container = new Container();

    $container->bind(Contract::class, function () {
        return new ContractImpl();
    });

    // Act.
    $a = $container->make(A::class);

    // Assert.
    $expectedA = new A(new B(new C(new ContractImpl())));

    expect($a)->toEqual($expectedA);
});

it('should make singleton with args', function () {
    // Arrange.
    $container = new Container();

    // Act.
    $container->singleton(D::class);

    $d = $container->make(D::class, [
        'name' => 'test',
    ]);

    // Assert.
    expect($d)->toEqual(new D('test'));
});

it('should use the same instance for singleton', function () {
    // Arrange.
    $container = new Container();

    // Act.
    $container->singleton(D::class);

    // Assert.
    $singleton1 = $container->make(D::class);
    $singleton2 = $container->make(D::class);

    expect($singleton1)->toBe($singleton2);
});

it('should override existing singleton instance on re-bind', function () {
    // Arrange.
    $container = new Container();
    $container->singleton(D::class);

    // Init the original singleton.
    $container->make(D::class);

    // Act.
    $container->singleton(D::class, function () {
        return 'new-singleton';
    });

    // Assert.
    expect($container->make(D::class))->toBe('new-singleton');
});
