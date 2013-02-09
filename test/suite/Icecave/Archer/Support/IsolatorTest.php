<?php
namespace Icecave\Archer\Support;

use PHPUnit_Framework_TestCase;

class IsolatorTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Isolator::resetIsolator();
    }

    public function testGet()
    {
        $isolator = new Isolator;
        $this->assertSame($isolator, Isolator::get($isolator));

        $singleton = Isolator::get(null);

        $this->assertInstanceOf(__NAMESPACE__ . '\Isolator', $singleton);
        $this->assertSame($singleton, Isolator::get(null));
    }

    public function testCall()
    {
        $isolator = new Isolator;
        $this->assertSame(3, $isolator->strlen('foo'));
    }

    public function testEcho()
    {
        $isolator = new Isolator;
        $this->expectOutputString('Echo works!');
        $isolator->echo('Echo works!');
    }

    public function testEval()
    {
        $isolator = new Isolator;
        $this->assertSame(3, $isolator->eval('return strlen("foo");'));
    }

    public function testInclude()
    {
        $isolator = new Isolator;
        $this->assertFalse(class_exists(__NAMESPACE__ . '\TestFixture\ClassA', false));

        $isolator->include(__DIR__ . '/../../../../lib/Icecave/Archer/Support/TestFixture/ClassA.php');
        $this->assertTrue(class_exists(__NAMESPACE__ . '\TestFixture\ClassA', false));
    }

    public function testIncludeOnce()
    {
        $isolator = new Isolator;
        $this->assertFalse(class_exists(__NAMESPACE__ . '\TestFixture\ClassB', false));

        $isolator->include_once(__DIR__ . '/../../../../lib/Icecave/Archer/Support/TestFixture/ClassB.php');
        $this->assertTrue(class_exists(__NAMESPACE__ . '\TestFixture\ClassB', false));
    }

    public function testRequire()
    {
        $isolator = new Isolator;
        $this->assertFalse(class_exists(__NAMESPACE__ . '\TestFixture\ClassC', false));

        $isolator->require(__DIR__ . '/../../../../lib/Icecave/Archer/Support/TestFixture/ClassC.php');
        $this->assertTrue(class_exists(__NAMESPACE__ . '\TestFixture\ClassC', false));
    }

    public function testRequireOnce()
    {
        $isolator = new Isolator;
        $this->assertFalse(class_exists(__NAMESPACE__ . '\TestFixture\ClassD', false));

        $isolator->require_once(__DIR__ . '/../../../../lib/Icecave/Archer/Support/TestFixture/ClassD.php');
        $this->assertTrue(class_exists(__NAMESPACE__ . '\TestFixture\ClassD', false));
    }
}
