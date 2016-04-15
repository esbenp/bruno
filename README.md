# Bruno

[![Latest Version](https://img.shields.io/github/release/esbenp/bruno.svg?style=flat-square)](https://github.com/esbenp/bruno/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/travis/esbenp/bruno/master.svg?style=flat-square)](https://travis-ci.org/esbenp/bruno)
[![Coverage Status](https://img.shields.io/coveralls/esbenp/bruno.svg?style=flat-square)](https://coveralls.io/github/esbenp/bruno)
[![Total Downloads](https://img.shields.io/packagist/dt/optimus/bruno.svg?style=flat-square)](https://packagist.org/packages/optimus/bruno)

## Introduction

A Laravel base controller class and a trait that will enable to add filtering, sorting, eager loading and pagination to your
resource URLs.

**Dedicated to Giordano Bruno**

This package is named after my hero Giordano Bruno. A true visionary who dared to dream beyond what was thought possible.
For his ideas and his refusal to renounce them he was burned to the stake in 1600.
[I highly recommend this short cartoon on his life narrated by Neil deGrasse Tyson](https://vimeo.com/89241669).

## Functionality

* Parse GET parameters for dynamic eager loading of related resources, sorting and pagination
* Advanced filtering of resources using filter groups
* Use [Optimus\Architect](https://github.com/esbenp/architect) for sideloading, id loading or embedded loading of related resources
* ... [Ideas for new functionality is welcome here](https://github.com/esbenp/bruno/issues/new)

## Tutorial

To get started with Bruno I highly recommend my article on
[resource controls in Laravel APIs](http://esbenp.github.io/2016/04/15/modern-rest-api-laravel-part-2/)

## Installation

```bash
composer require optimus/bruno ~1.0
```

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
Sort | array | Property to sort by, e.g. 'title'
Limit | integer | Limit of resources to return
Page | integer | For use with limit
Filter_groups | array | Array of filter groups. See below for syntax.

### Implementation

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

## Syntax documentation

### Eager loading

**Simple eager load**

`/books?includes[]=author`

Will return a collection of 5 `Book`s eager loaded with `Author`.

**IDs mode**

`/books?includes[]=author:ids`

Will return a collection of `Book`s eager loaded with the ID of their `Author`

**Sideload mode**

`/books?includes[]=author:sideload`

Will return a collection of `Book`s and a eager loaded collection of their
`Author`s in the root scope.

[See mere about eager loading types in Optimus\Architect's README](https://github.com/esbenp/architect)

### Pagination

Two parameters are available: `limit` and `page`. `limit` will determine the number of
records per page and `page` will determine the current page.

`/books?limit=10&page=3`

Will return books number 30-40.

### Sorting

Should be defined as an array of sorting rules. They will be applied in the
order of which they are defined.

**Sorting rules**

Property | Value type | Description
-------- | ---------- | -----------
key | string | The property of the model to sort by
direction | ASC or DESC | Which direction to sort the property by

**Example**

```json
[
    {
        "key": "title",
        "direction": "ASC"
    }, {
        "key": "year",
        "direction": "DESC"
    }
]
```

Will result in the books being sorted by title in ascending order and then year
in descending order.

### Filtering

Should be defined as an array of filter groups.

**Filter groups**

Property | Value type | Description
-------- | ---------- | -----------
or | boolean | Should the filters in this group be grouped by logical OR or AND operator
filters | array | Array of filters (see syntax below)

**Filters**

Property | Value type | Description
-------- | ---------- | -----------
key | string | The property of the model to filter by
value | mixed | The value to search for
operator | string | The filter operator to use (see different types below)
not | boolean | Negate the filter

**Operators**

Type | Description | Example
---- | ----------- | -------
ct | String contains | `ior` matches `Giordano Bruno` and `Giovanni`
sw | Starts with | `Gior` matches `Giordano Bruno` but not `Giovanni`
ew | Ends with | `uno` matches `Giordano Bruno` but not `Giovanni`
eq | Equals | `Giordano Bruno` matches `Giordano Bruno` but not `Bruno`
gt | Greater than | `1548` matches `1600` but not `1400`
lt | Lesser than | `1600` matches `1548` but not `1700`
in | In array | `['Giordano', 'Bruno']` matches `Giordano` and `Bruno` but not `Giovanni`

**Special values**

Value | Description
----- | -----------
null (string) | The property will be checked for NULL value
(empty string) | The property will be checked for NULL value

#### Custom filters

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

Please see [CONTRIBUTING](https://github.com/esbenp/bruno/blob/master/CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](https://github.com/esbenp/bruno/blob/master/LICENSE) for more information.
