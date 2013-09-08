<?php
namespace Icecave\Archer\Console\Helper;

use Phake;
use PHPUnit_Framework_TestCase;
use RuntimeException;

class HiddenInputHelperTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_isolator = Phake::mock('Icecave\Archer\Support\Isolator');
        $this->_helper = new HiddenInputHelper(
            'foo',
            $this->_isolator
        );

        Phake::when($this->_isolator)
            ->sys_get_temp_dir(Phake::anyParameters())
            ->thenReturn('doom')
        ;
        Phake::when($this->_isolator)
            ->uniqid(Phake::anyParameters())
            ->thenReturn('splat')
        ;

        $this->_output = Phake::mock(
            'Symfony\Component\Console\Output\OutputInterface'
        );
    }

    public function testConstructor()
    {
        $this->assertSame('hidden-input', $this->_helper->getName());
        $this->assertSame('foo', $this->_helper->hiddenInputPath());
    }

    public function testConstructorDefaults()
    {
        $this->_helper = new HiddenInputHelper;
        $expectedHiddenInputPath = __DIR__;
        for ($i = 0; $i < 6; $i ++) {
            $expectedHiddenInputPath = dirname($expectedHiddenInputPath);
        }
        $expectedHiddenInputPath .= '/src/Icecave/Archer/Console/Helper/../../../../../res/bin/hiddeninput.exe';

        $this->assertSame($expectedHiddenInputPath, $this->_helper->hiddenInputPath());
    }

    public function testAskHiddenResponseStty()
    {
        Phake::when($this->_isolator)
            ->defined(Phake::anyParameters())
            ->thenReturn(false)
        ;
        Phake::when($this->_isolator)
            ->shell_exec(Phake::anyParameters())
            ->thenReturn('baz')
            ->thenReturn('')
        ;
        Phake::when($this->_isolator)
            ->fgets(Phake::anyParameters())
            ->thenReturn('qux')
        ;
        $actual = $this->_helper->askHiddenResponse($this->_output, 'bar');

        $this->assertSame('qux', $actual);
        Phake::inOrder(
            Phake::verify($this->_isolator)->defined('PHP_WINDOWS_VERSION_BUILD'),
            Phake::verify($this->_output)->write('bar'),
            Phake::verify($this->_isolator)->shell_exec('stty -g'),
            Phake::verify($this->_isolator)->shell_exec('stty -echo'),
            Phake::verify($this->_isolator)->fgets(STDIN),
            Phake::verify($this->_isolator)->shell_exec('stty baz'),
            Phake::verify($this->_output)->writeln('')
        );
    }

    public function testAskHiddenResponseSttyFailureFgets()
    {
        $errorException = Phake::mock('ErrorException');
        Phake::when($this->_isolator)
            ->defined(Phake::anyParameters())
            ->thenReturn(false)
        ;
        Phake::when($this->_isolator)
            ->shell_exec(Phake::anyParameters())
            ->thenReturn('baz')
            ->thenReturn('')
        ;
        Phake::when($this->_isolator)
            ->fgets(Phake::anyParameters())
            ->thenThrow($errorException)
        ;
        $actual = null;
        try {
            $this->_helper->askHiddenResponse($this->_output, 'bar');
        } catch (RuntimeException $actual) {
        }
        $expected = new RuntimeException('Unable to read response.', 0, $errorException);

        $this->assertEquals($expected, $actual);
        Phake::inOrder(
            Phake::verify($this->_isolator)->defined('PHP_WINDOWS_VERSION_BUILD'),
            Phake::verify($this->_output)->write('bar'),
            Phake::verify($this->_isolator)->shell_exec('stty -g'),
            Phake::verify($this->_isolator)->shell_exec('stty -echo'),
            Phake::verify($this->_isolator)->fgets(STDIN),
            Phake::verify($this->_isolator)->shell_exec('stty baz')
        );
    }

    public function testAskHiddenResponseSttyFailureExecute()
    {
        Phake::when($this->_isolator)
            ->defined(Phake::anyParameters())
            ->thenReturn(false)
        ;
        Phake::when($this->_isolator)
            ->shell_exec(Phake::anyParameters())
            ->thenReturn(false)
        ;

        $this->setExpectedException(
            'RuntimeException',
            'Unable to create or read hidden input dialog.'
        );
        $this->_helper->askHiddenResponse($this->_output, 'bar');
    }

    public function testAskHiddenResponseWindows()
    {
        Phake::when($this->_isolator)
            ->defined(Phake::anyParameters())
            ->thenReturn(true)
        ;
        Phake::when($this->_isolator)
            ->shell_exec(Phake::anyParameters())
            ->thenReturn('baz')
        ;
        $actual = $this->_helper->askHiddenResponse($this->_output, 'bar');

        $this->assertSame('baz', $actual);
        Phake::inOrder(
            Phake::verify($this->_isolator)->defined('PHP_WINDOWS_VERSION_BUILD'),
            Phake::verify($this->_output)->write('bar'),
            Phake::verify($this->_isolator)->copy('foo', 'doom/hiddeninput-splat.exe'),
            Phake::verify($this->_isolator)->shell_exec('doom/hiddeninput-splat.exe'),
            Phake::verify($this->_output)->writeln('')
        );
    }

    public function testAskHiddenResponseWindowsFailureExecute()
    {
        Phake::when($this->_isolator)
            ->defined(Phake::anyParameters())
            ->thenReturn(true)
        ;
        Phake::when($this->_isolator)
            ->shell_exec(Phake::anyParameters())
            ->thenReturn(false)
        ;

        $this->setExpectedException(
            'RuntimeException',
            'Unable to create or read hidden input dialog.'
        );
        $this->_helper->askHiddenResponse($this->_output, 'bar');
    }
}
