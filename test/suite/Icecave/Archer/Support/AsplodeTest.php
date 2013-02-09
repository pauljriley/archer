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

        $this->_isolator = Phake::mock(__NAMESPACE__ . '\Isolator');
        $this->_asplode  = new Asplode($this->_isolator);
    }

    public function testInstance()
    {
        $this->assertInstanceOf(__NAMESPACE__ . '\Asplode', Asplode::instance());
    }

    public function testInstall()
    {
        $this->_asplode->install();

        Phake::verify($this->_isolator)->set_error_handler(array($this->_asplode, 'handleError'));
    }

    public function testInstallFailure()
    {
        $this->_asplode->install();

        $this->setExpectedException('RuntimeException', 'Already installed.');
        $this->_asplode->install();
    }

    public function testUninstall()
    {
        $this->_asplode->install();
        $this->_asplode->uninstall();

        Phake::verify($this->_isolator)->restore_error_handler();
    }

    public function testUninstallFailure()
    {
        $this->setExpectedException('RuntimeException', 'Not installed.');
        $this->_asplode->uninstall();
    }

    public function testHandleError()
    {
        try {
            $this->_asplode->handleError(1, 'Message.', 'foo.php', 20);
            $this->fail('Expected exception was not thrown.');
        } catch (ErrorException $e) {
            $this->assertSame(1, $e->getSeverity());
            $this->assertSame('Message.', $e->getMessage());
            $this->assertSame('foo.php', $e->getFile());
            $this->assertSame(20, $e->getLine());
        }
    }
}
