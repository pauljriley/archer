<?php
namespace Icecave\Archer\Console\Command\Travis;

use Icecave\Archer\Support\Isolator;
use PHPUnit_Framework_TestCase;
use Phake;

class AbstractTravisCommandTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->_isolator = Phake::mock('Icecave\Archer\Support\Isolator');
        $this->_command  = Phake::partialMock(__NAMESPACE__ . '\AbstractTravisCommand', $this->_isolator, 'travis:abstract');

        parent::setUp();
    }

    public function testIsEnabled()
    {
        $this->assertFalse($this->_command->isEnabled());

        Phake::when($this->_isolator)
            ->getenv('TRAVIS')
            ->thenReturn('true');

        $this->assertTrue($this->_command->isEnabled());
    }
}
