<?php
set_include_path(realpath(dirname(__FILE__) . '/../src/') . PATH_SEPARATOR . get_include_path());

require_once 'Doctrine.php';
spl_autoload_register(array('Doctrine', 'autoload'));

require_once dirname(__FILE__) . '/../src/Doctrine/Template/Positionable.php';

class StuffWhichShouldBePositionable extends Doctrine_Record
{
    function setUp()
    {
        $this->hasColumn('name', 'string', 255);
        $this->actAs('Positionable');
    }
}

try {
    $sqlite_file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'sandbox.db';
    Doctrine_Manager::connection('sqlite:///' . $sqlite_file, 'sandbox');
    Doctrine::createTablesFromArray(array('StuffWhichShouldBePositionable'));
} catch (Exception $e) {

}

$record1 = new StuffWhichShouldBePositionable();
$record1->name = 'test1';
$record1->save();

$record2 = new StuffWhichShouldBePositionable();
$record2->name = 'test2';
$record2->save();

$record3 = new StuffWhichShouldBePositionable();
$record3->name = 'test3';
$record3->save();

$record1->moveDown();
echo $record1->getPosition(); // outputs 2
echo $record2->getPosition(); // outputs 1
echo $record3->getPosition(); // outputs 3

$record1->moveTo(4);
echo $record1->getPosition(); // outputs 3
echo $record2->getPosition(); // outputs 1
echo $record3->getPosition(); // outputs 2

$record1->moveUp();
echo $record1->getPosition(); // outputs 2
echo $record2->getPosition(); // outputs 1
echo $record3->getPosition(); // outputs 3

$record1->delete();
$record2->delete();
$record3->delete();
@unlink($sqlite_file);