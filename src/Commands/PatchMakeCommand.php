<?php

namespace Rappasoft\LaravelPatches\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Rappasoft\LaravelPatches\Patcher;

/**
 * Class PatchMakeCommand
 *
 * @package Rappasoft\LaravelPatches\Commands
 */
class PatchMakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:patch {name : The name of the patch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a patch file';

    /**
     * The patcher instance.
     *
     * @var Patcher
     */
    protected Patcher $patcher;

    /**
     * The Composer instance.
     *
     * @var Composer
     */
    protected Composer $composer;

    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected Filesystem $files;

    /**
     * Create a new command instance.
     *
     * @param  Patcher  $patcher
     * @param  Composer  $composer
     * @param  Filesystem  $files
     */
    public function __construct(Patcher $patcher, Composer $composer, Filesystem $files)
    {
        parent::__construct();

        $this->patcher = $patcher;
        $this->composer = $composer;
        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws FileNotFoundException
     */
    public function handle(): int
    {
        $this->writePatch(Str::snake(trim($this->argument('name'))));

        $this->composer->dumpAutoloads();

        return 0;
    }

    /**
     * Create the patch file
     *
     * @param  string  $name
     *
     * @throws FileNotFoundException
     */
    protected function writePatch(string $name): void
    {
        $file = pathinfo($this->create($name, $this->patcher->getPatchPath()), PATHINFO_FILENAME);

        $this->line("<info>Created Patch:</info> {$file}");
    }

    /**
     * Create the patch and return the path
     *
     * @param $name
     * @param $path
     *
     * @return string
     * @throws FileNotFoundException
     */
    protected function create($name, $path): string
    {
        $this->ensurePatchDoesntAlreadyExist($name, $this->patcher->getPatchPath());

        $stub = $this->getStub();

        $path = $this->getPath($name, $path);

        $this->files->ensureDirectoryExists(dirname($path));

        $this->files->put($path, $this->populateStub($name, $stub));

        return $path;
    }

    /**
     * Make sure two patches with the same class name do not get created
     *
     * @param $name
     * @param  null  $patchesPath
     *
     * @throws FileNotFoundException
     */
    protected function ensurePatchDoesntAlreadyExist($name, $patchesPath = null): void
    {
        if (! empty($patchesPath)) {
            $patchFiles = $this->files->glob($patchesPath.'/*.php');

            foreach ($patchFiles as $patchFile) {
                $this->files->requireOnce($patchFile);
            }
        }

        if (class_exists($className = $this->patcher->getClassName($name))) {
            throw new InvalidArgumentException("A {$className} patch class already exists.");
        }
    }

    /**
     * Get the patch stub contents
     *
     * @return string
     * @throws FileNotFoundException
     */
    protected function getStub(): string
    {
        return $this->files->get($this->stubPath().'/patch.stub');
    }

    /**
     * Get the path to the stubs
     *
     * @return string
     */
    protected function stubPath(): string
    {
        return __DIR__.'/stubs';
    }

    /**
     * Get the path of the file to be created
     *
     * @param $name
     * @param $path
     *
     * @return string
     */
    protected function getPath($name, $path): string
    {
        return $path.'/'.$this->getDatePrefix().'_'.$name.'.php';
    }

    /**
     * Get the date prefix of the file
     *
     * @return false|string
     */
    protected function getDatePrefix()
    {
        return date('Y_m_d_His');
    }

    /**
     * Replace the placeholders in the stub with their actual data
     *
     * @param $name
     * @param $stub
     *
     * @return string|string[]
     */
    protected function populateStub($name, $stub)
    {
        return str_replace('{{ class }}', $this->patcher->getClassName($name), $stub);
    }
}
