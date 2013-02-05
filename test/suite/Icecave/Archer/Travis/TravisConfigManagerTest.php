<?php
namespace Icecave\Archer\Travis;

use Phake;
use PHPUnit_Framework_TestCase;

class TravisConfigManagerTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_fileSystem = Phake::mock('Icecave\Archer\FileSystem\FileSystem');
        $this->_fileFinder = Phake::mock('Icecave\Archer\Configuration\ConfigurationFileFinder');
        $this->_isolator = Phake::mock('Icecave\Archer\Support\Isolator');
        $this->_manager = new TravisConfigManager(
            $this->_fileSystem,
            $this->_fileFinder,
            $this->_isolator
        );
    }

    public function testConstructor()
    {
        $this->assertSame($this->_fileSystem, $this->_manager->fileSystem());
        $this->assertSame($this->_fileFinder, $this->_manager->fileFinder());
    }

    public function testConstructorDefaults()
    {
        $this->_manager = new TravisConfigManager;

        $this->assertInstanceOf(
            'Icecave\Archer\FileSystem\FileSystem',
            $this->_manager->fileSystem()
        );
        $this->assertInstanceOf(
            'Icecave\Archer\Configuration\ConfigurationFileFinder',
            $this->_manager->fileFinder()
        );
    }
}
