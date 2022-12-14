<?php

namespace StyleShit\DIContainer\Tests;

use StyleShit\DIContainer\Container;
use StyleShit\DIContainer\Exceptions\AbstractNotFoundException;
use StyleShit\DIContainer\Exceptions\ConcreteNotFoundException;
use StyleShit\DIContainer\Exceptions\ConcreteNotInstantiableException;
use StyleShit\DIContainer\Exceptions\InterfaceNotBoundException;
use StyleShit\DIContainer\Exceptions\InvalidAbstractException;
use StyleShit\DIContainer\Exceptions\UnresolvableDependencyException;
use StyleShit\DIContainer\Tests\Mocks\A;
use StyleShit\DIContainer\Tests\Mocks\B;
use StyleShit\DIContainer\Tests\Mocks\C;
use StyleShit\DIContainer\Tests\Mocks\Contract;
use StyleShit\DIContainer\Tests\Mocks\ContractImplementation;
use StyleShit\DIContainer\Tests\Mocks\ContractImplementation2;
use StyleShit\DIContainer\Tests\Mocks\D;
use StyleShit\DIContainer\Tests\Mocks\MultipleTypedDependencies;
use StyleShit\DIContainer\Tests\Mocks\NeedsContract;
use StyleShit\DIContainer\Tests\Mocks\PrimitiveDependency;
use StyleShit\DIContainer\Tests\Mocks\UntypedDependency;
use StyleShit\DIContainer\Tests\Mocks\VariadicClass;
use StyleShit\DIContainer\Tests\Mocks\VariadicPrimitive;

afterEach(function () {
    Container::getInstance()->flush();
});

it('should throw when binding invalid abstract', function () {
    // Arrange.
    $container = Container::getInstance();

    // Act & Assert.
    expect(function () use ($container) {
        $container->bind(null);
    })->toThrow(InvalidAbstractException::class);
});

it('should throw when binding a non-existing concrete', function () {
    // Arrange.
    $container = Container::getInstance();

    // Act & Assert.
    expect(function () use ($container) {
        $container->bind('non-existing-concrete');
    })->toThrow(ConcreteNotFoundException::class);
});

it('should throw when binding a non-instantiable concrete', function () {
    // Arrange.
    $container = Container::getInstance();

    // Act & Assert.
    expect(function () use ($container) {
        $container->bind(Contract::class);
    })->toThrow(ConcreteNotInstantiableException::class);
});

it('should automatically create a default concrete resolver if not supplied', function () {
    // Arrange.
    $container = Container::getInstance();

    // Act.
    $container->bind(D::class);

    // Assert.
    expect($container->make(D::class))->toEqual(new D());
});

it('should bind an abstract to concrete using resolver function', function () {
    // Arrange.
    $container = Container::getInstance();

    // Act.
    $container->bind(D::class, function (Container $container, $args) {
        return $args;
    });

    // Assert.
    expect($container->make(D::class, ['test' => 123]))->toEqual(['test' => 123]);
});

it('should bind an abstract to concrete using class string', function () {
    // Arrange.
    $container = Container::getInstance();

    // Act.
    $container->bind(Contract::class, ContractImplementation::class);

    // Assert.
    expect($container->make(Contract::class, ['name' => 'test']))->toEqual(new ContractImplementation('test'));
});

it('should resolve concrete automatically if not bound', function () {
    // Arrange.
    $container = Container::getInstance();

    // Act & Assert.
    expect($container->make(D::class))->toEqual(new D());
});

it('should determine if an abstract is bound', function () {
    // Arrange.
    $container = Container::getInstance();

    $container->bind(D::class);

    // Act & Assert.
    expect($container->has(D::class))->toBeTrue();
    expect($container->has(Contract::class))->toBeFalse();
});

it('should throw when making unbound interface', function () {
    // Arrange.
    $container = Container::getInstance();

    // Act & Assert.
    expect(function () use ($container) {
        $container->make(Contract::class);
    })->toThrow(InterfaceNotBoundException::class);
});

it('should throw when making invalid abstract', function () {
    // Arrange.
    $container = Container::getInstance();

    // Act & Assert.
    expect(function () use ($container) {
        $container->make('non-existing-abstract');
    })->toThrow(AbstractNotFoundException::class);
});

it('should make concrete without a constructor', function () {
    // Arrange.
    $container = Container::getInstance();

    // Act.
    $std = $container->make(\stdClass::class);

    // Assert.
    expect($std)->toEqual(new \stdClass());
});

it('should make concrete with args', function () {
    // Arrange.
    $container = Container::getInstance();

    // Act.
    $d = $container->make(D::class, [
        'name' => 'test',
    ]);

    // Assert.
    expect($d)->toEqual(new D('test'));
    expect($d->name)->toEqual('test');
});

it('should make concrete with a nullish arg', function () {
    // Arrange.
    $container = Container::getInstance();

    // Act.
    $d = $container->make(D::class, [
        'name' => null,
    ]);

    // Assert.
    expect($d)->toEqual(new D(null));
    expect($d->name)->toEqual(null);
});

it('should make concrete with args when using the default resolver', function () {
    // Arrange.
    $container = Container::getInstance();
    $container->bind(D::class);

    // Act.
    $d = $container->make(D::class, [
        'name' => 'test',
    ]);

    // Assert.
    expect($d)->toEqual(new D('test'));
});

it('should throw for untyped dependency without default value', function () {
    // Arrange.
    $container = Container::getInstance();

    // Act & Assert.
    expect(function () use ($container) {
        $container->make(UntypedDependency::class);
    })->toThrow(UnresolvableDependencyException::class);
});

it('should throw for primitive dependency without default value', function () {
    // Arrange.
    $container = Container::getInstance();

    // Act & Assert.
    expect(function () use ($container) {
        $container->make(PrimitiveDependency::class);
    })->toThrow(UnresolvableDependencyException::class);
});

it('should auto-wire class dependencies', function () {
    // Arrange.
    $container = Container::getInstance();

    $container->bind(Contract::class, function () {
        return new ContractImplementation();
    });

    // Act.
    $a = $container->make(A::class, [
        'name' => 'test',
    ]);

    // Assert.
    $expectedA = new A(new B(new C(new ContractImplementation())), 'test');

    expect($a)->toEqual($expectedA);
});

it('should auto-wire multiple typed class dependencies', function () {
    // Arrange.
    $container = Container::getInstance();

    $container->bind(Contract::class, function () {
        return new ContractImplementation();
    });

    // Act.
    $result = $container->make(MultipleTypedDependencies::class, [
        'name' => 'test',
    ]);

    // Assert.
    $expected = new MultipleTypedDependencies(new ContractImplementation(), new D(), 'test');

    expect($result)->toEqual($expected);
});

it('should auto-wire variadic primitive dependencies', function () {
    // Arrange.
    $container = Container::getInstance();

    // Act.
    $variadicPrimitive = $container->make(VariadicPrimitive::class);

    // Assert.
    $expected = new VariadicPrimitive('mock');

    expect($variadicPrimitive)->toEqual($expected);
});

it('should auto-wire variadic class dependencies', function () {
    // Arrange.
    $container = Container::getInstance();

    // Act.
    $variadicClass = $container->make(VariadicClass::class);

    // Assert.
    $expected = new VariadicClass('mock', new D());

    expect($variadicClass)->toEqual($expected);
});

it('should bind an abstract to a concrete as singleton', function () {
    // Arrange.
    $container = Container::getInstance();

    // Act.
    $container->singleton(Contract::class, ContractImplementation::class);

    $contractImpl = $container->make(Contract::class, [
        'name' => 'test',
    ]);

    // Assert.
    expect($contractImpl)->toEqual(new ContractImplementation('test'));
});

it('should bind an abstract to a resolver as singleton', function () {
    // Arrange.
    $container = Container::getInstance();

    // Act.
    $container->singleton(Contract::class, function ($container, $args) {
        return new ContractImplementation($args['name']);
    });

    $contractImpl = $container->make(Contract::class, [
        'name' => 'test',
    ]);

    // Assert.
    expect($contractImpl)->toEqual(new ContractImplementation('test'));
});

it('should bind a concrete as singleton', function () {
    // Arrange.
    $container = Container::getInstance();

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
    $container = Container::getInstance();

    // Act.
    $container->singleton(D::class);

    // Assert.
    $singleton1 = $container->make(D::class);
    $singleton2 = $container->make(D::class);

    expect($singleton1)->toBe($singleton2);
});

it('should override existing singleton instance on re-bind', function () {
    // Arrange.
    $container = Container::getInstance();
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

it('should make dependencies contextually using class string', function () {
    // Arrange.
    $container = Container::getInstance();
    $container->bind(Contract::class, ContractImplementation::class);

    // Act.
    $container->when(C::class)
        ->needs(Contract::class)
        ->give(ContractImplementation2::class);

    // Assert.
    $expectedNeedsContract = new NeedsContract(new ContractImplementation());
    $expectedC = new C(new ContractImplementation2());

    expect($container->make(NeedsContract::class))->toEqual($expectedNeedsContract);
    expect($container->make(C::class))->toEqual($expectedC);
});

it('should make dependencies contextually using resolver function', function () {
    // Arrange.
    $container = Container::getInstance();
    $container->bind(Contract::class, ContractImplementation::class);

    // Act.
    $container->when(C::class)
        ->needs(Contract::class)
        ->give(function () {
            return new ContractImplementation2('test');
        });

    // Assert.
    $expectedNeedsContract = new NeedsContract(new ContractImplementation());
    $expectedC = new C(new ContractImplementation2('test'));

    expect($container->make(NeedsContract::class))->toEqual($expectedNeedsContract);
    expect($container->make(C::class))->toEqual($expectedC);
});

it('should make singleton dependencies contextually without using the existing singletons', function () {
    // Arrange.
    $container = Container::getInstance();
    $container->singleton(Contract::class, ContractImplementation::class);

    $initialSingleton = $container->make(Contract::class);

    // Act.
    $container->when(C::class)
        ->needs(Contract::class)
        ->give(ContractImplementation::class);

    // Assert.
    $c = $container->make(C::class);

    expect($c->contract)->not()->toBe($initialSingleton);
    expect($container->make(Contract::class))->toBe($initialSingleton);
});

it('should make dependencies contextually with multiple typed dependencies', function () {
    // Arrange.
    $container = Container::getInstance();

    // Act.
    $container->when(MultipleTypedDependencies::class)
        ->needs(D::class)
        ->give(function () {
            return new D('test');
        });

    $container->when(MultipleTypedDependencies::class)
        ->needs(Contract::class)
        ->give(ContractImplementation::class);

    // Assert.
    $expected = new MultipleTypedDependencies(new ContractImplementation(), new D('test'));

    expect($container->make(MultipleTypedDependencies::class))->toEqual($expected);
});

it('should forget a singleton instance', function () {
    // Arrange.
    $container = Container::getInstance();

    $container->singleton(D::class);

    $instanceD = $container->make(D::class);

    // Act.
    $container->forgetInstance(D::class);

    // Assert.
    expect($instanceD)->not()->toBe($container->make(D::class));
});

it('should forget all singleton instances', function () {
    // Arrange.
    $container = Container::getInstance();

    $container->singleton(ContractImplementation::class);
    $container->singleton(D::class);

    $instanceContract = $container->make(ContractImplementation::class);
    $instanceD = $container->make(D::class);

    // Act.
    $container->forgetInstances();

    // Assert.
    expect($instanceContract)->not()->toBe($container->make(ContractImplementation::class));
    expect($instanceD)->not()->toBe($container->make(D::class));
});

it('should flush the current bindings and instances', function () {
    // Arrange.
    $container = Container::getInstance();

    $container->bind(Contract::class, function ($container, $args) {
        return new ContractImplementation($args['name']);
    });

    $container->singleton(D::class, function () {
        return 'test';
    });

    $container->when(C::class)
        ->needs(Contract::class)
        ->give(ContractImplementation::class);

    // Act.
    $container->flush();

    // Assert.
    expect($container->make(D::class))->toEqual(new D());

    expect(function () use ($container) {
        $container->make(Contract::class);
    })->toThrow(InterfaceNotBoundException::class);

    expect(function () use ($container) {
        $container->make(C::class);
    })->toThrow(InterfaceNotBoundException::class);
});
