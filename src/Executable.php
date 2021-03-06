<?php

namespace Amp\Injector;

final class Executable
{
    private \ReflectionFunctionAbstract $callable;
    private ?object $invocationObject;
    private bool $isInstanceMethod;

    public function __construct(\ReflectionFunctionAbstract $callable, ?object $invocationObject = null)
    {
        if ($callable instanceof \ReflectionMethod) {
            $this->isInstanceMethod = true;
            $this->setMethodCallable($callable, $invocationObject);
        } else {
            $this->isInstanceMethod = false;
            $this->callable = $callable;
        }
    }

    public function __invoke(...$args)
    {
        if ($this->isInstanceMethod) {
            return $this->callable->invokeArgs($this->invocationObject, $args);
        }

        return $this->callable->isClosure()
            ? $this->invokeClosureCompat($args)
            : $this->callable->invokeArgs($args);
    }

    public function getCallable()
    {
        return $this->callable;
    }

    public function getInvocationObject()
    {
        return $this->invocationObject;
    }

    public function isInstanceMethod()
    {
        return $this->isInstanceMethod;
    }

    private function setMethodCallable(\ReflectionMethod $method, $invocationObject): void
    {
        if (\is_object($invocationObject)) {
            $this->callable = $method;
            $this->invocationObject = $invocationObject;
        } elseif ($method->isStatic()) {
            $this->callable = $method;
            $this->invocationObject = null;
        } else {
            throw new \InvalidArgumentException(
                'ReflectionMethod callables must specify an invocation object'
            );
        }
    }

    /**
     * @return mixed
     */
    private function invokeClosureCompat(array $args)
    {
        $scope = $this->callable->getClosureScopeClass();
        $closure = \Closure::bind(
            $this->callable->getClosure(),
            $this->callable->getClosureThis(),
            $scope->name ?? null
        );

        return $closure(...$args);
    }
}
