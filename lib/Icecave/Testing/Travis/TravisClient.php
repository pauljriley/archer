<?php
namespace Icecave\Testing\Travis;

use RuntimeException;
use Icecave\Testing\Support\Isolator;

class TravisClient
{
    /**
     * @param Isolator|null $isolator
     */
    public function __construct(Isolator $isolator = null)
    {
        $this->isolator = Isolator::get($isolator);
    }

    /**
     * @param string $repoOwner
     * @param string $repoName
     *
     * @return string
     */
    public function publicKey($repoOwner, $repoName)
    {
        $response = $this->isolator->file_get_contents(sprintf(
            'https://api.travis-ci.org/repos/%s/%s/key',
            urlencode($repoOwner),
            urlencode($repoName)
        ));

        return json_decode($response)->key;
    }

    /**
     * @param string $publicKey
     * @param string $gitHubToken
     *
     * @return string
     */
    public function encryptEnvironment($publicKey, $gitHubToken)
    {
        return $this->encrypt(
            $publicKey,
            sprintf('ICT_TOKEN="%s"', $gitHubToken)
        );
    }

    /**
     * @param string $publicKey
     * @param string $plainText
     *
     * @return string
     */
    public function encrypt($publicKey, $plainText)
    {
        $cipherText = null;
        $result = $this->isolator->openssl_public_encrypt(
            $plainText,
            $cipherText,
            str_replace('RSA PUBLIC KEY', 'PUBLIC KEY', $publicKey)
        );
        if (!$result) {
            throw new RuntimeException('Encryption failed.');
        }

        return base64_encode($cipherText);
    }

    private $isolator;
}
