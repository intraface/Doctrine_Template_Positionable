<?php
/**
 * Test class requires Sebastian Bergmann's PHPUnit
 *
 * PHP version 5
 *
 * @category  Utility
 * @package   Doctrine_Template_Positionable
 * @author    Lars Olesen <lars@legestue.net>
 * @copyright 2007 Authors
 * @license   GPL http://www.opensource.org/licenses/gpl-license.php
 * @version   <package-version>
 * @link      http://public.intraface.dk
 */
require_once 'PHPUnit/Framework.php';
require_once 'Doctrine.php';

set_include_path(realpath(dirname(__FILE__) . '/../src/') . PATH_SEPARATOR . get_include_path());

spl_autoload_register(array('Doctrine', 'autoload'));

require_once dirname(__FILE__) . '/../src/Doctrine/Template/Positionable.php';

PHPUnit_Util_Filter::addDirectoryToWhitelist(realpath(dirname(__FILE__) . '/../src/'));

class StuffWhichShouldBePositionable extends Doctrine_Record
{
    function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 255);
        $this->hasColumn('belong_to_id', 'integer', 11);
    }

    function setUp()
    {
        $this->actAs('Positionable');
    }
}


class StuffWhichShouldBePositionableWithExtraWhere extends Doctrine_Record
{
    function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 255);
        $this->hasColumn('belong_to_id', 'integer', 11);
    }

    function setUp()
    {
        $options = array('extra_where' => 'belong_to_id = 1');
        $this->actAs('Positionable', $options);
    }
}

/**
 * Test class
 *
 * @category  Utility
 * @package   Ilib_RandomKeyGenerator
 * @author    Lars Olesen <lars@legestue.net>
 * @copyright 2007 Authors
 * @license   GPL http://www.opensource.org/licenses/gpl-license.php
 * @version   <package-version>
 * @link      http://public.intraface.dk
 */
class PositionableTest extends PHPUnit_Framework_TestCase
{
    private $record;
    private $record1;
    private $record2;
    private $record3;
    private $record4;
    private $sqlite_file;

    function setUp()
    {
        $this->sqlite_file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'sandbox.db';
        Doctrine_Manager::connection('sqlite:///' . $this->sqlite_file, 'sandbox');
        Doctrine::createTablesFromArray(array('StuffWhichShouldBePositionable'));

        $this->createRecords();
    }

    function createRecords()
    {
        $this->record = new StuffWhichShouldBePositionable();
        $this->record->name = 'test1';
        $this->record->belong_to_id = 1;
        $this->record->save();

        $this->record1 = new StuffWhichShouldBePositionable();
        $this->record1->name = 'test2';
        $this->record1->belong_to_id = 1;
        $this->record1->save();

        $this->record2 = new StuffWhichShouldBePositionable();
        $this->record2->belong_to_id = 1;
        $this->record2->name = 'test3';
        $this->record2->save();

        $this->record3 = new StuffWhichShouldBePositionable();
        $this->record3->belong_to_id = 2;
        $this->record3->name = 'test4';
        $this->record3->save();

        $this->record4 = new StuffWhichShouldBePositionable();
        $this->record4->belong_to_id = 2;
        $this->record4->name = 'test5';
        $this->record4->save();

        $this->assertEquals(1, $this->record->getPosition());
        $this->assertEquals(2, $this->record1->getPosition());
        $this->assertEquals(3, $this->record2->getPosition());
    }

    function tearDown()
    {
        $this->record->delete();
        $this->record1->delete();
        $this->record2->delete();
        $this->record3->delete();
        $this->record4->delete();
        if (file_exists($this->sqlite_file)) {
            chmod($this->sqlite_file, 777);
            @unlink($this->sqlite_file);
        }
    }

    public function testPreInsertSetsThePositionOnSave()
    {
        $this->assertEquals(1, $this->record->position);
    }

    public function testMovingUpThrowsExceptionIfPositionIsAlreadyOne()
    {
        try {
            $this->assertTrue($this->record->moveUp());
            $this->assertFalse(false, 'Exception should have been thrown');
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testMovingDownThrowsExceptionIfPositionIsToBig()
    {
        try {
            $this->assertTrue($this->record2->moveDown());
            $this->assertFalse(false, 'Exception should have been thrown');
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testMovingUpMovesCurrentPostOneUpAndTheExistingPostOneDown()
    {
        $this->assertTrue($this->record1->moveUp());
        $this->assertEquals(1, $this->record1->getPosition());
        $this->assertEquals(2, $this->record->getPosition());
        $this->assertEquals(3, $this->record2->getPosition());
    }

    public function testMovingDownMovesCurrentPostOneDownAndExistingPostOneUp()
    {
        $this->assertTrue($this->record->moveDown());
        $this->assertEquals(2, $this->record->getPosition());
        $this->assertEquals(1, $this->record1->getPosition());
        $this->assertEquals(3, $this->record2->getPosition());
    }

    public function testMovingToWillMovePostToTheAskedPositionWhileTheOthersArePositionedAccordinly()
    {
        // If moving to position 3, in reality it is moved to
        // position two because all other records are moved
        // accordingly.
        $move_to_pos = 3;
        $this->assertTrue($this->record->moveTo($move_to_pos));
        // actually this should be repositioned so it is 2, 1, 3
        $this->assertEquals(2, $this->record->getPosition());
        $this->assertEquals(1, $this->record1->getPosition());
        $this->assertEquals(3, $this->record2->getPosition());

    }

    function testGetPosition()
    {
        // If moving to position 3, in reality it is moved to
        // position two because all other records are moved
        // accordingly.
        $move_to_pos = 3;
        $this->assertTrue($this->record->moveTo($move_to_pos));
        $this->assertEquals(2, $this->record->getPosition());
    }

}