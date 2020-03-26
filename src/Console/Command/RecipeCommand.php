<?php

namespace Synga\Installer\Console\Command;

use CliHighlighter\Service\Highlighter\JsonHighlighter;
use Composer\Command\InitCommand;
use Composer\Command\SearchCommand;
use Composer\IO\ConsoleIO;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synga\Installer\Console\Command;
use Synga\Installer\Recipes\MutableRecipe;

class RecipeCommand extends Command
{
    const TRANSLATION_PROVIDE_MENU_ITEM = 'Please type in the numeric value of the above choices';
    const TRANSLATION_MENU_ITEM_NOT_FOUND = "<fg=red>The number %s could not be found</>\n";
    const TRANSLATION_PACKAGE_LISTING = '<info>[%d]</info> <fg=magenta>%s</>:<fg=green>%s</>';
    const TRANSLATION_PACKAGE_DELETE = 'Which package do you want to delete?';
    const TRANSLATION_PACKAGE_NOT_FOUND = 'Could not find the package, please try again';
    const TRANSLATION_PACKAGE_NOT_REGISTERED = "No %s packages are registered yet. \n";
    const TRANSLATION_PACKAGE_REGISTERED = "The following %S packages are registered: \n";
    const TRANSLATION_PACKAGE_LINE = ' <fg=magenta>%s</>:<fg=green>%s</>';
    const TRANSLATION_BACK_OPTION = '<info>[%d]</info> Back';
    const TRANSLATION_RECIPE_CURRENT = "<fg=%s>%s</> recipe with name: <fg=red>%s</>\n";
    const TRANSLATION_RECIPE_SAVED_PROCEED = 'The recipe has been saved, do you wish to proceed?';
    const TRANSLATION_RECIPE_REQUIREMENTS_VIOLATED = '<fg=red>The recipe does not meet all the requirements</>.';
    const TRANSLATION_COMING_SOON = '<fg=red>Coming soon.</>';
    const TRANSLATION_CONFIRM_PROCEED = 'Press enter to proceed';
    const TRANSLATION_REFLECTION_EXCEPTION = '<fg=red>Reflection exception, did you properly install PHP and the composer packages?</>';
    const TRANSLATION_EXIT = 'We wish you all the best of luck, fortune and prosperity.';

    /** @var SearchCommand */
    protected $search;

    /** @var JsonHighlighter */
    protected $highlighter;

    /** @var string */
    protected $path = __DIR__ . '/../../../recipes';

    /**
     * RecipeCommand constructor.
     * @param string|null $name
     */
    public function __construct(string $name = null)
    {
        parent::__construct($name);

        $this->highlighter = new JsonHighlighter([
            'keys' => 'magenta',
            'values' => 'green',
            'braces' => 'light_white',
        ]);
    }

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('recipe')
            ->setDescription('Create a recipe.')
            ->addArgument('name', InputArgument::REQUIRED);
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
        $name = $this->argument('name');

        $recipe = MutableRecipe::createByName($this->path, $name);
        $creating = is_null($recipe);

        while (true) {
            $this->line(
                sprintf(self::TRANSLATION_RECIPE_CURRENT,
                    ($creating) ? 'cyan' : 'red',
                    ($creating) ? 'Creating' : 'Updateing',
                    is_array($name) ? implode(' ', $name) : $name
                )
            );

            $this->line(' <info>[1]</info> Add production packages');
            $this->line(' <info>[2]</info> Add development packages');
            $this->line(' <info>[3]</info> Add commands');
            $this->line(' <info>[4]</info> Delete production packages');
            $this->line(' <info>[5]</info> Delete development packages');
            $this->line(' <info>[6]</info> Delete commands');
            $this->line(str_repeat('-', 80));
            $this->line(' <info>[7]</info> View current state');
            $this->line(' <info>[8]</info> Save');
            $this->line(' <info>[9]</info> Exit - Working copy will be deleted');

            $menuItem = $this->ask(self::TRANSLATION_PROVIDE_MENU_ITEM);

            switch ($menuItem) {
                case 1:
                    $recipe->setProductionPackages($this->addPackages($input, $output, $recipe->getProductionPackages(), 'production'));
                    break;
                case 2:
                    $recipe->setDevelopmentPackages($this->addPackages($input, $output, $recipe->getDevelopmentPackages(), 'development'));
                    break;
                case 3:
                case 6:
                    $this->line(self::TRANSLATION_COMING_SOON);
                    $this->ask(self::TRANSLATION_CONFIRM_PROCEED);
                    break;
                case 4:
                    while (true) {
                        if (is_null($this->deletePackageFromCollection($recipe->getProductionPackages()))) {
                            break;
                        }
                    }
                    break;
                case 5:
                    while (true) {
                        if (is_null($this->deletePackageFromCollection($recipe->getDevelopmentPackages()))) {
                            break;
                        }
                    }
                    break;
                case 7:
                    echo $this->highlighter->highlight($recipe->toJson()) . "\n\n";

                    $this->ask(self::TRANSLATION_CONFIRM_PROCEED);
                    break;
                case 8:
                    if ($recipe->save($this->path)) {
                        if (!$this->confirm(self::TRANSLATION_RECIPE_SAVED_PROCEED)) {
                            break 2;
                        }
                    } else {
                        $this->line(self::TRANSLATION_RECIPE_REQUIREMENTS_VIOLATED);

                        $this->ask(self::TRANSLATION_CONFIRM_PROCEED);
                    }
                    break;
                case 9:
                    break 2;
                default:
                    $this->line(sprintf(self::TRANSLATION_MENU_ITEM_NOT_FOUND, $menuItem));
            }
        }

        $this->info(self::TRANSLATION_EXIT);

        return 0;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return Collection
     */
    protected function search(InputInterface $input, OutputInterface $output): Collection
    {
        $result = new Collection();

        try {
            $initCommandReflection = new \ReflectionClass(InitCommand::class);
            $method = $initCommandReflection->getMethod('determineRequirements');
            $method->setAccessible(true);

            // @todo with some modifications in composer, this can work even better.
            // @todo Need to find a way to not let the application crash when you type in a package that does not exist
            $initCommand = new InitCommand();
            $initCommand->setIO(new ConsoleIO($input, $output, new HelperSet([
                'question' => new QuestionHelper(),
            ])));

            $packages = [];

            try {
                $packages = $method->invoke($initCommand, $input, $output, $packages);
            } catch (\RuntimeException $e) {
                $this->line(self::TRANSLATION_PACKAGE_NOT_FOUND);
            }

            foreach ($packages as $item) {
                $items = explode(' ', $item);

                if (count($items) === 2) {
                    $result[$items[0]] = $items[1];
                }
            };

        } catch (\ReflectionException $e) {
            $this->line(self::TRANSLATION_REFLECTION_EXCEPTION);
        }

        return $result;

    }

    /**
     * @param Collection $packages
     *
     * @return bool
     */
    protected function deletePackageFromCollection(Collection $packages): ?Collection
    {
        $this->line(self::TRANSLATION_PACKAGE_DELETE . "\n");

        $choice = $this->choicePackageList($packages);

        if (is_null($choice)) {
            return null;
        }

        $this->info('UNSET');

        unset($packages[$choice]);

        return $packages;
    }

    /**
     * @param Collection $packages
     *
     * @return string|null
     */
    protected function choicePackageList(Collection $packages): ?string
    {
        $loop = 1;

        $packages->map(function ($version, $packageName) use (&$loop) {
            $this->line(' ' . sprintf(self::TRANSLATION_PACKAGE_LISTING, $loop++, $packageName, $version));
        });

        $this->line(sprintf("\n " . self::TRANSLATION_BACK_OPTION, $loop++));

        $choice = (int)$this->ask(self::TRANSLATION_PROVIDE_MENU_ITEM);

        if ($packages->count() === $choice - 1) {
            return null;
        }

        $loop = 1;
        $result = '';

        $packages->map(function ($version, $packageName) use ($choice, &$loop, &$result) {
            if ($choice === $loop) {
                $result = $packageName;
            }

            $loop++;
        });

        return $result;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param Collection $packages
     * @param string $packageType
     *
     * @return Collection
     */
    protected function addPackages(InputInterface $input, OutputInterface $output, Collection $packages, string $packageType): Collection
    {
        if ($packages->isEmpty()) {
            $this->info(sprintf(self::TRANSLATION_PACKAGE_NOT_REGISTERED, $packageType));
        } else {
            $this->info(sprintf(self::TRANSLATION_PACKAGE_REGISTERED, $packageType));

            $packages->map(function ($version, $packageName) {
                $this->line(sprintf(self::TRANSLATION_PACKAGE_LINE, $packageName, $version));
            });

            $this->line('');
        }

        return $packages->merge($this->search($input, $output));
    }
}