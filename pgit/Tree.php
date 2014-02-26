<?php
/************************************************************\
* PGit - PHP Git Implementation                              *
*   http://github.com/zordtk/pgit                            *
*   Written By Jeremy Harmon <jeremy.harmon@zoho.com>        *
\************************************************************/

namespace PGit;

class Tree extends Object
{
    public function __construct($Repo, $Data, $isFromPack)
    {
        $this->mObjectType = Object::TYPE_TREE;
        parent::__construct($Repo, $Data);

        $entryData = $Data;

        if( !$isFromPack )
        {
            if( substr($this->mData, 0, 4) != 'tree' )
                throw new \Exception("$this->mObjectHash is not a valid tree object");

            for( $i=5; $i<strlen($this->mData); $i++ )
            {
                if( $this->mData[$i] == "\0" )
                    break;
            }

            $Len            = substr($this->mData, 5, $i-5);
            $entryData      = substr($this->mData, $i+1, strlen($this->mData)-$i);
        }

        $this->mEntries = array();
        $Idx            = 0;
        $Pos            = 0;

        while( $Idx < strlen($entryData) )
        {
            if( $entryData[$Idx] == "\0" )
            {
                $Idx            += 20;
                $entryLine       = substr($entryData, $Pos, $Idx+1);
                $Pos             = $Idx+1;
                $Entry['mode']   = substr($entryLine, 0, strpos($entryLine, ' '));

                $i = 0;                
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
            else
                $Idx++;
        }
    }

    public function getEntries()
    {
        return $this->mEntries;
    }
}

?>
