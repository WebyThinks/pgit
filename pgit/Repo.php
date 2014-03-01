<?php
/************************************************************\
* PGit - PHP Git Implementation                              *
*   http://github.com/zordtk/pgit                            *
*   Written By Jeremy Harmon <jeremy.harmon@zoho.com>        *
\************************************************************/

namespace PGit;

require_once('Object.php');
require_once('SHA.php');

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
    private $mIndexEntries;
    private $mIndexRead = false;
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

    public function getObjectFromPath($Path)
    {
        if( ($Obj = $this->lookupPath($Path)) !== false )
            return Object::Open($this, $Obj->getHash());
        return false;
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

    private function readIndex()
    {
        $fp = fopen($this->mRepoPath . '/index', 'rb');
        if( !$fp )
            throw new \Exception('No index file');

        // Index header is 12 bytes long. All binary numbers are stored in
        // network byte order (big-endian). 

        // 4 byte signature containing DIRC (dircache)
        if( fread($fp, 4) != 'DIRC' )
            throw new \Exception('Invalid or corrupted index file!');

        $this->verifyIndexfile($fp);

        // Now that we have verified the hash on the index file we can start 
        // reading the rest, so rewind the file while skipping the first 4 bytes
        // which we already checked above
        fseek($fp, 4); 
        
        // 4 byte version number
        $Version = readInt32($fp);
        
        // Right now only version 2 is supported
        if( $Version != 2 )
            throw new \Exception("Unsupported index version  $Version!");

        // Number of index entries (32 bit int)
        $numEntries = readInt32($fp);
        
        // Now we can read each of the index entries
        for( $i=0; $i<$numEntries; $i++ )
            $this->mIndexEntries[] = new IndexEntry($fp);

        fclose($fp);
        $this->mReadIndex = true;
    }

    private function verifyIndexFile($fpIndex)
    {
        fseek($fpIndex, -20, SEEK_END);
        $fileSize = ftell($fpIndex);
        $shaHash  = readSHA1($fpIndex);

        if( $shaHash != SHA::hashFileData($fpIndex, $fileSize) )
            throw new InvalidHash("Index file's hash is invalid");
    }

    private function lookupPath($Path)
    {
        if( !$this->mIndexRead )
            $this->readIndex();

        foreach( $this->mIndexEntries as $Entry )
        {
            if( $Entry->getName() == $Path )
                return $Entry;
        }

        return false;
    }
}

class IndexEntry
{
    private $mCTimeSeconds;
    private $mCTimeNanoSeconds;
    private $mMTimeSeconds;
    private $mMTimeNanoSeconds;
    private $mDevNum;
    private $mInode;
    private $mMode;
    private $mUid;
    private $mGid;
    private $mSize;
    private $mHash;
    private $mFlags;
    private $mName;

    public function __construct($fpHandle)
    {
        $this->mCTimeSeconds        = readInt32($fpHandle);
        $this->mCTimeNanoSeconds    = readInt32($fpHandle);
        $this->mMTimeSeconds        = readInt32($fpHandle);
        $this->mMTimeNanoSeconds    = readInt32($fpHandle);
        $this->mDevNum              = readInt32($fpHandle);
        $this->mInode               = readInt32($fpHandle);
        $this->mMode                = readInt32($fpHandle);
        $this->mUid                 = readInt32($fpHandle);
        $this->mGid                 = readInt32($fpHandle);
        $this->mSize                = readInt32($fpHandle);
        $this->mHash                = readSHA1($fpHandle);
        $this->mFlags               = readInt16($fpHandle);
        $this->mName                = readNullPaddedStr($fpHandle);
    }

    public function getCTimeSeconds()
    {
        return $this->mCTimeSeconds;
    }

    public function getCTimeNanoSeconds()
    {
        return $this->mCTimeNanoSeconds;
    }

    public function getCTime()
    {
        return date('r', $this->mCTimeSeconds);
    }

    public function getMTimeSeconds()
    {
        return $this->mMTimeSeconds;
    }

    public function getMTimeNanoSeconds()
    {
        return $this->mMTimeNanoSeconds;
    }

    public function getMTime()
    {
        return date('r', $this->mMTimeSeconds);
    }

    public function getDevNum()
    {
        return $this->mDevNum;
    }

    public function getInode()
    {
        return $this->mInode;
    }

    public function getUid()
    {
        return $this->mUid;
    }

    public function getGid()
    {
        return $this->mGid;
    }

    public function getSize()
    {
        return $this->mSize;
    }

    public function getHash()
    {
        return $this->mHash;
    }

    public function getName()
    {
        return $this->mName;
    }
}

?>
