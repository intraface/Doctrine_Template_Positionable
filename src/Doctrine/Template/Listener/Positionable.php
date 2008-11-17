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
 * Doctrine_Template_Listener_Sluggable
 *
 * Easily create a slug for each record based on a specified set of fields
 *
 * @package     Doctrine
 * @subpackage  Template
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Template_Listener_Positionable extends Doctrine_Record_Listener
{
    /**
     * Array of timestampable options
     *
     * @var string
     */
    protected $_options = array();

    /**
     * __construct
     *
     * @param string $array
     * @return void
     */
    public function __construct(array $options)
    {
        $this->_options = $options;
    }

    /**
     * preInsert
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function preInsert(Doctrine_Event $event)
    {
        $name = $this->_options['name'];

        $record = $event->getInvoker();

        if (!$record->$name) {
            $max = $this->getMaxPosition($record);
            $record->$name = $max + 1;
        }
    }

    /**
     * Returns the current max position for any possible
     * record in the table
     *
     * @return integer
     */
    private function getMaxPosition($record)
    {
        $conn = Doctrine_Manager::connection();

        try {
            if (!empty($this->_options['extra_where'])) {
                try {
                    $q = $record->getTable()->createQuery();
                    $q->select('*');
                    foreach ((array)$this->_options['extra_where'] as $where) {
                        $q->addWhere($where . ' = ?', array($record->{$where}));
                    }
                    $collection = $q->execute();
                } catch (Doctrine_Exception $e) {
                    throw $e;
                } catch (Exception $e) {
                    throw $e;
                }
            } else {
                $collection = $record->getTable()->findAll();
            }

            if (count($collection) > 0) {
                $object = $collection->getLast();
                $name = $this->_options['name'];
                $max = $object->$name;
            } else {
                $max = 0;
            }
        } catch (Doctrine_Exception $e) {
            $max = 0;
        }

        return $max;
    }
}