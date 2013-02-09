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

        $this->_githubClient = Phake::mock('Icecave\Archer\GitHub\GitHubClient');
        $this->_isolator = Phake::mock('Icecave\Archer\Support\Isolator');

        $this->_application = new Application('/path/to/archer');

        $this->_command = new BuildCommand(
            $this->_githubClient,
            $this->_isolator
        );

        $this->_command->setApplication($this->_application);

        $this->_input = new StringInput('travis:build /path/to/project');
        $this->_output = Phake::mock('Symfony\Component\Console\Output\OutputInterface');

        Phake::when($this->_githubClient)
            ->defaultBranch(Phake::anyParameters())
            ->thenReturn('master');

        Phake::when($this->_isolator)
            ->getenv('TRAVIS_BRANCH')
            ->thenReturn('master');

        Phake::when($this->_isolator)
            ->getenv('TRAVIS_BUILD_NUMBER')
            ->thenReturn('543');

        Phake::when($this->_isolator)
            ->getenv('TRAVIS_REPO_SLUG')
            ->thenReturn('Vendor/package');

        Phake::when($this->_isolator)
            ->getenv('ARCHER_PUBLISH_VERSION')
            ->thenReturn('5.4');

        Phake::when($this->_isolator)
            ->getenv('ARCHER_TOKEN')
            ->thenReturn('b1a94b90073382b330f601ef198bb0729b0168aa');
    }

    public function testExecute()
    {
        $expectedTestCommand = '/path/to/archer/bin/archer test';

        Phake::when($this->_isolator)
            ->passthru(
                $expectedTestCommand,
                Phake::setReference(123)
            )
            ->thenReturn(null);

        $exitCode = $this->_command->run($this->_input, $this->_output);

        Phake::verify($this->_isolator)->passthru($expectedTestCommand, 255);
        Phake::verifyNoInteraction($this->_githubClient);

        $this->assertSame(123, $exitCode);
    }

    public function testExecuteWithPublishVersionButWrongBranch()
    {
        $expectedTestCommand = '/path/to/archer/bin/archer test';

        Phake::when($this->_isolator)
            ->getenv('TRAVIS_PHP_VERSION')
            ->thenReturn('5.4');

        Phake::when($this->_isolator)
            ->getenv('TRAVIS_BRANCH')
            ->thenReturn('feature/some-thing');

        Phake::when($this->_isolator)
            ->passthru(
                $expectedTestCommand,
                Phake::setReference(123)
            )
            ->thenReturn(null);

        $exitCode = $this->_command->run($this->_input, $this->_output);

        Phake::inOrder(
            Phake::verify($this->_githubClient)->setAuthToken('b1a94b90073382b330f601ef198bb0729b0168aa'),
            Phake::verify($this->_githubClient)->defaultBranch('Vendor', 'package'),
            Phake::verify($this->_isolator)->passthru($expectedTestCommand, 255)
        );

        $this->assertSame(123, $exitCode);
    }

    public function testExecuteWithPublish()
    {
        $expectedTestCommand = '/path/to/archer/bin/archer coverage';

        $expectedWoodhouseCommand  = "/path/to/archer/bin/woodhouse publish 'Vendor/package'";
        $expectedWoodhouseCommand .= ' /path/to/project/artifacts:artifacts';
        $expectedWoodhouseCommand .= ' --message "Publishing artifacts from build #543."';
        $expectedWoodhouseCommand .= ' --coverage-image artifacts/images/coverage.png';
        $expectedWoodhouseCommand .= ' --coverage-phpunit artifacts/tests/coverage/coverage.txt';
        $expectedWoodhouseCommand .= ' --build-status-image artifacts/images/build-status.png';
        $expectedWoodhouseCommand .= ' --build-status-tap artifacts/tests/report.tap';
        $expectedWoodhouseCommand .= ' --auth-token-env ARCHER_TOKEN';
        $expectedWoodhouseCommand .= ' --no-interaction';
        $expectedWoodhouseCommand .= ' --verbose';

        Phake::when($this->_isolator)
            ->getenv('TRAVIS_PHP_VERSION')
            ->thenReturn('5.4');

        Phake::when($this->_isolator)
            ->passthru(
                $expectedTestCommand,
                Phake::setReference(0)
            )
            ->thenReturn(null);

        Phake::when($this->_isolator)
            ->passthru(
                $expectedWoodhouseCommand,
                Phake::setReference(222)
            )
            ->thenReturn(null);

        $exitCode = $this->_command->run($this->_input, $this->_output);

        Phake::inOrder(
            Phake::verify($this->_githubClient)->setAuthToken('b1a94b90073382b330f601ef198bb0729b0168aa'),
            Phake::verify($this->_githubClient)->defaultBranch('Vendor', 'package'),
            Phake::verify($this->_isolator)->passthru($expectedTestCommand, 255),
            Phake::verify($this->_isolator)->passthru($expectedWoodhouseCommand, 255)
        );

        $this->assertSame(222, $exitCode);
    }

    public function testExecuteWithAndTestFailure()
    {
        $expectedTestCommand = '/path/to/archer/bin/archer coverage';

        $expectedWoodhouseCommand  = "/path/to/archer/bin/woodhouse publish 'Vendor/package'";
        $expectedWoodhouseCommand .= ' /path/to/project/artifacts:artifacts';
        $expectedWoodhouseCommand .= ' --message "Publishing artifacts from build #543."';
        $expectedWoodhouseCommand .= ' --coverage-image artifacts/images/coverage.png';
        $expectedWoodhouseCommand .= ' --coverage-phpunit artifacts/tests/coverage/coverage.txt';
        $expectedWoodhouseCommand .= ' --build-status-image artifacts/images/build-status.png';
        $expectedWoodhouseCommand .= ' --build-status-tap artifacts/tests/report.tap';
        $expectedWoodhouseCommand .= ' --auth-token-env ARCHER_TOKEN';
        $expectedWoodhouseCommand .= ' --no-interaction';
        $expectedWoodhouseCommand .= ' --verbose';

        Phake::when($this->_isolator)
            ->getenv('TRAVIS_PHP_VERSION')
            ->thenReturn('5.4');

        Phake::when($this->_isolator)
            ->passthru(
                $expectedTestCommand,
                Phake::setReference(111)
            )
            ->thenReturn(null);

        Phake::when($this->_isolator)
            ->passthru(
                $expectedWoodhouseCommand,
                Phake::setReference(222)
            )
            ->thenReturn(null);

        $exitCode = $this->_command->run($this->_input, $this->_output);

        Phake::inOrder(
            Phake::verify($this->_githubClient)->setAuthToken('b1a94b90073382b330f601ef198bb0729b0168aa'),
            Phake::verify($this->_githubClient)->defaultBranch('Vendor', 'package'),
            Phake::verify($this->_isolator)->passthru($expectedTestCommand, 255),
            Phake::verify($this->_isolator)->passthru($expectedWoodhouseCommand, 255)
        );

        $this->assertSame(111, $exitCode);
    }
}
