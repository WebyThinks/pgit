<?php
/************************************************************\
* PGit - PHP Git Implementation                              *
*   http://github.com/zordtk/pgit                            *
*   Written By Jeremy Harmon <jeremy.harmon@zoho.com>        *
\************************************************************/

function __autoload($Class)
{
	switch( $Class )
	{
		case 'PGit\Blob':
			require_once(__DIR__ . '/pgit/Blob.php');
		break;
		
		case 'PGit\Commit':
			require_once(__DIR__ . '/pgit/Commit.php');
		break;
		
		case 'PGit\Object':
			require_once(__DIR__ . '/pgit/Object.php');
		break;
		
		case 'PGit\Repo':
			require_once(__DIR__ . '/pgit/Repo.php');
		break;
		
		case 'PGit\SHA':
			require_once(__DIR__ . '/pgit/SHA.php');
		break;
		
		case 'PGit\Tree':
			require_once(__DIR__ . '/pgit/Tree.php');
		break;
	}
}

?>