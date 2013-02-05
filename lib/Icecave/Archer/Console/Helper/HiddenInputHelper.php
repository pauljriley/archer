<?php
namespace Icecave\Archer\Console\Helper;

use Icecave\Archer\Support\Isolator;
use RuntimeException;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Output\OutputInterface;

class HiddenInputHelper extends Helper
{
    /**
     * @param string        $archerPackageRoot
     * @param Isolator|null $isolator
     */
    public function __construct($archerPackageRoot, Isolator $isolator = null)
    {
        $this->archerPackageRoot = $archerPackageRoot;
        $this->isolator = Isolator::get($isolator);
    }

    /**
     * @return string
     */
    public function archerPackageRoot()
    {
        return $this->archerPackageRoot;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'hidden-input';
    }

    /**
     * @param OutputInterface $output
     * @param string|array    $question
     *
     * @return string
     */
    public function askHiddenResponse(OutputInterface $output, $question)
    {
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            return $this->askHiddenResponseWindows($output, $question);
        }

        return $this->askHiddenResponseStty($output, $question);
    }

    /**
     * @param OutputInterface $output
     * @param string|array    $question
     *
     * @return string
     */
    protected function askHiddenResponseWindows(OutputInterface $output, $question)
    {
        $output->write($question);
        $value = rtrim(
            $this->execute(sprintf(
                '%s/res/bin/hiddeninput.exe',
                $this->archerPackageRoot()
            )),
            "\r\n"
        );
        $output->writeln('');

        return $value;
    }

    /**
     * @param OutputInterface $output
     * @param string|array    $question
     *
     * @return string
     */
    protected function askHiddenResponseStty(OutputInterface $output, $question)
    {
        $output->write($question);

        $sttyMode = $this->execute('stty -g');
        $this->execute('stty -echo');

        $error = null;

        try {
            $value = rtrim(
                $this->isolator->fgets(STDIN, 4096),
                "\r\n"
            );
        } catch (RuntimeException $error) {
            // reset stty before throwing
        }

        $this->execute(sprintf('stty %s', $sttyMode));
        if (null !== $error) {
            throw $error;
        }
        $output->writeln('');

        return $value;
    }

    /**
     * @param string $command
     *
     * @return string
     */
    protected function execute($command)
    {
        $result = $this->isolator->shell_exec($command);
        if (false === $result) {
            throw new RuntimeException('Unable to create or read hidden input dialog.');
        }

        return $result;
    }

    private $archerPackageRoot;
    private $isolator;
    private $hasSttyAvailable;
}
