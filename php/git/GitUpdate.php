<?php

namespace digitalhigh\GitUpdate;


class GitUpdate
{
    protected $repository;
    protected $lastUpdate;
    protected $cwd;

    public $branch;
    public $hasGit;
    public $revision;

    /**
     * GitUpdate constructor.
     * @param $repository
     */
    public function __construct($repository)
    {
        exec("git", $lines);
        $hasGit = (preg_match("/git help/", implode(" ", $lines)));
        if(basename($repository) === '.git')
        {
            $repository = dirname($repository);
        }

        $path = realpath($repository);

        if($repository === FALSE || $hasGit === FALSE)
        {
            $msg = $hasGit ? "No git Binary Found." : "Directory specified is not a repository.";
            write_log("$msg","ERROR");
            $this->hasGit = false;
            return false;
        }

        $this->hasGit = true;
        $this->repository = realpath($path);
        $this->cwd = getcwd();
        $this->revision = $this->gitExec('git rev-parse HEAD');
        $this->branch = $this->getBranch();
        $this->gitExec('git config --global http.sslVerify false');
        return true;
    }

    /**
     * Get the name of the current branch
     * @return string
     */
    public function getBranch()
    {
        $branch = $this->gitExec('git branch -a',true);
        if (is_array($branch)) foreach($branch as $line) {
            $values = explode (" ",$line);
            if ($values[0] == "*") return $values[1];
        }
        return 'master';
    }

    /**
     * Check the log for needed updates.
     * @param int $limit - Maximum number of commit messages to display
     * @return array - An array of short revision codes
     */
    public function checkMissing($limit=10)
    {
        $this->gitExec("git fetch");
        $refs = [];
        $branch = $this->branch;
        $command = "git log ..origin/$branch --oneline";
        exec($command, $lines);
        foreach($lines as $line) array_push($refs,explode(" ",$line)[0]);
        $commits = count($refs) ? $this->fetchCommits($refs,$limit) : [];
        return ['refs'=>$refs,'commits'=>$commits];
    }


    /**
     * Fetch an array of data for an array of specific commits
     * @param array $refs - An array of revision codes to fetch, use checkMissing
     * or stored data
     * @param int $limit - Max records to fetch
     * @return array - An array of data including revision, short revision, subject, body, author and data committed.
     */
    public function fetchCommits(array $refs,int $limit=10)
    {
        $commits = [];
        foreach ($refs as $ref) {
            $ref = trim($ref);
            $commit = [];
            $cmd = 'git log '.$ref.' -1 --pretty=format:"shortHead==%h||head==%H||subject==%s||body==%b||author==%aN||date==%aD"';
            $data = $this->gitExec($cmd);
            $lines = explode("||",$data);
            foreach($lines as $pair) {
                $data = explode("==",$pair);
                $key = $data[0];
                $value = $data[1];
                $commit[$key] = $value;
            }
            array_push($commits,$commit);
            if (count($commits) >= $limit) break;
        }
        return $commits;
    }

    public function update()
    {
        write_log("FUNCTION FIRED!!","INFO");
        $result = $this->gitExec("git pull",true);
        write_log("Install result: ".json_encode($result));
        $result = (preg_match("/updating/",strtolower(join(" ",$result))));
        if ($result) write_log("Update was successful!","ALERT"); else write_log("UPDATE FAILED.","ERROR");
        $this->revision = $this->gitExec('git rev-parse HEAD');
        return $result;
    }

    /**
     * @param $cmd - The git command to execute
     * @param bool $allLines - Whether or not to return the last line, or all lines as an array
     * @return string | array - Return values from command. Format depends on $allLines
     */
    protected function gitExec($cmd, $allLines=false) {
        chdir($this->repository);
        if ($allLines) {
            exec($cmd, $ret);
        } else {
            $ret = `$cmd`;
        }
        chdir($this->cwd);
        return $ret;
    }


}