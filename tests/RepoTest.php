<?php
/************************************************************\
* PGit - PHP Git Implementation                              *
*   http://github.com/zordtk/pgit                            *
*   Written By Jeremy Harmon <jeremy.harmon@zoho.com>        *
\************************************************************/

namespace PGit;

define('HEAD_COMMIT', '41c4809279745c1b7c3a5d2ce1c8a3755a365560');

class PGitTest extends \PHPUnit_Framework_TestCase
{
    public function testRepoHeadCommit()
    {
        $r = Repo::Open(__DIR__ . '/testRepo');
        $this->assertEquals($r->getHead(), HEAD_COMMIT);
    }

    public function testRepoGetPackedObject()
    {
        $r = Repo::Open(__DIR__ . '/testRepo');
        $commitObject = $r->getObject('ca3934a669e2c72eecaeef530d0f9147e4e435f5');
        $this->assertEquals($commitObject->getObjectHash(), 'ca3934a669e2c72eecaeef530d0f9147e4e435f5');
        $this->assertEquals($commitObject->getObjectType(), Object::TYPE_COMMIT);
    }

    public function testRepoGetUnPackedObject()
    {
        $r = Repo::Open(__DIR__ . '/testRepo');
        $commitObject = $r->getObject('9f908553d11230106d37f3d7fcf7286dfbe19e24');
        $this->assertEquals($commitObject->getObjectHash(), '9f908553d11230106d37f3d7fcf7286dfbe19e24');
        $this->assertEquals($commitObject->getObjectType(), Object::TYPE_BLOB);
    }

    public function testRepoGetRef()
    {
        $r = Repo::Open(__DIR__ . '/testRepo');
        $this->assertEquals($r->getRef('refs/heads/master'), HEAD_COMMIT);
    }
}

?>