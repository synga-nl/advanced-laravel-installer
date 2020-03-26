<?php

namespace Synga\Installer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Synga\Installer\Recipes\Recipe;
use Synga\Installer\Recipes\RecipeCollection;

class AdvancedInstaller
{
    /**
     * @param Command $command
     */
    public static function configure(Command $command): void
    {
        $command
            ->addOption('recipe-path', null, InputOption::VALUE_REQUIRED, 'Path to recipe json.')
            ->addOption('recipe-name', null, InputOption::VALUE_REQUIRED, 'Name of recipe.')
            ->addOption('recipe', '-r', InputOption::VALUE_NONE, 'Choose recipe interactively.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return Recipe
     */
    public static function getRecipe(InputInterface $input, OutputInterface $output): ?Recipe
    {
        if (empty(array_diff(
            ['recipe-path', 'recipe-name', 'recipe'],
            array_keys(array_filter($input->getOptions()))
        ))) {
            return null;
        }

        $recipeOption = $input->getOption('recipe-path');

        if (!empty($recipeOption)) {
            $recipe = Recipe::creatByFilePath($recipeOption);

            if ($recipe instanceof Recipe) {
                return $recipe;
            }
        }

        $recipeCollection = RecipeCollection::collect(__DIR__ . '/../recipes');

        $recipeOption = $input->getOption('recipe-name');

        if (!empty($recipeOption)) {
            $recipe = $recipeCollection->search($recipeOption);

            if (empty($recipe)) {
                $recipe = $recipeCollection->consoleSelect($input, $output);
            }

            return $recipe;
        }

        return null;
    }
}