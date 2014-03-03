<?php
/************************************************************\
* PGit - PHP Git Implementation                              *
*   http://github.com/zordtk/pgit                            *
*   Written By Jeremy Harmon <jeremy.harmon@zoho.com>        *
\************************************************************/

/**
 * Binary read functions
 */

/**
 * Read 32Bit int
 * @param resource $fp Handle of the file to read from
 * @return int 32-Bit Integer
 */
function readInt32($fp)
{
    $n = unpack('N', fread($fp, 4));
    return $n[1];
}

/**
 * Read 16bit int
 * @param resource $fp Handle of the file to read from
 * @return int 16-Bit Integer
 */
function readInt16($fp)
{
    $n = unpack('n', fread($fp, 2));
    return $n[1];
}

/**
 * Read SHA-1
 * @param resource $fp Handle of the file to read from
 * @return string SHA-1 hash in hex form
 */
function readSHA1($fp)
{
	$n = unpack('H40', fread($fp, 20));
	return $n[1];
}

/**
 * Convert SHA1 to binary
 * @param string $Hash The hex SHA-1 to convert
 * @return string SHA-1 hash in binary
 */
function sha1bin($Hash)
{
    return pack('H40', $Hash);
}

/**
 * Read Git VarInt
 * @param string $Str The buffer to read from
 * @param int $Pos The start position
 * @return string
 */
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


/**
 * Read NULL padded string
 * @param resource $fp The handle of the file to read from
 * @return string
 */
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

?>
