Git-PHP
=======

Library for work with Git repository in PHP.

Usage
-----

``` php
<?php
	// create repo object
	$repo = new Cz\Git\GitRepository('/path/to/repo');

	// create a new file in repo
	$filename = $repo->getRepositoryPath() . '/readme.txt';
	file_put_contents($filename, "Lorem ipsum
		dolor
		sit amet
	");

	// commit
	$repo->addFile($filename);
	$repo->commit('init commit');
```


Initialization of empty repository
----------------------------------

``` php
<?php
$repo = GitRepository::init('/path/to/repo-directory');
```

With parameters:

``` php
<?php
$repo = GitRepository::init('/path/to/repo-directory', array(
	'--bare', // creates bare repo
));
```


Cloning of repository
---------------------

``` php
<?php
// Cloning of repository into subdirectory 'git-php' in current working directory
$repo = GitRepository::cloneRepository('https://github.com/czproject/git-php.git');

// Cloning of repository into own directory
$repo = GitRepository::cloneRepository('https://github.com/czproject/git-php.git', '/path/to/my/subdir');
```


Basic operations
----------------

``` php
<?php
$repo->hasChanges();    // returns boolean
$repo->commit('commit message');
$repo->merge('branch-name');
$repo->checkout('master');

$repo->getRepositoryPath();

// adds files into commit
$repo->addFile('file.txt');
$repo->addFile('file1.txt', 'file2.txt');
$repo->addFile(array('file3.txt', 'file4.txt'));

// renames files in repository
$repo->renameFile('old.txt', 'new.txt');
$repo->renameFile(array(
    'old1.txt' => 'new1.txt',
    'old2.txt' => 'new2.txt',
));

// removes files from repository
$repo->removeFile('file.txt');
$repo->removeFile('file1.txt', 'file2.txt');
$repo->removeFile(array('file3.txt', 'file4.txt'));

// adds all changes in repository
$repo->addAllChanges();
```



Branches
--------

``` php
<?php
// gets list of all repository branches (remotes & locals)
$repo->getBranches();

// gets list of all local branches
$repo->getLocalBranches();

// gets name of current branch
$repo->getCurrentBranchName();

// creates new branch
$repo->createBranch('new-branch');

// creates new branch and checkout
$repo->createBranch('patch-1', TRUE);

// removes branch
$repo->removeBranch('branch-name');
```


Tags
----

``` php
<?php
// gets list of all tags in repository
$repo->getTags();

// creates new tag
$repo->createTag('v1.0.0');

// renames tag
$repo->renameTag('old-tag-name', 'new-tag-name');

// removes tag
$repo->removeTag('tag-name');
```


Remotes
-------

``` php
<?php
// pulls changes from remote
$repo->pull('remote-name', array('--options'));
$repo->pull('origin');

// pushs changes to remote
$repo->push('remote-name', array('--options'));
$repo->push('origin');

// fetchs changes from remote
$repo->fetch('remote-name', array('--options'));
$repo->fetch('origin');

// adds remote repository
$repo->addRemote('remote-name', 'repository-url', array('--options'));
$repo->addRemote('origin', 'git@github.com:czproject/git-php.git');

// renames remote
$repo->renameRemote('old-remote-name', 'new-remote-name');
$repo->renameRemote('origin', 'upstream');

// removes remote
$repo->removeRemote('remote-name');
$repo->removeRemote('origin');

// changes remote URL
$repo->setRemoteUrl('remote-name', 'new-repository-url');
$repo->removeRemote('upstream', 'https://github.com/czproject/git-php.git');
```

**Troubleshooting - How to provide username and password for commands**

1) use SSH instead of HTTPS - https://stackoverflow.com/a/8588786
2) store credentials to *Git Credential Storage*
	* http://www.tilcode.com/push-github-without-entering-username-password-windows-git-bash/
	* https://help.github.com/articles/caching-your-github-password-in-git/
	* https://git-scm.com/book/en/v2/Git-Tools-Credential-Storage
3) insert user and password into remote URL - https://stackoverflow.com/a/16381160
	* `git remote add origin https://user:password@server/path/repo.git`
4) for `push()` you can use `--repo` argument - https://stackoverflow.com/a/12193555
	* `$git->push(NULL, array('--repo' => 'https://user:password@server/path/repo.git'));`


Custom methods
--------------

You can create custom methods. For example:

``` php
class OwnGitRepository extends \Cz\Git\GitRepository
{
	public function setRemoteBranches($name, array $branches)
	{
		return $this->begin()
			->run('git remote set-branches', $name, $branches)
			->end();
	}
}


$repo = new OwnGitRepository('/path/to/repo');
$repo->addRemote('origin', 'repository-url');
$repo->setRemoteBranches('origin', array(
	'branch-1',
	'branch-2',
));
```


Installation
------------

[Download a latest package](https://github.com/czproject/git-php/releases) or use [Composer](http://getcomposer.org/):

```
composer require czproject/git-php
```

Library requires PHP 5.4 or later and `git` client (path to Git must be in system variable `PATH`).

Git installers:

* for Linux - https://git-scm.com/download/linux
* for Windows - https://git-scm.com/download/win
* for others - https://git-scm.com/downloads

------------------------------

License: [New BSD License](license.md)
<br>Author: Jan Pecha, https://www.janpecha.cz/
