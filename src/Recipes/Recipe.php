<?php

namespace Synga\Installer\Recipes;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class Recipe
{
    /** @var string */
    protected $name;

    /** @var Collection|string[] */
    protected $productionPackages;

    /** @var Collection|string[] */
    protected $developmentPackages;

    /** @var Collection|string[] */
    protected $commands;

    /** @var string * */
    protected $filename;

    /**
     * Recipe constructor.
     * @param array $recipe
     */
    public function __construct(array $recipe)
    {
        $this->name = Arr::get($recipe, 'name');

        if (empty($this->name)) {
            throw new \InvalidArgumentException('No name provided for class: ' . static::class);
        }

        $this->productionPackages = collect(Arr::get($recipe, 'packages.production', []));
        $this->developmentPackages = collect(Arr::get($recipe, 'packages.development', []));
        $this->commands = collect(Arr::get($recipe, 'commands', []));
        $this->filename = Arr::get($recipe, 'filename', '');
    }

    /**
     * @param string $filePath
     *
     * @return static
     */
    public static function creatByFilePath(string $filePath): self
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("The path {$filePath} does not exist.");
        }

        try {
            $recipe = json_decode(file_get_contents($filePath), true);

            $recipe = static::createByArray($recipe);

            $recipe->filename = basename($filePath);

            return $recipe;
        } catch (\Throwable $throwable) {
            throw new \LogicException("Could not retrieve data from {$filePath}");
        }
    }

    /**
     * @param array $recipe
     *
     * @return static
     */
    public static function createByArray(array $recipe): self
    {
        if (!static::createValidate($recipe)) {
            throw new \InvalidArgumentException('The data is not correct.');
        }

        return new static($recipe);
    }

    public static function createByName(string $path, string $name): ?self
    {
        $filePath = $path . '/' . md5($name) . '.json';

        if (file_exists($filePath)) {
            return static::creatByFilePath($filePath);
        }

        return null;
    }

    /**
     * @param array $recipe
     *
     * @return bool
     * @todo Check also for empty
     */
    protected static function createValidate(array $recipe): bool
    {
        return Arr::has($recipe, ['name', 'packages']) && Arr::hasAny($recipe, ['packages.production', 'packages.development']);
    }

    /**
     * @return bool
     */
    protected function validate(): bool
    {
        if (empty($this->name) || ($this->productionPackages->isEmpty() && $this->developmentPackages->isEmpty())) {
            return false;
        }

        return true;
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public function save(string $path): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $filename = md5($this->name) . '.json';

        file_put_contents("{$path}/$filename", $this->toJson());

        return true;
    }

    /**
     * @return Collection
     */
    public function getProductionPackages(): Collection
    {
        return $this->productionPackages;
    }

    /**
     * @return Collection
     */
    public function getDevelopmentPackages(): Collection
    {
        return $this->developmentPackages;
    }

    /**
     * @return Collection
     */
    public function getAllPackageNames(): Collection
    {
        return $this->productionPackages->merge($this->developmentPackages);
    }

    /**
     * @param string $composer
     * @return string|null
     */
    public function productionCommand(string $composer): ?string
    {
        return $this->createCommand($composer, $this->productionPackages);
    }

    /**
     * @param string $composer
     * @return string|null
     */
    public function developmentCommand(string $composer): ?string
    {
        return $this->createCommand($composer, $this->developmentPackages);
    }

    /**
     * @param string $composer
     * @return array
     */
    public function composerCommands(string $composer): array
    {
        return array_filter([$this->productionCommand("{$composer} require"), $this->developmentCommand("{$composer} require --dev")]);
    }

    /**
     * @param string $artisanPath
     * @return Collection
     */
    public function commands(string $artisanPath): Collection
    {
        return $this->commands->map(function ($item) use ($artisanPath) {
            $escapedCommand = [];
            foreach (explode(' ', $item) as $argument) {
                $escapedCommand[] = escapeshellarg($argument);
            }

            $escapedCommand = implode(' ', $escapedCommand);

            return "{$artisanPath} {$escapedCommand}";
        });
    }

    /**
     * @param string $composer
     * @param Collection $packages
     * @return string|null
     */
    protected function createCommand(string $composer, Collection $packages): ?string
    {
        if ($packages->isEmpty()) {
            return null;
        }

        foreach ($packages as $package => $version) {
            $composer .= ' ' . escapeshellarg("{$package}:{$version}");
        }

        return $composer;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function toJson(): string
    {
        return json_encode([
            'name' => $this->name,
            'packages' => [
                'production' => $this->productionPackages->toArray(),
                'development' => $this->developmentPackages->toArray(),
            ],
            'commands' => $this->commands->toArray(),
        ], JSON_PRETTY_PRINT);
    }
}
