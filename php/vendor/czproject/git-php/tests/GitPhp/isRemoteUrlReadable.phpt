<?php
use Tester\Assert;
use Cz\Git\GitRepository;
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../../src/IGit.php';
require __DIR__ . '/../../src/GitRepository.php';

Assert::true(GitRepository::isRemoteUrlReadable('https://github.com/czproject/git-php'));
Assert::false(GitRepository::isRemoteUrlReadable('https://github.com/czproject/git-php-404'));
