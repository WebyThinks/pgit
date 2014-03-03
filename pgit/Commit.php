<?php
/************************************************************\
* PGit - PHP Git Implementation                              *
*   http://github.com/zordtk/pgit                            *
*   Written By Jeremy Harmon <jeremy.harmon@zoho.com>        *
\************************************************************/

/**
 * Contains the Commit object implementation
 */

namespace PGit;

/** 
 * Represents a Git Commit object
 */
class Commit extends Object
{
    /**
     * @var string $mAuthor Author's name
     */
    private $mAuthor;

    /**
     * @var string $mAuthorEmail Author's email address
     */
    private $mAuthorEmail;

    /**
     * @var string $mAuthorDate Date the commit was originally authored
     */
    private $mAuthorDate;

    /**
     * @var string $mCommitter Name of the person who made the commit
     */
    private $mCommitter;

    /**
     * @var string $mCommitterEmail Committer's email address
     */    
    private $mCommitterEmail;
    
    /**
     * @var string $mCommitDate Date the commit was made
    */
    private $mCommitDate;

    /**
     * @var string $mTreeHash SHA-1 hash of the Tree object that the commit references
     */
    private $mTreeHash;

    /**
     * @var string $mParentHashes SHA-1 hashes of parent objects
     */
    private $mParentHashes = array();

    /** 
     * @var string $mMessage The commit message
     */
    private $mMessage;

    /**
     * @var string $mCommitData The uncompressed commit data before parsing
     */
    private $mCommitData;

    /**
     * Parses a commit object
     *
     * @param Repo $Repo Instance of the Repo object
     * @param string $Data Commit object data
     * @param bool $isFromPack Did the data come from a pack or a raw object
     * @throw InvalidObject
     */
    public function __construct($Repo, $Data, $isFromPack)
    {
        $this->mObjectType = Object::TYPE_COMMIT;
        parent::__construct($Repo, $Data);

        if( !$isFromPack )
        {

            if( substr($this->mData, 0, 6) != 'commit' )
                throw new InvalidObject($this->mObjectHash);

            for( $i=7; $i<strlen($this->mData); $i++ )
            {
                if( $this->mData[$i] == 0 )
                    break;
            }

            $commitLen          = substr($this->mData, 7, $i-7);
            $this->mCommitData  = substr($this->mData, 8+strlen($commitLen));
        }
        else
            $this->mCommitData = $Data;
        $this->parseCommit();
    }

    /**
     * Apply delta to the commit
     *
     * @param string $Delta The delta to apply
     * @see Object::applyDelta
     */
    public function applyDelta($Delta)
    {
        parent::applyDelta($Delta);
        $this->mCommitData = $this->mData;
        $this->parseCommit();
    }

    /**
     * Parse the commit data into a usable format
     */
    protected function parseCommit()
    {
        $Lines = explode("\n", $this->mCommitData);

        for( $i=0; $i<count($Lines); $i++ )
        {
            if( empty($Lines[$i]) )
                break;
            else
            {
                $Pos    = strpos($Lines[$i], ' ');
                $Type   = substr($Lines[$i], 0, $Pos);
                $Value  = substr($Lines[$i], $Pos+1);

                switch( $Type )
                {
                    case 'tree':
                        $this->mTreeHash = $Value;
                        break;

                    case 'parent':
                        $this->mParentHashes[] = $Value;
                        break;

                    case 'author':
                    {
                    	$this->mAuthor = $Value;
                    	
                    	if(preg_match('/^(.+?)\s+<(.+?)>\s+(\d+)\s+([+-]\d{4})$/', $Value, $Matches))
                    	{
                    		$this->mAuthor 		= $Matches[1];	
                    		$this->mAuthorEmail	= $Matches[2];
                    		$this->mAuthorDate	= $Matches[3];
                    	}
                    }
                    break;

                    case 'committer':
                    {
                        $this->mCommitter = $Value;

                        if(preg_match('/^(.+?)\s+<(.+?)>\s+(\d+)\s+([+-]\d{4})$/', $Value, $Matches))
                        {
                        	$this->mCommitter 		= $Matches[1];
                        	$this->mCommitterEmail	= $Matches[2];
                        	$this->mCommitDate		= $Matches[3];
                        }
                    }
                    break;
                }
            }
        }

        $this->mMessage = implode(array_slice($Lines, 5, count($Lines) - 6), "\n");
        unset($this->mCommitData);
    }

    /**
     * Get Author Name
     * @return string Author's name
     */
    public function getAuthor()
    {
        return $this->mAuthor;
    }

    /**
     * Get Author Email
     * @return string Author's email address
     */
    public function getAuthorEmail()
    {
    	return $this->mAuthorEmail;
    }
    
    /**
     * Get Author Date
     * @return string Date the commit was originally authored
     */
    public function getAuthorDate()
    {
    	return $this->mAuthorDate;
    }
    
    /**
     * Get Committer
     * @return string Name of the person who made the commit
     */
    public function getCommitter()
    {
        return $this->mCommitter;
    }

    /**
     * Get Comitter Email
     * @return string Committer's email address
     */    
    public function getCommitterEmail()
    {
    	return $this->mCommitterEmail;
    }

    /**
     * Get Commit Date
     * @return string Date the commit was made
     */
    public function getCommitDate()
    {
        return $this->mCommitDate;
    }

    /**
     * Get Parent Hashes
     * @return array SHA-1 hashes of parent objects
     */
    public function getParentHashes()
    {
        return $this->mParentHashes;
    }

    /** 
     * Get Tree Hash
     * @return string SHA-1 hash of the Tree object that the commit references
     */
    public function getTreeHash()
    {
        return $this->mTreeHash;
    }

    /**
     * Get Tree Object
     * @return Tree Tree object that the commit references
     */
    public function getTree()
    {
        return Object::Open($this->mRepo, $this->mTreeHash);
    }

    /**
     * Get Message
     * @return string The commit message
     */
    public function getMessage()
    {
        return $this->mMessage;
    }
}

?>
