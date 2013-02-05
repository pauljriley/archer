<?php
namespace Icecave\Archer\GitHub;

use Phake;
use PHPUnit_Framework_TestCase;

class GitConfigReaderTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_process = Phake::mock('Symfony\Component\Process\Process');
        $this->_processFactory = Phake::mock(
            'Icecave\Archer\Process\ProcessFactory'
        );
        $this->_reader = new GitConfigReader(
            'foo',
            $this->_processFactory
        );

        Phake::when($this->_processFactory)
            ->create(Phake::anyParameters())
            ->thenReturn($this->_process)
        ;
        Phake::when($this->_process)
            ->isSuccessful(Phake::anyParameters())
            ->thenReturn(true)
        ;

        $configuration = <<<EOD
user.name=Test Ease
user.email=testease@gmail.com
color.ui=auto
github.user=testease
credential.helper=cache --timeout=21600
push.default=simple
core.repositoryformatversion=0
core.filemode=true
core.bare=false
core.logallrefupdates=true
core.ignorecase=true
core.precomposeunicode=false
remote.origin.url=git@github.com:IcecaveStudios/archer.git
remote.origin.fetch=+refs/heads/*:refs/remotes/origin/*
branch.master.remote=origin
branch.master.merge=refs/heads/master
branch.3.0.0.remote=origin
branch.3.0.0.merge=refs/heads/3.0.0

EOD;
        Phake::when($this->_process)
            ->getOutput(Phake::anyParameters())
            ->thenReturn($configuration)
        ;
    }

    public function testConstructor()
    {
        $this->assertSame('foo', $this->_reader->repositoryPath());
        $this->assertSame($this->_processFactory, $this->_reader->processFactory());
    }

    public function testConstructorDefaults()
    {
        $this->_reader = new GitConfigReader(
            'foo'
        );

        $this->assertInstanceOf(
            'Icecave\Archer\Process\ProcessFactory',
            $this->_reader->processFactory()
        );
    }

    public function testGet()
    {
        $this->assertSame('Test Ease', $this->_reader->get('user.name'));
        $this->assertSame('testease@gmail.com', $this->_reader->get('user.email'));
        $this->assertSame('ambiguous', $this->_reader->get('user.gender', 'ambiguous'));
        $this->assertNull($this->_reader->get('user.gender'));
    }

    public function testRepositoryOwner()
    {
        $this->assertSame('IcecaveStudios', $this->_reader->repositoryOwner());
    }

    public function testRepositoryOwnerFailure()
    {
        $configuration = <<<EOD
remote.origin.url=derp

EOD;
        Phake::when($this->_process)
            ->getOutput(Phake::anyParameters())
            ->thenReturn($configuration)
        ;

        $this->setExpectedException(
            'RuntimeException',
            'Origin URL "derp" is not a GitHub repository.'
        );
        $this->_reader->repositoryOwner();
    }

    public function testRepositoryName()
    {
        $this->assertSame('archer', $this->_reader->repositoryName());
    }

    public function testRepositoryNameFailure()
    {
        $configuration = <<<EOD
remote.origin.url=derp

EOD;
        Phake::when($this->_process)
            ->getOutput(Phake::anyParameters())
            ->thenReturn($configuration)
        ;

        $this->setExpectedException(
            'RuntimeException',
            'Origin URL "derp" is not a GitHub repository.'
        );
        $this->_reader->repositoryName();
    }

    public function testParseFailure()
    {
        Phake::when($this->_process)
            ->isSuccessful(Phake::anyParameters())
            ->thenReturn(false)
        ;
        Phake::when($this->_process)
            ->getErrorOutput(Phake::anyParameters())
            ->thenReturn('Bar.')
        ;

        $this->setExpectedException(
            'RuntimeException',
            'Unable to read git configuration: Bar.'
        );
        $this->_reader->get('user.name');
    }
}
