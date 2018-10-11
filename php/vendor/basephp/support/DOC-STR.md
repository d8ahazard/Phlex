# Str

The `Base\Support\Str` class provides a convenient way to work with **strings**.

[Learn More](README.md) about using the Base Support within your project.

## Namespace

Use the following namespace to include into your current project.

```php
use Base\Support\Str;
```


## Check String

This will check if the string contains the characters `can`

```php
if (Str::is('*can*','Music can be loud. Music can be soft.'))
{
    // do something
}
```


## URI Format String

This will format the string for the use of urls.

```php
$name = 'timothy marois';

$url = 'https://github.com/'.Str::uri($name,'-');
// formats: /timothy-marois

$url = 'https://github.com/'.Str::uri($name,'');
// formats: /timothymarois
```

## Available Methods

These methods are available on a `Str` static class.

|Method                             |Return Type       |Description                          |
|---                                |---               |---                                  |
|`upper($str)`                      |`string`          | Converts the string to uppercase |
|`lower($str)`                      |`string`          | Converts the string to lowercase |
|`title($str)`                      |`string`          | Converts the string to title-case `My Title` |
|`ucfirst($str)`                    |`string`          | Capitalize first letter in string |
|`length($str)`                     |`int`             | the length of the given string |
|`contains($haystack, $needles)`    |`bool`            | if a given string contains a given substring. |
|`limit($value, $limit, $end)`      |`string`          | Limit the number of characters in a string. |
|`words($value, $limit, $end)`      |`string`          | Limit the number of words in a string. |
|`substr($string, $start, $length)` |`string`          | Returns the portion of string |
|`is($pattern, $value)`             |`bool`            | if a given string matches a given pattern |
|`clean($str, $strict)`             |`string`          | convert string to alpha-numeric |
|`uri($uri, $separator)`            |`string`          | convert string to URI format |
|`wordArray($string,$min,$max,$unique)`  |`array`      | Returns an array of all the words within a string |
