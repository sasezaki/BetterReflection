<?php

namespace Roave\BetterReflectionTest\Fixture;

abstract class PHP85ClassForSourceStubber extends \Roave\BetterReflectionTest\Fixture\ParentClassForSourceStubber
{
    public function methodWithSelfAndParentParameters(\Roave\BetterReflectionTest\Fixture\PHP85ClassForSourceStubber $self, \Roave\BetterReflectionTest\Fixture\ParentClassForSourceStubber $parent): void
    {
    }
}
