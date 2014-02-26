<?php
/************************************************************\
* PGit - PHP Git Implementation                              *
*   http://github.com/zordtk/pgit                            *
*   Written By Jeremy Harmon <jeremy.harmon@zoho.com>        *
\************************************************************/

namespace PGit;

class SHA
{
    public static function hashStr($Str)
    {
        return hash('sha1', $Str);
    }

    public static function hashFile($Path)
    {
        return hash_file('sha1', $Path);
    }

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
