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

require_once dirname(__FILE__) . '/../src/Doctrine/Template/Positionable.php';

class StuffWhichShouldBePositionableWithExtraWhere extends Doctrine_Record
{
    function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 255);
        $this->hasColumn('belong_to_id', 'integer', 11);
    }

    function setUp()
    {
        $options = array('extra_where' => array('belong_to_id'));
        $this->actAs('Positionable', $options);
    }
}

/**
 * Test class
 *
 * @category  Utility
 * @package   Doctrine_Template_Positionable
 * @author    Lars Olesen <lars@legestue.net>
 * @copyright 2007 Authors
 * @license   GPL http://www.opensource.org/licenses/gpl-license.php
 * @version   <package-version>
 * @link      http://public.intraface.dk
 */
class PositionableWithExtraWhereTest extends PHPUnit_Framework_TestCase
{
    private $record;
    private $record1;
    private $record2;
    private $record3;
    private $record4;
    private $sqlite_file;

    public function setUp()
    {
        $result = Doctrine_Manager::connection('sqlite::memory:', 'sandbox');
        try {
            $result = Doctrine::createTablesFromArray(array('StuffWhichShouldBePositionableWithExtraWhere'));
        } catch (Exception $e) {
            print($e->getMessage()); 
        }
        
        $this->createRecords();
    }

    protected function createRecords()
    {
        $this->record = new StuffWhichShouldBePositionableWithExtraWhere();
        $this->record->name = 'test1';
        $this->record->belong_to_id = 1;
        $this->record->save();

        $this->record1 = new StuffWhichShouldBePositionableWithExtraWhere();
        $this->record1->name = 'test2';
        $this->record1->belong_to_id = 1;
        $this->record1->save();

        $this->record2 = new StuffWhichShouldBePositionableWithExtraWhere();
        $this->record2->belong_to_id = 1;
        $this->record2->name = 'test3';
        $this->record2->save();

        $this->record3 = new StuffWhichShouldBePositionableWithExtraWhere();
        $this->record3->belong_to_id = 2;
        $this->record3->name = 'test4';
        $this->record3->save();

        $this->record4 = new StuffWhichShouldBePositionableWithExtraWhere();
        $this->record4->belong_to_id = 2;
        $this->record4->name = 'test5';
        $this->record4->save();

        $this->assertEquals(1, $this->record->getPosition());
        $this->assertEquals(2, $this->record1->getPosition());
        $this->assertEquals(3, $this->record2->getPosition());
        $this->assertEquals(1, $this->record3->getPosition());
        $this->assertEquals(2, $this->record4->getPosition());
    }

    public function tearDown()
    {
        $this->record->delete();
        $this->record1->delete();
        $this->record2->delete();
        $this->record3->delete();
        $this->record4->delete();
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
