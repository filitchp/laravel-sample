# Laravel 5.2 Sample Project

This is a basic Laravel sample project that shows statistics about a user uploaded CSS file. 

The project demonstrates basic use of:
- OOP principles (see [example](app/CssParser.php))
- MVC design (see [model](app/CssParser.php), [view](resources/views/stats.blade.php), and  [controller](app/Http/Controllers/UploadController.php))
- Unit testing (see [test case](tests/ExampleTest.php))
- File upload handling (see [example](app/Http/Controllers/UploadController.php#L50))
- Composer dependency management & PSR-4 autoloading (see [composer.json](composer.json))

NOTE: it is not the intent of this project to include a comprehensize CSS parser that accounts for all corner cases (there are plenty of libraries to chose from).  

## License

This project is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
