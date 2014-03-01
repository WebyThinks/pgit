<?php
/************************************************************\
* PGit - PHP Git Implementation                              *
*   http://github.com/zordtk/pgit                            *
*   Written By Jeremy Harmon <jeremy.harmon@zoho.com>        *
\************************************************************/

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

?>
