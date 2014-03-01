<?php
/************************************************************\
* PGit - PHP Git Implementation                              *
*   http://github.com/zordtk/pgit                            *
*   Written By Jeremy Harmon <jeremy.harmon@zoho.com>        *
\************************************************************/

namespace PGit;

class Blob extends Object
{
    private $mBlobContent;
    private $mBlobHash;

    public function __construct($Repo, $Data, $isFromPack)
    {
        $this->mObjectType = Object::TYPE_BLOB;
        parent::__construct($Repo, $Data);

        $Offset = 0;
        if( !$isFromPack )
        {
            if( substr($this->mData, 0, 4) != 'blob' )
                throw new InvalidObject($this->mObjectHash);

            // Read size
            for( $i=5; $i<strlen($this->mData)-4; $i++ )
            {
                if( $this->mData[$i] == 0 )
                    break;
            }

            $Offset = $i + 1;
            $this->mBlobHash    = SHA::hashStr($this->mData);
        }
        else
            $this->mBlobHash    = SHA::hashStr('blob ' . strlen($this->mData) . "\0" . $this->mData);

        $this->mBlobContent = substr($this->mData, $Offset);
    }

    public function Verify()
    {       
        if( $this->mBlobHash != $this->mObjectHash )
            throw new InvalidHash($this->mObjectHash);
    }

    public function applyDelta($Delta)
    {
        // we override Object::applyDelta to calculate the correct
        // hash and set mBlobContent:

        parent::applyDelta($Delta);
        $this->mBlobContent = $this->mData;
        $this->mBlobHash    = SHA::hashStr('blob ' . strlen($this->mData) . "\0" . $this->mData);
    }
    
    public function getBlobContent()
    {
        return $this->mBlobContent;
    }

    public function getBlobHash()
    {
        return $this->mBlobHash;
    }
}

?>
