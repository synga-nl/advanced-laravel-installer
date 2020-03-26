<?php

namespace Synga\Installer\Recipes;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class RecipeCollection
{
    /** @var Recipe[] */
    protected $items = [];

    /**
     * @param string $directory
     * @return static
     */
    public static function collect(string $directory)
    {
        $collection = new static();

        foreach (scandir($directory) as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) == 'json') {
                $collection->push(Recipe::creatByFilePath("{$directory}/{$file}"));
            }
        }

        return $collection;
    }

    /**
     * @param Recipe $recipe
     */
    public function push(Recipe $recipe)
    {
        $this->items[$recipe->getName()] = $recipe;
    }

    /**
     * @todo move this outside the collection. For example the AdvancedInstaller class.
     * @todo move question helper outside since we can't test this
     *
     * @param InputInterface $input
     * @param OutputInterface $ouput
     * @return Recipe|null
     */
    public function consoleSelect(InputInterface $input, OutputInterface $ouput): ?Recipe
    {
        if (empty($this->items) || !$input->isInteractive()) {
            return null;
        }

        $question = new ChoiceQuestion('Please choose the recipe', array_keys($this->items));
        $question->setMaxAttempts(3);

        $helper = new QuestionHelper();
        $answer = $helper->ask($input, $ouput, $question);

        return $this->items[$answer];
    }

    /**
     * @param string $name
     * @return Recipe|null
     */
    public function search(string $name): ?Recipe
    {
        return $this->items[$name] ?? null;
    }

    /**
     * @return Recipe[]
     */
    public function getRecipes(): array
    {
        return $this->items;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }
}
