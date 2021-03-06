<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\SourceStubber;

use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionFunction as CoreReflectionFunction;
use ReflectionMethod as CoreReflectionMethod;
use ReflectionParameter as CoreReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use const PHP_VERSION_ID;
use function array_filter;
use function array_map;
use function array_merge;
use function get_declared_classes;
use function get_declared_interfaces;
use function get_declared_traits;
use function get_defined_functions;
use function in_array;
use function sort;
use function sprintf;

/**
 * @covers \Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber
 */
class PhpStormStubsSourceStubberTest extends TestCase
{
    /** @var PhpStormStubsSourceStubber */
    private $sourceStubber;

    /** @var PhpInternalSourceLocator */
    private $phpInternalSourceLocator;

    /** @var ClassReflector */
    private $classReflector;

    /** @var FunctionReflector */
    private $functionReflector;

    protected function setUp() : void
    {
        parent::setUp();

        $betterReflection = BetterReflectionSingleton::instance();

        $this->sourceStubber            = new PhpStormStubsSourceStubber($betterReflection->phpParser());
        $this->phpInternalSourceLocator = new PhpInternalSourceLocator(
            $betterReflection->astLocator(),
            $this->sourceStubber
        );
        $this->classReflector           = new ClassReflector($this->phpInternalSourceLocator);
        $this->functionReflector        = new FunctionReflector($this->phpInternalSourceLocator, $this->classReflector);
    }

    /**
     * @return string[][]
     */
    public function internalClassesProvider() : array
    {
        $classNames = array_merge(
            get_declared_classes(),
            get_declared_interfaces(),
            get_declared_traits()
        );

        // Needs fixes in JetBrains/phpstorm-stubs
        $missingClassesInStubs = ['Generator', 'ClosedGeneratorException', 'AssertionError'];

        return array_map(
            static function (string $className) : array {
                return [$className];
            },
            array_filter(
                $classNames,
                static function (string $className) use ($missingClassesInStubs) : bool {
                    if (in_array($className, $missingClassesInStubs, true)) {
                        return false;
                    }

                    $reflection = new CoreReflectionClass($className);

                    if (! $reflection->isInternal()) {
                        return false;
                    }

                    // Check only always enabled extensions
                    return in_array($reflection->getExtensionName(), ['Core', 'standard', 'pcre', 'SPL'], true);
                }
            )
        );
    }

    /**
     * @dataProvider internalClassesProvider
     */
    public function testInternalClasses(string $className) : void
    {
        $class = $this->classReflector->reflect($className);

        self::assertInstanceOf(ReflectionClass::class, $class);
        self::assertSame($className, $class->getName());
        self::assertTrue($class->isInternal());
        self::assertFalse($class->isUserDefined());

        $internalReflection = new CoreReflectionClass($className);

        self::assertSame($internalReflection->isInterface(), $class->isInterface());
        self::assertSame($internalReflection->isTrait(), $class->isTrait());

        self::assertSameClassAttributes($internalReflection, $class);
    }

    private function assertSameParentClass(CoreReflectionClass $original, ReflectionClass $stubbed) : void
    {
        $originalParentClass = $original->getParentClass();
        $stubbedParentClass  = $stubbed->getParentClass();

        self::assertSame(
            $originalParentClass ? $originalParentClass->getName() : null,
            $stubbedParentClass ? $stubbedParentClass->getName() : null
        );
    }

    private function assertSameInterfaces(CoreReflectionClass $original, ReflectionClass $stubbed) : void
    {
        $originalInterfacesNames = $original->getInterfaceNames();
        $stubbedInterfacesNames  = $stubbed->getInterfaceNames();

        sort($originalInterfacesNames);
        sort($stubbedInterfacesNames);

        self::assertSame($originalInterfacesNames, $stubbedInterfacesNames);
    }

    private function assertSameClassAttributes(CoreReflectionClass $original, ReflectionClass $stubbed) : void
    {
        self::assertSame($original->getName(), $stubbed->getName());

        // Changed in PHP 7.3.0
        if (PHP_VERSION_ID < 70300 && $original->getName() === 'ParseError') {
            return;
        }

        $this->assertSameParentClass($original, $stubbed);
        $this->assertSameInterfaces($original, $stubbed);

        foreach ($original->getMethods() as $method) {
            $this->assertSameMethodAttributes($method, $stubbed->getMethod($method->getName()));
        }

        self::assertEquals($original->getConstants(), $stubbed->getConstants());
    }

    private function assertSameMethodAttributes(CoreReflectionMethod $original, ReflectionMethod $stubbed) : void
    {
        $originalParameterNames = array_map(
            static function (CoreReflectionParameter $parameter) : string {
                return $parameter->getDeclaringFunction()->getName() . '.' . $parameter->getName();
            },
            $original->getParameters()
        );
        $stubParameterNames     = array_map(
            static function (ReflectionParameter $parameter) : string {
                return $parameter->getDeclaringFunction()->getName() . '.' . $parameter->getName();
            },
            $stubbed->getParameters()
        );

        // Needs fixes in JetBrains/phpstorm-stubs
        // self::assertSame($originalParameterNames, $stubParameterNames);

        foreach ($original->getParameters() as $parameter) {
            $stubbedParameter = $stubbed->getParameter($parameter->getName());

            if ($stubbedParameter === null) {
                // Needs fixes in JetBrains/phpstorm-stubs
                continue;
            }

            $this->assertSameParameterAttributes(
                $original,
                $parameter,
                $stubbedParameter
            );
        }

        self::assertSame($original->isPublic(), $stubbed->isPublic());
        self::assertSame($original->isPrivate(), $stubbed->isPrivate());
        self::assertSame($original->isProtected(), $stubbed->isProtected());
        self::assertSame($original->returnsReference(), $stubbed->returnsReference());
        self::assertSame($original->isStatic(), $stubbed->isStatic());
        self::assertSame($original->isFinal(), $stubbed->isFinal());
    }

    private function assertSameParameterAttributes(
        CoreReflectionMethod $originalMethod,
        CoreReflectionParameter $original,
        ReflectionParameter $stubbed
    ) : void {
        $parameterName = $original->getDeclaringClass()->getName()
            . '#' . $originalMethod->getName()
            . '.' . $original->getName();

        self::assertSame($original->getName(), $stubbed->getName(), $parameterName);
        // Bugs in PHP: https://3v4l.org/1HSTK
        if (! in_array($parameterName, ['SplFileObject#fputcsv.fields'], true)) {
            self::assertSame($original->isArray(), $stubbed->isArray(), $parameterName);
        }
        // Bugs in PHP: https://3v4l.org/RjCDr
        if (! in_array($parameterName, ['Closure#fromCallable.callable', 'CallbackFilterIterator#__construct.callback'], true)) {
            self::assertSame($original->isCallable(), $stubbed->isCallable(), $parameterName);
        }
        self::assertSame($original->canBePassedByValue(), $stubbed->canBePassedByValue(), $parameterName);
        // Bugs in PHP
        if (! in_array($parameterName, [
            'ErrorException#__construct.message',
            'ErrorException#__construct.code',
            'ErrorException#__construct.severity',
            'ErrorException#__construct.filename',
            'ErrorException#__construct.lineno',
            'ErrorException#__construct.previous',
            'RecursiveIteratorIterator#getSubIterator.level',
            'RecursiveIteratorIterator#setMaxDepth.max_depth',
            'SplTempFileObject#__construct.max_memory',
            'MultipleIterator#__construct.flags',
        ], true)) {
            self::assertSame($original->isOptional(), $stubbed->isOptional(), $parameterName);
        }
        self::assertSame($original->isPassedByReference(), $stubbed->isPassedByReference(), $parameterName);
        self::assertSame($original->isVariadic(), $stubbed->isVariadic(), $parameterName);

        $class = $original->getClass();
        if ($class) {
            // Not possible to write "RecursiveIterator|IteratorAggregate" in PHP code in JetBrains/phpstorm-stubs
            if ($parameterName !== 'RecursiveTreeIterator#__construct.iterator') {
                $stubbedClass = $stubbed->getClass();

                self::assertInstanceOf(ReflectionClass::class, $stubbedClass, $parameterName);
                self::assertSame($class->getName(), $stubbedClass->getName(), $parameterName);
            }
        } else {
            // Bugs in PHP
            if (! in_array($parameterName, [
                'Error#__construct.previous',
                'Exception#__construct.previous',
                'Closure#bind.closure',
            ], true)) {
                self::assertNull($stubbed->getClass(), $parameterName);
            }
        }
    }

    /**
     * @return string[][]
     */
    public function internalFunctionsProvider() : array
    {
        $functionNames = get_defined_functions()['internal'];

        // Needs fixes in JetBrains/phpstorm-stubs
        $missingFunctionsInStubs = [
            'get_resources',
            'gc_mem_caches',
            'error_clear_last',
            'is_iterable',
            'password_hash',
            'password_get_info',
            'password_needs_rehash',
            'password_verify',
            'random_bytes',
            'random_int',
            'sapi_windows_cp_conv',
            'sapi_windows_cp_get',
            'sapi_windows_cp_set',
            'sapi_windows_cp_is_utf8',
            'sapi_windows_vt100_support',
            'utf8_encode',
            'utf8_decode',
        ];

        return array_map(
            static function (string $functionName) : array {
                return [$functionName];
            },
            array_filter(
                $functionNames,
                static function (string $functionName) use ($missingFunctionsInStubs) : bool {
                    if (in_array($functionName, $missingFunctionsInStubs, true)) {
                        return false;
                    }

                    $reflection = new CoreReflectionFunction($functionName);

                    // Check only always enabled extensions
                    return in_array($reflection->getExtensionName(), ['Core', 'standard', 'pcre', 'SPL'], true);
                }
            )
        );
    }

    /**
     * @dataProvider internalFunctionsProvider
     */
    public function testInternalFunctions(string $functionName) : void
    {
        $stubbedReflection = $this->functionReflector->reflect($functionName);

        self::assertSame($functionName, $stubbedReflection->getName());
        self::assertTrue($stubbedReflection->isInternal());
        self::assertFalse($stubbedReflection->isUserDefined());

        $originalReflection = new CoreReflectionFunction($functionName);

        // Needs fixes in JetBrains/phpstorm-stubs
        if (in_array($functionName, [
            'setlocale',
            'sprintf',
            'printf',
            'fprintf',
            'trait_exists',
            'user_error',
            'preg_replace_callback_array',
            'strtok',
            'strtr',
            'hrtime',
            'forward_static_call',
            'forward_static_call_array',
            'pack',
            'min',
            'max',
            'var_dump',
            'register_shutdown_function',
            'register_tick_function',
            'compact',
            'array_map',
            'array_merge',
            'array_replace',
            'array_replace_recursive',
            'array_intersect',
            'array_intersect_key',
            'array_intersect_ukey',
            'array_intersect_assoc',
            'array_uintersect',
            'array_uintersect_assoc',
            'array_intersect_uassoc',
            'array_uintersect_uassoc',
            'array_diff',
            'array_diff_key',
            'array_diff_ukey',
            'array_diff_assoc',
            'array_udiff',
            'array_udiff_assoc',
            'array_diff_uassoc',
            'array_udiff_uassoc',
            'array_multisort',
            'dns_get_record',
            'extract',
            'pos',
        ], true)) {
            return;
        }

        // Changed in PHP 7.3.0
        if (PHP_VERSION_ID < 70300 && in_array($functionName, ['array_push', 'array_unshift'], true)) {
            return;
        }

        self::assertSame($originalReflection->getNumberOfParameters(), $stubbedReflection->getNumberOfParameters());
        self::assertSame($originalReflection->getNumberOfRequiredParameters(), $stubbedReflection->getNumberOfRequiredParameters());

        $stubbedReflectionParameters = $stubbedReflection->getParameters();
        foreach ($originalReflection->getParameters() as $parameterNo => $originalReflectionParameter) {
            $parameterName = sprintf('%s.%s', $functionName, $originalReflectionParameter->getName());

            $stubbedReflectionParameter = $stubbedReflectionParameters[$parameterNo];

            self::assertSame($originalReflectionParameter->isOptional(), $stubbedReflectionParameter->isOptional(), $parameterName);
            self::assertSame($originalReflectionParameter->isPassedByReference(), $stubbedReflectionParameter->isPassedByReference(), $parameterName);
            self::assertSame($originalReflectionParameter->canBePassedByValue(), $stubbedReflectionParameter->canBePassedByValue(), $parameterName);

            // Bugs in PHP
            if (! in_array($parameterName, ['preg_replace_callback.callback', 'header_register_callback.callback'], true)) {
                self::assertSame($originalReflectionParameter->isCallable(), $stubbedReflectionParameter->isCallable(), $parameterName);
            }

            // Needs fixes in JetBrains/phpstorm-stubs
            if (! in_array($parameterName, ['fscanf.vars', 'debug_zval_dump.vars', 'array_merge_recursive.arrays'], true)) {
                self::assertSame($originalReflectionParameter->isVariadic(), $stubbedReflectionParameter->isVariadic(), $parameterName);
            }

            $class = $originalReflectionParameter->getClass();
            if ($class) {
                // Needs fixes in JetBrains/phpstorm-stubs
                if (! in_array($parameterName, [
                    'iterator_to_array.iterator',
                    'iterator_count.iterator',
                    'iterator_apply.iterator',
                ], true)) {
                    $stubbedClass = $stubbedReflectionParameter->getClass();
                    self::assertInstanceOf(ReflectionClass::class, $stubbedClass, $parameterName);
                    self::assertSame($class->getName(), $stubbedClass->getName(), $parameterName);
                }
            } else {
                self::assertNull($originalReflectionParameter->getClass(), $parameterName);
            }
        }
    }

    public function testNoStubForUnknownClass() : void
    {
        $reflection = $this->createMock(CoreReflectionClass::class);
        $reflection->method('getName')
            ->willReturn('SomeClass');
        $reflection->method('isUserDefined')
            ->willReturn(false);
        $reflection->method('getExtensionName')
            ->willReturn('Core');

        self::assertNull($this->sourceStubber->generateClassStub($reflection));
    }

    public function testNoStubForUnknownFunction() : void
    {
        $reflection = $this->createMock(CoreReflectionFunction::class);
        $reflection->method('getName')
            ->willReturn('SomeFunction');
        $reflection->method('isUserDefined')
            ->willReturn(false);
        $reflection->method('getExtensionName')
            ->willReturn('Core');

        self::assertNull($this->sourceStubber->generateFunctionStub($reflection));
    }

    public function testNoStubForClassOfUnknownExtension() : void
    {
        $reflection = $this->createMock(CoreReflectionClass::class);
        $reflection->method('getName')
            ->willReturn('SomeClass');
        $reflection->method('isUserDefined')
            ->willReturn(false);
        $reflection->method('getExtensionName')
            ->willReturn('UnknownExtension');

        self::assertNull($this->sourceStubber->generateClassStub($reflection));
    }

    public function testNoStubForFunctionOfUnknownExtension() : void
    {
        $reflection = $this->createMock(CoreReflectionFunction::class);
        $reflection->method('getName')
            ->willReturn('SomeFunction');
        $reflection->method('isUserDefined')
            ->willReturn(false);
        $reflection->method('getExtensionName')
            ->willReturn('UnknownExtension');

        self::assertNull($this->sourceStubber->generateFunctionStub($reflection));
    }

    public function testNoStubForUserDefinedClass() : void
    {
        $reflection = $this->createMock(CoreReflectionClass::class);
        $reflection->method('isUserDefined')
            ->willReturn(true);

        self::assertNull($this->sourceStubber->generateClassStub($reflection));
    }

    public function testNoStubForUserDefinedFunction() : void
    {
        $reflection = $this->createMock(CoreReflectionFunction::class);
        $reflection->method('isUserDefined')
            ->willReturn(true);

        self::assertNull($this->sourceStubber->generateFunctionStub($reflection));
    }
}
