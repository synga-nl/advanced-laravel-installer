<?php

namespace Laravel\Installer\Tests;

use PHPUnit\Framework\TestCase;
use Synga\Installer\Recipes\Recipe;
use Synga\Installer\Recipes\RecipeCollection;

class RecipeCollectionTest extends TestCase
{
    protected $directory = 'tests/Resources/Recipes';

    public function test_can_return_recipes()
    {
        $recipeCollection = RecipeCollection::collect(realpath($this->directory));

        $this->assertSame(1, $recipeCollection->count());
        foreach ($recipeCollection->getRecipes() as $recipe) {
            $this->assertInstanceOf(Recipe::class, $recipe);
        }
    }

    public function test_can_search_recipe()
    {
        $recipeCollection = RecipeCollection::collect(realpath($this->directory));
        $recipe = $recipeCollection->search('test');

        $this->assertInstanceOf(Recipe::class, $recipe);
    }

    public function test_returns_null_on_search_non_existing_key()
    {
        $recipeCollection = RecipeCollection::collect(realpath($this->directory));

        $null = $recipeCollection->search('does_not_exist');
        $this->assertNull($null);
    }

    public function test_can_push_recipe()
    {
        $recipeCollection = RecipeCollection::collect(realpath($this->directory));

        $recipeMock = $this->createMock(Recipe::class);
        $recipeCollection->push($recipeMock);

        $this->assertSame(2, $recipeCollection->count());
    }
}