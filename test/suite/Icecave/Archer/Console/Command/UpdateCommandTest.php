<?php
namespace Icecave\Archer\Console\Command;

use Icecave\Archer\Console\Application;
use Icecave\Archer\FileSystem\Exception\ReadException;
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
        $this->_processA             = Phake::mock('Symfony\Component\Process\Process');
        $this->_processB             = Phake::mock('Symfony\Component\Process\Process');
        $this->_processFactory       = Phake::mock('Icecave\Archer\Process\ProcessFactory');

        $this->_application = new Application('/path/to/archer');

        $this->_helperSet = Phake::mock('Symfony\Component\Console\Helper\HelperSet');
        $this->_dialogHelper = Phake::mock('Symfony\Component\Console\Helper\DialogHelper');
        $this->_hiddenInputHelper = Phake::mock('Icecave\Archer\Console\Helper\HiddenInputHelper');
        Phake::when($this->_helperSet)
            ->get('dialog')
            ->thenReturn($this->_dialogHelper)
        ;
        Phake::when($this->_helperSet)
            ->get('hidden-input')
            ->thenReturn($this->_hiddenInputHelper)
        ;

        $this->_command = new UpdateCommand(
            $this->_dotFilesManager,
            $this->_configReaderFactory,
            $this->_travisClient,
            $this->_travisConfigManager,
            $this->_processFactory
        );

        $this->_command->setApplication($this->_application);
        $this->_command->setHelperSet($this->_helperSet);

        $this->_output = Phake::mock('Symfony\Component\Console\Output\OutputInterface');

        Phake::when($this->_configReader)
            ->isGitHubRepository()
            ->thenReturn(true);

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

        Phake::when($this->_processFactory)
            ->createFromArray(Phake::anyParameters())
            ->thenReturn($this->_processA)
            ->thenReturn($this->_processB);

        Phake::when($this->_processA)
            ->isSuccessful(Phake::anyParameters())
            ->thenReturn(true);

        Phake::when($this->_processB)
            ->isSuccessful(Phake::anyParameters())
            ->thenReturn(true);
    }

    public function testConstructor()
    {
        $this->assertSame($this->_dotFilesManager, $this->_command->dotFilesManager());
        $this->assertSame($this->_configReaderFactory, $this->_command->configReaderFactory());
        $this->assertSame($this->_travisClient, $this->_command->travisClient());
        $this->assertSame($this->_travisConfigManager, $this->_command->travisConfigManager());
        $this->assertSame($this->_processFactory, $this->_command->processFactory());
    }

    public function testConstructorDefaults()
    {
        $this->_command = new UpdateCommand;

        $this->assertInstanceOf(
            'Icecave\Archer\Git\GitDotFilesManager',
            $this->_command->dotFilesManager()
        );
        $this->assertInstanceOf(
            'Icecave\Archer\Git\GitConfigReaderFactory',
            $this->_command->configReaderFactory()
        );
        $this->assertInstanceOf(
            'Icecave\Archer\Travis\TravisClient',
            $this->_command->travisClient()
        );
        $this->assertInstanceOf(
            'Icecave\Archer\Travis\TravisConfigManager',
            $this->_command->travisConfigManager()
        );
        $this->assertInstanceOf(
            'Icecave\Archer\Process\ProcessFactory',
            $this->_command->processFactory()
        );
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
            Phake::verify($this->_output)->writeln('')
        );

        Phake::verifyNoInteraction($this->_travisClient);
    }

    public function testExecuteWithNonGitHubRepository()
    {
        Phake::when($this->_configReader)
            ->isGitHubRepository()
            ->thenReturn(false);

        $input = new StringInput('update /path/to/project');

        $this->_command->run($input, $this->_output);

        Phake::inOrder(
            Phake::verify($this->_dotFilesManager)->updateDotFiles('/path/to/archer', '/path/to/project'),
            Phake::verify($this->_output)->writeln('Updated <info>.gitignore</info>.')
        );

        Phake::verifyNoInteraction($this->_travisClient);
        Phake::verifyNoInteraction($this->_travisConfigManager);
        Phake::verifyNoInteraction($this->_processFactory);
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

    public function testExecuteWithAuthorizeExistingToken()
    {
        $input = new StringInput('update --authorize --username foo --password bar /path/to/project');

        Phake::when($this->_processA)
            ->getOutput(Phake::anyParameters())
            ->thenReturn("1584201: b1a94b90073382b330f601ef198bb0729b0168aa Archer (API) [repo] https://github.com/IcecaveStudios/archer\n")
        ;

        $this->_command->run($input, $this->_output);

        Phake::inOrder(
            Phake::verify($this->_dotFilesManager)->updateDotFiles('/path/to/archer', '/path/to/project'),
            Phake::verify($this->_output)->writeln('Updated <info>.gitignore</info>.'),
            Phake::verify($this->_processFactory)->createFromArray(Phake::capture($processAArguments)),
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
            Phake::verify($this->_output)->writeln('')
        );
        $this->assertSame(array(
            '/path/to/archer/bin/woodhouse',
            'github:list-auth',
            '--name',
            '/^Archer$/',
            '--url',
            '~^https://github\.com/IcecaveStudios/archer$~',
            '--username',
            'foo',
            '--password',
            'bar',
        ), $processAArguments);
    }

    public function testExecuteWithAuthorizeNewToken()
    {
        $input = new StringInput('update --authorize --username foo --password bar /path/to/project');

        Phake::when($this->_processA)
            ->getOutput(Phake::anyParameters())
            ->thenReturn("\n")
        ;
        Phake::when($this->_processB)
            ->getOutput(Phake::anyParameters())
            ->thenReturn("1584201: b1a94b90073382b330f601ef198bb0729b0168aa Archer (API) [repo] https://github.com/IcecaveStudios/archer\n")
        ;

        $this->_command->run($input, $this->_output);

        Phake::inOrder(
            Phake::verify($this->_dotFilesManager)->updateDotFiles('/path/to/archer', '/path/to/project'),
            Phake::verify($this->_output)->writeln('Updated <info>.gitignore</info>.'),
            Phake::verify($this->_processFactory)->createFromArray(Phake::capture($processAArguments)->when($this->contains('github:list-auth'))),
            Phake::verify($this->_processFactory)->createFromArray(Phake::capture($processBArguments)->when($this->contains('github:create-auth'))),
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
            Phake::verify($this->_output)->writeln('')
        );
        $this->assertSame(array(
            '/path/to/archer/bin/woodhouse',
            'github:list-auth',
            '--name',
            '/^Archer$/',
            '--url',
            '~^https://github\.com/IcecaveStudios/archer$~',
            '--username',
            'foo',
            '--password',
            'bar',
        ), $processAArguments);
        $this->assertSame(array(
            '/path/to/archer/bin/woodhouse',
            'github:create-auth',
            '--name',
            'Archer',
            '--url',
            'https://github.com/IcecaveStudios/archer',
            '--username',
            'foo',
            '--password',
            'bar',
        ), $processBArguments);
    }

    public function testExecuteWithAuthorizeInteractiveCredentials()
    {
        $input = new StringInput('update --authorize /path/to/project');

        Phake::when($this->_dialogHelper)
            ->ask(Phake::anyParameters())
            ->thenReturn('foo')
        ;
        Phake::when($this->_hiddenInputHelper)
            ->askHiddenResponse(Phake::anyParameters())
            ->thenReturn('bar')
        ;
        Phake::when($this->_processA)
            ->getOutput(Phake::anyParameters())
            ->thenReturn("1584201: b1a94b90073382b330f601ef198bb0729b0168aa Archer (API) [repo] https://github.com/IcecaveStudios/archer\n")
        ;

        $this->_command->run($input, $this->_output);

        Phake::inOrder(
            Phake::verify($this->_dotFilesManager)->updateDotFiles('/path/to/archer', '/path/to/project'),
            Phake::verify($this->_output)->writeln('Updated <info>.gitignore</info>.'),
            Phake::verify($this->_processFactory)->createFromArray(Phake::capture($processAArguments)),
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
            Phake::verify($this->_output)->writeln('')
        );
        $this->assertSame(array(
            '/path/to/archer/bin/woodhouse',
            'github:list-auth',
            '--name',
            '/^Archer$/',
            '--url',
            '~^https://github\.com/IcecaveStudios/archer$~',
            '--username',
            'foo',
            '--password',
            'bar',
        ), $processAArguments);
    }

    public function testExecuteWithAuthorizeFailureMultipleAuthorizations()
    {
        $input = new StringInput('update --authorize --username foo --password bar /path/to/project');

        Phake::when($this->_processA)
            ->getOutput(Phake::anyParameters())
            ->thenReturn(
                "1584201: b1a94b90073382b330f601ef198bb0729b0168aa Archer (API) [repo] https://github.com/IcecaveStudios/archer\n" .
                "1584202: c1a94b90073382b330f601ef198bb0729b0168aa Archer (API) [repo] https://github.com/IcecaveStudios/archer\n"
            )
        ;

        $this->setExpectedException(
            'RuntimeException',
            'Mutiple Archer GitHub authorizations found. Delete redundant authorizations before continuing.'
        );
        $this->_command->run($input, $this->_output);
    }

    public function testExecuteWithAuthorizeFailureIncorrectScope()
    {
        $input = new StringInput('update --authorize --username foo --password bar /path/to/project');

        Phake::when($this->_processA)
            ->getOutput(Phake::anyParameters())
            ->thenReturn("1584201: b1a94b90073382b330f601ef198bb0729b0168aa Archer (API) [user, repo] https://github.com/IcecaveStudios/archer\n")
        ;

        $this->setExpectedException(
            'RuntimeException',
            'Archer GitHub authorization has incorrect scope. Expected [repo], but actual token scope is [user, repo].'
        );
        $this->_command->run($input, $this->_output);
    }

    public function testExecuteWithAuthorizeFailureParseError()
    {
        $input = new StringInput('update --authorize --username foo --password bar /path/to/project');

        Phake::when($this->_processA)
            ->getOutput(Phake::anyParameters())
            ->thenReturn('baz')
        ;

        $this->setExpectedException(
            'RuntimeException',
            'Unable to parse authorization token.'
        );
        $this->_command->run($input, $this->_output);
    }

    public function testExecuteWithAuthorizeFailureWoodhouseError()
    {
        $input = new StringInput('update --authorize --username foo --password bar /path/to/project');

        Phake::when($this->_processA)
            ->isSuccessful(Phake::anyParameters())
            ->thenReturn(false)
        ;

        $this->setExpectedException(
            'RuntimeException',
            'Failed to execute authorization management command (github:list-auth).'
        );
        $this->_command->run($input, $this->_output);
    }

    public function testExecuteWithSuppliedToken()
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
            Phake::verify($this->_output)->writeln('')
        );
    }

    public function testExecuteWithSuppliedTokenAndExistingKey()
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
            Phake::verify($this->_output)->writeln('')
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
            Phake::verify($this->_output)->writeln('')
        );
    }

    public function testExecuteFailureUnsyncedRepo()
    {
        $input = new StringInput('update --auth-token b1a94b90073382b330f601ef198bb0729b0168aa --update-public-key /path/to/project');

        Phake::when($this->_travisClient)
            ->publicKey(Phake::anyParameters())
            ->thenThrow(new ReadException('foo'));

        $this->setExpectedException(
            'RuntimeException',
            'Unable to retrieve the public key for repository owner/repo-name. Check that the repository has been synced to Travis CI.'
        );
        $this->_command->run($input, $this->_output);
    }

    public function testExecuteWithInvalidToken()
    {
        $input = new StringInput('update --auth-token XXX');

        $exitCode = $this->_command->run($input, $this->_output);

        $this->assertSame(1, $exitCode);

        Phake::inOrder(
            Phake::verify($this->_output)->writeln('Invalid GitHub OAuth token <comment>"XXX"</comment>.'),
            Phake::verify($this->_output)->writeln('')
        );
    }

    public function testExecuteWithUpdatePublicKeyAndNoToken()
    {
        $input = new StringInput('update --update-public-key');

        $exitCode = $this->_command->run($input, $this->_output);

        $this->assertSame(1, $exitCode);

        Phake::inOrder(
            Phake::verify($this->_output)->writeln('Can not update public key without --authorize or --auth-token.'),
            Phake::verify($this->_output)->writeln('')
        );
    }
}
