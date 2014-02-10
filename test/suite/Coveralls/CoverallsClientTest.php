<?php
namespace Icecave\Archer\Coveralls;

use Phake;
use PHPUnit_Framework_TestCase;

class CoverallsClientTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->isolator = Phake::mock('Icecave\Archer\Support\Isolator');
        Phake::when($this->isolator)
            ->json_decode(Phake::anyParameters())
            ->thenCallParent();
        Phake::when($this->isolator)
            ->json_last_error(Phake::anyParameters())
            ->thenCallParent();
        $this->client = new CoverallsClient($this->isolator);
    }

    public function testExists()
    {
        Phake::when($this->isolator)
            ->file_get_contents(Phake::anyParameters())
            ->thenReturn('{}');

        $this->assertTrue($this->client->exists('vendor', 'project'));
        Phake::verify($this->isolator)
            ->file_get_contents('https://coveralls.io/r/vendor/project.json');
    }

    public function testExistsFailureHttpError()
    {
        Phake::when($this->isolator)
            ->file_get_contents(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'));

        $this->assertFalse($this->client->exists('vendor', 'project'));
        Phake::verify($this->isolator)
            ->file_get_contents('https://coveralls.io/r/vendor/project.json');
    }

    public function testExistsFailureJsonError()
    {
        Phake::when($this->isolator)
            ->file_get_contents(Phake::anyParameters())
            ->thenReturn('{');

        $this->assertFalse($this->client->exists('vendor', 'project'));
        Phake::verify($this->isolator)
            ->file_get_contents('https://coveralls.io/r/vendor/project.json');
    }
}
