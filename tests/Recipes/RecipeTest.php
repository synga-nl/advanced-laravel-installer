<?php

use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Synga\Installer\Recipes\Recipe;

class RecipeTest extends TestCase
{
    /** @var string */
    protected $path = 'tests/Resources/Recipes';

    /** @var string */
    protected $pathName = 'tests/Resources/Recipes/098f6bcd4621d373cade4e832627b4f6.json';

    public function test_create_by_file_path()
    {
        $recipe = Recipe::creatByFilePath($this->pathName);

        $this->assertInstanceOf(Recipe::class, $recipe);
        $this->assertStringContainsString($recipe->getFilename(), $this->pathName);
    }

    public function test_create_by_name()
    {
        $recipe = Recipe::createByName($this->path, 'test');

        $this->assertInstanceOf(Recipe::class, $recipe);
        $this->assertStringContainsString($recipe->getFilename(), $this->pathName);
    }

    public function test_create_by_array()
    {
        $recipeArray = json_decode(file_get_contents($this->pathName), true);

        $recipe = Recipe::createByArray($recipeArray);

        $this->assertInstanceOf(Recipe::class, $recipe);
        $this->assertEmpty($recipe->getFilename());
    }

    public function test_can_save_recipe()
    {
        $name = 'test_name';
        $recipeArray = json_decode(file_get_contents($this->pathName), true);
        $recipeArray['name'] = $name;

        $recipe = Recipe::createByArray($recipeArray);
        $recipe->save($this->path);

        $pathName = "{$this->path}/" . md5($name) . '.json';

        $this->assertFileExists($pathName);
        @unlink($pathName);
    }

    public function test_can_get_composer_commands()
    {
        $recipe = Recipe::creatByFilePath($this->pathName);

        $composerCommands = $recipe->composerCommands('composer');

        $this->assertIsArray($composerCommands);
        $this->assertCount(2, $composerCommands);
        $this->assertStringContainsString('--dev', $composerCommands[1]);
    }

    public function test_can_get_artisan_commands()
    {
        $recipe = Recipe::creatByFilePath($this->pathName);

        $commands = $recipe->commands('artisan');

        $this->assertCount(1, $commands);
        $this->assertStringContainsString('--help', $commands[0]);
        $this->assertStringContainsString('artisan', $commands[0]);
    }

    public function test_can_get_all_packages()
    {
        $recipe = Recipe::creatByFilePath($this->pathName);

        $packages = $recipe->getAllPackageNames();

        $this->assertArrayHasKey('mtolhuys/laravel-schematics', $packages);
        $this->assertArrayHasKey('spatie/laravel-query-builder', $packages);
    }

    public function test_can_return_valid_data()
    {
        $recipe = Recipe::creatByFilePath($this->pathName);

        $this->assertIsString($recipe->getName());
        $this->assertInstanceOf(Collection::class, $recipe->getProductionPackages());
        $this->assertSame(1, $recipe->getProductionPackages()->count());
        $this->assertInstanceOf(Collection::class, $recipe->getDevelopmentPackages());
        $this->assertSame(1, $recipe->getDevelopmentPackages()->count());
    }
}