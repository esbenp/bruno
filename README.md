# Laravel API Controller

[![Latest Version](https://img.shields.io/github/release/esbenp/laravel-controller.svg?style=flat-square)](https://github.com/esbenp/laravel-controller/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/travis/esbenp/laravel-controller/master.svg?style=flat-square)](https://travis-ci.org/esbenp/laravel-controller)
[![Coverage Status](https://img.shields.io/coveralls/esbenp/laravel-controller.svg?style=flat-square)](https://coveralls.io/github/esbenp/laravel-controller)
[![Total Downloads](https://img.shields.io/packagist/dt/optimus/laravel-controller.svg?style=flat-square)](https://packagist.org/packages/optimus/laravel-controller)

## Functionality

* Parse GET parameters for dynamic eager loading of related resources, sorting, pagination and limiting
* Use [Optimus\Architect](https://github.com/esbenp/architect) for sideloading, id loading or embedded loading of related resources
* ... [Ideas for new functionality is welcome here](https://github.com/esbenp/laravel-controller/issues/new)

## Usage

The examples will be of a hypothetical resource endpoint `/books` which will return a collection of `Book`,
each belonging to a `Author`.

```
Book 1-----n Author
```

### Available query parameters

Key | Type | Description
--- | ---- | -----------
Includes | array | Array of related resources to load, e.g. ['author', 'publisher', 'publisher.books']
Sort | string | Property to sort by, e.g. 'title'
Limit | integer | Limit of resources to return
Page | integer | For use with limit

### Usage

```php
<?php

namespace App\Http\Controllers;

use Optimus\Api\Controller\EloquentBuilderTrait;
use Optimus\Api\Controller\LaravelController;
use App\Models\Book;

class BookController extends LaravelController
{
    use EloquentBuilderTrait;

    public function getBooks()
    {
        // Parse the resource options given by GET parameters
        $resourceOptions = $this->parseResourceOptions();

        // Start a new query for books using Eloquent query builder
        // (This would normally live somewhere else, e.g. in a Repository)
        $query = Book::query();
        $this->applyResourceOptions($query, $resourceOptions);
        $books = $query->get();

        // Parse the data using Optimus\Architect
        $parsedData = $this->parseData($books, $resourceOptions, 'books');

        // Create JSON response of parsed data
        return $this->response($parsedData);
    }
}
```

Now books' relations can be dynamically loaded using GET parameters.

`/books?includes[]=author&sort=title&limit=5`

Will return a collection of 5 `Book`s eager loaded with `Author`, sorted by title.

`/books?includes[]=author:ids`

Will return a collection of `Book`s eager loaded with the ID of their `Author`

`/books?includes[]=author:sideload`

Will return a collection of `Book`s and a eager loaded collection of their
`Author`s in the root scope.

[See mere about eager loading types in Optimus\Architect's README](https://github.com/esbenp/architect)

## Installation

```bash
composer require optimus/laravel-controller ~1.0
```

## Standards

This package is compliant with [PSR-1], [PSR-2] and [PSR-4]. If you notice compliance oversights,
please send a patch via pull request.

[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PSR-4]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md

## Testing

``` bash
$ phpunit
```

## Contributing

Please see [CONTRIBUTING](https://github.com/esbenp/laravel-controller/blob/master/CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](https://github.com/esbenp/laravel-controller/blob/master/LICENSE) for more information.
