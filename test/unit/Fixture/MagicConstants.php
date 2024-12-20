<?php

namespace {

    define('_DIR', __DIR__);
    define('_FILE', __FILE__);
    define('_LINE', __LINE__);
    define('_NAMESPACE', __NAMESPACE__);
    define('_CLASS', __CLASS__);
    define('_TRAIT', __TRAIT__);
    define('_METHOD', __METHOD__);
    define('_FUNCTION', __FUNCTION__);
    define('_PROPERTY', __PROPERTY__);

}

namespace Roave\BetterReflectionTest\Fixture {

    const _DIR = __DIR__;
    const _FILE = __FILE__;
    const _LINE = __LINE__;
    const _NAMESPACE = __NAMESPACE__;
    const _CLASS = __CLASS__;
    const _TRAIT = __TRAIT__;
    const _METHOD = __METHOD__;
    const _FUNCTION = __FUNCTION__;
    const _PROPERTY = __PROPERTY__;

    trait MagicConstantsTrait
    {
        protected $dir = __DIR__;
        protected $file = __FILE__;
        protected $line = __LINE__;
        protected $namespace = __NAMESPACE__;
        protected $class = __CLASS__;
        protected $trait = __TRAIT__;
        protected $method = __METHOD__;
        protected $function = __FUNCTION__;
        protected $property = __PROPERTY__;
    }

    class MagicConstantsClass
    {
        private $dir = __DIR__;
        private $file = __FILE__;
        private $line = __LINE__;
        private $namespace = __NAMESPACE__;
        private $class = __CLASS__;
        private $trait = __TRAIT__;
        private $method = __METHOD__;
        private $function = __FUNCTION__;
        private $property = __PROPERTY__;

        public function magicConstantsMethod(
            $dir = __DIR__,
            $file = __FILE__,
            $line = __LINE__,
            $namespace = __NAMESPACE__,
            $class = __CLASS__,
            $trait = __TRAIT__,
            $method = __METHOD__,
            $function = __FUNCTION__,
            $property = __PROPERTY__,
        )
        {
        }
    }

    function magicConstantsFunction(
        $dir = __DIR__,
        $file = __FILE__,
        $line = __LINE__,
        $namespace = __NAMESPACE__,
        $class = __CLASS__,
        $trait = __TRAIT__,
        $method = __METHOD__,
        $function = __FUNCTION__,
        $property = __PROPERTY__,
    )
    {
    }

}
