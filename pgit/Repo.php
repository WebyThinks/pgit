<?php
/************************************************************\
* PGit - PHP Git Implementation                              *
*   http://github.com/zordtk/pgit                            *
*   Written By Jeremy Harmon <jeremy.harmon@zoho.com>        *
\************************************************************/

/**
 * Contains the Repo implementation
 */

namespace PGit;

require_once('Binary.php');

/**
 * The Repo class represents a Git repository.
 *
 * Repo is the main object in PGit that you will use to read information
 * such as the head commit in a given ref, or a object from the repository.
 */
class Repo
{
    /**
     * Full path to the repository directory.
     */
    private $mRepoPath;

    /**
     * List of pack files found.
     */
    private $mPacks = array();

    /**
     * Opens the repository and scans for pack files.
     *
     * @param string $Path Path to the repository
     */
    private function __construct($Path)
    {
        $this->mRepoPath = $Path;

        // Index pack files
        $dirHandle = opendir("$this->mRepoPath/objects/pack");
        if( $dirHandle !== false )
        {
            while( ($File = readdir($dirHandle)) !== false )
            {
                if (preg_match('#^pack-([0-9a-fA-F]{40})\.idx$#', $File, $Hash))
                    $this->mPacks[] = array('fileName' => $Hash[1], 'hash' => '');
            }
        }
    }

    /** 
     * Get the list of pack files for the repository.
     * 
     * @return array Pack files
     */
    public function getPackFiles()
    {
        return $this->mPacks;
    }

    /**
     * Get the tip (last) commit for the given ref.
     *
     * @param string $refName Name of the reference such as 'master'.
     * @return string|bool SHA-1 hash of the head commit or FALSE on error.
     */
    public function getRef($refName)
    {
        $refPath = "$this->mRepoPath/refs/heads/$refName";

        if( file_exists($refPath) )
        {
            $refHead = trim(file_get_contents($refPath));
            return $refHead;
        }
        else if( file_exists("$this->mRepoPath/packed-refs") )
        {
            $fpRefs     = fopen("$this->mRepoPath/packed-refs", 'r');
            $headCommit = false;

            while( ($line = fgets($fpRefs)) !== false )
            {
                if( $line[0] == '#' )
                    continue;
                
                if( ($pos = strpos($line, ' ')) !== false )
                {
                    $Ref = substr($line, $pos+1, strlen($line)-$pos-2);
                    if( $Ref == $refName )
                    {
                        $headCommit = substr($line, 0, $pos);
                        break;
                    }
                }
            }

            fclose($fpRefs);
            return $headCommit;
        }

        return false;
    }

    /**
     * Get the HEAD hash of the repository.
     *
     * Gives you the hash of the repository's current branch latest
     * commit (or tip).
     *
     * @returns string SHA-1 hash of the object.
     */
    public function getHead()
    {
        $headRef = file_get_contents($this->mRepoPath . '/HEAD');

        if( substr($headRef, 0, 4) != 'ref:' )
            throw new InvalidObject('HEAD');

        $headRef     = trim(substr($headRef, 5));
        return $this->getRef($headRef);
    }

    /**
     * Get the HEAD commit of the repository.
     *
     * @see Repo::getHead() 
     * @returns string SHA-1 hash of the object.
     */
    public function getHeadCommit()
    {
        $headCommit = $this->getHead();
        return Object::Open($this, $headCommit);
    }
    
    /**
     * Get a object from the repository
     *
     * Retrieve's the object from the Git repository and gives you a instance 
     * of either Tree, Commit, or Blob depending on the object type.
     *
     * @param string $objectHash SHA-1 Hash of the object
     * @return Tree|Commit|Blob|bool Tree, Commit, or Blob or FALSE on error
     */
    public function getObject($objectHash)
    {
        return Object::Open($this, $objectHash);
    }

    /**
     * The full path to the repository.
     * 
     * @return string The full path to the repo
     */
    public function getRepoPath()
    {
        return $this->mRepoPath;
    }

    /**
     * Open a Git repository.
     *
     * Opens a Git repository and returns a Repo object.
     * 
     * @param string $Path Path to the git repository. It will automatically add 
     * the .git subfolder to the path if necessary.    
     * 
     * @return Repo Repo object
     * @throws \Exception
     */
    public static function Open($Path)
    {
        if( !is_dir($Path) )
            throw new \Exception("$Path does not exist!");
    
        $repoPath = Repo::isRepository($Path);
        if( $repoPath === false )
            throw new \Exception("$Path is not a valid Git repository!");

        return new Repo($repoPath);
    }

    /**
     * Check if the given directory is a Git repository
     *
     * @param string $Path Path to the directory to check
     * @return bool Returns TRUE if it is a valid repository
     */
    public static function isRepository($Path)
    {    
        if( file_exists($Path .  '/HEAD') )
            return $Path;

        if( file_exists($Path . '/.git/HEAD') )
            return $Path . '/.git';

        return false;
    }
}
?>
