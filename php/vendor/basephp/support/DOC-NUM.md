# Num

The `Base\Support\Num` class provides a convenient way to work with **numbers**.

[Learn More](README.md) about using the Base Support within your project.

## Namespace

Use the following namespace to include into your current project.

```php
use Base\Support\Num;
```


## Available Methods

These methods are available on a `Num` static class.

|Method                             |Return Type       |Description                          |
|---                                |---               |---                                  |
|`add($a, $b)`                      |`int`             | Add 2 numbers together |
|`subtract($a, $b)`                 |`int`       | Subtract 2 numbers from eachother |
|`divide($a, $b)`                   |`int`       | Divide 2 numbers |
|`multiply($a, $b)`                 |`int`       | Multiply 2 numbers |
|`percent($a, $b, $percent)`        |`int`       | Get the percentage of 2 numbers |
|`avg($array, $decmial)`            |`int`       | get average from an array of numbers |
|`average($array, $decmial)`        |`int`       | alias of `avg()` |
|`min($array)`                      |`int`       | Find the lowest number (min) within an array |
|`max($array)`                      |`int`       | Find the highest number (max) within an array |
|`total($array)`                    |`int`       | Total up all the numbers in array |
|`round($num, $decmial)`            |`int`       | Round a number |
|`currency($number, $decmial)`      |`int`       | format to a Currency (USD) |
|`cpm($impressions, $revenue, $decmial)` |`int`       | Get the CPM of the impressions served |
