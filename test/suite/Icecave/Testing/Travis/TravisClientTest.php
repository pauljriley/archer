<?php
namespace Icecave\Testing\Travis;

use Phake;
use PHPUnit_Framework_TestCase;

class TravisClientTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_isolator = Phake::mock('Icecave\Testing\Support\Isolator');
        $this->_fileSystem = Phake::mock('Icecave\Testing\FileSystem\FileSystem');
        $this->_client = new TravisClient(
            $this->_fileSystem,
            $this->_isolator
        );
    }

    public function testConstructor()
    {
        $this->assertSame($this->_fileSystem, $this->_client->fileSystem());
    }

    public function testConstructorDefaults()
    {
        $this->_client = new TravisClient;

        $this->assertInstanceOf(
            'Icecave\Testing\FileSystem\FileSystem',
            $this->_client->fileSystem()
        );
    }

    public function testPublicKey()
    {
        Phake::when($this->_fileSystem)
            ->read(Phake::anyParameters())
            ->thenReturn('{"key": "foo"}')
        ;

        $this->assertSame('foo', $this->_client->publicKey('bar', 'baz'));
        Phake::verify($this->_fileSystem)
            ->read('https://api.travis-ci.org/repos/bar/baz/key')
        ;
    }

    public function testEncryptEnvironment()
    {
        Phake::when($this->_isolator)
            ->openssl_public_encrypt(
                'ICT_TOKEN="bar"',
                Phake::setReference('baz'),
                'PUBLIC KEY foo'
            )
            ->thenReturn(true)
        ;
        $actual = $this->_client->encryptEnvironment('RSA PUBLIC KEY foo', 'bar');
        $expected = base64_encode('baz');

        $this->assertSame($expected, $actual);
        Phake::verify($this->_isolator)->openssl_public_encrypt(
            'ICT_TOKEN="bar"',
            null,
            'PUBLIC KEY foo'
        );
    }

    public function testEncrypt()
    {
        Phake::when($this->_isolator)
            ->openssl_public_encrypt(
                'bar',
                Phake::setReference('baz'),
                'PUBLIC KEY foo'
            )
            ->thenReturn(true)
        ;
        $actual = $this->_client->encrypt('RSA PUBLIC KEY foo', 'bar');
        $expected = base64_encode('baz');

        $this->assertSame($expected, $actual);
        Phake::verify($this->_isolator)->openssl_public_encrypt(
            'bar',
            null,
            'PUBLIC KEY foo'
        );
    }

    public function testEncryptFailure()
    {
        Phake::when($this->_isolator)
            ->openssl_public_encrypt(Phake::anyParameters())
            ->thenReturn(false)
        ;

        $this->setExpectedException(
            'RuntimeException',
            'Encryption failed.'
        );
        $this->_client->encrypt('RSA PUBLIC KEY foo', 'bar');
    }
}
