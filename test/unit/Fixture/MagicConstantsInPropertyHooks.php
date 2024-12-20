<?php

namespace Roave\BetterReflectionTest\Fixture;

class MagicConstantsInPropertyHooks
{
    public string $propertyMagicConstantMethod {
        set (
            #[SomeAttribute(__METHOD__)]
            string $value
        ) {
            $this->propertyMagicConstantMethod = $value;
        }
    }

    public string $propertyMagicConstantFunction {
        set (
            #[SomeAttribute(__FUNCTION__)]
            string $value
        ) {
            $this->propertyMagicConstantFunction = $value;
        }
    }

    public string $propertyMagicConstantProperty {
        set (
            #[SomeAttribute(__PROPERTY__)]
            string $value
        ) {
            $this->propertyMagicConstantProperty = $value;
        }
    }
}
