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

    public function testPublicKeyCache()
    {
        Phake::when($this->_fileSystem)
            ->fileExists('/path/to/project/.travis.key')
            ->thenReturn(false);

        $this->assertNull($this->_manager->publicKeyCache('/path/to/project'));

        Phake::verify($this->_fileSystem)->fileExists('/path/to/project/.travis.key');
    }

    public function testPublicKeyCacheExists()
    {
        Phake::when($this->_fileSystem)
            ->fileExists('/path/to/project/.travis.key')
            ->thenReturn(true);

        Phake::when($this->_fileSystem)
            ->read('/path/to/project/.travis.key')
            ->thenReturn('<key data>');

        $this->assertSame('<key data>', $this->_manager->publicKeyCache('/path/to/project'));

        Phake::inOrder(
            Phake::verify($this->_fileSystem)->fileExists('/path/to/project/.travis.key'),
            Phake::verify($this->_fileSystem)->read('/path/to/project/.travis.key')
        );
    }

    public function testSetPublicKeyCache()
    {
        Phake::when($this->_fileSystem)
            ->fileExists('/path/to/project/.travis.key')
            ->thenReturn(false);

        $this->assertTrue($this->_manager->setPublicKeyCache('/path/to/project', '<key data>'));

        Phake::inOrder(
            Phake::verify($this->_fileSystem)->fileExists('/path/to/project/.travis.key'),
            Phake::verify($this->_fileSystem)->write('/path/to/project/.travis.key', '<key data>')
        );
    }

    public function testSetPublicKeyCacheSame()
    {
        Phake::when($this->_fileSystem)
            ->fileExists('/path/to/project/.travis.key')
            ->thenReturn(true);

        Phake::when($this->_fileSystem)
            ->read('/path/to/project/.travis.key')
            ->thenReturn('<key data>');

        $this->assertFalse($this->_manager->setPublicKeyCache('/path/to/project', '<key data>'));

        Phake::inOrder(
            Phake::verify($this->_fileSystem)->fileExists('/path/to/project/.travis.key'),
            Phake::verify($this->_fileSystem)->read('/path/to/project/.travis.key')
        );

        Phake::verify($this->_fileSystem, Phake::never())->write('/path/to/project/.travis.key', '<key data>');
    }

    public function testSetPublicKeyCacheDelete()
    {
        Phake::when($this->_fileSystem)
            ->fileExists('/path/to/project/.travis.key')
            ->thenReturn(true);

        Phake::when($this->_fileSystem)
            ->read('/path/to/project/.travis.key')
            ->thenReturn('<key data>');

        $this->assertTrue($this->_manager->setPublicKeyCache('/path/to/project', null));

        Phake::inOrder(
            Phake::verify($this->_fileSystem)->fileExists('/path/to/project/.travis.key'),
            Phake::verify($this->_fileSystem)->read('/path/to/project/.travis.key'),
            Phake::verify($this->_fileSystem)->delete('/path/to/project/.travis.key')
        );
    }

    public function testSecureEnvironmentCache()
    {
        Phake::when($this->_fileSystem)
            ->fileExists('/path/to/project/.travis.env')
            ->thenReturn(false);

        $this->assertNull($this->_manager->secureEnvironmentCache('/path/to/project'));

        Phake::verify($this->_fileSystem)->fileExists('/path/to/project/.travis.env');
    }

    public function testSecureEnvironmentCacheExists()
    {
        Phake::when($this->_fileSystem)
            ->fileExists('/path/to/project/.travis.env')
            ->thenReturn(true);

        Phake::when($this->_fileSystem)
            ->read('/path/to/project/.travis.env')
            ->thenReturn('<env data>');

        $this->assertSame('<env data>', $this->_manager->secureEnvironmentCache('/path/to/project'));

        Phake::inOrder(
            Phake::verify($this->_fileSystem)->fileExists('/path/to/project/.travis.env'),
            Phake::verify($this->_fileSystem)->read('/path/to/project/.travis.env')
        );
    }

    public function testSetSecureEnvironmentCache()
    {
        Phake::when($this->_fileSystem)
            ->fileExists('/path/to/project/.travis.env')
            ->thenReturn(false);

        $this->assertTrue($this->_manager->setSecureEnvironmentCache('/path/to/project', '<env data>'));

        Phake::inOrder(
            Phake::verify($this->_fileSystem)->fileExists('/path/to/project/.travis.env'),
            Phake::verify($this->_fileSystem)->write('/path/to/project/.travis.env', '<env data>')
        );
    }

    public function testSetSecureEnvironmentCacheSame()
    {
        Phake::when($this->_fileSystem)
            ->fileExists('/path/to/project/.travis.env')
            ->thenReturn(true);

        Phake::when($this->_fileSystem)
            ->read('/path/to/project/.travis.env')
            ->thenReturn('<env data>');

        $this->assertFalse($this->_manager->setSecureEnvironmentCache('/path/to/project', '<env data>'));

        Phake::inOrder(
            Phake::verify($this->_fileSystem)->fileExists('/path/to/project/.travis.env'),
            Phake::verify($this->_fileSystem)->read('/path/to/project/.travis.env')
        );

        Phake::verify($this->_fileSystem, Phake::never())->write('/path/to/project/.travis.env', '<env data>');
    }

    public function testSetSecureEnvironmentCacheDelete()
    {
        Phake::when($this->_fileSystem)
            ->fileExists('/path/to/project/.travis.env')
            ->thenReturn(true);

        Phake::when($this->_fileSystem)
            ->read('/path/to/project/.travis.env')
            ->thenReturn('<env data>');

        $this->assertTrue($this->_manager->setSecureEnvironmentCache('/path/to/project', null));

        Phake::inOrder(
            Phake::verify($this->_fileSystem)->fileExists('/path/to/project/.travis.env'),
            Phake::verify($this->_fileSystem)->read('/path/to/project/.travis.env'),
            Phake::verify($this->_fileSystem)->delete('/path/to/project/.travis.env')
        );
    }

    public function testUpdateConfig()
    {
        Phake::when($this->_fileFinder)
            ->find(Phake::anyParameters())
            ->thenReturn('/real/path/to/template');

        Phake::when($this->_fileSystem)
            ->read(Phake::anyParameters())
            ->thenReturn('<template content: {token-env}>');

        $result = $this->_manager->updateConfig('/path/to/archer', '/path/to/project');

        Phake::inOrder(
            Phake::verify($this->_fileSystem)->copy('/path/to/archer/res/travis/travis.install.php', '/path/to/project/.travis.install'),
            Phake::verify($this->_fileSystem)->chmod('/path/to/project/.travis.install', 0755),
            Phake::verify($this->_fileFinder)->find(array('/path/to/project/test/travis.tpl.yml'), '/path/to/archer/res/travis/travis.tpl.yml'),
            Phake::verify($this->_fileSystem)->read('/real/path/to/template'),
            Phake::verify($this->_fileSystem)->write('/path/to/project/.travis.yml', '<template content: >')
        );

        $this->assertFalse($result);
    }

    public function testUpdateConfigWithOAuth()
    {
        Phake::when($this->_fileSystem)
            ->fileExists('/path/to/project/.travis.env')
            ->thenReturn(true);

        Phake::when($this->_fileSystem)
            ->read('/path/to/project/.travis.env')
            ->thenReturn('<env data>');

        Phake::when($this->_fileFinder)
            ->find(Phake::anyParameters())
            ->thenReturn('/real/path/to/template');

        Phake::when($this->_fileSystem)
            ->read('/real/path/to/template')
            ->thenReturn('<template content: {token-env}>');

        $result = $this->_manager->updateConfig('/path/to/archer', '/path/to/project');

        Phake::inOrder(
            Phake::verify($this->_fileSystem)->copy('/path/to/archer/res/travis/travis.install.php', '/path/to/project/.travis.install'),
            Phake::verify($this->_fileSystem)->chmod('/path/to/project/.travis.install', 0755),
            Phake::verify($this->_fileFinder)->find(array('/path/to/project/test/travis.tpl.yml'), '/path/to/archer/res/travis/travis.tpl.yml'),
            Phake::verify($this->_fileSystem)->read('/real/path/to/template'),
            Phake::verify($this->_fileSystem)->write('/path/to/project/.travis.yml', '<template content: - secure: "<env data>">')
        );

        $this->assertTrue($result);
    }
}
