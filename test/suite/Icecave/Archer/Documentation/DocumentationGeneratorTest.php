<?php
namespace Icecave\Archer\Documentation;

use Eloquent\Liberator\Liberator;
use Phake;
use PHPUnit_Framework_TestCase;
use Sami\Sami;
use Symfony\Component\Finder\Finder;

class DocumentationGeneratorTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->fileSystem = Phake::mock('Icecave\Archer\FileSystem\FileSystem');
        $this->isolator = Phake::mock('Icecave\Archer\Support\Isolator');
        $this->generator = Phake::partialMock(
            __NAMESPACE__ . '\DocumentationGenerator',
            $this->fileSystem,
            $this->isolator
        );

        Phake::when($this->fileSystem)
            ->read(Phake::anyParameters())
            ->thenReturn('{"name": "vendor/project"}');
        Phake::when($this->isolator)
            ->sys_get_temp_dir()
            ->thenReturn('/path/to/tmp');

        $this->finder = Finder::create();
        $this->sami = Phake::mock('Sami\Sami');
        $this->samiProject = Phake::mock('Sami\Project');

        Phake::when($this->sami)
            ->offsetGet('project')
            ->thenReturn($this->samiProject);
    }

    public function testConstructor()
    {
        $this->assertSame($this->fileSystem, $this->generator->fileSystem());
    }

    public function testConstructorDefaults()
    {
        $this->generator = new DocumentationGenerator;

        $this->assertInstanceOf(
            'Icecave\Archer\FileSystem\FileSystem',
            $this->generator->fileSystem()
        );
    }

    public function testGenerate()
    {
        Phake::when($this->generator)
            ->sourcePath(Phake::anyParameters())
            ->thenReturn('/path/to/source');
        Phake::when($this->generator)
            ->createFinder(Phake::anyParameters())
            ->thenReturn($this->finder);
        Phake::when($this->generator)
            ->createSami(Phake::anyParameters())
            ->thenReturn($this->sami);
        $this->generator->generate('foo');

        Phake::inOrder(
            Phake::verify($this->generator)->createFinder('/path/to/source'),
            Phake::verify($this->fileSystem)->read('foo/composer.json'),
            Phake::verify($this->generator)->createSami(
                $this->identicalTo($this->finder),
                array(
                    'title' => 'vendor/project API',
                    'build_dir' => 'foo/artifacts/documentation/api',
                    'cache_dir' => '/path/to/tmp/archer-sami-cache',
                )
            ),
            Phake::verify($this->samiProject)->update()
        );
    }

    public function testGenerateDefaultPath()
    {
        Phake::when($this->generator)
            ->sourcePath(Phake::anyParameters())
            ->thenReturn('/path/to/source');
        Phake::when($this->generator)
            ->createFinder(Phake::anyParameters())
            ->thenReturn($this->finder);
        Phake::when($this->generator)
            ->createSami(Phake::anyParameters())
            ->thenReturn($this->sami);
        $this->generator->generate();

        Phake::inOrder(
            Phake::verify($this->generator)->createFinder('/path/to/source'),
            Phake::verify($this->fileSystem)->read('./composer.json'),
            Phake::verify($this->generator)->createSami(
                $this->identicalTo($this->finder),
                array(
                    'title' => 'vendor/project API',
                    'build_dir' => './artifacts/documentation/api',
                    'cache_dir' => '/path/to/tmp/archer-sami-cache',
                )
            ),
            Phake::verify($this->samiProject)->update()
        );
    }

    public function testSourcePath()
    {
        Phake::when($this->fileSystem)
            ->directoryExists(Phake::anyParameters())
            ->thenReturn(false)
            ->thenReturn(true);

        $this->assertSame(
            'foo/lib',
            Liberator::liberate($this->generator)->sourcePath('foo')
        );
        $this->assertSame(
            'foo/src',
            Liberator::liberate($this->generator)->sourcePath('foo')
        );
    }

    public function testProjectNameFailureUndefined()
    {
        Phake::when($this->fileSystem)
            ->read(Phake::anyParameters())
            ->thenReturn('{}');
        $generator = Liberator::liberate($this->generator);

        $this->setExpectedException('RuntimeException');
        $generator->projectName('foo');
    }

    public function testCreateFinder()
    {
        $finder = Liberator::liberate($this->generator)->createFinder(__DIR__);
        $expected = Finder::create()->in(__DIR__);

        $this->assertEquals($expected, $finder);
    }

    public function testCreateSami()
    {
        $sami = Liberator::liberate($this->generator)
            ->createSami($this->finder, array('title' => 'foo'));
        $expected = new Sami($this->finder, array('title' => 'foo'));

        $this->assertEquals($expected, $sami);
    }

    public function testPopErrorHandlers()
    {
        $handlerA = function () { return 'A'; };
        $handlerB = function () { return 'B'; };
        $handlerStack = array($handlerA, $handlerB);
        Phake::when($this->isolator)
            ->set_error_handler(Phake::anyParameters())
            ->thenGetReturnByLambda(function ($handler) use (&$handlerStack) {
                return array_pop($handlerStack);
            });
        $expected = array_reverse($handlerStack);

        $this->assertSame(
            $expected,
            Liberator::liberate($this->generator)->popErrorHandlers()
        );
        $setVerification = Phake::verify($this->isolator, Phake::times(3))
            ->set_error_handler(function() {});
        $restoreVerification = Phake::verify($this->isolator, Phake::times(6))
            ->restore_error_handler();
        Phake::inOrder(
            $setVerification,
            $restoreVerification,
            $restoreVerification,
            $setVerification,
            $restoreVerification,
            $restoreVerification,
            $setVerification,
            $restoreVerification,
            $restoreVerification
        );
    }

    public function testPushErrorHandlers()
    {
        $handlerA = function () { return 'A'; };
        $handlerB = function () { return 'B'; };
        $handlerStack = array($handlerB, $handlerA);
        Liberator::liberate($this->generator)->pushErrorHandlers($handlerStack);

        Phake::inOrder(
            Phake::verify($this->isolator)->set_error_handler(
                $this->identicalTo($handlerA)
            ),
            Phake::verify($this->isolator)->set_error_handler(
                $this->identicalTo($handlerB)
            )
        );
    }
}
