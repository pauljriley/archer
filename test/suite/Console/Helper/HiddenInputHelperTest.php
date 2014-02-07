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

        $this->isolator = Phake::mock('Icecave\Archer\Support\Isolator');
        $this->helper = new HiddenInputHelper(
            'foo',
            $this->isolator
        );

        Phake::when($this->isolator)
            ->sys_get_temp_dir(Phake::anyParameters())
            ->thenReturn('doom')
        ;
        Phake::when($this->isolator)
            ->uniqid(Phake::anyParameters())
            ->thenReturn('splat')
        ;

        $this->output = Phake::mock(
            'Symfony\Component\Console\Output\OutputInterface'
        );
    }

    public function testConstructor()
    {
        $this->assertSame('hidden-input', $this->helper->getName());
        $this->assertSame('foo', $this->helper->hiddenInputPath());
    }

    public function testConstructorDefaults()
    {
        $this->helper = new HiddenInputHelper;
        $expectedHiddenInputPath = __DIR__ . '/../../../../res/bin/hiddeninput.exe';

        $this->assertTrue(file_exists($expectedHiddenInputPath));

        $this->assertSame(
            realpath($expectedHiddenInputPath),
            realpath($this->helper->hiddenInputPath())
        );
    }

    public function testAskHiddenResponseStty()
    {
        Phake::when($this->isolator)
            ->defined(Phake::anyParameters())
            ->thenReturn(false)
        ;
        Phake::when($this->isolator)
            ->shell_exec(Phake::anyParameters())
            ->thenReturn('baz')
            ->thenReturn('')
        ;
        Phake::when($this->isolator)
            ->fgets(Phake::anyParameters())
            ->thenReturn('qux')
        ;
        $actual = $this->helper->askHiddenResponse($this->output, 'bar');

        $this->assertSame('qux', $actual);
        Phake::inOrder(
            Phake::verify($this->isolator)->defined('PHP_WINDOWS_VERSION_BUILD'),
            Phake::verify($this->output)->write('bar'),
            Phake::verify($this->isolator)->shell_exec('stty -g'),
            Phake::verify($this->isolator)->shell_exec('stty -echo'),
            Phake::verify($this->isolator)->fgets(STDIN),
            Phake::verify($this->isolator)->shell_exec('stty baz'),
            Phake::verify($this->output)->writeln('')
        );
    }

    public function testAskHiddenResponseSttyFailureFgets()
    {
        $errorException = Phake::mock('ErrorException');
        Phake::when($this->isolator)
            ->defined(Phake::anyParameters())
            ->thenReturn(false)
        ;
        Phake::when($this->isolator)
            ->shell_exec(Phake::anyParameters())
            ->thenReturn('baz')
            ->thenReturn('')
        ;
        Phake::when($this->isolator)
            ->fgets(Phake::anyParameters())
            ->thenThrow($errorException)
        ;
        $actual = null;
        try {
            $this->helper->askHiddenResponse($this->output, 'bar');
        } catch (RuntimeException $actual) {
        }
        $expected = new RuntimeException('Unable to read response.', 0, $errorException);

        $this->assertEquals($expected, $actual);
        Phake::inOrder(
            Phake::verify($this->isolator)->defined('PHP_WINDOWS_VERSION_BUILD'),
            Phake::verify($this->output)->write('bar'),
            Phake::verify($this->isolator)->shell_exec('stty -g'),
            Phake::verify($this->isolator)->shell_exec('stty -echo'),
            Phake::verify($this->isolator)->fgets(STDIN),
            Phake::verify($this->isolator)->shell_exec('stty baz')
        );
    }

    public function testAskHiddenResponseSttyFailureExecute()
    {
        Phake::when($this->isolator)
            ->defined(Phake::anyParameters())
            ->thenReturn(false)
        ;
        Phake::when($this->isolator)
            ->shell_exec(Phake::anyParameters())
            ->thenReturn(false)
        ;

        $this->setExpectedException(
            'RuntimeException',
            'Unable to create or read hidden input dialog.'
        );
        $this->helper->askHiddenResponse($this->output, 'bar');
    }

    public function testAskHiddenResponseWindows()
    {
        Phake::when($this->isolator)
            ->defined(Phake::anyParameters())
            ->thenReturn(true)
        ;
        Phake::when($this->isolator)
            ->shell_exec(Phake::anyParameters())
            ->thenReturn('baz')
        ;
        $actual = $this->helper->askHiddenResponse($this->output, 'bar');

        $this->assertSame('baz', $actual);
        Phake::inOrder(
            Phake::verify($this->isolator)->defined('PHP_WINDOWS_VERSION_BUILD'),
            Phake::verify($this->output)->write('bar'),
            Phake::verify($this->isolator)->copy('foo', 'doom/hiddeninput-splat.exe'),
            Phake::verify($this->isolator)->shell_exec('doom/hiddeninput-splat.exe'),
            Phake::verify($this->output)->writeln('')
        );
    }

    public function testAskHiddenResponseWindowsFailureExecute()
    {
        Phake::when($this->isolator)
            ->defined(Phake::anyParameters())
            ->thenReturn(true)
        ;
        Phake::when($this->isolator)
            ->shell_exec(Phake::anyParameters())
            ->thenReturn(false)
        ;

        $this->setExpectedException(
            'RuntimeException',
            'Unable to create or read hidden input dialog.'
        );
        $this->helper->askHiddenResponse($this->output, 'bar');
    }
}
