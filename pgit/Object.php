<?php
/************************************************************\
* PGit - PHP Git Implementation                              *
*   http://github.com/zordtk/pgit                            *
*   Written By Jeremy Harmon <jeremy.harmon@zoho.com>        *
\************************************************************/
namespace PGit;

class Object
{
    const TYPE_COMMIT       = 1;
    const TYPE_TREE         = 2;
    const TYPE_BLOB         = 3;
    const TYPE_TAG          = 4;
    const TYPE_OFS_DELTA    = 6;
    const TYPE_REF_DELTA    = 7;
    
    protected $mRepo;
    protected $mData;
    protected $mObjectType;
    protected $mObjectHash;

    public function __construct($Repo, $Data)
    {
        $this->mRepo = $Repo;
        $this->mData = $Data;
    }

    public static function Open($Repo, $objectHash)
    {
        if( empty($objectHash) )
            return false;

        $Prefix     = substr($objectHash, 0, 2);
        $Remaining  = substr($objectHash, 2);
        $Path       = $Repo->getRepoPath() . '/objects/' . $Prefix . '/' . $Remaining;

        if( file_exists($Path) )
        {
            $Data           = gzuncompress(file_get_contents($Path));
            $objectType     = substr($Data, 0, strpos($Data, ' '));

            $Object = false;
            if( $objectType == 'blob' )
                $Object = new Blob($Repo, $Data, false);
            else if( $objectType == 'commit' )
                $Object = new Commit($Repo, $Data, false);
            else if( $objectType == 'tree' )
                $Object = new Tree($Repo, $Data, false);

            if( !is_object($Object) )
                throw new \Exception("Invalid object type: $objectType");

            $Object->mObjectHash = $objectHash;
            $Object->Verify();

            unset($Object->mData);
            return $Object;
        }
        else
        {
            if( ($packData = Object::findPackedObject($Repo, $objectHash)) !== false )
            {
                list($Pack, $Offset, $objHash) = $packData;
                $packHash = Object::verifyPackFile($Repo, $Pack);

                // First we check that the pack file's hash is valid
                if( $packHash === false )
                    throw new InvalidHash($Path);

                // Now we check that the hash matches the one in the pack index
                if( $packHash != $Pack['hash'] )
                    throw new InvalidHash($Path);
    
                $Object                 = Object::unpackObject($Repo, $Pack['fileName'], $Offset);
                $Object->mObjectHash    = $objHash;
                $Object->Verify();

                unset($Object->mData);
                return $Object;
            }
        }

        return false;
    }

    private static function verifyPackFile($Repo, $Pack)
    {
        $fpPack = fopen($Repo->getRepoPath() . '/objects/pack/pack-' . $Pack['fileName'] . '.pack', 'rb');
        if( !$fpPack )
            return false;

        fseek($fpPack, -20, SEEK_END);
        $Size = ftell($fpPack);
        $validHash  = readSHA1($fpPack);
        $packHash   = SHA::hashFileData($fpPack, $Size);

        fclose($fpPack);
        if( $validHash == $packHash )
            return $packHash;

        return false;
    }
    
    private static function readCompressedData($fpPack, $uncompressedSize)
    {
        // Git stores the size of the uncompressed data, not the compressed size.
        // So we need to keep feeding it data until we get a valid object. We
        // will do this in 32 byte chunks.
        $compressedData     = fread($fpPack, $uncompressedSize);
        $uncompressedData   = '';

        while( ($uncompressedData = @gzuncompress($compressedData, $uncompressedSize)) === false )
        {
            $compressedData .= fread($fpPack, 32);
            if( $compressedData === false ) break;
        }
        return $uncompressedData;
    }

    private static function unpackObject($Repo, $packName, $Offset)
    {
        $fpPack = fopen($Repo->getRepoPath() . "/objects/pack/pack-$packName.pack", 'rb');
        $Magic  = fread($fpPack, 4);
        
        if( $Magic != 'PACK' )
            throw new InvalidObject($packName . '.pack');

        fseek($fpPack, $Offset);

        // get object header
        $Bits = ord(fgetc($fpPack));
        $Type = ($Bits >> 4) & 0x07;
        $Size = $Bits & 0x0F;

        for( $i=4; $Bits & 0x80; $i += 7 )
        {
            $Bits  = ord(fgetc($fpPack));
            $Size |= (($Bits & 0x7F) << $i);
        }

        $Object = null;
        switch( $Type )
        {
            case Object::TYPE_COMMIT:       $Object = new Commit($Repo, Object::readCompressedData($fpPack, $Size), true);      break;
            case Object::TYPE_TREE:         $Object = new Tree($Repo,   Object::readCompressedData($fpPack, $Size), true);      break;
            case Object::TYPE_BLOB:         $Object = new Blob($Repo,   Object::readCompressedData($fpPack, $Size), true);      break;

            case Object::TYPE_OFS_DELTA:
            {
                $Buf            = fread($fpPack, $Size);
                $Cnt            = 0;
                $Pos            = 0;
                $deltaOffset    = -1; 

                do
                {
                    $deltaOffset++;
                    $Bits        = ord($Buf[$Pos++]);
                    $deltaOffset = ($deltaOffset << 7) + ($Bits & 0x7F);
                } while( $Bits & 0x80 );

                $deltaBuffer = substr($Buf, $Pos);

                // Again we don't know the length of the compressed data so we will
                // keep adding 32 byte chunks until we get a valid object
                while( ($Delta = @gzuncompress($deltaBuffer, $Size)) === false )
                {
                    $deltaBuffer .= fread($fpPack, 32);
                    if( $deltaBuffer === false ) break;
                }

                $baseOffset = $Offset - $deltaOffset;
                $Object     = Object::unpackObject($Repo, $packName, $baseOffset);                
                $Object->applyDelta($Delta);
            }
            break;

            case Object::TYPE_REF_DELTA:
                throw new \Exception('REF Deltas aren\'t supported yet');
            break;
        }

        fclose($fpPack);
        return $Object;
    }

    public function Verify()
    {

    }

    private static function findPackedObject($Repo, $objectHash)
    {
        $packFiles = $Repo->getPackFiles();
        foreach( $packFiles as &$Pack )
        {
            $packIndexFile  = $Repo->getRepoPath() . '/objects/pack/pack-' . $Pack['fileName'] . '.idx';
            $fpIdx          = fopen($packIndexFile, 'rb');

            if( !$fpIdx ) continue;

            fseek($fpIdx, -40, SEEK_END);
            $Size           = ftell($fpIdx) + 20;
            $Pack['hash']   = readSHA1($fpIdx);
            $idxHash        = readSHA1($fpIdx);

            if( $idxHash != SHA::hashFileData($fpIdx, $Size) )
                throw new InvalidHash($packIndexFile);
            
            list($curr, $after) = Object::readFanout($fpIdx, $objectHash, 8);
            if( $curr == $after )
                continue;

            // skip all of fanout table (255 entries of 4 bytes) and the 8byte header
            fseek($fpIdx, 8 + 4 * 255);
            
            $totalObjects = readInt32($fpIdx);

            // seek down to the range the object should be listed in the sha table at
            fseek($fpIdx, 8 + 4 * 256 + 20 * $curr);
            for( $i = $curr; $i < $after; $i++ )
            {
                $objHash = readSHA1($fpIdx);
                if( $objHash == $objectHash )
                    break;
            }

            if( $i == $after )
                continue; // we didn't find it

            // seek to the object's place in the offset table
            fseek($fpIdx, 8 + 4 * 256 + 24 * $totalObjects + 4 * $i);
            $Offset = readInt32($fpIdx);

            fclose($fpIdx);

            return array($Pack, $Offset, $objHash);
        }

        return false;
    }

    // This function is based on Patrik Fimml's code from glip
    // All credit goes to him
    private static function readFanout($fp, $objectHash, $Offset)
    {
        $objectHash = sha1bin($objectHash);

        if( $objectHash[0] == "\x00" )
        {
            $curr = 0;
            fseek($fp, $Offset);
            $after = readInt32($fp);
        }
        else
        {
            fseek($fp, $Offset + (ord($objectHash[0]) - 1) * 4);
            $curr = readInt32($fp);
            $after = readInt32($fp);
        }

        return array($curr, $after);
    }

    protected function applyDelta($Delta)
    {
        $Pos        = 0;
        $baseSize   = readGitVarInt($Delta, $Pos);
        $resultSize = readGitVarInt($Delta, $Pos);

        $newData = '';
        while( $Pos < strlen($Delta) )
        {
            $opCode = ord($Delta[$Pos++]);
            if( $opCode & 0x80 )
            {
                $copyOffset = 0;
                if( $opCode & 0x01 )    $copyOffset  = ord($Delta[$Pos++]);
                if( $opCode & 0x02 )    $copyOffset |= ord($Delta[$Pos++]) << 8;
                if( $opCode & 0x04 )    $copyOffset |= ord($Delta[$Pos++]) << 16;
                if( $opCode & 0x08 )    $copyOffset |= ord($Delta[$Pos++]) << 24;

                $copyLen = 0;
                if( $opCode & 0x10 )    $copyLen     = ord($Delta[$Pos++]);
                if( $opCode & 0x20 )    $copyLen    |= ord($Delta[$Pos++]) << 8;
                if( $opCode & 0x40 )    $copyLen    |= ord($Delta[$Pos++]) << 16;

                if( $copyLen == 0 )
                    $copyLen = 0x10000;

                $newData .= substr($this->mData, $copyOffset, $copyLen);
            }
            else
            {
                $newData .= substr($Delta, $Pos, $opCode);
                $Pos += $opCode;    
            }
        }

        $this->mData = $newData;
    }

    public function getObjectHash()
    {
        return $this->mObjectHash;
    }

    public function getObjectType()
    {
        return $this->mObjectType;
    }
}

?>
