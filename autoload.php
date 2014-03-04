<?php
/************************************************************\
* PGit - PHP Git Implementation                              *
*   http://github.com/zordtk/pgit                            *
*   Written By Jeremy Harmon <jeremy.harmon@zoho.com>        *
\************************************************************/

function autoLoadPGit($className)
{
    // We only care about classes in the PGit namespace:
    if( substr($className, 0, 5) == "PGit\\" )
    {
        $actualClassName    = substr($className, 5);
        $classPath          = __DIR__ . "/pgit/$actualClassName.php";
        if( file_exists($classPath) )
        {
            require_once($classPath);
            return;
        }
        
        die('Error: Can\'t load ' . htmlspecialchars($className));
    }
}

spl_autoload_register('autoLoadPGit');

?>
