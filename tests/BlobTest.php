<?php
/************************************************************\
* PGit - PHP Git Implementation                              *
*   http://github.com/zordtk/pgit                            *
*   Written By Jeremy Harmon <jeremy.harmon@zoho.com>        *
\************************************************************/

namespace PGit;

class PGitBlobTest extends \PHPUnit_Framework_TestCase
{
    public function testGetBlob()
    {
        $r    = Repo::Open(__DIR__ . '/testRepo');
        $blob = $r->getObject('5716ca5987cbf97d6bb54920bea6adde242d87e6');
        $this->assertEquals($blob->getObjectType(), Object::TYPE_BLOB);
        $this->assertEquals($blob->getObjectHash(), '5716ca5987cbf97d6bb54920bea6adde242d87e6');
        $this->assertEquals($blob->getBlobHash(), '5716ca5987cbf97d6bb54920bea6adde242d87e6');
        $this->assertEquals($blob->getBlobContent(), "bar\n");
    }
}

?>