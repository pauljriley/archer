<?php
namespace Icecave\Archer\GitHub;

use Phake;
use PHPUnit_Framework_TestCase;
use stdClass;

class GitHubClientTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->isolator = Phake::mock('Icecave\Archer\Support\Isolator');
        $this->client = Phake::partialMock(__NAMESPACE__ . '\GitHubClient', $this->isolator);
    }

    public function testValidateToken()
    {
        $this->assertTrue(GitHubClient::validateToken('b1a94b90073382b330f601ef198bb0729b0168aa'));

        // Too short ...
        $this->assertFalse(GitHubClient::validateToken('b1a94b90073382b330f601ef198bb0729b0168a'));

        // Too long ...
        $this->assertFalse(GitHubClient::validateToken('b1a94b90073382b330f601ef198bb0729b0168aaa'));

        // Invalid character ...
        $this->assertFalse(GitHubClient::validateToken('b1a94b90073382b330f601ef198bb0729b0168aq'));

        // Empty ...
        $this->assertFalse(GitHubClient::validateToken(''));
    }

    public function testDefaultBranch()
    {
        $response = new stdClass;
        $response->default_branch = 'branch-name';
        $response->master_branch = 'not-branch-name';

        Phake::when($this->client)
            ->apiGet(Phake::anyParameters())
            ->thenReturn($response);

        $this->assertSame('branch-name', $this->client->defaultBranch('bar', 'baz'));

        Phake::verify($this->client)->apiGet('repos/%s/%s', 'bar', 'baz');
    }

    public function testDefaultBranchUsingMasterBranch()
    {
        $response = new stdClass;
        $response->master_branch = 'branch-name';

        Phake::when($this->client)
            ->apiGet(Phake::anyParameters())
            ->thenReturn($response);

        $this->assertSame('branch-name', $this->client->defaultBranch('bar', 'baz'));

        Phake::verify($this->client)->apiGet('repos/%s/%s', 'bar', 'baz');
    }

    public function testDefaultBranchFallback()
    {
        Phake::when($this->client)
            ->apiGet(Phake::anyParameters())
            ->thenReturn(new stdClass);

        $this->assertSame('master', $this->client->defaultBranch('bar', 'baz'));

        Phake::verify($this->client)->apiGet('repos/%s/%s', 'bar', 'baz');
    }

    public function testSetAuthToken()
    {
        $this->assertNull($this->client->authToken());

        $token = 'b1a94b90073382b330f601ef198bb0729b0168aa';

        $this->client->setAuthToken($token);

        $this->assertSame($token, $this->client->authToken());
    }

    public function testSetAuthTokenFailure()
    {
        $this->assertNull($this->client->authToken());

        $this->setExpectedException('InvalidArgumentException', 'Invalid auth token.');
        $this->client->setAuthToken('invalid-token');
    }

    public function testSetUserAgent()
    {
        $this->assertNull($this->client->userAgent());

        $this->client->setUserAgent('test-agent');

        $this->assertSame('test-agent', $this->client->userAgent());
    }

    public function testApiGet()
    {
        Phake::when($this->isolator)
            ->file_get_contents(Phake::anyParameters())
            ->thenReturn('{ "result" : true }');

        $response = $this->client->apiGet('foo/%s', 'bar');

        $expected = new stdClass;
        $expected->result = true;

        $this->assertEquals($expected, $response);

        Phake::verify($this->isolator)->file_get_contents('https://api.github.com/foo/bar', false, null);
    }

    public function testApiGetWithUserAgent()
    {
        Phake::when($this->isolator)
            ->file_get_contents(Phake::anyParameters())
            ->thenReturn('{ "result" : true }');

        Phake::when($this->isolator)
            ->stream_context_create(Phake::anyParameters())
            ->thenReturn('<context>');

        $this->client->setUserAgent('test-agent');

        $response = $this->client->apiGet('foo/%s', 'bar');

        $expected = new stdClass;
        $expected->result = true;

        $this->assertEquals($expected, $response);

        $contextOptions = array(
            'http' => array(
                'user_agent' => 'test-agent'
            )
        );

        Phake::verify($this->isolator)->stream_context_create($contextOptions);
        Phake::verify($this->isolator)->file_get_contents('https://api.github.com/foo/bar', false, '<context>');
    }

    public function testApiGetWithAuthToken()
    {
        Phake::when($this->isolator)
            ->file_get_contents(Phake::anyParameters())
            ->thenReturn('{ "result" : true }');

        Phake::when($this->isolator)
            ->stream_context_create(Phake::anyParameters())
            ->thenReturn('<context>');

        $this->client->setAuthToken('b1a94b90073382b330f601ef198bb0729b0168aa');

        $response = $this->client->apiGet('foo/%s', 'bar');

        $expected = new stdClass;
        $expected->result = true;

        $this->assertEquals($expected, $response);

        $contextOptions = array(
            'http' => array(
                'header' => 'Authorization: token b1a94b90073382b330f601ef198bb0729b0168aa'
            )
        );

        Phake::verify($this->isolator)->stream_context_create($contextOptions);
        Phake::verify($this->isolator)->file_get_contents('https://api.github.com/foo/bar', false, '<context>');
    }
}
