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

        $this->dotFilesManager      = Phake::mock('Icecave\Archer\Git\GitDotFilesManager');
        $this->configReader         = Phake::mock('Icecave\Archer\Git\GitConfigReader');
        $this->configReaderFactory  = Phake::mock('Icecave\Archer\Git\GitConfigReaderFactory');
        $this->travisClient         = Phake::mock('Icecave\Archer\Travis\TravisClient');
        $this->travisConfigManager  = Phake::mock('Icecave\Archer\Travis\TravisConfigManager');
        $this->processA             = Phake::mock('Symfony\Component\Process\Process');
        $this->processB             = Phake::mock('Symfony\Component\Process\Process');
        $this->processFactory       = Phake::mock('Icecave\Archer\Process\ProcessFactory');

        $this->application = new Application('/path/to/archer');

        $this->helperSet = Phake::mock('Symfony\Component\Console\Helper\HelperSet');
        $this->dialogHelper = Phake::mock('Symfony\Component\Console\Helper\DialogHelper');
        $this->hiddenInputHelper = Phake::mock('Icecave\Archer\Console\Helper\HiddenInputHelper');
        Phake::when($this->helperSet)
            ->get('dialog')
            ->thenReturn($this->dialogHelper)
        ;
        Phake::when($this->helperSet)
            ->get('hidden-input')
            ->thenReturn($this->hiddenInputHelper)
        ;

        $this->command = new UpdateCommand(
            $this->dotFilesManager,
            $this->configReaderFactory,
            $this->travisClient,
            $this->travisConfigManager,
            $this->processFactory
        );

        $this->command->setApplication($this->application);
        $this->command->setHelperSet($this->helperSet);

        $this->output = Phake::mock('Symfony\Component\Console\Output\OutputInterface');

        Phake::when($this->configReader)
            ->isGitHubRepository()
            ->thenReturn(true);

        Phake::when($this->dotFilesManager)
            ->updateDotFiles(Phake::anyParameters())
            ->thenReturn(array('.gitignore' => true, '.gitattributes' => false));

        Phake::when($this->configReaderFactory)
            ->create(Phake::anyParameters())
            ->thenReturn($this->configReader);

        Phake::when($this->configReader)
            ->repositoryOwner()
            ->thenReturn('owner');

        Phake::when($this->configReader)
            ->repositoryName()
            ->thenReturn('repo-name');

        Phake::when($this->travisConfigManager)
            ->updateConfig(Phake::anyParameters())
            ->thenReturn(true);

        Phake::when($this->travisClient)
            ->publicKey(Phake::anyParameters())
            ->thenReturn('<key data>');

        Phake::when($this->travisClient)
            ->encryptEnvironment(Phake::anyParameters())
            ->thenReturn('<env data>');

        Phake::when($this->processFactory)
            ->createFromArray(Phake::anyParameters())
            ->thenReturn($this->processA)
            ->thenReturn($this->processB);

        Phake::when($this->processA)
            ->isSuccessful(Phake::anyParameters())
            ->thenReturn(true);

        Phake::when($this->processB)
            ->isSuccessful(Phake::anyParameters())
            ->thenReturn(true);
    }

    public function testConstructor()
    {
        $this->assertSame($this->dotFilesManager, $this->command->dotFilesManager());
        $this->assertSame($this->configReaderFactory, $this->command->configReaderFactory());
        $this->assertSame($this->travisClient, $this->command->travisClient());
        $this->assertSame($this->travisConfigManager, $this->command->travisConfigManager());
        $this->assertSame($this->processFactory, $this->command->processFactory());
    }

    public function testConstructorDefaults()
    {
        $this->command = new UpdateCommand;

        $this->assertInstanceOf(
            'Icecave\Archer\Git\GitDotFilesManager',
            $this->command->dotFilesManager()
        );
        $this->assertInstanceOf(
            'Icecave\Archer\Git\GitConfigReaderFactory',
            $this->command->configReaderFactory()
        );
        $this->assertInstanceOf(
            'Icecave\Archer\Travis\TravisClient',
            $this->command->travisClient()
        );
        $this->assertInstanceOf(
            'Icecave\Archer\Travis\TravisConfigManager',
            $this->command->travisConfigManager()
        );
        $this->assertInstanceOf(
            'Icecave\Archer\Process\ProcessFactory',
            $this->command->processFactory()
        );
    }

    public function testExecute()
    {
        $input = new StringInput('update /path/to/project');

        $this->command->run($input, $this->output);

        Phake::inOrder(
            Phake::verify($this->dotFilesManager)->updateDotFiles('/path/to/archer', '/path/to/project'),
            Phake::verify($this->output)->writeln('Updated <info>.gitignore</info>.'),
            Phake::verify($this->configReader)->repositoryOwner(),
            Phake::verify($this->configReader)->repositoryName(),
            Phake::verify($this->travisConfigManager)->publicKeyCache('/path/to/project'),
            Phake::verify($this->travisConfigManager)->updateConfig('/path/to/archer', '/path/to/project'),
            Phake::verify($this->output)->writeln('Updated <info>.travis.yml</info>.'),
            Phake::verify($this->output)->writeln('Configuration updated successfully.'),
            Phake::verify($this->output)->writeln('')
        );

        Phake::verifyNoInteraction($this->travisClient);
    }

    public function testExecuteWithNonGitHubRepository()
    {
        Phake::when($this->configReader)
            ->isGitHubRepository()
            ->thenReturn(false);

        $input = new StringInput('update /path/to/project');

        $this->command->run($input, $this->output);

        Phake::inOrder(
            Phake::verify($this->dotFilesManager)->updateDotFiles('/path/to/archer', '/path/to/project'),
            Phake::verify($this->output)->writeln('Updated <info>.gitignore</info>.')
        );

        Phake::verifyNoInteraction($this->travisClient);
        Phake::verifyNoInteraction($this->travisConfigManager);
        Phake::verifyNoInteraction($this->processFactory);
    }

    public function testExecuteWithoutArtifactSupport()
    {
        Phake::when($this->travisConfigManager)
            ->updateConfig(Phake::anyParameters())
            ->thenReturn(false);

        $input = new StringInput('update /path/to/project');

        $this->command->run($input, $this->output);

        Phake::inOrder(
            Phake::verify($this->output)->writeln('Updated <info>.travis.yml</info>.'),
            Phake::verify($this->output)->writeln('<comment>Artifact publication is not available as no GitHub OAuth token has been configured.</comment>'),
            Phake::verify($this->output)->writeln('Configuration updated successfully.')
        );

        Phake::verifyNoInteraction($this->travisClient);
    }

    public function testExecuteWithAuthorizeExistingToken()
    {
        $input = new StringInput('update --authorize --username foo --password bar /path/to/project');

        Phake::when($this->processA)
            ->getOutput(Phake::anyParameters())
            ->thenReturn("1584201: b1a94b90073382b330f601ef198bb0729b0168aa Archer (API) [repo] https://github.com/IcecaveStudios/archer\n")
        ;

        $this->command->run($input, $this->output);

        Phake::inOrder(
            Phake::verify($this->dotFilesManager)->updateDotFiles('/path/to/archer', '/path/to/project'),
            Phake::verify($this->output)->writeln('Updated <info>.gitignore</info>.'),
            Phake::verify($this->processFactory)->createFromArray(Phake::capture($processAArguments)),
            Phake::verify($this->configReader)->repositoryOwner(),
            Phake::verify($this->configReader)->repositoryName(),
            Phake::verify($this->travisConfigManager)->publicKeyCache('/path/to/project'),
            Phake::verify($this->output)->writeln('Fetching public key for <info>owner/repo-name</info>.'),
            Phake::verify($this->travisClient)->publicKey('owner', 'repo-name'),
            Phake::verify($this->travisConfigManager)->setPublicKeyCache('/path/to/project', '<key data>'),
            Phake::verify($this->output)->writeln('Encrypting OAuth token.'),
            Phake::verify($this->travisClient)->encryptEnvironment('<key data>', 'b1a94b90073382b330f601ef198bb0729b0168aa'),
            Phake::verify($this->travisConfigManager)->setSecureEnvironmentCache('/path/to/project', '<env data>'),
            Phake::verify($this->travisConfigManager)->updateConfig('/path/to/archer', '/path/to/project'),
            Phake::verify($this->output)->writeln('Updated <info>.travis.yml</info>.'),
            Phake::verify($this->output)->writeln('Configuration updated successfully.'),
            Phake::verify($this->output)->writeln('')
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

        Phake::when($this->processA)
            ->getOutput(Phake::anyParameters())
            ->thenReturn("\n")
        ;
        Phake::when($this->processB)
            ->getOutput(Phake::anyParameters())
            ->thenReturn("1584201: b1a94b90073382b330f601ef198bb0729b0168aa Archer (API) [repo] https://github.com/IcecaveStudios/archer\n")
        ;

        $this->command->run($input, $this->output);

        Phake::inOrder(
            Phake::verify($this->dotFilesManager)->updateDotFiles('/path/to/archer', '/path/to/project'),
            Phake::verify($this->output)->writeln('Updated <info>.gitignore</info>.'),
            Phake::verify($this->processFactory)->createFromArray(Phake::capture($processAArguments)->when($this->contains('github:list-auth'))),
            Phake::verify($this->processFactory)->createFromArray(Phake::capture($processBArguments)->when($this->contains('github:create-auth'))),
            Phake::verify($this->configReader)->repositoryOwner(),
            Phake::verify($this->configReader)->repositoryName(),
            Phake::verify($this->travisConfigManager)->publicKeyCache('/path/to/project'),
            Phake::verify($this->output)->writeln('Fetching public key for <info>owner/repo-name</info>.'),
            Phake::verify($this->travisClient)->publicKey('owner', 'repo-name'),
            Phake::verify($this->travisConfigManager)->setPublicKeyCache('/path/to/project', '<key data>'),
            Phake::verify($this->output)->writeln('Encrypting OAuth token.'),
            Phake::verify($this->travisClient)->encryptEnvironment('<key data>', 'b1a94b90073382b330f601ef198bb0729b0168aa'),
            Phake::verify($this->travisConfigManager)->setSecureEnvironmentCache('/path/to/project', '<env data>'),
            Phake::verify($this->travisConfigManager)->updateConfig('/path/to/archer', '/path/to/project'),
            Phake::verify($this->output)->writeln('Updated <info>.travis.yml</info>.'),
            Phake::verify($this->output)->writeln('Configuration updated successfully.'),
            Phake::verify($this->output)->writeln('')
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

        Phake::when($this->dialogHelper)
            ->ask(Phake::anyParameters())
            ->thenReturn('foo')
        ;
        Phake::when($this->hiddenInputHelper)
            ->askHiddenResponse(Phake::anyParameters())
            ->thenReturn('bar')
        ;
        Phake::when($this->processA)
            ->getOutput(Phake::anyParameters())
            ->thenReturn("1584201: b1a94b90073382b330f601ef198bb0729b0168aa Archer (API) [repo] https://github.com/IcecaveStudios/archer\n")
        ;

        $this->command->run($input, $this->output);

        Phake::inOrder(
            Phake::verify($this->dotFilesManager)->updateDotFiles('/path/to/archer', '/path/to/project'),
            Phake::verify($this->output)->writeln('Updated <info>.gitignore</info>.'),
            Phake::verify($this->processFactory)->createFromArray(Phake::capture($processAArguments)),
            Phake::verify($this->configReader)->repositoryOwner(),
            Phake::verify($this->configReader)->repositoryName(),
            Phake::verify($this->travisConfigManager)->publicKeyCache('/path/to/project'),
            Phake::verify($this->output)->writeln('Fetching public key for <info>owner/repo-name</info>.'),
            Phake::verify($this->travisClient)->publicKey('owner', 'repo-name'),
            Phake::verify($this->travisConfigManager)->setPublicKeyCache('/path/to/project', '<key data>'),
            Phake::verify($this->output)->writeln('Encrypting OAuth token.'),
            Phake::verify($this->travisClient)->encryptEnvironment('<key data>', 'b1a94b90073382b330f601ef198bb0729b0168aa'),
            Phake::verify($this->travisConfigManager)->setSecureEnvironmentCache('/path/to/project', '<env data>'),
            Phake::verify($this->travisConfigManager)->updateConfig('/path/to/archer', '/path/to/project'),
            Phake::verify($this->output)->writeln('Updated <info>.travis.yml</info>.'),
            Phake::verify($this->output)->writeln('Configuration updated successfully.'),
            Phake::verify($this->output)->writeln('')
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

        Phake::when($this->processA)
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
        $this->command->run($input, $this->output);
    }

    public function testExecuteWithAuthorizeFailureIncorrectScope()
    {
        $input = new StringInput('update --authorize --username foo --password bar /path/to/project');

        Phake::when($this->processA)
            ->getOutput(Phake::anyParameters())
            ->thenReturn("1584201: b1a94b90073382b330f601ef198bb0729b0168aa Archer (API) [user, repo] https://github.com/IcecaveStudios/archer\n")
        ;

        $this->setExpectedException(
            'RuntimeException',
            'Archer GitHub authorization has incorrect scope. Expected [repo], but actual token scope is [user, repo].'
        );
        $this->command->run($input, $this->output);
    }

    public function testExecuteWithAuthorizeFailureParseError()
    {
        $input = new StringInput('update --authorize --username foo --password bar /path/to/project');

        Phake::when($this->processA)
            ->getOutput(Phake::anyParameters())
            ->thenReturn('baz')
        ;

        $this->setExpectedException(
            'RuntimeException',
            'Unable to parse authorization token.'
        );
        $this->command->run($input, $this->output);
    }

    public function testExecuteWithAuthorizeFailureWoodhouseError()
    {
        $input = new StringInput('update --authorize --username foo --password bar /path/to/project');

        Phake::when($this->processA)
            ->isSuccessful(Phake::anyParameters())
            ->thenReturn(false)
        ;

        $this->setExpectedException(
            'RuntimeException',
            'Failed to execute authorization management command (github:list-auth).'
        );
        $this->command->run($input, $this->output);
    }

    public function testExecuteWithSuppliedToken()
    {
        $input = new StringInput('update --auth-token b1a94b90073382b330f601ef198bb0729b0168aa /path/to/project');

        $this->command->run($input, $this->output);

        Phake::inOrder(
            Phake::verify($this->dotFilesManager)->updateDotFiles('/path/to/archer', '/path/to/project'),
            Phake::verify($this->output)->writeln('Updated <info>.gitignore</info>.'),
            Phake::verify($this->configReader)->repositoryOwner(),
            Phake::verify($this->configReader)->repositoryName(),
            Phake::verify($this->travisConfigManager)->publicKeyCache('/path/to/project'),
            Phake::verify($this->output)->writeln('Fetching public key for <info>owner/repo-name</info>.'),
            Phake::verify($this->travisClient)->publicKey('owner', 'repo-name'),
            Phake::verify($this->travisConfigManager)->setPublicKeyCache('/path/to/project', '<key data>'),
            Phake::verify($this->output)->writeln('Encrypting OAuth token.'),
            Phake::verify($this->travisClient)->encryptEnvironment('<key data>', 'b1a94b90073382b330f601ef198bb0729b0168aa'),
            Phake::verify($this->travisConfigManager)->setSecureEnvironmentCache('/path/to/project', '<env data>'),
            Phake::verify($this->travisConfigManager)->updateConfig('/path/to/archer', '/path/to/project'),
            Phake::verify($this->output)->writeln('Updated <info>.travis.yml</info>.'),
            Phake::verify($this->output)->writeln('Configuration updated successfully.'),
            Phake::verify($this->output)->writeln('')
        );
    }

    public function testExecuteWithSuppliedTokenAndExistingKey()
    {
        Phake::when($this->travisConfigManager)
            ->publicKeyCache(Phake::anyParameters())
            ->thenReturn('<cached key data>');

        $input = new StringInput('update --auth-token b1a94b90073382b330f601ef198bb0729b0168aa /path/to/project');

        $this->command->run($input, $this->output);

        Phake::inOrder(
            Phake::verify($this->dotFilesManager)->updateDotFiles('/path/to/archer', '/path/to/project'),
            Phake::verify($this->output)->writeln('Updated <info>.gitignore</info>.'),
            Phake::verify($this->configReader)->repositoryOwner(),
            Phake::verify($this->configReader)->repositoryName(),
            Phake::verify($this->travisConfigManager)->publicKeyCache('/path/to/project'),
            Phake::verify($this->output)->writeln('Encrypting OAuth token.'),
            Phake::verify($this->travisClient)->encryptEnvironment('<cached key data>', 'b1a94b90073382b330f601ef198bb0729b0168aa'),
            Phake::verify($this->travisConfigManager)->setSecureEnvironmentCache('/path/to/project', '<env data>'),
            Phake::verify($this->travisConfigManager)->updateConfig('/path/to/archer', '/path/to/project'),
            Phake::verify($this->output)->writeln('Updated <info>.travis.yml</info>.'),
            Phake::verify($this->output)->writeln('Configuration updated successfully.'),
            Phake::verify($this->output)->writeln('')
        );
    }

    public function testExecuteWithUpdatePublicKey()
    {
        $input = new StringInput('update --auth-token b1a94b90073382b330f601ef198bb0729b0168aa --update-public-key /path/to/project');

        $this->command->run($input, $this->output);

        Phake::inOrder(
            Phake::verify($this->dotFilesManager)->updateDotFiles('/path/to/archer', '/path/to/project'),
            Phake::verify($this->output)->writeln('Updated <info>.gitignore</info>.'),
            Phake::verify($this->configReader)->repositoryOwner(),
            Phake::verify($this->configReader)->repositoryName(),
            Phake::verify($this->travisConfigManager)->publicKeyCache('/path/to/project'),
            Phake::verify($this->output)->writeln('Fetching public key for <info>owner/repo-name</info>.'),
            Phake::verify($this->travisClient)->publicKey('owner', 'repo-name'),
            Phake::verify($this->travisConfigManager)->setPublicKeyCache('/path/to/project', '<key data>'),
            Phake::verify($this->travisConfigManager)->updateConfig('/path/to/archer', '/path/to/project'),
            Phake::verify($this->output)->writeln('Updated <info>.travis.yml</info>.'),
            Phake::verify($this->output)->writeln('Configuration updated successfully.'),
            Phake::verify($this->output)->writeln('')
        );
    }

    public function testExecuteFailureUnsyncedRepo()
    {
        $input = new StringInput('update --auth-token b1a94b90073382b330f601ef198bb0729b0168aa --update-public-key /path/to/project');

        Phake::when($this->travisClient)
            ->publicKey(Phake::anyParameters())
            ->thenThrow(new ReadException('foo'));

        $this->setExpectedException(
            'RuntimeException',
            'Unable to retrieve the public key for repository owner/repo-name. Check that the repository has been synced to Travis CI.'
        );
        $this->command->run($input, $this->output);
    }

    public function testExecuteWithInvalidToken()
    {
        $input = new StringInput('update --auth-token XXX');

        $exitCode = $this->command->run($input, $this->output);

        $this->assertSame(1, $exitCode);

        Phake::inOrder(
            Phake::verify($this->output)->writeln('Invalid GitHub OAuth token <comment>"XXX"</comment>.'),
            Phake::verify($this->output)->writeln('')
        );
    }

    public function testExecuteWithUpdatePublicKeyAndNoToken()
    {
        $input = new StringInput('update --update-public-key');

        $exitCode = $this->command->run($input, $this->output);

        $this->assertSame(1, $exitCode);

        Phake::inOrder(
            Phake::verify($this->output)->writeln('Can not update public key without --authorize or --auth-token.'),
            Phake::verify($this->output)->writeln('')
        );
    }
}
