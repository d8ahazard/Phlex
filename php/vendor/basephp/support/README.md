> **Note:** This repository is a **standalone package** and does *not* require the BasePHP framework. If you would like to learn more about the framework, visit [BasePHP](https://github.com/basephp/framework). You may use this essential package and it's common functionality in your packages, projects and applications.

# Base Support (Common PHP Helpers)

[![Build Status](https://travis-ci.org/basephp/support.svg?branch=1.3)](https://travis-ci.org/basephp/support) [![Coverage Status](https://coveralls.io/repos/github/basephp/support/badge.svg?branch=1.3)](https://coveralls.io/github/basephp/support?branch=1.3) [![Slack](http://timothymarois.com/a/slack-02.svg)](https://join.slack.com/t/basephp/shared_invite/enQtNDI0MzQyMDE0MDAwLWU3Nzg0Yjk4MjM0OWVmZDZjMjEyYWE2YjA1ODFhNjI2MzI3MjAyOTIyOTRkMmVlNWNhZWYzMTIwZDJlOWQ2ZTA)

This package will help **simplify your PHP development**. PHP core functions are never consistent with it's naming conventions, and this package gives you an alternative that simplifies the use of common php functionality. This package has **no dependencies** and can be used in any of your PHP projects.


## Installation

Install using composer (or download and include manually).

```
composer require basephp/support
```
*Note: If you're using [BasePHP](https://github.com/basephp/basephp), this package is already included.*


## Included

*This package includes the following:*

|Class            |Documentation                         |Subject                         |Description                     |
|---              |---                                   |---                   |---                             |
|`Arr`            |[Documentation](DOC-ARR.md)           | Array                | Working with Arrays            |
|`Str`            |[Documentation](DOC-STR.md)           | String               | Working with Strings           |
|`Num`            |[Documentation](DOC-NUM.md)           | Number               | Working with Numbers           |
|`Filesystem`     |[Documentation](DOC-FILESYSTEM.md)    | File System          | Working with the File System   |
|`Collection`     |[Documentation](DOC-COLLECTION.md)    | Collections          | Wrapper for working with arrays of data |
|`Container`      |[Documentation](DOC-CONTAINER.md)     | Instances            | Wrapper for working with instances |
|`Pipeline`       |[Documentation](DOC-PIPELINE.md)      | Instances            | Ability to pass an instance through stages |


## About

This package was designed to be **independent from any specific framework** and to give all your projects consistency.

Inspired by core helpers from the Laravel framework.


## Contributions

Anyone can contribute to the library. Please do so by posting issues when you've found something that is unexpected or sending a pull request for improvements.


## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
