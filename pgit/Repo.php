<?php
/************************************************************\
* PGit - PHP Git Implementation                              *
*   http://github.com/zordtk/pgit                            *
*   Written By Jeremy Harmon <jeremy.harmon@zoho.com>        *
\************************************************************/

namespace PGit;

function readInt32($fp)
{
    $n = unpack('N', fread($fp, 4));
    return $n[1];
}

function readInt16($fp)
{
    $n = unpack('n', fread($fp, 2));
    return $n[1];
}

function readSHA1($fp)
{
	$n = unpack('H40', fread($fp, 20));
	return $n[1];
}

function sha1bin($Hash)
{
    return pack('H40', $Hash);
}

function readGitVarInt($Str, &$Pos = 0)
{
    $r = 0;
    $c = 0x80;
    for ($i = 0; $c & 0x80; $i += 7)
    {
        $c = ord($Str{$Pos++});
        $r |= (($c & 0x7F) << $i);
    }

    return $r;
}

function readNullPaddedStr($fp)
{
    $Buffer = '';
    $foundNul = false;
    while( !feof($fp) )
    {
        $Char = fread($fp, 1);
         
        if( ord($Char) == 0 )
        {
            $foundNul = true;
        }
        else
        {
            if( $foundNul )
            {
                fseek($fp, -1, SEEK_CUR);
                break;
            }

            $Buffer .= $Char;
        }
    }

    return $Buffer;
}

class Repo
{
    private $mRepoPath;
    private $mPacks = array();

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

    public function getPackFiles()
    {
        return $this->mPacks;
    }

    public function getRef($refName)
    {
        $refPath = "$this->mRepoPath/$refName";

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

    public function getHead()
    {
        $headRef = file_get_contents($this->mRepoPath . '/HEAD');

        if( substr($headRef, 0, 4) != 'ref:' )
            throw new InvalidObject('HEAD');

        $headRef     = trim(substr($headRef, 5));
        return $this->getRef($headRef);
    }

    public function getHeadCommit()
    {
        $headCommit = $this->getHead();
        return Object::Open($this, $headCommit);
    }

    public function getTree($Hash)
    {
        $treeObj = $this->getObject($Hash);
        if( $treeObj !== false )
        {
            if( $treeObj->getObjectType() == Object::TYPE_COMMIT )
                $treeObj = $treeObj->getTree();
        }

        return $treeObj;
    }

    public function getObject($objectHash)
    {
        return Object::Open($this, $objectHash);
    }

    public function getRepoPath()
    {
        return $this->mRepoPath;
    }

    public static function Open($Path)
    {
        if( !is_dir($Path) )
            throw new \Exception("$Path does not exist!");
    
        $repoPath = Repo::isRepository($Path);
        if( $repoPath === false )
            throw new \Exception("$Path is not a valid Git repository!");

        return new Repo($repoPath);
    }

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
