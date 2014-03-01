<?php
/************************************************************\
* PGit - PHP Git Implementation                              *
*   http://github.com/zordtk/pgit                            *
*   Written By Jeremy Harmon <jeremy.harmon@zoho.com>        *
\************************************************************/
namespace PGit;

class Commit extends Object
{
    private $mAuthor;
    private $mAuthorEmail;
    private $mAuthorDate;
    private $mCommitter;
    private $mCommitterEmail;
    private $mCommitDate;
    private $mTreeHash;
    private $mParentHashes = array();
    private $mMessage;
    private $mCommitData;

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

    public function applyDelta($Delta)
    {
        parent::applyDelta($Delta);
        $this->mCommitData = $this->mData;
        $this->parseCommit();
    }

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

    public function getAuthor()
    {
        return $this->mAuthor;
    }
    
    public function getAuthorEmail()
    {
    	return $this->mAuthorEmail;
    }
    
    public function getAuthorDate()
    {
    	return $this->mAuthorDate;
    }

    public function getCommitter()
    {
        return $this->mCommitter;
    }

    public function getCommitterEmail()
    {
    	return $this->mCommitterEmail;
    }

    public function getCommitDate()
    {
        return $this->mCommitDate;
    }
    
    public function getParentHashes()
    {
        return $this->mParentHashes;
    }

    public function getTreeHash()
    {
        return $this->mTreeHash;
    }

    public function getTree()
    {
        return Object::Open($this->mRepo, $this->mTreeHash);
    }

    public function getMessage()
    {
        return $this->mMessage;
    }
}

?>
