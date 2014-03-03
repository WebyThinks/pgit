<?php
/************************************************************\
* PGit - PHP Git Implementation                              *
*   http://github.com/zordtk/pgit                            *
*   Written By Jeremy Harmon <jeremy.harmon@zoho.com>        *
\************************************************************/

/**
 * Contains the Blob object implementation
 */

namespace PGit;

/** 
 * Represents a Git Blob object
 */
class Blob extends Object
{
    /**
     * The uncompressed content of the blob
     */
    private $mBlobContent;

    /**
     * The hash of the blob's content
     */
    private $mBlobHash;

    /**
     * Parses a blob object
     *
     * @param Repo $Repo Instance of the Repo object
     * @param string $Data Blob object data
     * @param bool $isFromPack Did the data come from a pack or a raw object
     * @throw InvalidObject
     */
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

    /**
     * Verify that the blob's hash is correct.
     *
     * @throw InvalidHash If the blob's hash is incorrect
     */
    public function Verify()
    {       
        if( $this->mBlobHash != $this->mObjectHash )
            throw new InvalidHash($this->mObjectHash);
    }

    /**
     * Apply delta to the blob
     *
     * @param string $Delta The delta to apply
     * @see Object::applyDelta
     */
    public function applyDelta($Delta)
    {
        // we override Object::applyDelta to calculate the correct
        // hash and set mBlobContent:

        parent::applyDelta($Delta);
        $this->mBlobContent = $this->mData;
        $this->mBlobHash    = SHA::hashStr('blob ' . strlen($this->mData) . "\0" . $this->mData);
    }
    
    /**
     * Get the blob content
     *
     * @return string The uncompressed blob content
     */
    public function getBlobContent()
    {
        return $this->mBlobContent;
    }

    /**
     * The SHA-1 hash of the blob
     *
     * @return string SHA-1 hash of the blob
     */
    public function getBlobHash()
    {
        return $this->mBlobHash;
    }
}

?>
