<?php

namespace Roave\BetterReflectionTest\Fixture;

class AsymmetricVisibilityImplicitProtectedSet
{
    public public(set) readonly bool $publicPublicSet;
    public protected(set) readonly bool $publicProtectedSet;
    public private(set) readonly bool $publicPrivateSet;

    protected readonly bool $protected;
    private readonly bool $private;

    public readonly bool $publicImplicitProtectedSet;
}
