<?php
namespace Icecave\Archer\FileSystem\Exception;

use Exception;
use PHPUnit_Framework_TestCase;

class WriteExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $previous = new Exception;
        $exception = new WriteException('foo', $previous);

        $this->assertSame("Unable to write to 'foo'.", $exception->getMessage());
        $this->assertSame('foo', $exception->path());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
