<?php
namespace Icecave\Archer\Travis;

use RuntimeException;
use Icecave\Archer\FileSystem\FileSystem;
use Icecave\Archer\Support\Isolator;

class TravisClient
{
    /**
     * @param FileSystem|null $fileSystem
     * @param Isolator|null   $isolator
     */
    public function __construct(
        FileSystem $fileSystem = null,
        Isolator $isolator = null
    ) {
        if (null === $fileSystem) {
            $fileSystem = new FileSystem;
        }

        $this->fileSystem = $fileSystem;
        $this->isolator = Isolator::get($isolator);
    }

    /**
     * @return FileSystem
     */
    public function fileSystem()
    {
        return $this->fileSystem;
    }

    /**
     * @param string $repoOwner
     * @param string $repoName
     *
     * @return string
     */
    public function publicKey($repoOwner, $repoName)
    {
        $response = $this->fileSystem()->read(sprintf(
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
            sprintf('ARCHER_TOKEN="%s"', $gitHubToken)
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

    private $fileSystem;
    private $isolator;
}
