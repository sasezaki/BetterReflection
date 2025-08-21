<?php

namespace Roave\BetterReflectionTest\Fixture;

class ParentClassForSourceStubber
{
}

abstract class PHP85ClassForSourceStubber extends ParentClassForSourceStubber
{
    public function methodWithSelfAndParentParameters(self $self, parent $parent) : void
    {
    }
}
