<?php
namespace Icecave\Archer\Documentation;

use Icecave\Archer\Support\Isolator;
use Icecave\Archer\FileSystem\FileSystem;
use RuntimeException;
use Sami\Sami;
use Symfony\Component\Finder\Finder;

class DocumentationGenerator
{
    /**
     * @param FileSystem|null $fileSystem
     * @param Isolator|null   $isolator
     */
    public function __construct(
        FileSystem $fileSystem = null,
        Isolator $isolator = null
    ) {
        $this->isolator = Isolator::get($isolator);

        if (null === $fileSystem) {
            $fileSystem = new FileSystem($this->isolator);
        }

        $this->fileSystem = $fileSystem;
    }

    /**
     * @return FileSystem
     */
    public function fileSystem()
    {
        return $this->fileSystem;
    }

    /**
     * @param string|null $projectPath
     */
    public function generate($projectPath = null)
    {
        if (null === $projectPath) {
            $projectPath = '.';
        }

        $sami = $this->createSami(
            $this->createFinder($this->sourcePath($projectPath)),
            array(
                'title' => sprintf('%s API', $this->projectName($projectPath)),
                'build_dir' => sprintf(
                    '%s/artifacts/documentation/api',
                    $projectPath
                ),
                'cache_dir' => sprintf(
                    '%s/archer-sami-cache',
                    $this->isolator->sys_get_temp_dir()
                ),
            )
        );

        $handlers = $this->popErrorHandlers();
        $sami['project']->update();
        $this->pushErrorHandlers($handlers);
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
     * @param string $projectPath
     *
     * @return string
     */
    protected function projectName($projectPath)
    {
        $json = $this->fileSystem()->read(
            sprintf('%s/composer.json', $projectPath)
        );
        $configuration = json_decode($json);
        if (!property_exists($configuration, 'name')) {
            throw new RuntimeException(
                'No project name set in Composer configuration.'
            );
        }

        return $configuration->name;
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
    private $isolator;
}
