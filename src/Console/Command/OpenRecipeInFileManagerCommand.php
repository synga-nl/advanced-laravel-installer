<?php


namespace Synga\Installer\Console\Command;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Synga\Installer\Console\Command;
use Synga\Installer\Recipes\RecipeCollection;
use Tivie\OS;

class OpenRecipeInFileManagerCommand extends Command
{
    // @todo make paths configurable would be a nice addition.
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
            ->setName('recipe:open')
            ->setAliases(['ro'])
            ->addArgument('recipe-name', InputArgument::OPTIONAL)
            ->addOption('list', InputOption::VALUE_NONE)
            ->setDescription('Open the recipe directory for current platform.');
    }

    /**
     * Execute the command.
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     * @todo support linux (most common versions)
     * @todo if this is working well, let's extract opening in file manager to a package.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $this->path;

        $recipeCollection = RecipeCollection::collect($this->path);

        if ($this->option('list')) {
            $recipe = $recipeCollection->consoleSelect($input, $output);

            $path .= "/{$recipe->getFilename()}";
        }

        if ($this->argument('recipe-name')) {
            $recipe = $recipeCollection->search($this->argument('recipe-name'));

            if ($recipe) {
                $path .= "/{$recipe->getFilename()}";
            } else {
                $this->line("<fg=red>Could not find the recipe, opening the directory instead.</>\n");
                try {
                    (new ListRecipesCommand())->run(new StringInput(''), $output);
                } catch (\Exception $e) {
                    $this->line("<fg=red>Something went wrong while listing the recipes.</>\n");
                }
            }
        }

        $path = escapeshellarg($path);

        $os = new Os\Detector();
        switch ($os->getType()) {
            case OS\WINDOWS:
            case OS\CYGWIN:
                exec("start {$path}");
                break;
            case OS\MACOSX:
                exec("open -R {$path}");
                break;
            // Not yet supported. Possible commands:
            // xdg-open, gnome-open and nautilus
            case OS\GEN_UNIX:
            case OS\LINUX:
            case OS\MSYS:
            case OS\SUN_OS:
            case OS\NONSTOP:
            case OS\QNX:
            case OS\BSD:
            case OS\BE_OS:
            case OS\HP_UX:
            case OS\ZOS:
            case OS\AIX:
            default:
                $this->line("<info>You can use to following command to go to the recipes directory: </info>\n");
                $this->line("<fg=magenta>cd {$path}</>\n");

                $this->line('<fg=red>Your platform not yet supported. Please create a pull request to support this for your platform.</>');
        }

        return 0;
    }
}