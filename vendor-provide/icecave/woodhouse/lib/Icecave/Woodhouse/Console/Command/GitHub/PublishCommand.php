<?php
namespace Icecave\Woodhouse\Console\Command\GitHub;

use Icecave\Isolator\Isolator;
use Icecave\Woodhouse\Coverage\CoverageImageSelector;
use Icecave\Woodhouse\Coverage\CoverageReaderFactory;
use Icecave\Woodhouse\Publisher\GitHubPublisher;
use Icecave\Woodhouse\TypeCheck\TypeCheck;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PublishCommand extends Command
{
    /**
     * @param GitHubPublisher|null       $publisher
     * @param CoverageReaderFactory|null $readerFactory
     * @param CoverageImageSelector|null $imageSelector
     * @param Isolator|null              $isolator
     */
    public function __construct(
        GitHubPublisher $publisher = null,
        CoverageReaderFactory $readerFactory = null,
        CoverageImageSelector $imageSelector = null,
        Isolator $isolator = null
    ) {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        $this->isolator = Isolator::get($isolator);

        if (null === $publisher) {
            $publisher = new GitHubPublisher($this->isolator);
        }

        if (null === $readerFactory) {
            $readerFactory = new CoverageReaderFactory;
        }

        if (null === $imageSelector) {
            $imageSelector = new CoverageImageSelector;
        }

        $this->publisher = $publisher;
        $this->readerFactory = $readerFactory;
        $this->imageSelector = $imageSelector;

        parent::__construct();
    }

    public function publisher()
    {
        return $this->publisher;
    }

    public function readerFactory()
    {
        return $this->readerFactory;
    }

    public function imageSelector()
    {
        return $this->imageSelector;
    }

    protected function configure()
    {
        $this->typeCheck->configure(func_get_args());

        $this->setName('github:publish');
        $this->setDescription('Publish content to a GitHub pages branch.');

        $this->addArgument(
            'repository',
            InputArgument::REQUIRED,
            'The target of the GitHub repository (eg: icecave/woodhouse).'
        );

        $this->addArgument(
            'content',
            InputArgument::REQUIRED | InputArgument::IS_ARRAY,
            'Pairs of source/destination folders separated by hyphens for publication (eg: stuff/source-folder:stuff/destination-folder).'
        );

        $this->addOption(
            'message',
            'm',
            InputOption::VALUE_REQUIRED,
            'The Git commit message.'
        );

        $this->addOption(
            'branch',
            null,
            InputOption::VALUE_REQUIRED,
            'The target branch of the repository.',
            'gh-pages'
        );

        $this->addOption(
            'coverage-image',
            'i',
            InputOption::VALUE_REQUIRED,
            'Publish a coverage badge to the given location (requires one of the other --coverage-* options).'
        );

        $this->addOption(
            'fixed-width',
            'f',
            InputOption::VALUE_NONE,
            'Use fixed width coverage images.'
        );

        $this->addOption(
            'coverage-phpunit',
            null,
            InputOption::VALUE_REQUIRED,
            'Path to a PHPUnit code coverage report in text format.'
        );

        $this->addOption(
            'coverage-percentage',
            null,
            InputOption::VALUE_REQUIRED,
            'Specify the coverage percentage directly on the command-line.'
        );

        $this->addOption(
            'auth-token',
            't',
            InputOption::VALUE_REQUIRED,
            'Use a GitHub OAuth API token for authentication.'
        );

        $this->addOption(
            'auth-token-env',
            'e',
            InputOption::VALUE_REQUIRED,
            'Use a GitHub OAuth API token for authentication, the value should be the name of an environment variable containing the token.'
        );

    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $coverageReader = null;
        $coverageOption = null;

        // Select the appropriate coverage reader ...
        foreach ($this->readerFactory->supportedTypes() as $type) {
            $optionName = 'coverage-' . $type;
            if ($value = $input->getOption($optionName)) {
                if (null === $coverageOption) {
                    $coverageReader = $this->readerFactory->create($type, $value);
                    $coverageOption = $optionName;
                } else {
                    throw new RuntimeException('--' . $optionName . ' can not be used with --' . $coverageOption . '.');
                }
            }
        }

        // Enqueue the coverage image ...
        if ($imageTarget = $input->getOption('coverage-image')) {
            if (null === $coverageOption) {
                throw new RuntimeException('--coverage-image requires one of the other --coverage-* options.');
            }

            $imageRoot = $this->getApplication()->vendorPath() . '/ezzatron/ci-status-images/img/test-coverage';
            if ($input->getOption('fixed-width')) {
                $imageRoot .= '-fixed-width';
            }

            $percentage = $coverageReader->readPercentage();
            $imageFilename = $this->imageSelector->imageFilename($percentage);
            $this->publisher->add($imageRoot . '/' . $imageFilename, $imageTarget);
        } elseif ($coverageOption) {
            throw new RuntimeException('--' . $coverageOption . ' requires --coverage-image.');
        }

        // Enqueue content ...
        foreach ($input->getArgument('content') as $content) {
            $index = strrpos($content, ':');
            if (false === $index) {
                throw new RuntimeException('Invalid content specifier: "' . $content . '", content must be specified as colon separated pairs of source and destination path.');
            }

            $sourcePath = substr($content, 0, $index);
            $targetPath = substr($content, $index + 1);

            if (!$this->isolator->file_exists($sourcePath)) {
                throw new RuntimeException('Content does not exist: "' . $sourcePath . '".');
            }

            if (!preg_match('{^([a-z]:[\\\\/]|/)}i', $sourcePath)) {
                $sourcePath = $this->isolator->getcwd() . '/' . $sourcePath;
            }

            $this->publisher->add(
                $sourcePath,
                $targetPath
            );
        }

        // Set the authentication token ...
        $authToken = $input->getOption('auth-token');
        if ($authTokenEnv = $input->getOption('auth-token-env')) {
            if (null === $authToken) {
                $authToken = $this->isolator->getenv($authTokenEnv);
            } else {
                throw new RuntimeException('--auth-token-env can not be used with --auth-token.');
            }
        }

        if ($message = $input->getOption('message')) {
            $this->publisher->setCommitMessage($message);
        }

        $this->publisher->setAuthToken($authToken);
        $this->publisher->setRepository($input->getArgument('repository'));
        $this->publisher->setBranch($input->getOption('branch'));

        $this->publisher->publish();

        $output->writeln('Content published successfully.');
    }

    private $typeCheck;
    private $publisher;
    private $readerFactory;
    private $imageSelector;
    private $isolator;
}
