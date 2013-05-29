<?php
namespace Icecave\Archer\Documentation;

use Eloquent\Liberator\Liberator;
use Phake;
use PHPUnit_Framework_TestCase;
use Sami\Sami;
use stdClass;
use Symfony\Component\Finder\Finder;

class DocumentationGeneratorTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->fileSystem = Phake::mock('Icecave\Archer\FileSystem\FileSystem');
        $this->composerConfigReader = Phake::mock(
            'Icecave\Archer\Configuration\ComposerConfigurationReader'
        );
        $this->isolator = Phake::mock('Icecave\Archer\Support\Isolator');
        $this->generator = Phake::partialMock(
            __NAMESPACE__ . '\DocumentationGenerator',
            $this->fileSystem,
            $this->composerConfigReader,
            $this->isolator
        );

        Phake::when($this->fileSystem)
            ->read(Phake::anyParameters())
            ->thenReturn('{"name": "vendor/project"}');
        Phake::when($this->isolator)
            ->sys_get_temp_dir()
            ->thenReturn('/path/to/tmp');
        Phake::when($this->isolator)
            ->uniqid(Phake::anyParameters())
            ->thenReturn('uniqid');

        $this->composerConfiguration = json_decode(
            '{"autoload": {"psr-0": {"Vendor\\\\Project\\\\SubProject": "lib"}}}'
        );
        $this->finder = Finder::create();
        $this->sami = Phake::mock('Sami\Sami');
        $this->samiProject = Phake::mock('Sami\Project');

        Phake::when($this->composerConfigReader)
            ->read(Phake::anyParameters())
            ->thenReturn($this->composerConfiguration);
        Phake::when($this->sami)
            ->offsetGet('project')
            ->thenReturn($this->samiProject);
    }

    public function testConstructor()
    {
        $this->assertSame($this->fileSystem, $this->generator->fileSystem());
        $this->assertSame(
            $this->composerConfigReader,
            $this->generator->composerConfigReader()
        );
    }

    public function testConstructorDefaults()
    {
        $this->generator = new DocumentationGenerator;

        $this->assertInstanceOf(
            'Icecave\Archer\FileSystem\FileSystem',
            $this->generator->fileSystem()
        );
        $this->assertInstanceOf(
            'Icecave\Archer\Configuration\ComposerConfigurationReader',
            $this->generator->composerConfigReader()
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
        Phake::when($this->fileSystem)
            ->directoryExists('foo/artifacts/documentation/api')
            ->thenReturn(true);
        $this->generator->generate('foo');

        Phake::inOrder(
            Phake::verify($this->generator)->createFinder('/path/to/source'),
            Phake::verify($this->generator)->createSami(
                $this->identicalTo($this->finder),
                array(
                    'title' => 'Project - SubProject API',
                    'default_opened_level' => 3,
                    'build_dir' => 'foo/artifacts/documentation/api',
                    'cache_dir' => '/path/to/tmp/uniqid',
                )
            ),
            Phake::verify($this->fileSystem)->delete(
                'foo/artifacts/documentation/api'
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
        Phake::when($this->fileSystem)
            ->directoryExists('./artifacts/documentation/api')
            ->thenReturn(true);
        $this->generator->generate();

        Phake::inOrder(
            Phake::verify($this->generator)->createFinder('/path/to/source'),
            Phake::verify($this->generator)->createSami(
                $this->identicalTo($this->finder),
                array(
                    'title' => 'Project - SubProject API',
                    'default_opened_level' => 3,
                    'build_dir' => './artifacts/documentation/api',
                    'cache_dir' => '/path/to/tmp/uniqid',
                )
            ),
            Phake::verify($this->fileSystem)->delete(
                './artifacts/documentation/api'
            ),
            Phake::verify($this->samiProject)->update()
        );
    }

    public function testGenerateBuildDirNonExistant()
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
        Phake::when($this->fileSystem)
            ->directoryExists('foo/artifacts/documentation/api')
            ->thenReturn(false);
        $this->generator->generate('foo');

        Phake::inOrder(
            Phake::verify($this->generator)->createFinder('/path/to/source'),
            Phake::verify($this->generator)->createSami(
                $this->identicalTo($this->finder),
                array(
                    'title' => 'Project - SubProject API',
                    'default_opened_level' => 3,
                    'build_dir' => 'foo/artifacts/documentation/api',
                    'cache_dir' => '/path/to/tmp/uniqid',
                )
            ),
            Phake::verify($this->samiProject)->update()
        );
        Phake::verify($this->fileSystem, Phake::never())->delete(
            'foo/artifacts/documentation/api'
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

    public function testProjectNameWithSingleNamespace()
    {
        $this->composerConfiguration = json_decode(
            '{"autoload": {"psr-0": {"Project": "lib"}}}'
        );
        $generator = Liberator::liberate($this->generator);

        $this->assertSame(
            'Project',
            $generator->projectName($this->composerConfiguration)
        );
    }

    public function testProjectNameFallback()
    {
        $this->composerConfiguration = json_decode(
            '{"name": "vendor/project"}'
        );
        $generator = Liberator::liberate($this->generator);

        $this->assertSame(
            'vendor/project',
            $generator->projectName($this->composerConfiguration)
        );
    }

    public function testOpenedLevelWithSingleNamespace()
    {
        $this->composerConfiguration = json_decode(
            '{"autoload": {"psr-0": {"Project": "lib"}}}'
        );
        $generator = Liberator::liberate($this->generator);

        $this->assertSame(
            1,
            $generator->openedLevel($this->composerConfiguration)
        );
    }

    public function testOpenedLevelFallback()
    {
        $this->composerConfiguration = json_decode(
            '{"name": "vendor/project"}'
        );
        $generator = Liberator::liberate($this->generator);

        $this->assertSame(
            2,
            $generator->openedLevel($this->composerConfiguration)
        );
    }

    public function testOpenedLevelFallbackNoEntries()
    {
        $this->composerConfiguration = json_decode(
            '{"autoload": {"psr-0": {}}}'
        );
        $generator = Liberator::liberate($this->generator);

        $this->assertSame(
            2,
            $generator->openedLevel($this->composerConfiguration)
        );
    }

    public function testOpenedLevelFallbackNamespaceTooShort()
    {
        $this->composerConfiguration = json_decode(
            '{"autoload": {"psr-0": {"": "lib"}}}'
        );
        $generator = Liberator::liberate($this->generator);

        $this->assertSame(
            2,
            $generator->openedLevel($this->composerConfiguration)
        );
    }

    public function testProjectNameFailureUndefined()
    {
        $generator = Liberator::liberate($this->generator);

        $this->setExpectedException('RuntimeException');
        $generator->projectName(new stdClass);
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
