<?php
namespace Icecave\Archer\Console\Command\Travis;

use Icecave\Archer\Support\Isolator;
use PHPUnit_Framework_TestCase;
use Phake;

class AbstractTravisCommandTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->isolator = Phake::mock('Icecave\Archer\Support\Isolator');
        $this->command  = Phake::partialMock(__NAMESPACE__ . '\AbstractTravisCommand', $this->isolator, 'travis:abstract');

        parent::setUp();
    }

    public function testIsEnabled()
    {
        $this->assertFalse($this->command->isEnabled());

        Phake::when($this->isolator)
            ->getenv('TRAVIS')
            ->thenReturn('true');

        $this->assertTrue($this->command->isEnabled());
    }
}
