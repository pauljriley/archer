<?php
namespace Icecave\Archer\Support;

use ErrorException;
use PHPUnit_Framework_TestCase;
use Phake;

class AsplodeTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->isolator = Phake::mock(__NAMESPACE__ . '\Isolator');
        $this->asplode  = new Asplode($this->isolator);
    }

    public function testInstance()
    {
        $this->assertInstanceOf(__NAMESPACE__ . '\Asplode', Asplode::instance());
    }

    public function testInstall()
    {
        $this->asplode->install();

        Phake::verify($this->isolator)->set_error_handler(array($this->asplode, 'handleError'));
    }

    public function testInstallFailure()
    {
        $this->asplode->install();

        $this->setExpectedException('RuntimeException', 'Already installed.');
        $this->asplode->install();
    }

    public function testUninstall()
    {
        $this->asplode->install();
        $this->asplode->uninstall();

        Phake::verify($this->isolator)->restore_error_handler();
    }

    public function testUninstallFailure()
    {
        $this->setExpectedException('RuntimeException', 'Not installed.');
        $this->asplode->uninstall();
    }

    public function testHandleError()
    {
        try {
            $this->asplode->handleError(1, 'Message.', 'foo.php', 20);
            $this->fail('Expected exception was not thrown.');
        } catch (ErrorException $e) {
            $this->assertSame(1, $e->getSeverity());
            $this->assertSame('Message.', $e->getMessage());
            $this->assertSame('foo.php', $e->getFile());
            $this->assertSame(20, $e->getLine());
        }
    }
}
