<?php
namespace Icecave\Archer\Console\Command\Travis;

use Icecave\Archer\Console\Application;
use Icecave\Archer\Support\Isolator;
use PHPUnit_Framework_TestCase;
use Phake;
use Symfony\Component\Console\Input\StringInput;

class BuildCommandTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->githubClient = Phake::mock('Icecave\Archer\GitHub\GitHubClient');
        $this->coverallsClient = Phake::mock('Icecave\Archer\Coveralls\CoverallsClient');
        $this->fileSystem = Phake::mock('Icecave\Archer\FileSystem\FileSystem');
        $this->isolator = Phake::mock('Icecave\Archer\Support\Isolator');

        $this->application = new Application('/path/to/archer');

        $this->command = new BuildCommand(
            $this->githubClient,
            $this->coverallsClient,
            $this->fileSystem,
            $this->isolator
        );

        $this->command->setApplication($this->application);

        $this->input = new StringInput('travis:build /path/to/project');
        $this->output = Phake::mock('Symfony\Component\Console\Output\OutputInterface');

        Phake::when($this->githubClient)
            ->defaultBranch(Phake::anyParameters())
            ->thenReturn('master');

        Phake::when($this->isolator)
            ->getenv('TRAVIS_BRANCH')
            ->thenReturn('master');

        Phake::when($this->isolator)
            ->getenv('TRAVIS_BUILD_NUMBER')
            ->thenReturn('543');

        Phake::when($this->isolator)
            ->getenv('TRAVIS_REPO_SLUG')
            ->thenReturn('Vendor/package');

        Phake::when($this->isolator)
            ->getenv('ARCHER_PUBLISH_VERSION')
            ->thenReturn('5.4');

        Phake::when($this->isolator)
            ->getenv('ARCHER_TOKEN')
            ->thenReturn('b1a94b90073382b330f601ef198bb0729b0168aa');
    }

    public function testConstructor()
    {
        $this->assertSame($this->githubClient, $this->command->githubClient());
        $this->assertSame($this->coverallsClient, $this->command->coverallsClient());
    }

    public function testConstructorDefaults()
    {
        $this->command = new BuildCommand;

        $this->assertInstanceOf(
            'Icecave\Archer\GitHub\GitHubClient',
            $this->command->githubClient()
        );

        $this->assertInstanceOf(
            'Icecave\Archer\Coveralls\CoverallsClient',
            $this->command->coverallsClient()
        );
    }

    public function testExecute()
    {
        $expectedTestCommand = '/path/to/archer/bin/archer test';

        Phake::when($this->isolator)
            ->passthru(
                $expectedTestCommand,
                Phake::setReference(123)
            )
            ->thenReturn(null);

        $exitCode = $this->command->run($this->input, $this->output);

        Phake::verify($this->isolator)->passthru($expectedTestCommand, 255);
        Phake::verify($this->githubClient)->setUserAgent($this->application->getName() . '/' . $this->application->getVersion());

        $this->assertSame(123, $exitCode);
    }

    public function testExecuteWithPublishVersionButWrongBranch()
    {
        $expectedTestCommand = '/path/to/archer/bin/archer test';

        Phake::when($this->isolator)
            ->getenv('TRAVIS_PHP_VERSION')
            ->thenReturn('5.4');

        Phake::when($this->isolator)
            ->getenv('TRAVIS_BRANCH')
            ->thenReturn('feature/some-thing');

        Phake::when($this->isolator)
            ->passthru(
                $expectedTestCommand,
                Phake::setReference(123)
            )
            ->thenReturn(null);

        $exitCode = $this->command->run($this->input, $this->output);

        Phake::inOrder(
            Phake::verify($this->githubClient)->setAuthToken('b1a94b90073382b330f601ef198bb0729b0168aa'),
            Phake::verify($this->githubClient)->defaultBranch('Vendor', 'package'),
            Phake::verify($this->isolator)->passthru($expectedTestCommand, 255)
        );

        $this->assertSame(123, $exitCode);
    }

    public function testExecuteWithPublishVersionButNoToken()
    {
        $expectedTestCommand = '/path/to/archer/bin/archer test';

        Phake::when($this->isolator)
            ->getenv('TRAVIS_PHP_VERSION')
            ->thenReturn('5.4');

        Phake::when($this->isolator)
            ->getenv('ARCHER_TOKEN')
            ->thenReturn(false);

        Phake::when($this->isolator)
            ->passthru(
                $expectedTestCommand,
                Phake::setReference(123)
            )
            ->thenReturn(null);

        $exitCode = $this->command->run($this->input, $this->output);

        Phake::verify($this->githubClient, Phake::never())->setAuthToken(Phake::anyParameters());
        Phake::verify($this->isolator)->passthru($expectedTestCommand, 255);

        $this->assertSame(123, $exitCode);
    }

    public function testExecuteWithPublish()
    {
        $expectedTestCommand = '/path/to/archer/bin/archer coverage';
        $expectedDocumentationCommand = '/path/to/archer/bin/archer documentation';

        $expectedWoodhouseCommand  = "/path/to/archer/bin/woodhouse publish 'Vendor/package'";
        $expectedWoodhouseCommand .= ' /path/to/project/artifacts:artifacts';
        $expectedWoodhouseCommand .= ' --message "Publishing artifacts from build 543."';
        $expectedWoodhouseCommand .= ' --auth-token-env ARCHER_TOKEN';
        $expectedWoodhouseCommand .= ' --no-interaction';
        $expectedWoodhouseCommand .= ' --verbose';
        $expectedWoodhouseCommand .= ' --coverage-image artifacts/images/coverage.png';
        $expectedWoodhouseCommand .= ' --coverage-phpunit artifacts/tests/coverage/coverage.txt';
        $expectedWoodhouseCommand .= ' --image-theme travis/variable-width';
        $expectedWoodhouseCommand .= ' --image-theme icecave/regular';

        Phake::when($this->coverallsClient)
            ->exists('Vendor', 'package')
            ->thenReturn(false);

        Phake::when($this->isolator)
            ->getenv('TRAVIS_PHP_VERSION')
            ->thenReturn('5.4');

        Phake::when($this->isolator)
            ->passthru(
                $expectedTestCommand,
                Phake::setReference(0)
            )
            ->thenReturn(null);

        Phake::when($this->isolator)
            ->passthru(
                $expectedDocumentationCommand,
                Phake::setReference(0)
            )
            ->thenReturn(null);

        Phake::when($this->isolator)
            ->passthru(
                $expectedWoodhouseCommand,
                Phake::setReference(0)
            )
            ->thenReturn(null);

        $exitCode = $this->command->run($this->input, $this->output);

        Phake::inOrder(
            Phake::verify($this->githubClient)->setAuthToken('b1a94b90073382b330f601ef198bb0729b0168aa'),
            Phake::verify($this->githubClient)->defaultBranch('Vendor', 'package'),
            Phake::verify($this->output)->write('Checking for Coveralls... '),
            Phake::verify($this->coverallsClient)->exists('Vendor', 'package'),
            Phake::verify($this->output)->writeln('not enabled.'),
            Phake::verify($this->isolator)->passthru($expectedTestCommand, 255),
            Phake::verify($this->isolator)->passthru($expectedDocumentationCommand, 255),
            Phake::verify($this->isolator)->passthru($expectedWoodhouseCommand, 255)
        );

        $this->assertSame(0, $exitCode);
    }

    public function testExecuteWithPublishErrorCode()
    {
        $expectedTestCommand = '/path/to/archer/bin/archer coverage';
        $expectedDocumentationCommand = '/path/to/archer/bin/archer documentation';

        $expectedWoodhouseCommand  = "/path/to/archer/bin/woodhouse publish 'Vendor/package'";
        $expectedWoodhouseCommand .= ' /path/to/project/artifacts:artifacts';
        $expectedWoodhouseCommand .= ' --message "Publishing artifacts from build 543."';
        $expectedWoodhouseCommand .= ' --auth-token-env ARCHER_TOKEN';
        $expectedWoodhouseCommand .= ' --no-interaction';
        $expectedWoodhouseCommand .= ' --verbose';
        $expectedWoodhouseCommand .= ' --coverage-image artifacts/images/coverage.png';
        $expectedWoodhouseCommand .= ' --coverage-phpunit artifacts/tests/coverage/coverage.txt';
        $expectedWoodhouseCommand .= ' --image-theme travis/variable-width';
        $expectedWoodhouseCommand .= ' --image-theme icecave/regular';

        Phake::when($this->coverallsClient)
            ->exists('Vendor', 'package')
            ->thenReturn(false);

        Phake::when($this->isolator)
            ->getenv('TRAVIS_PHP_VERSION')
            ->thenReturn('5.4');

        Phake::when($this->isolator)
            ->passthru(
                $expectedTestCommand,
                Phake::setReference(0)
            )
            ->thenReturn(null);

        Phake::when($this->isolator)
            ->passthru(
                $expectedDocumentationCommand,
                Phake::setReference(0)
            )
            ->thenReturn(null);

        Phake::when($this->isolator)
            ->passthru(
                $expectedWoodhouseCommand,
                Phake::setReference(222)
            )
            ->thenReturn(null);

        $exitCode = $this->command->run($this->input, $this->output);

        Phake::inOrder(
            Phake::verify($this->githubClient)->setAuthToken('b1a94b90073382b330f601ef198bb0729b0168aa'),
            Phake::verify($this->githubClient)->defaultBranch('Vendor', 'package'),
            Phake::verify($this->output)->write('Checking for Coveralls... '),
            Phake::verify($this->coverallsClient)->exists('Vendor', 'package'),
            Phake::verify($this->output)->writeln('not enabled.'),
            Phake::verify($this->isolator)->passthru($expectedTestCommand, 255),
            Phake::verify($this->isolator)->passthru($expectedDocumentationCommand, 255),
            Phake::verify($this->isolator)->passthru($expectedWoodhouseCommand, 255)
        );

        $this->assertSame(222, $exitCode);
    }

    public function testExecuteWithPublishAndCoveralls()
    {
        $expectedTestCommand = '/path/to/archer/bin/archer coverage';
        $expectedDocumentationCommand = '/path/to/archer/bin/archer documentation';

        $expectedCoverallsCommand = '/path/to/project/vendor/bin/coveralls --config';
        $expectedCoverallsCommand .= " '/path/to/project/.coveralls.yml'";

        $expectedWoodhouseCommand  = "/path/to/archer/bin/woodhouse publish 'Vendor/package'";
        $expectedWoodhouseCommand .= ' /path/to/project/artifacts:artifacts';
        $expectedWoodhouseCommand .= ' --message "Publishing artifacts from build 543."';
        $expectedWoodhouseCommand .= ' --auth-token-env ARCHER_TOKEN';
        $expectedWoodhouseCommand .= ' --no-interaction';
        $expectedWoodhouseCommand .= ' --verbose';

        Phake::when($this->coverallsClient)
            ->exists('Vendor', 'package')
            ->thenReturn(true);

        Phake::when($this->isolator)
            ->getenv('TRAVIS_PHP_VERSION')
            ->thenReturn('5.4');

        Phake::when($this->isolator)
            ->passthru(
                $expectedTestCommand,
                Phake::setReference(0)
            )
            ->thenReturn(null);

        Phake::when($this->isolator)
            ->passthru(
                $expectedDocumentationCommand,
                Phake::setReference(0)
            )
            ->thenReturn(null);

        Phake::when($this->isolator)
            ->passthru(
                $expectedCoverallsCommand,
                Phake::setReference(0)
            )
            ->thenReturn(null);

        Phake::when($this->isolator)
            ->passthru(
                $expectedWoodhouseCommand,
                Phake::setReference(222)
            )
            ->thenReturn(null);

        $exitCode = $this->command->run($this->input, $this->output);

        Phake::inOrder(
            Phake::verify($this->githubClient)->setAuthToken('b1a94b90073382b330f601ef198bb0729b0168aa'),
            Phake::verify($this->githubClient)->defaultBranch('Vendor', 'package'),
            Phake::verify($this->output)->write('Checking for Coveralls... '),
            Phake::verify($this->coverallsClient)->exists('Vendor', 'package'),
            Phake::verify($this->output)->writeln('enabled.'),
            Phake::verify($this->isolator)->passthru($expectedTestCommand, 255),
            Phake::verify($this->output)->write('Publishing Coveralls data... '),
            Phake::verify($this->isolator)->copy('/path/to/archer/res/coveralls/coveralls.yml', '/path/to/project/.coveralls.yml'),
            Phake::verify($this->isolator)->passthru($expectedCoverallsCommand, 255),
            Phake::verify($this->output)->writeln('done.'),
            Phake::verify($this->isolator)->passthru($expectedDocumentationCommand, 255),
            Phake::verify($this->fileSystem)->delete('/path/to/project/artifacts/tests'),
            Phake::verify($this->isolator)->passthru($expectedWoodhouseCommand, 255)
        );

        $this->assertSame(222, $exitCode);
    }

    public function testExecuteWithPublishAndTestFailure()
    {
        $expectedTestCommand = '/path/to/archer/bin/archer coverage';
        $expectedDocumentationCommand = '/path/to/archer/bin/archer documentation';

        $expectedWoodhouseCommand  = "/path/to/archer/bin/woodhouse publish 'Vendor/package'";
        $expectedWoodhouseCommand .= ' /path/to/project/artifacts:artifacts';
        $expectedWoodhouseCommand .= ' --message "Publishing artifacts from build 543."';
        $expectedWoodhouseCommand .= ' --auth-token-env ARCHER_TOKEN';
        $expectedWoodhouseCommand .= ' --no-interaction';
        $expectedWoodhouseCommand .= ' --verbose';
        $expectedWoodhouseCommand .= ' --coverage-image artifacts/images/coverage.png';
        $expectedWoodhouseCommand .= ' --coverage-phpunit artifacts/tests/coverage/coverage.txt';
        $expectedWoodhouseCommand .= ' --image-theme travis/variable-width';
        $expectedWoodhouseCommand .= ' --image-theme icecave/regular';

        Phake::when($this->coverallsClient)
            ->exists('Vendor', 'package')
            ->thenReturn(false);

        Phake::when($this->isolator)
            ->getenv('TRAVIS_PHP_VERSION')
            ->thenReturn('5.4');

        Phake::when($this->isolator)
            ->passthru(
                $expectedTestCommand,
                Phake::setReference(111)
            )
            ->thenReturn(null);

        Phake::when($this->isolator)
            ->passthru(
                $expectedDocumentationCommand,
                Phake::setReference(0)
            )
            ->thenReturn(null);

        Phake::when($this->isolator)
            ->passthru(
                $expectedWoodhouseCommand,
                Phake::setReference(222)
            )
            ->thenReturn(null);

        $exitCode = $this->command->run($this->input, $this->output);

        Phake::inOrder(
            Phake::verify($this->githubClient)->setAuthToken('b1a94b90073382b330f601ef198bb0729b0168aa'),
            Phake::verify($this->githubClient)->defaultBranch('Vendor', 'package'),
            Phake::verify($this->output)->write('Checking for Coveralls... '),
            Phake::verify($this->coverallsClient)->exists('Vendor', 'package'),
            Phake::verify($this->output)->writeln('not enabled.'),
            Phake::verify($this->isolator)->passthru($expectedTestCommand, 255),
            Phake::verify($this->isolator)->passthru($expectedDocumentationCommand, 255),
            Phake::verify($this->isolator)->passthru($expectedWoodhouseCommand, 255)
        );

        $this->assertSame(111, $exitCode);
    }

    public function testExecuteWithPublishAndDocumentationFailure()
    {
        $expectedTestCommand = '/path/to/archer/bin/archer coverage';
        $expectedDocumentationCommand = '/path/to/archer/bin/archer documentation';

        $expectedWoodhouseCommand  = "/path/to/archer/bin/woodhouse publish 'Vendor/package'";
        $expectedWoodhouseCommand .= ' /path/to/project/artifacts:artifacts';
        $expectedWoodhouseCommand .= ' --message "Publishing artifacts from build 543."';
        $expectedWoodhouseCommand .= ' --auth-token-env ARCHER_TOKEN';
        $expectedWoodhouseCommand .= ' --no-interaction';
        $expectedWoodhouseCommand .= ' --verbose';
        $expectedWoodhouseCommand .= ' --coverage-image artifacts/images/coverage.png';
        $expectedWoodhouseCommand .= ' --coverage-phpunit artifacts/tests/coverage/coverage.txt';
        $expectedWoodhouseCommand .= ' --image-theme travis/variable-width';
        $expectedWoodhouseCommand .= ' --image-theme icecave/regular';

        Phake::when($this->coverallsClient)
            ->exists('Vendor', 'package')
            ->thenReturn(false);

        Phake::when($this->isolator)
            ->getenv('TRAVIS_PHP_VERSION')
            ->thenReturn('5.4');

        Phake::when($this->isolator)
            ->passthru(
                $expectedTestCommand,
                Phake::setReference(0)
            )
            ->thenReturn(null);

        Phake::when($this->isolator)
            ->passthru(
                $expectedDocumentationCommand,
                Phake::setReference(111)
            )
            ->thenReturn(null);

        Phake::when($this->isolator)
            ->passthru(
                $expectedWoodhouseCommand,
                Phake::setReference(222)
            )
            ->thenReturn(null);

        $exitCode = $this->command->run($this->input, $this->output);

        Phake::inOrder(
            Phake::verify($this->githubClient)->setAuthToken('b1a94b90073382b330f601ef198bb0729b0168aa'),
            Phake::verify($this->githubClient)->defaultBranch('Vendor', 'package'),
            Phake::verify($this->output)->write('Checking for Coveralls... '),
            Phake::verify($this->coverallsClient)->exists('Vendor', 'package'),
            Phake::verify($this->output)->writeln('not enabled.'),
            Phake::verify($this->isolator)->passthru($expectedTestCommand, 255),
            Phake::verify($this->isolator)->passthru($expectedDocumentationCommand, 255),
            Phake::verify($this->isolator)->passthru($expectedWoodhouseCommand, 255)
        );

        $this->assertSame(111, $exitCode);
    }

    public function testExecuteWithPublishAndCoverallsPublishFailure()
    {
        $expectedTestCommand = '/path/to/archer/bin/archer coverage';
        $expectedDocumentationCommand = '/path/to/archer/bin/archer documentation';

        $expectedCoverallsCommand = '/path/to/project/vendor/bin/coveralls --config';
        $expectedCoverallsCommand .= " '/path/to/project/.coveralls.yml'";

        $expectedWoodhouseCommand  = "/path/to/archer/bin/woodhouse publish 'Vendor/package'";
        $expectedWoodhouseCommand .= ' /path/to/project/artifacts:artifacts';
        $expectedWoodhouseCommand .= ' --message "Publishing artifacts from build 543."';
        $expectedWoodhouseCommand .= ' --auth-token-env ARCHER_TOKEN';
        $expectedWoodhouseCommand .= ' --no-interaction';
        $expectedWoodhouseCommand .= ' --verbose';

        Phake::when($this->coverallsClient)
            ->exists('Vendor', 'package')
            ->thenReturn(true);

        Phake::when($this->isolator)
            ->getenv('TRAVIS_PHP_VERSION')
            ->thenReturn('5.4');

        Phake::when($this->isolator)
            ->passthru(
                $expectedTestCommand,
                Phake::setReference(0)
            )
            ->thenReturn(null);

        Phake::when($this->isolator)
            ->passthru(
                $expectedDocumentationCommand,
                Phake::setReference(0)
            )
            ->thenReturn(null);

        Phake::when($this->isolator)
            ->passthru(
                $expectedCoverallsCommand,
                Phake::setReference(222)
            )
            ->thenReturn(null);

        Phake::when($this->isolator)
            ->passthru(
                $expectedWoodhouseCommand,
                Phake::setReference(333)
            )
            ->thenReturn(null);

        $exitCode = $this->command->run($this->input, $this->output);

        Phake::inOrder(
            Phake::verify($this->githubClient)->setAuthToken('b1a94b90073382b330f601ef198bb0729b0168aa'),
            Phake::verify($this->githubClient)->defaultBranch('Vendor', 'package'),
            Phake::verify($this->output)->write('Checking for Coveralls... '),
            Phake::verify($this->coverallsClient)->exists('Vendor', 'package'),
            Phake::verify($this->output)->writeln('enabled.'),
            Phake::verify($this->isolator)->passthru($expectedTestCommand, 255),
            Phake::verify($this->output)->write('Publishing Coveralls data... '),
            Phake::verify($this->isolator)->copy('/path/to/archer/res/coveralls/coveralls.yml', '/path/to/project/.coveralls.yml'),
            Phake::verify($this->isolator)->passthru($expectedCoverallsCommand, 255),
            Phake::verify($this->output)->writeln('failed.'),
            Phake::verify($this->isolator)->passthru($expectedDocumentationCommand, 255),
            Phake::verify($this->isolator)->passthru($expectedWoodhouseCommand, 255)
        );

        $this->assertSame(222, $exitCode);
    }

    public function testExecuteWithCoverallsButNoPublish()
    {
        $expectedTestCommand = '/path/to/archer/bin/archer coverage';

        $expectedCoverallsCommand = '/path/to/project/vendor/bin/coveralls --config';
        $expectedCoverallsCommand .= " '/path/to/project/.coveralls.yml'";

        Phake::when($this->coverallsClient)
            ->exists('Vendor', 'package')
            ->thenReturn(true);

        Phake::when($this->isolator)
            ->getenv('TRAVIS_PHP_VERSION')
            ->thenReturn('5.4');

        Phake::when($this->isolator)
            ->getenv('ARCHER_TOKEN')
            ->thenReturn('');

        Phake::when($this->isolator)
            ->passthru(
                $expectedTestCommand,
                Phake::setReference(0)
            )
            ->thenReturn(null);

        Phake::when($this->isolator)
            ->passthru(
                $expectedCoverallsCommand,
                Phake::setReference(111)
            )
            ->thenReturn(null);

        $exitCode = $this->command->run($this->input, $this->output);

        Phake::inOrder(
            Phake::verify($this->output)->write('Checking for Coveralls... '),
            Phake::verify($this->coverallsClient)->exists('Vendor', 'package'),
            Phake::verify($this->output)->writeln('enabled.'),
            Phake::verify($this->isolator)->passthru($expectedTestCommand, 255),
            Phake::verify($this->output)->write('Publishing Coveralls data... '),
            Phake::verify($this->isolator)->copy('/path/to/archer/res/coveralls/coveralls.yml', '/path/to/project/.coveralls.yml'),
            Phake::verify($this->isolator)->passthru($expectedCoverallsCommand, 255),
            Phake::verify($this->output)->writeln('failed.')
        );

        $this->assertSame(111, $exitCode);
    }
}
