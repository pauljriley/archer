<?php
namespace Icecave\Archer\Travis;

use Phake;
use PHPUnit_Framework_TestCase;

class TravisClientTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->isolator = Phake::mock('Icecave\Archer\Support\Isolator');
        $this->fileSystem = Phake::mock('Icecave\Archer\FileSystem\FileSystem');
        $this->client = new TravisClient(
            $this->fileSystem,
            $this->isolator
        );
    }

    public function testConstructor()
    {
        $this->assertSame($this->fileSystem, $this->client->fileSystem());
    }

    public function testConstructorDefaults()
    {
        $this->client = new TravisClient;

        $this->assertInstanceOf(
            'Icecave\Archer\FileSystem\FileSystem',
            $this->client->fileSystem()
        );
    }

    public function testPublicKey()
    {
        Phake::when($this->fileSystem)
            ->read(Phake::anyParameters())
            ->thenReturn('{"key": "foo"}')
        ;

        $this->assertSame('foo', $this->client->publicKey('bar', 'baz'));
        Phake::verify($this->fileSystem)
            ->read('https://api.travis-ci.org/repos/bar/baz/key')
        ;
    }

    public function testEncryptEnvironment()
    {
        Phake::when($this->isolator)
            ->openssl_public_encrypt(
                'ARCHER_TOKEN="bar"',
                Phake::setReference('baz'),
                'PUBLIC KEY foo'
            )
            ->thenReturn(true)
        ;
        $actual = $this->client->encryptEnvironment('RSA PUBLIC KEY foo', 'bar');
        $expected = base64_encode('baz');

        $this->assertSame($expected, $actual);
        Phake::verify($this->isolator)->openssl_public_encrypt(
            'ARCHER_TOKEN="bar"',
            null,
            'PUBLIC KEY foo'
        );
    }

    public function testEncrypt()
    {
        Phake::when($this->isolator)
            ->openssl_public_encrypt(
                'bar',
                Phake::setReference('baz'),
                'PUBLIC KEY foo'
            )
            ->thenReturn(true)
        ;
        $actual = $this->client->encrypt('RSA PUBLIC KEY foo', 'bar');
        $expected = base64_encode('baz');

        $this->assertSame($expected, $actual);
        Phake::verify($this->isolator)->openssl_public_encrypt(
            'bar',
            null,
            'PUBLIC KEY foo'
        );
    }

    public function testEncryptFailure()
    {
        Phake::when($this->isolator)
            ->openssl_public_encrypt(Phake::anyParameters())
            ->thenReturn(false)
        ;

        $this->setExpectedException(
            'RuntimeException',
            'Encryption failed.'
        );
        $this->client->encrypt('RSA PUBLIC KEY foo', 'bar');
    }
}
