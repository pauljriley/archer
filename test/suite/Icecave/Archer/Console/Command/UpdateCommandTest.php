<?php
namespace Icecave\Archer\Console\Command;

use Icecave\Archer\Console\Application;
use PHPUnit_Framework_TestCase;
use Phake;
use Symfony\Component\Console\Input\StringInput;

class UpdateCommandTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_dotFilesManager      = Phake::mock('Icecave\Archer\Git\GitDotFilesManager');
        $this->_configReader         = Phake::mock('Icecave\Archer\Git\GitConfigReader');
        $this->_configReaderFactory  = Phake::mock('Icecave\Archer\Git\GitConfigReaderFactory');
        $this->_travisClient         = Phake::mock('Icecave\Archer\Travis\TravisClient');
        $this->_travisConfigManager  = Phake::mock('Icecave\Archer\Travis\TravisConfigManager');

        $this->_application = new Application('/path/to/archer');

        $this->_command = new UpdateCommand(
            $this->_dotFilesManager,
            $this->_configReaderFactory,
            $this->_travisClient,
            $this->_travisConfigManager
        );

        $this->_command->setApplication($this->_application);

        $this->_output = Phake::mock('Symfony\Component\Console\Output\OutputInterface');

        Phake::when($this->_dotFilesManager)
            ->updateDotFiles(Phake::anyParameters())
            ->thenReturn(array('.gitignore' => true, '.gitattributes' => false));

        Phake::when($this->_configReaderFactory)
            ->create(Phake::anyParameters())
            ->thenReturn($this->_configReader);

        Phake::when($this->_configReader)
            ->repositoryOwner()
            ->thenReturn('owner');

        Phake::when($this->_configReader)
            ->repositoryName()
            ->thenReturn('repo-name');

        Phake::when($this->_travisConfigManager)
            ->updateConfig(Phake::anyParameters())
            ->thenReturn(true);

        Phake::when($this->_travisClient)
            ->publicKey(Phake::anyParameters())
            ->thenReturn('<key data>');

        Phake::when($this->_travisClient)
            ->encryptEnvironment(Phake::anyParameters())
            ->thenReturn('<env data>');
    }

    public function testExecute()
    {
        $input = new StringInput('update /path/to/project');

        $this->_command->run($input, $this->_output);

        Phake::inOrder(
            Phake::verify($this->_dotFilesManager)->updateDotFiles('/path/to/archer', '/path/to/project'),
            Phake::verify($this->_output)->writeln('Updated <info>.gitignore</info>.'),
            Phake::verify($this->_configReader)->repositoryOwner(),
            Phake::verify($this->_configReader)->repositoryName(),
            Phake::verify($this->_travisConfigManager)->publicKeyCache('/path/to/project'),
            Phake::verify($this->_travisConfigManager)->updateConfig('/path/to/archer', '/path/to/project'),
            Phake::verify($this->_output)->writeln('Updated <info>.travis.yml</info>.'),
            Phake::verify($this->_output)->writeln('Configuration updated successfully.'),
            Phake::verify($this->_output)->write(PHP_EOL)
        );

        Phake::verifyNoInteraction($this->_travisClient);
    }

    public function testExecuteWithoutArtifactSupport()
    {
        Phake::when($this->_travisConfigManager)
            ->updateConfig(Phake::anyParameters())
            ->thenReturn(false);

        $input = new StringInput('update /path/to/project');

        $this->_command->run($input, $this->_output);

        Phake::inOrder(
            Phake::verify($this->_output)->writeln('Updated <info>.travis.yml</info>.'),
            Phake::verify($this->_output)->writeln('<comment>Artifact publication is not available as no GitHub OAuth token has been configured.</comment>'),
            Phake::verify($this->_output)->writeln('Configuration updated successfully.')
        );

        Phake::verifyNoInteraction($this->_travisClient);
    }

    public function testExecuteWithNewToken()
    {
        $input = new StringInput('update --auth-token b1a94b90073382b330f601ef198bb0729b0168aa /path/to/project');

        $this->_command->run($input, $this->_output);

        Phake::inOrder(
            Phake::verify($this->_dotFilesManager)->updateDotFiles('/path/to/archer', '/path/to/project'),
            Phake::verify($this->_output)->writeln('Updated <info>.gitignore</info>.'),
            Phake::verify($this->_configReader)->repositoryOwner(),
            Phake::verify($this->_configReader)->repositoryName(),
            Phake::verify($this->_travisConfigManager)->publicKeyCache('/path/to/project'),
            Phake::verify($this->_output)->writeln('Fetching public key for <info>owner/repo-name</info>.'),
            Phake::verify($this->_travisClient)->publicKey('owner', 'repo-name'),
            Phake::verify($this->_travisConfigManager)->setPublicKeyCache('/path/to/project', '<key data>'),
            Phake::verify($this->_output)->writeln('Encrypting OAuth token.'),
            Phake::verify($this->_travisClient)->encryptEnvironment('<key data>', 'b1a94b90073382b330f601ef198bb0729b0168aa'),
            Phake::verify($this->_travisConfigManager)->setSecureEnvironmentCache('/path/to/project', '<env data>'),
            Phake::verify($this->_travisConfigManager)->updateConfig('/path/to/archer', '/path/to/project'),
            Phake::verify($this->_output)->writeln('Updated <info>.travis.yml</info>.'),
            Phake::verify($this->_output)->writeln('Configuration updated successfully.'),
            Phake::verify($this->_output)->write(PHP_EOL)
        );
    }

    public function testExecuteWithNewTokenAndExistingKey()
    {
        Phake::when($this->_travisConfigManager)
            ->publicKeyCache(Phake::anyParameters())
            ->thenReturn('<cached key data>');

        $input = new StringInput('update --auth-token b1a94b90073382b330f601ef198bb0729b0168aa /path/to/project');

        $this->_command->run($input, $this->_output);

        Phake::inOrder(
            Phake::verify($this->_dotFilesManager)->updateDotFiles('/path/to/archer', '/path/to/project'),
            Phake::verify($this->_output)->writeln('Updated <info>.gitignore</info>.'),
            Phake::verify($this->_configReader)->repositoryOwner(),
            Phake::verify($this->_configReader)->repositoryName(),
            Phake::verify($this->_travisConfigManager)->publicKeyCache('/path/to/project'),
            Phake::verify($this->_output)->writeln('Encrypting OAuth token.'),
            Phake::verify($this->_travisClient)->encryptEnvironment('<cached key data>', 'b1a94b90073382b330f601ef198bb0729b0168aa'),
            Phake::verify($this->_travisConfigManager)->setSecureEnvironmentCache('/path/to/project', '<env data>'),
            Phake::verify($this->_travisConfigManager)->updateConfig('/path/to/archer', '/path/to/project'),
            Phake::verify($this->_output)->writeln('Updated <info>.travis.yml</info>.'),
            Phake::verify($this->_output)->writeln('Configuration updated successfully.'),
            Phake::verify($this->_output)->write(PHP_EOL)
        );
    }

    public function testExecuteWithUpdatePublicKey()
    {
        $input = new StringInput('update --auth-token b1a94b90073382b330f601ef198bb0729b0168aa --update-public-key /path/to/project');

        $this->_command->run($input, $this->_output);

        Phake::inOrder(
            Phake::verify($this->_dotFilesManager)->updateDotFiles('/path/to/archer', '/path/to/project'),
            Phake::verify($this->_output)->writeln('Updated <info>.gitignore</info>.'),
            Phake::verify($this->_configReader)->repositoryOwner(),
            Phake::verify($this->_configReader)->repositoryName(),
            Phake::verify($this->_travisConfigManager)->publicKeyCache('/path/to/project'),
            Phake::verify($this->_output)->writeln('Fetching public key for <info>owner/repo-name</info>.'),
            Phake::verify($this->_travisClient)->publicKey('owner', 'repo-name'),
            Phake::verify($this->_travisConfigManager)->setPublicKeyCache('/path/to/project', '<key data>'),
            Phake::verify($this->_travisConfigManager)->updateConfig('/path/to/archer', '/path/to/project'),
            Phake::verify($this->_output)->writeln('Updated <info>.travis.yml</info>.'),
            Phake::verify($this->_output)->writeln('Configuration updated successfully.'),
            Phake::verify($this->_output)->write(PHP_EOL)
        );
    }

    public function testExecuteWithInvalidToken()
    {
        $input = new StringInput('update --auth-token XXX');

        $exitCode = $this->_command->run($input, $this->_output);

        $this->assertSame(1, $exitCode);

        Phake::inOrder(
            Phake::verify($this->_output)->writeln('Invalid GitHub OAuth token <comment>"XXX"</comment>.'),
            Phake::verify($this->_output)->write(PHP_EOL)
        );
    }

    public function testExecuteWithUpdatePublicKeyAndNoToken()
    {
        $input = new StringInput('update --update-public-key');

        $exitCode = $this->_command->run($input, $this->_output);

        $this->assertSame(1, $exitCode);

        Phake::inOrder(
            Phake::verify($this->_output)->writeln('Can not update public key without --auth-token.'),
            Phake::verify($this->_output)->write(PHP_EOL)
        );
    }
}
