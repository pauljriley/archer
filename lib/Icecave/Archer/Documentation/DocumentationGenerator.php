<?php
namespace Icecave\Archer\Documentation;

use Icecave\Archer\Configuration\ComposerConfigurationReader;
use Icecave\Archer\Support\Isolator;
use Icecave\Archer\FileSystem\FileSystem;
use RuntimeException;
use Sami\Sami;
use stdClass;
use Symfony\Component\Finder\Finder;

class DocumentationGenerator
{
    /**
     * @param FileSystem|null                  $fileSystem
     * @param ComposerConfigurationReader|null $composerConfigReader
     * @param Isolator|null                    $isolator
     */
    public function __construct(
        FileSystem $fileSystem = null,
        ComposerConfigurationReader $composerConfigReader = null,
        Isolator $isolator = null
    ) {
        $this->isolator = Isolator::get($isolator);

        if (null === $fileSystem) {
            $fileSystem = new FileSystem($this->isolator);
        }
        if (null === $composerConfigReader) {
            $composerConfigReader = new ComposerConfigurationReader(
                $fileSystem,
                $this->isolator
            );
        }

        $this->fileSystem = $fileSystem;
        $this->composerConfigReader = $composerConfigReader;
    }

    /**
     * @return FileSystem
     */
    public function fileSystem()
    {
        return $this->fileSystem;
    }

    /**
     * @return ComposerConfigurationReader
     */
    public function composerConfigReader()
    {
        return $this->composerConfigReader;
    }

    /**
     * @param string|null $projectPath
     */
    public function generate($projectPath = null)
    {
        if (null === $projectPath) {
            $projectPath = '.';
        }

        $composerConfiguration = $this->composerConfigReader()->read(
            $projectPath
        );
        $cachePath = sprintf(
            '%s/%s',
            $this->isolator->sys_get_temp_dir(),
            $this->isolator->uniqid('archer-sami-cache-', true)
        );

        $sami = $this->createSami(
            $this->createFinder($this->sourcePath($projectPath)),
            array(
                'title' => sprintf(
                    '%s API',
                    $this->projectName($composerConfiguration)
                ),
                'default_opened_level' => $this->openedLevel(
                    $composerConfiguration
                ),
                'build_dir' => sprintf(
                    '%s/artifacts/documentation/api',
                    $projectPath
                ),
                'cache_dir' => $cachePath,
            )
        );

        $handlers = $this->popErrorHandlers();
        $sami['project']->update();
        $this->pushErrorHandlers($handlers);

        $this->fileSystem()->delete($cachePath);
    }

    /**
     * @param string $projectPath
     *
     * @return string
     */
    protected function sourcePath($projectPath)
    {
        $sourcePath = sprintf('%s/src', $projectPath);
        if ($this->fileSystem()->directoryExists($sourcePath)) {
            return $sourcePath;
        }

        return sprintf('%s/lib', $projectPath);
    }

    /**
     * @param stdClass $composerConfiguration
     *
     * @return string
     */
    protected function projectName(stdClass $composerConfiguration)
    {
        $primaryNamespace = $this->primaryNamespace($composerConfiguration);
        if (null !== $primaryNamespace) {
            $namespaceAtoms = explode('\\', $primaryNamespace);
            if (count($namespaceAtoms) > 0) {
                $projectName = array_pop($namespaceAtoms);
            }
        } else {
            if (!property_exists($composerConfiguration, 'name')) {
                throw new RuntimeException(
                    'No project name set in Composer configuration.'
                );
            }

            $projectName = $composerConfiguration->name;
        }

        return $projectName;
    }

    /**
     * @param stdClass $composerConfiguration
     *
     * @return string
     */
    protected function openedLevel(stdClass $composerConfiguration)
    {
        $openedLevel = 3;
        $primaryNamespace = $this->primaryNamespace($composerConfiguration);
        if (null !== $primaryNamespace) {
            $numNamespaceAtoms = count(explode('\\', $primaryNamespace));
            if ($numNamespaceAtoms > 0) {
                $openedLevel = $numNamespaceAtoms + 1;
            }
        }

        return $openedLevel;
    }

    /**
     * @param stdClass $composerConfiguration
     *
     * @return string|null
     */
    protected function primaryNamespace(stdClass $composerConfiguration)
    {
        if (
            property_exists($composerConfiguration, 'autoload') &&
            property_exists($composerConfiguration->autoload, 'psr-0')
        ) {
            $psr0Autoload = get_object_vars(
                $composerConfiguration->autoload->{'psr-0'}
            );
            foreach ($psr0Autoload as $namespace => $path) {
                if ('_empty_' === $namespace) {
                    $namespace = null;
                }

                return $namespace;
            }
        }

        return null;
    }

    /**
     * @param string $sourcePath
     *
     * @return Finder
     */
    protected function createFinder($sourcePath)
    {
        return Finder::create()->in($sourcePath);
    }

    /**
     * @param Finder              $finder
     * @param array<string,mixed> $options
     *
     * @return Sami
     */
    protected function createSami(Finder $finder, array $options)
    {
        return new Sami($finder, $options);
    }

    /**
     * @return array<callable>
     */
    protected function popErrorHandlers()
    {
        $handlers = array();

        $handler = $this->isolator->set_error_handler(function () {});
        $this->isolator->restore_error_handler();
        $this->isolator->restore_error_handler();

        while (null !== $handler) {
            $handlers[] = $handler;
            $handler = $this->isolator->set_error_handler(function () {});
            $this->isolator->restore_error_handler();
            $this->isolator->restore_error_handler();
        }

        return $handlers;
    }

    /**
     * @param array<callable> $handlers
     */
    protected function pushErrorHandlers(array $handlers)
    {
        foreach (array_reverse($handlers) as $handler) {
            $this->isolator->set_error_handler($handler);
        }
    }

    private $fileSystem;
    private $composerConfigReader;
    private $isolator;
}
