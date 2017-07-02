<?php
use Tester\Assert;
use Cz\Git\GitRepository;
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../../src/IGit.php';
require __DIR__ . '/../../src/GitRepository.php';

Assert::same('repo', GitRepository::extractRepositoryNameFromUrl('/path/to/repo.git'));
Assert::same('repo', GitRepository::extractRepositoryNameFromUrl('/path/to/repo/.git'));
Assert::same('foo', GitRepository::extractRepositoryNameFromUrl('host.xz:foo/.git'));
Assert::same('repo', GitRepository::extractRepositoryNameFromUrl('file:///path/to/repo.git/'));
Assert::same('git-php', GitRepository::extractRepositoryNameFromUrl('https://github.com/czproject/git-php.git'));
Assert::same('git-php', GitRepository::extractRepositoryNameFromUrl('git@github.com:czproject/git-php.git'));
