<?php

	/**
	 * Default implementation of IGit interface
	 *
	 * @author  Jan Pecha, <janpecha@email.cz>
	 * @license New BSD License (BSD-3), see file license.md
	 */

	namespace Cz\Git;

	class GitRepository implements IGit
	{
		/** @var  string */
		protected $repository;

		/** @var  string|NULL  @internal */
		protected $cwd;


		/**
		 * @param  string
		 */
		public function __construct($repository)
		{
			if(basename($repository) === '.git')
			{
				$repository = dirname($repository);
				// Fix ssl errors.
				exec('git config --global http.sslVerify false');
			}

			$this->repository = realpath($repository);

			if($this->repository === FALSE)
			{
				throw new GitException("Repository '$repository' not found.");
			}
		}


		/**
		 * @return string
		 */
		public function getRepositoryPath()
		{
			return $this->repository;
		}


		/**
		 * Creates a tag.
		 * `git tag <name>`
		 * @param  string
		 * @throws Cz\Git\GitException
		 * @return self
		 */
		public function createTag($name)
		{
			return $this->begin()
				->run('git tag', $name)
				->end();
		}


		/**
		 * Removes tag.
		 * `git tag -d <name>`
		 * @param  string
		 * @throws Cz\Git\GitException
		 * @return self
		 */
		public function removeTag($name)
		{
			return $this->begin()
				->run('git tag', array(
					'-d' => $name,
				))
				->end();
		}


		/**
		 * Renames tag.
		 * `git tag <new> <old>`
		 * `git tag -d <old>`
		 * @param  string
		 * @param  string
		 * @throws Cz\Git\GitException
		 * @return self
		 */
		public function renameTag($oldName, $newName)
		{
			return $this->begin()
				// http://stackoverflow.com/a/1873932
				// create new as alias to old (`git tag NEW OLD`)
				->run('git tag', $newName, $oldName)
				// delete old (`git tag -d OLD`)
				->removeTag($oldName) // WARN! removeTag() calls end() method!!!
				->end();
		}


		/**
		 * Returns list of tags in repo.
		 * @return string[]|NULL  NULL => no tags
		 */
		public function getTags()
		{
			return $this->extractFromCommand('git tag', 'trim');
		}


		/**
		 * Merges branches.
		 * `git merge <options> <name>`
		 * @param  string
		 * @param  array|NULL
		 * @throws Cz\Git\GitException
		 * @return self
		 */
		public function merge($branch, $options = NULL)
		{
			return $this->begin()
				->run('git merge', $options, $branch)
				->end();
		}


		/**
		 * Creates new branch.
		 * `git branch <name>`
		 * (optionaly) `git checkout <name>`
		 * @param  string
		 * @param  bool
		 * @throws Cz\Git\GitException
		 * @return self
		 */
		public function createBranch($name, $checkout = FALSE)
		{
			$this->begin();

			// git branch $name
			$this->run('git branch', $name);

			if($checkout)
			{
				$this->checkout($name);
			}

			return $this->end();
		}


		/**
		 * Removes branch.
		 * `git branch -d <name>`
		 * @param  string
		 * @throws Cz\Git\GitException
		 * @return self
		 */
		public function removeBranch($name)
		{
			return $this->begin()
				->run('git branch', array(
					'-d' => $name,
				))
				->end();
		}


		/**
		 * Gets name of current branch
		 * `git branch` + magic
		 * @return string
		 * @throws Cz\Git\GitException
		 */
		public function getCurrentBranchName()
		{
			try
			{
				$branch = $this->extractFromCommand('git branch -a', function($value) {
					if(isset($value[0]) && $value[0] === '*')
					{
						return trim(substr($value, 1));
					}

					return FALSE;
				});

				if(is_array($branch))
				{
					return $branch[0];
				}
			}
			catch(GitException $e) {}
			throw new GitException('Getting current branch name failed.');
		}


		/**
		 * Returns list of all (local & remote) branches in repo.
		 * @return string[]|NULL  NULL => no branches
		 */
		public function getBranches()
		{
			return $this->extractFromCommand('git branch -a', function($value) {
				return trim(substr($value, 1));
			});
		}


		/**
		 * Returns list of local branches in repo.
		 * @return string[]|NULL  NULL => no branches
		 */
		public function getLocalBranches()
		{
			return $this->extractFromCommand('git branch', function($value) {
				return trim(substr($value, 1));
			});
		}


		/**
		 * Checkout branch.
		 * `git checkout <branch>`
		 * @param  string
		 * @throws Cz\Git\GitException
		 * @return self
		 */
		public function checkout($name)
		{
			return $this->begin()
				->run('git checkout', $name)
				->end();
		}


		/**
		 * Removes file(s).
		 * `git rm <file>`
		 * @param  string|string[]
		 * @throws Cz\Git\GitException
		 * @return self
		 */
		public function removeFile($file)
		{
			if(!is_array($file))
			{
				$file = func_get_args();
			}

			$this->begin();

			foreach($file as $item)
			{
				$this->run('git rm', $item, '-r');
			}

			return $this->end();
		}


		/**
		 * Adds file(s).
		 * `git add <file>`
		 * @param  string|string[]
		 * @throws Cz\Git\GitException
		 * @return self
		 */
		public function addFile($file)
		{
			if(!is_array($file))
			{
				$file = func_get_args();
			}

			$this->begin();

			foreach($file as $item)
			{
				// TODO: ?? is file($repo . / . $item) ??
				$this->run('git add', $item);
			}

			return $this->end();
		}


		/**
		 * Adds all created, modified & removed files.
		 * `git add --all`
		 * @throws Cz\Git\GitException
		 * @return self
		 */
		public function addAllChanges()
		{
			return $this->begin()
				->run('git add --all')
				->end();
		}


		/**
		 * Renames file(s).
		 * `git mv <file>`
		 * @param  string|string[]  from: array('from' => 'to', ...) || (from, to)
		 * @param  string|NULL
		 * @throws Cz\Git\GitException
		 * @return self
		 */
		public function renameFile($file, $to = NULL)
		{
			if(!is_array($file)) // rename(file, to);
			{
				$file = array(
					$file => $to,
				);
			}

			$this->begin();

			foreach($file as $from => $to)
			{
				$this->run('git mv', $from, $to);
			}

			return $this->end();
		}


		/**
		 * Commits changes
		 * `git commit <params> -m <message>`
		 * @param  string
		 * @param  string[]  param => value
		 * @throws Cz\Git\GitException
		 * @return self
		 */
		public function commit($message, $params = NULL)
		{
			if(!is_array($params))
			{
				$params = array();
			}

			return $this->begin()
				->run("git commit", $params, array(
					'-m' => $message,
				))
				->end();
		}


		/**
		 * Exists local changes?
		 * `git status` + magic
		 * @return bool
		 */
		public function hasLocalChanges()
		{
			$this->begin();
			$lastLine = exec('git status');
			$this->end();
			return !preg_match("/nothing to commit/",$lastLine);
		}


		/**
		 * Exists local changes?
		 * `git status` + magic
		 * @return bool
		 */
		public function hasRemoteChanges()
		{
			$this->begin();
			exec('git status',$lines);
			$this->end();
			return (preg_match("/fast-forwarded/",implode(" ",$lines)));
		}


		/**
		 * @deprecated
		 */
		public function isChanges()
		{
			return $this->hasLocalChanges();
		}

		/**
		 * Read Log Messages to JSON
		 *
		 * @param  string $branch - The branch to read logs from
		 * @param  string|int $limit - Number of commits to return, or the commit hash to return logs until
		 * @throws Cz\Git\GitException
		 * @return array $logs
		 */

		public function readLog($branch="origin/master",$limit=10)
		{
			$output = "";
			$this->begin();
			$commits = [];
			if (!is_numeric($limit)) {
				$command = "git log $limit..$branch --oneline";
				exec($command,$shorts);
			}
			if (count($shorts)) $limit = count($shorts)-1;
			if (is_numeric($limit)) {
				$i = 0;
				do {
					$command = "git log $branch -1 --pretty=format:";
					if ($i) $command = "git log $branch --skip $i -1 --pretty=format:";
					$head = exec($command.'"%H"');
					$shortHead = exec($command.'"%h"');
					$subject = exec($command.'"%s"');
					exec($command.'"%b"',$body);
					$body = implode('<br>',$body);
					$author = exec($command.'"%aN"');
					$date = exec($command.'"%aD"');
					$commit = [
						'head'=>$head,
						'shortHead'=>$shortHead,
						'subject'=>$subject,
						'body'=>$body,
						'author'=>$author,
						'date'=>$date
					];
					array_push($commits,$commit);
					$i++;
				} while ($i <= $limit);
			}

			$this->end();
			return $commits;
		}

		public function getRev() {
			$this->begin();
			$head = exec('git rev-parse HEAD');
			$this->end();
			return $head;
		}

		/**
		 * Pull changes from a remote
		 * @param  string|NULL
		 * @param  array|NULL
		 * @return self
		 * @throws GitException
		 */
		public function pull($remote = NULL, array $params = NULL)
		{
			if(!is_array($params))
			{
				$params = array();
			}

			$this->begin();
			$result = $this->run("git pull $remote", $params);
			$this->end();
			return $result;
		}


		/**
		 * Push changes to a remote
		 * @param  string|NULL
		 * @param  array|NULL
		 * @return self
		 * @throws GitException
		 */
		public function push($remote = NULL, array $params = NULL)
		{
			if(!is_array($params))
			{
				$params = array();
			}

			return $this->begin()
				->run("git push $remote", $params)
				->end();
		}


		/**
		 * Run fetch command to get latest branches
		 * @param  string|NULL
		 * @param  array|NULL
		 * @return string Result
		 */
		public function fetch($remote = NULL, array $params = NULL)
		{
			if(!is_array($params))
			{
				$params = array();
			}

				$this->begin();
				$result = $this->run("git fetch $remote", $params);
				$this->end();
				return $result;
		}


		/**
		 * Adds new remote repository
		 * @param  string
		 * @param  string
		 * @param  array|NULL
		 * @return self
		 */
		public function addRemote($name, $url, array $params = NULL)
		{
			return $this->begin()
				->run('git remote add', $params, $name, $url)
				->end();
		}


		/**
		 * Renames remote repository
		 * @param  string
		 * @param  string
		 * @return self
		 */
		public function renameRemote($oldName, $newName)
		{
			return $this->begin()
				->run('git remote rename', $oldName, $newName)
				->end();
		}


		/**
		 * Removes remote repository
		 * @param  string
		 * @return self
		 */
		public function removeRemote($name)
		{
			return $this->begin()
				->run('git remote remove', $name)
				->end();
		}


		/**
		 * Changes remote repository URL
		 * @param  string
		 * @param  string
		 * @param  array|NULL
		 * @return self
		 */
		public function setRemoteUrl($name, $url, array $params = NULL)
		{
			return $this->begin()
				->run('git remote set-url', $params, $name, $url)
				->end();
		}


		/**
		 * @return self
		 */
		protected function begin()
		{
			if($this->cwd === NULL) // TODO: good idea??
			{
				$this->cwd = getcwd();
				chdir($this->repository);
			}

			return $this;
		}


		/**
		 * @return self
		 */
		protected function end()
		{
			if(is_string($this->cwd))
			{
				chdir($this->cwd);
			}

			$this->cwd = NULL;
			return $this;
		}


		/**
		 * @param  string
		 * @param  callback|NULL
		 * @return string[]|NULL
		 */
		protected function extractFromCommand($cmd, $filter = NULL)
		{
			$output = array();
			$exitCode = NULL;

			$this->begin();
			exec("$cmd", $output, $exitCode);
			$this->end();

			if($exitCode !== 0 || !is_array($output))
			{
				throw new GitException("Command $cmd failed.");
			}

			if($filter !== NULL)
			{
				$newArray = array();

				foreach($output as $line)
				{
					$value = $filter($line);

					if($value === FALSE)
					{
						continue;
					}

					$newArray[] = $value;
				}

				$output = $newArray;
			}

			if(!isset($output[0])) // empty array
			{
				return NULL;
			}

			return $output;
		}


		/**
		 * Runs command.
		 * @param  string|array
		 * @return string Result
		 */
		protected function run($cmd/*, $options = NULL*/)
		{
			$args = func_get_args();
			$cmd = self::processCommand($args);
			exec($cmd, $output, $ret);
			$result = implode (" ",$output);
			if($ret !== 0)
			{
				$result = "Command '$cmd' failed (exit-code $ret): $ret";
			}

			return $result;
		}


		protected static function processCommand(array $args)
		{
			$cmd = array();

			$programName = array_shift($args);

			foreach($args as $arg)
			{
				if(is_array($arg))
				{
					foreach($arg as $key => $value)
					{
						$_c = '';

						if(is_string($key))
						{
							$_c = "$key ";
						}

						$cmd[] = $_c . escapeshellarg($value);
					}
				}
				elseif(is_scalar($arg) && !is_bool($arg))
				{
					$cmd[] = escapeshellarg($arg);
				}
			}

			return "$programName " . implode(' ', $cmd);
		}


		/**
		 * Init repo in directory
		 * @param  string
		 * @param  array|NULL
		 * @return self
		 * @throws GitException
		 */
		public static function init($directory, array $params = NULL)
		{
			if(is_dir("$directory/.git"))
			{
				throw new GitException("Repo already exists in $directory.");
			}

			if(!is_dir($directory) && !@mkdir($directory, 0777, TRUE)) // intentionally @; not atomic; from Nette FW
			{
				throw new GitException("Unable to create directory '$directory'.");
			}

			$cwd = getcwd();
			chdir($directory);
			exec(self::processCommand(array(
				'git init',
				$params,
				$directory,
			)), $output, $returnCode);

			if($returnCode !== 0)
			{
				throw new GitException("Git init failed (directory $directory).");
			}

			$repo = getcwd();
			chdir($cwd);

			return new static($repo);
		}


		/**
		 * Clones GIT repository from $url into $directory
		 * @param  string
		 * @param  string|NULL
		 * @param  array|NULL
		 * @return self
		 */
		public static function cloneRepository($url, $directory = NULL, array $params = NULL)
		{
			if($directory !== NULL && is_dir("$directory/.git"))
			{
				throw new GitException("Repo already exists in $directory.");
			}

			$cwd = getcwd();

			if($directory === NULL)
			{
				$directory = self::extractRepositoryNameFromUrl($url);
				$directory = "$cwd/$directory";
			}
			elseif(!self::isAbsolute($directory))
			{
				$directory = "$cwd/$directory";
			}

			if ($params === NULL) {
				$params = '-q';
			}

			exec(self::processCommand(array(
				'git clone',
				$params,
				$url,
				$directory
			)), $output, $returnCode);

			if($returnCode !== 0)
			{
				throw new GitException("Git clone failed (directory $directory).");
			}

			return new static($directory);
		}


		/**
		 * @param  string
		 * @param  array|NULL
		 * @return bool
		 */
		public static function isRemoteUrlReadable($url, array $refs = NULL)
		{
			exec(self::processCommand(array(
				'GIT_TERMINAL_PROMPT=0 git ls-remote',
				'--heads',
				'--quiet',
				'--exit-code',
				$url,
				$refs,
			)) . ' 2>&1', $output, $returnCode);

			return $returnCode === 0;
		}


		/**
		 * @param  string  /path/to/repo.git | host.xz:foo/.git | ...
		 * @return string  repo | foo | ...
		 */
		public static function extractRepositoryNameFromUrl($url)
		{
			// /path/to/repo.git => repo
			// host.xz:foo/.git => foo
			$directory = rtrim($url, '/');
			if(substr($directory, -5) === '/.git')
			{
				$directory = substr($directory, 0, -5);
			}

			$directory = basename($directory, '.git');

			if(($pos = strrpos($directory, ':')) !== FALSE)
			{
				$directory = substr($directory, $pos + 1);
			}

			return $directory;
		}


		/**
		 * Is path absolute?
		 * Method from Nette\Utils\FileSystem
		 * @link   https://github.com/nette/nette/blob/master/Nette/Utils/FileSystem.php
		 * @return bool
		 */
		public static function isAbsolute($path)
		{
			return (bool) preg_match('#[/\\\\]|[a-zA-Z]:[/\\\\]|[a-z][a-z0-9+.-]*://#Ai', $path);
		}
	}
