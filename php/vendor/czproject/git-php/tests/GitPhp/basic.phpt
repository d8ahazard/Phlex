<?php
use Tester\Assert;
use Cz\Git\GitRepository;
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../../src/IGit.php';
require __DIR__ . '/../../src/GitRepository.php';

$repo = GitRepository::init(TEMP_DIR);

// repo already exists
Assert::exception(function() {
	GitRepository::init(TEMP_DIR);
}, 'Cz\Git\GitException');

Assert::same(realpath(TEMP_DIR), $repo->getRepositoryPath());

// repo is empty
Assert::exception(function() use ($repo) {
	$repo->getCurrentBranchName();
}, 'Cz\Git\GitException', 'Getting current branch name failed.');

Assert::false($repo->hasChanges());
Assert::null($repo->getTags());
Assert::null($repo->getBranches());

// init commit
$file = TEMP_DIR . '/first.txt';
file_put_contents($file, "Lorem\n\tipsum\ndolor sit\namet.\n");
$repo->addFile($file);
Assert::true($repo->hasChanges());
$repo->commit('First commit');

Assert::same('master', $repo->getCurrentBranchName());
Assert::same(array(
	'master',
), $repo->getBranches());


// second commit
$file = TEMP_DIR . '/second.txt';
file_put_contents($file, "Sit amet dolor ipsum lorem.\n");
$repo->addFile(array(
	$file,
));
Assert::true($repo->hasChanges());
$repo->commit('Second commit');
Assert::false($repo->hasChanges());


// remove second file
$repo->removeFile($file);
Assert::true($repo->hasChanges());
$repo->commit('Removed second file');
Assert::false($repo->hasChanges());


// Branches
$repo->createBranch('develop', TRUE);
Assert::same(array(
	'develop',
	'master',
), $repo->getBranches());
Assert::same('develop', $repo->getCurrentBranchName());

// ...change file
$file = TEMP_DIR . '/first.txt';
$content = file_get_contents($file);
$newContent = "$content\n\tchanged " . date('Y-m-d H:i:s');

Assert::false($repo->hasChanges());
file_put_contents($file, $newContent);
Assert::true($repo->hasChanges());
$repo->addFile($file);
$repo->commit('Changed first file.');
Assert::false($repo->hasChanges());

$repo->checkout('master');
Assert::same('master', $repo->getCurrentBranchName());
Assert::null($repo->getTags());
$repo->createTag('v0.9.0');
Assert::same(array(
	'v0.9.0',
), $repo->getTags());
Assert::false($repo->hasChanges());
$repo->merge('develop');
Assert::false($repo->hasChanges());

Assert::same($newContent, file_get_contents($file));

$repo->createTag('v2.0.0');
Assert::same(array(
	'v0.9.0',
	'v2.0.0',
), $repo->getTags());
$repo->removeBranch('develop');
Assert::same(array(
	'master',
), $repo->getBranches());

$repo->renameTag('v0.9.0', 'v1.0.0');
Assert::same(array(
	'v1.0.0',
	'v2.0.0',
), $repo->getTags());
$repo->checkout('v1.0.0');
Assert::same($content, file_get_contents($file));

$repo->checkout('v2.0.0');
Assert::false($repo->hasChanges());
$repo->renameFile($file, $newFile = TEMP_DIR . '/renamed.txt');
Assert::true($repo->hasChanges());
$repo->commit('First file renamed.');
Assert::false(is_file($file));
Assert::true(is_file($newFile));
Assert::same($newContent, file_get_contents($newFile));

// creating repo object
$newRepo = new GitRepository(TEMP_DIR . '/.git');
Assert::same(realpath(TEMP_DIR), $newRepo->getRepositoryPath());

Assert::exception(function () {
	new GitRepository(TEMP_DIR . '/bad/bad/bad/repo/');
}, 'Cz\Git\GitException');
