<?php
/************************************************************\
* PGit - PHP Git Implementation                              *
*   http://github.com/zordtk/pgit                            *
*   Written By Jeremy Harmon <jeremy.harmon@zoho.com>        *
\************************************************************/

namespace PGit;

class PGitCommitTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCommit()
    {
        $r = Repo::Open(__DIR__ . '/testRepo');
        $commit = $r->getObject('44824b6596a790a00a0127f10aa077d2f2bed9dc');
        $this->assertEquals($commit->getObjectType(), Object::TYPE_COMMIT);
        $this->assertEquals($commit->getObjectHash(), '44824b6596a790a00a0127f10aa077d2f2bed9dc');
        $this->assertEquals($commit->getAuthor(), 'Jeremy Harmon');
        $this->assertEquals($commit->getAuthorEmail(), 'jeremy.harmon@zoho.com');
        $this->assertEquals($commit->getAuthorDate(), '1393473427');
        $this->assertEquals($commit->getCommitter(), 'Jeremy Harmon');
        $this->assertEquals($commit->getCommitterEmail(), 'jeremy.harmon@zoho.com');
        $this->assertEquals($commit->getCommitDate(), '1393473427');
        $this->assertEquals($commit->getMessage(), 'added foo');
    }

    public function testCommitWithoutParent()
    {
        $r = Repo::Open(__DIR__ . '/testRepo');
        $commit = $r->getObject('56363a0225eb5a94d0acdfba8a384487161e9e19');
        $this->assertEquals($commit->getObjectType(), Object::TYPE_COMMIT);
        $this->assertEquals($commit->getObjectHash(), '56363a0225eb5a94d0acdfba8a384487161e9e19');
        $this->assertEquals($commit->getAuthor(), 'Jeremy Harmon');
        $this->assertEquals($commit->getAuthorEmail(), 'jeremy.harmon@zoho.com');
        $this->assertEquals($commit->getAuthorDate(), '1392850024');
        $this->assertEquals($commit->getCommitter(), 'Jeremy Harmon');
        $this->assertEquals($commit->getCommitterEmail(), 'jeremy.harmon@zoho.com');
        $this->assertEquals($commit->getCommitDate(), '1392850024');
        $this->assertEquals($commit->getMessage(), 'asda');
    }
}

?>
