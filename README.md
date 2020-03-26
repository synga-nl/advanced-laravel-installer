# Advanced laravel installer
Installs Laravel with additional packages specified in recipes.

## Composer

Run `composer global require synga/advanced-laravel-installer`

## Commands

When the composer bin directory is in your PATH you can call the following command everywhere:

`advanced-laravel new` You can use this command to create a new laravel project in the current directory  
`advanced-laravel recipe` Create/Edit a recipe. You can add (development) packages and commands.  
`advanced-laravel recipe:list` List all the available recipes and their filenames  
`advanced-laravel recipe:open` Opens a recipe or the recipe directory in the file manager.

## Improvements
- [ ] Better console menu handling (Menustack), maybe 
- [ ] Composer command is used to search for packages. This needs to be improved because it causes bugs.
- [ ] Save recipes in the cloud so you can use them anywhere, only access to your account is needed.
- [ ] Include other recipes so you can composer recipes from other recipes.
 
## Testing
You can run the tests with:

`composer test`

## Contributing 
Since I'm getting some questions about this I want these things to be perfectly clear:

This is a safe haven for contributions, every (positive) contributon matters!
You are free (and encouraged) to use anything of this package for your own ideas.
You can always ask for help or email me directly for any questions.

## Security
If you discover any security related issues, please email info@synga.nl instead of using the issue tracker.

## License
The MIT License (MIT). Please see License File for more information.