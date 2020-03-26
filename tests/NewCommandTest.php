<?php

namespace Laravel\Installer\Console\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Synga\Installer\Console\Command\NewCommand;

class NewCommandTest extends TestCase
{
    /** @var string */
    protected $scaffoldDirectoryName = 'tests-output/my-app';

    /** @var string */
    protected $scaffoldDirectory;

    /** @var string */
    protected $recipe = 'tests/Resources/Recipes/098f6bcd4621d373cade4e832627b4f6.json';

    /**
     * NewCommandTest constructor.
     * @param string|null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->scaffoldDirectory = __DIR__ . '/../' . $this->scaffoldDirectoryName;
    }

    /**
     * @return Application
     */
    protected function setUpApplication(): Application
    {
        if (file_exists($this->scaffoldDirectory)) {
            (new Filesystem)->remove($this->scaffoldDirectory);
        }

        return new Application('Laravel Installer');
    }

    /**
     * This method is called after each test.
     */
    protected function tearDown(): void
    {
        if (file_exists($this->scaffoldDirectory)) {
            (new Filesystem)->remove($this->scaffoldDirectory);
        }
    }

    public function test_it_can_scaffold_a_new_laravel_app()
    {
        $app = $this->setUpApplication();

        $app->add(new NewCommand());

        $tester = new CommandTester($app->find('new'));

        $statusCode = $tester->execute(['name' => $this->scaffoldDirectoryName, '--auth' => null]);

        $this->assertEquals($statusCode, 0);
        $this->assertDirectoryExists($this->scaffoldDirectory . '/vendor');
        $this->assertFileExists($this->scaffoldDirectory . '/.env');
        $this->assertFileExists($this->scaffoldDirectory . '/resources/views/auth/login.blade.php');
    }

    public function test_it_can_scaffold_a_new_laravel_app_from_recipe()
    {
        $app = $this->setUpApplication();

        $app->add(new NewCommand());

        $tester = new CommandTester($app->find('new'));

        $statusCode = $tester->execute(['name' => $this->scaffoldDirectoryName, '--auth' => null, '--recipe-path' => realpath($this->recipe)]);

        $this->assertEquals($statusCode, 0);
        $this->assertDirectoryExists($this->scaffoldDirectory . '/vendor');
        $this->assertFileExists($this->scaffoldDirectory . '/.env');
        $this->assertFileExists($this->scaffoldDirectory . '/resources/views/auth/login.blade.php');

        $composerJsonPathName = $this->scaffoldDirectory . '/composer.json';
        $this->assertFileExists($composerJsonPathName);
        $composerJson = file_get_contents($composerJsonPathName);

        $this->assertStringContainsString('spatie/laravel-query-builder', $composerJson);
        $this->assertStringContainsString('mtolhuys/laravel-schematics', $composerJson);
    }
}
