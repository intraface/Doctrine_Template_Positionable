<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.phpdoctrine.org>.
 */

/**
 * Doctrine_Template_Positionable
 *
 * <code>
 *
 * </code>
 *
 * @package     Doctrine
 * @subpackage  Template
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @author      Lars Olesen <lars@legestue.net>
 */
class Doctrine_Template_Positionable extends Doctrine_Template
{
    /**
     * Array of timestampable options
     *
     * @var string
     */
    protected $_options = array('name'       =>  'position',
                                'type'       =>  'integer',
                                'length'     =>  8,
                                'options'    =>  array(),
                                'fields'     =>  array(),
                                'uniqueBy'   =>  array(),
                                'uniqueIndex'=>  false
    );

    /**
     * __construct
     *
     * @param string $array
     * @return void
     */
    public function __construct(array $options)
    {
        $this->_options = Doctrine_Lib::arrayDeepMerge($this->_options, $options);
    }

    /**
     * setUp
     *
     * @return void
     */
    public function setTableDefinition()
    {
        $this->hasColumn($this->_options['name'], $this->_options['type'], $this->_options['length'], $this->_options['options']);
        require_once 'Doctrine/Template/Listener/Positionable.php';
        $this->addListener(new Doctrine_Template_Listener_Positionable($this->_options));
    }

    /**
     * Moves the item one position up
     *
     * @return integer
     */
    public function moveUp()
    {
        $object = $this->getInvoker();
        if ($object->position <= 1) {
            throw new Exception('Item is already at postion 1. Could not move it any further up.');
        }

        $table = Doctrine::getTable($object->getTable()->name);
        $row = $table->findOneByPosition($object->position - 1);
        $row->position = $object->position;
        $row->save();

        $object->position = $object->position - 1;
        $object->save();

        return true;
    }

    /**
     * Moves the item one position down
     *
     * @return integer
     */
    public function moveDown()
    {
        $object = $this->getInvoker();
        if ($object->position >= $this->getMaxPosition()) {
            throw new Exception('Item is already at position ' . (int)$object->position . ' which is max postion. Could not move it any further down.');
        }

        $table = Doctrine::getTable($object->getTable()->name);
        $row = $table->findOneByPosition($object->position + 1);
        $row->position = $object->position;
        $row->save();

        $object->position = $object->position + 1;
        $object->save();

        return true;
    }

    /**
     * Moves the item to a specific position
     *
     * @return integer
     */
    public function moveTo($position)
    {
        // first we will add one to every post from the position this post will get
        // echo "\nfirst reposition";
        $this->reposition($position, $position + 1);

        $object = $this->getInvoker();
        if ($position <= 1 OR $position > $this->getMaxPosition() + 1) {
            throw new Exception('The supplied position '.(int)$position.' is outside the scope of the posible positions. Could not move it.');
        }
        $object->position = $position;
        $object->save();
        // echo "\nsecond reposition";
        $this->reposition();

        return true;
    }

    /**
     * Gets the position for the current record
     *
     * @return integer
     */
    public function getPosition()
    {
        $record = $this->getInvoker();
        return $record->position;
    }

    /**
     * Returns the current max position for any possible
     * record in the table
     *
     * @return integer
     */
    private function getMaxPosition()
    {
        $record = $this->getInvoker();
        $conn = Doctrine_Manager::connection();
        try {
            $collection = $record->getTable()->findAll();
            $object = $collection->getLast();
            $name = $this->_options['name'];
            $max = $object->$name;
        } catch (Doctrine_Exception $e) {
            $max = 0;
        }

        return $max;
    }

    /**
     * Repositions all items so they are in order after moving
     * an item to a specific position.
     */
    private function reposition($start_from_position = 0, $new_position = 1)
    {
        $record = $this->getInvoker();
        $q = Doctrine_Query::create();

        $rows = $q->select($this->_options['name'], 'id')
          ->from($record->getTable()->name)
          ->where($this->_options['name'] . ' >= ' . $start_from_position)
          ->orderby($this->_options['name'] . ' ASC')
          ->execute();

        foreach ($rows as $row) {
            //echo  "\n" . $row->name . ' has ' . $row->position;
            $row->position = $new_position;
            //echo ' and becomes ' . $new_position;
            $row->save();
            $new_position++;
        }
    }
}