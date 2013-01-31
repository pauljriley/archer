<?php
namespace Icecave\Testing\Travis;

use RuntimeException;
use Icecave\Testing\Support\Isolator;

class TravisClient
{
    public function __construct(Isolator $isolator = null)
    {
        $this->isolator = Isolator::get($isolator);
    }

    public function publicKey($repoOwner, $repoName, $forceCacheRefresh = false)
    {
        $cacheKey = $repoOwner . '/' . $repoName;

        $url  = sprintf(
            'https://api.travis-ci.org/repos/%s/%s/key',
            urlencode($repoOwner),
            urlencode($repoName)
        );

        $response = $this->isolator->file_get_contents($url);
        $response = json_decode($response);

        return $response->key;
    }

    public function encryptEnvironment($publicKey, $repoOwner, $repoName, $gitHubToken)
    {
        $env = sprintf(
            'ICT_TOKEN="%s"',
            $repoOwner,
            $repoName,
            $gitHubToken
        );

        return $this->encrypt($publicKey, $env);
    }

    public function encrypt($publicKey, $plainText)
    {
        $cipherText = null;

        $result = $this->isolator->openssl_public_encrypt(
            $plainText,
            $cipherText,
            str_replace('RSA PUBLIC KEY', 'PUBLIC KEY', $publicKey),
            OPENSSL_PKCS1_PADDING
        );

        if (!$result) {
            throw new RuntimeException('Encryption failed.');
        }

        return base64_encode($cipherText);
    }

    private $isolator;
}
