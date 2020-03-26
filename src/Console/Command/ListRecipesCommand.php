<?php


namespace Synga\Installer\Console\Command;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synga\Installer\Console\Command;
use Synga\Installer\Recipes\RecipeCollection;

class ListRecipesCommand extends Command
{
    /** @var string */
    protected $path = __DIR__ . '/../../../recipes';

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('recipe:list')
            ->setAliases(['rl'])
            ->setDescription('Lists all recipes.');
    }

    /**
     * Execute the command.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $recipes = RecipeCollection::collect($this->path);

        $this->info("Available recipes: \n");

        foreach ($recipes->getRecipes() as $recipe) {
            $this->info(" [<fg=magenta>{$recipe->getFilename()}</>] {$recipe->getName()}");
        }

        return 0;
    }
}