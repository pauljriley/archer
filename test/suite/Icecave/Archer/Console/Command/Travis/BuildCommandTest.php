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
            ->getenv('ARCHER_REPO_OWNER')
            ->thenReturn('Vendor');

        Phake::when($this->_isolator)
            ->getenv('ARCHER_REPO_NAME')
            ->thenReturn('package');

        Phake::when($this->_isolator)
            ->getenv('ARCHER_TOKEN')
            ->thenReturn('b1a94b90073382b330f601ef198bb0729b0168aa');
    }

    public function testExecute()
    {
        $input = new StringInput('travis:build /path/to/project');

        Phake::when($this->_isolator)
            ->passthru(
                '/path/to/archer/bin/archer test --no-interaction',
                Phake::setReference(123)
            )
            ->thenReturn(null);

        $exitCode = $this->_command->run($input, $this->_output);

        Phake::verify($this->_isolator)->passthru('/path/to/archer/bin/archer test --no-interaction', 1);
        Phake::verifyNoInteraction($this->_githubClient);

        $this->assertSame(123, $exitCode);
    }

    public function testExecuteWithPublish()
    {
        Phake::when($this->_isolator)
            ->getenv('ARCHER_PUBLISH')
            ->thenReturn('true');

        $input = new StringInput('travis:build /path/to/project');

        $expectedWoodhouseCommand  = '/path/to/archer/res/bin/woodhouse publish Vendor/package artifacts:artifacts';
        $expectedWoodhouseCommand .= ' --message "Publishing artifacts from build #543."';
        $expectedWoodhouseCommand .= ' --coverage-image artifacts/images/coverage.png';
        $expectedWoodhouseCommand .= ' --coverage-phpunit artifacts/tests/coverage/coverage.txt';
        $expectedWoodhouseCommand .= ' --build-status-image artifacts/images/build-status.png';
        $expectedWoodhouseCommand .= ' --build-status-tap artifacts/tests/report.tap';
        $expectedWoodhouseCommand .= ' --auth-token-env ARCHER_TOKEN';
        $expectedWoodhouseCommand .= ' --no-interaction';
        $expectedWoodhouseCommand .= ' --verbose';

        Phake::when($this->_isolator)
            ->passthru(
                $expectedWoodhouseCommand,
                Phake::setReference(123)
            )
            ->thenReturn(null);

        $exitCode = $this->_command->run($input, $this->_output);

        Phake::inOrder(
            Phake::verify($this->_githubClient)->setAuthToken('b1a94b90073382b330f601ef198bb0729b0168aa'),
            Phake::verify($this->_githubClient)->defaultBranch('Vendor', 'package'),
            Phake::verify($this->_isolator)->passthru('/path/to/archer/bin/archer coverage --no-interaction'),
            Phake::verify($this->_isolator)->passthru($expectedWoodhouseCommand, 1)
        );

        $this->assertSame(123, $exitCode);
    }

    public function testExecuteWithPublishWithNonDefaultBranch()
    {
        Phake::when($this->_isolator)
            ->getenv('TRAVIS_BRANCH')
            ->thenReturn('feature/some-thing');

        Phake::when($this->_isolator)
            ->getenv('ARCHER_PUBLISH')
            ->thenReturn('true');

        $input = new StringInput('travis:build /path/to/project');

        $exitCode = $this->_command->run($input, $this->_output);

        Phake::inOrder(
            Phake::verify($this->_githubClient)->setAuthToken('b1a94b90073382b330f601ef198bb0729b0168aa'),
            Phake::verify($this->_githubClient)->defaultBranch('Vendor', 'package'),
            Phake::verify($this->_output)->writeln('Skipping artifact publication for branch "feature/some-thing".')
        );

        $this->assertSame(0, $exitCode);
    }
}
