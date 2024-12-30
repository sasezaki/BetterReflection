<?php

namespace Roave\BetterReflectionTest\Fixture;

class AsymmetricVisibilityImplicitFinal
{
    public private(set) bool $publicPrivateSetIsFinal = true;
    protected private(set) bool $protectedPrivateSetIsFinal = true;
    private private(set) bool $privatePrivateSetIsNotFinal = true;
}
