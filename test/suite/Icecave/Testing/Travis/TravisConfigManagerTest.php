<?php
namespace Icecave\Testing\Travis;

use Phake;
use PHPUnit_Framework_TestCase;

class TravisConfigManagerTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_fileManager = Phake::mock('Icecave\Testing\Support\FileManager');
        $this->_fileFinder = Phake::mock('Icecave\Testing\Configuration\ConfigurationFileFinder');
        $this->_isolator = Phake::mock('Icecave\Testing\Support\Isolator');
        $this->_manager = new TravisConfigManager(
            $this->_fileManager,
            $this->_fileFinder,
            $this->_isolator
        );
    }

    public function testConstructor()
    {
        $this->assertSame($this->_fileManager, $this->_manager->fileManager());
        $this->assertSame($this->_fileFinder, $this->_manager->fileFinder());
    }

    public function testConstructorDefaults()
    {
        $this->_manager = new TravisConfigManager(
            $this->_fileManager
        );

        $this->assertInstanceOf(
            'Icecave\Testing\Configuration\ConfigurationFileFinder',
            $this->_manager->fileFinder()
        );
    }
}
