<?php
/************************************************************\
* PGit - PHP Git Implementation                              *
*   http://github.com/zordtk/pgit                            *
*   Written By Jeremy Harmon <jeremy.harmon@zoho.com>        *
\************************************************************/

/**
 * Contains the Tree object implementation
 */

namespace PGit;

/**
 * Represents a Git Tree object.
 */
class Tree extends Object
{
    /**
     * The un-parsed tree object data.
     */
    private $mEntryData;

    /**
     * Parses tree object's data into a usable format.
     *
     * @param Repo $Repo Instance of the Repo object
     * @param string $Data Tree object data
     * @param bool $isFromPack Did the data come from a pack or a raw object
     * @throw InvalidObject
     */
    public function __construct($Repo, $Data, $isFromPack)
    {
        $this->mObjectType = Object::TYPE_TREE;
        parent::__construct($Repo, $Data);

        if( !$isFromPack )
        {
            if( substr($this->mData, 0, 4) != 'tree' )
                throw new InvalidObject($this->mObjectHash);

            for( $i=5; $i<strlen($this->mData); $i++ )
            {
                if( $this->mData[$i] == "\0" )
                    break;
            }

            $Len                = substr($this->mData, 5, $i-5);
            $this->mEntryData   = substr($this->mData, $i+1, strlen($this->mData)-$i);
        }
        else
            $this->mEntryData = $Data;

        $this->parseTreeEntries();
    }

    /**
     * Apply a delta to the object
     * @see Object::applyDelta
     * @param string $Delta The delta to apply
     */
    public function applyDelta($Delta)
    {
        parent::applyDelta($Delta);
        $this->mEntryData = $this->mData;
        $this->parseTreeEntries();
    }

    /**
     * Parses the tree object's data into a usable format.
     */
    private function parseTreeEntries()
    {
        $this->mEntries = array();
        $Idx            = 0;
        $Pos            = 0;

        while( !empty($this->mEntryData) )
        {
            if( $this->mEntryData[$Idx++] == "\0" )
            {
                $entryLine          = substr($this->mEntryData, 0, $Idx + 20);
                $this->mEntryData   = substr($this->mEntryData, strlen($entryLine));
                $Idx = 0;

                $Entry['mode']   = substr($entryLine, 0, strpos($entryLine, ' '));

                for( $i=strlen($Entry['mode']); $i<strlen($entryLine); $i++ )
                {
                    if( $entryLine[$i] == "\0" )
                        break;
                }

                $Entry['name']      = substr($entryLine, strlen($Entry['mode'])+1, $i-strlen($Entry['mode'])-1);
                $Entry['hash']      = bin2hex(substr($entryLine, $i+1, 20));
                $Entry['type']      = ( $Entry['mode'] == 40000 ? Object::TYPE_TREE : Object::TYPE_BLOB );
                $this->mEntries[]   = $Entry;
            }
        }

        unset($this->mEntryData);
    }

    /**
     * Get the parsed tree entries
     * @return string[] An array containing the tree's entries
     */
    public function getTreeEntries()
    {
        return $this->mEntries;
    }
}

?>
