<?php
namespace Icecave\Testing\Console\Command;

use RuntimeException;
use Symfony\Component\Console\Command\Command;

abstract class AbstractCommand extends Command
{
    protected function readGitConfig($key)
    {
        $exitCode = 0;
        $result = exec('git config --global ' . escapeshellarg($key), $output, $exitCode);
        $result = trim($result);

        if (0 === $exitCode && '' !== $result) {
            return $result;
        }

        return null;
    }

    protected function travisKey($githubAccount, $package)
    {
        $json = file_get_contents('https://api.travis-ci.org/repos/' . $githubAccount . '/' . $package . '/key');

        $key = json_decode($json)->key;
        $key = str_replace('-----BEGIN RSA PUBLIC KEY-----', '', $key);
        $key = str_replace('-----END RSA PUBLIC KEY-----', '', $key);
        $key = preg_replace('/\s+/', '', $key);

        return $key;
    }

    protected function encryptToken($key, $token)
    {
        if (!function_exists('openssl_public_encrypt')) {
            throw new RuntimeException('Encrypting OAuth key requires the PECL openssl module.');
        }

        $paddedKey  = '-----BEGIN PUBLIC KEY-----' . PHP_EOL;
        $paddedKey .= chunk_split($key);
        $paddedKey .= '-----END PUBLIC KEY-----' . PHP_EOL;

        $encrypted = null;

        openssl_public_encrypt('ICT_GITHUB_TOKEN="' . $token . '"', $encrypted, $paddedKey, OPENSSL_PKCS1_PADDING);

        return base64_encode($encrypted);
    }

    protected function cloneSkeletons($projectRoot, array $skeletons, array $variables)
    {
        $command = $projectRoot . '/vendor/bin/ict-chassis clone ';

        foreach ($skeletons as $skel) {
            $command .= ' ' . escapeshellarg($projectRoot . '/vendor/icecave/testing/res/skel.' . $skel);
        }

        foreach ($variables as $key => $value) {
            $command .= ' --define ' . escapeshellarg($key . '=' . $value);
        }

        $command .= ' --output-path ' . escapeshellarg($projectRoot);

        $this->passthru($command);
    }

    protected function passthru($command)
    {
        $arguments = array_slice(func_get_args(), 1);
        $arguments = array_map('escapeshellarg', $arguments);
        $command   = vsprintf($command, $arguments);

        $exitCode = null;
        passthru($command, $exitCode);

        if (0 !== $exitCode) {
            throw new RuntimeException('Failed executing "' . $command . '".');
        }
    }
}
