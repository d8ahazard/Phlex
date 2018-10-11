# Collection

The `Base\Support\Collection` class provides a convenient way to work with a **collection of arrays**.

[Learn More](README.md) about using the Base Support within your project.


## Namespace

Use the following namespace to include into your current project.

```php
use Base\Support\Collection;
```

## Creating Collections

Creating a collection is very simple and easy. First you define the new collection class, and then you inject the array into the class construct (as shown below).

A simple collection:

```php
$collection = new Collection([1,2,3,4,5,6]);
```

A collection of products:

```php
// collection with all your products
$collection = new Collection([
    ['category' => 'furniture', 'product' => 'Chair'],
    ['category' => 'software', 'product' => 'Atom'],
    ['category' => 'furniture', 'product' => 'Desk']
]);
```

## Available Methods

These methods are available on a `new Collection` instance.

|Method                         |Return Type       |Description                          |
|---                            |---               |---                                  |
|`all()`                        |`array`           | Get all items in collection |
|`has($key)`                    |`boolean`         | Returns `true` if the key is defined |
|`get($key, $default)`          |`array`           | Get the specified value |
|`replace($array)`              |`self`            | Replace all items in collection |
|`set($key, $value)`            |`self`            | Set a given value into the collection |
|`first()`                      |`mixed`           | Get the first item from the collection |
|`last()`                       |`mixed`           | Get the last item from the collection |
|`shuffle($seed)`               |`new Collection`  | Returns a new collection shuffled |
|`slice($offset, $length)`      |`new Collection`  | Slice the collection and return a new instance |
|`shift()`                      |`mixed`           | Get and remove the first item from collection |
|`reverse()`                    |`new Collection`  | Reverse the items and return a new collection |
|`remove($key)`                 |`self`            | Remove an item from collection |
|`count()`                      |`int`             | Count how many items are in collection |
|`take($limit)`                 |`new Collection`  | Take the first or last ($limit) from collection |
|`search($value, $strict)`      |`mixed`           | Search collection and Return the a given value |
|`implode($value, $glue)`       |`string`          | Concatenate values of a given key as a string |
|`keys()`                       |`new Collection`  | Get the keys of the collection items |
|`values()`                     |`new Collection`  | Reset the keys on the underlying array |
|`map($callback)`               |`new Collection`  | Run a map over each of the items |
|`pluck($value, $key)`          |`new Collection`  | Get the values of a given key |
|`random($number)`              |`new Collection`  | Get items randomly from the collection |
|`except($keys)`                |`new Collection`  | Get all items except for those with the specified keys |
|`only($keys)`                  |`new Collection`  | Get the items with the specified keys |
|`only($keys)`                    |`new Collection`  | Get the items with the specified keys |
|`sort($callback)`                |`new Collection`  | Sort through each item with a callback |
|`sortBy($callback, $dir)`        |`new Collection`  | Sort the collection using the given callback |
|`filter($callback)`              |`new Collection`  | Run a filter over each of the items |
|`where($key, $operator, $value)` |`new Collection`  | Filter items by the given key value pair |
|`whereIn($key, $values)`         |`new Collection`  | Filter items by the given key value pair |
|`push($value)`                   |`self`            | Push an item onto the end of the collection |
|`each($callback)`                |`self`            | Execute a callback over each item |
|`pull($key, $default)`           |`mixed`           | Get and remove an item from the collection |
|`put($key, $default)`            |`self`            | Put an item in the collection by key |
|`reject($callback)`              |`new Collection`  | Return a collection of items that do not pass the test |
|`unique($key, $string)`          |`new Collection`  | Return only unique items from the collection array |
|`groupBy($groupBy)`              |`new Collection`  | Group an associative array by a field or using a callback |
|`merge($items)`                  |`new Collection`  | Merge the collection with the given items |
|`combine($values)`               |`new Collection`  | New collection with keys and another for its values |
|`union($items)`                  |`new Collection`  | Union the collection with the given items |
|`toJson()`                       |`string`          | Get the collection of items as JSON. |
|`toArray()`                      |`array`           | Get the collection of items as ARRAY. |
