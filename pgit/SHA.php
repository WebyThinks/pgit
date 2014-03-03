<?php
/************************************************************\
* PGit - PHP Git Implementation                              *
*   http://github.com/zordtk/pgit                            *
*   Written By Jeremy Harmon <jeremy.harmon@zoho.com>        *
\************************************************************/

/**
 * SHA-1 hashing functions
 */
namespace PGit;

/**
 * SHA-1 Hashing functions
 */
class SHA
{
    /**
     * Hash string
     * @param string $Str The string to hash
     * @return string SHA-1 hash of the string
     */
    public static function hashStr($Str)
    {
        return hash('sha1', $Str);
    }

    /**
     * Hash file
     * @param string $Path The file path
     * @return string SHA-1 hash of the file
     */
    public static function hashFile($Path)
    {
        return hash_file('sha1', $Path);
    }

    /**
     * Hash file
     * @param resource $fpHandle The file handle
     * @param int $Length Amount of bytes to read
     * @return string SHA-1 hash of the file
     */
    public static function hashFileData($fpHandle, $Length)
    {
        $origPos = ftell($fpHandle);
        rewind($fpHandle);

        $Ctx = hash_init('sha1');

        while( $Length > 0 )
        {
            $blockSize = 4096;
            if( $Length < 4096 )
                $blockSize = $Length;

            $Buffer  = fread($fpHandle, $blockSize);
            $Length -= $blockSize;

            hash_update($Ctx, $Buffer);
        }

        fseek($fpHandle, $origPos);
        return hash_final($Ctx);
    }
}

?>
