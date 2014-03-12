<?php
/************************************************************\
* PGit - PHP Git Implementation                              *
*   http://github.com/zordtk/pgit                            *
*   Written By Jeremy Harmon <jeremy.harmon@zoho.com>        *
\************************************************************/

namespace PGit;


class PGitTreeTest extends \PHPUnit_Framework_TestCase
{
    public function testGetTree()
    {
        $r = Repo::Open(__DIR__ . '/testRepo');
        $object = $r->getObject('78cfaada51dca7d26f8632b6526d2c404d3268a1');
        $this->assertEquals($object->getObjectType(), Object::TYPE_TREE);
        $this->assertEquals($object->getObjectHash(), '78cfaada51dca7d26f8632b6526d2c404d3268a1');
        $this->assertEquals($object->getTreeEntries(), array(0 => array(
                                                                     'mode' => 100644, 
                                                                     'name' => '.mailbox', 
                                                                     'hash' => '038d718da6a1ebbc6a7780a96ed75a70cc2ad6e2', 
                                                                     'type' => 3
                                                                    ),
                                                          1 => array(
                                                                     'mode' => 40000, 
                                                                     'name' => 'gui', 
                                                                     'hash' => 'ef43f576cc98b5d49a3e1c50a6806254439ca54b', 
                                                                     'type' => 2
                                                                    ),
                                                          2 => array(
                                                                     'mode' => 100644, 
                                                                     'name' => 'test', 
                                                                     'hash' => '16b14f5da9e2fcd6f3f38cc9e584cef2f3c90ebe', 
                                                                     'type' => 3
                                                                    ),
                                                          3 => array(
                                                                     'mode' => 100644, 
                                                                     'name' => 'test2', 
                                                                     'hash' => 'd15d3b2db97aea6bb3ec9d7684125876da789c8a', 
                                                                     'type' => 3
                                                                    ),
                                                          4 => array(
                                                                     'mode' => 40000, 
                                                                     'name' => 'tmp', 
                                                                     'hash' => '3140ff00eeab44f80db384ac144309a70014a081', 
                                                                     'type' => 2
                                                                    )));
    }

    public function testTreeDelta()
    {
        $r = Repo::Open(__DIR__ . '/testRepo');
        $tree = $r->getObject('cb60db310b484df68fb0e72dfab22d0eeb49f01a');
        $this->assertEquals($tree->getTreeEntries(), array(0 => array(
                                                                     'mode' => 100644, 
                                                                     'name' => '.mailbox', 
                                                                     'hash' => '038d718da6a1ebbc6a7780a96ed75a70cc2ad6e2', 
                                                                     'type' => 3
                                                                    ),
                                                          1 => array(
                                                                     'mode' => 40000, 
                                                                     'name' => 'gui', 
                                                                     'hash' => 'ef43f576cc98b5d49a3e1c50a6806254439ca54b', 
                                                                     'type' => 2
                                                                    ),
                                                          2 => array(
                                                                     'mode' => 100644, 
                                                                     'name' => 'test', 
                                                                     'hash' => '16b14f5da9e2fcd6f3f38cc9e584cef2f3c90ebe', 
                                                                     'type' => 3
                                                                    ),
                                                          3 => array(
                                                                     'mode' => 40000, 
                                                                     'name' => 'tmp', 
                                                                     'hash' => '3140ff00eeab44f80db384ac144309a70014a081', 
                                                                     'type' => 2
                                                                    )));
    }

    public function testTreeFind()
    {
        $r = Repo::Open(__DIR__ . '/testRepo');
        $tree = $r->getHeadCommit()->getTree();
        $this->assertEquals($tree->Find('gui/lib/index.tcl')->getBlobHash(), '2a294220b93638ee5378fc4851db98dab23ab002');
        $this->assertEquals($tree->Find('gui/lib/')->getObjectType(), Object::TYPE_TREE);
    }
}

?>
