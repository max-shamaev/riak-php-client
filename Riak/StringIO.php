<?php
// vim: set ts=4 sw=4 sts=4 et:

/**
 * PHP version 5.3.0
 * 
 * @author  Rusty Klophaus (@rklophaus) (rusty@basho.com) 
 * @author  Maxim Shamaev (maxim.shamaev@gmail.com) 
 * @license Apache License 2.0 http://www.apache.org/licenses/LICENSE-2.0
 * @version $id$
 * @link    http://www.basho.com/
 * @since   1.0.0
 */

namespace Riak;

/**
 * Private class used to accumulate a CURL response.
 * 
 * @since 1.0.0
 */
class StringIO
{
    protected $contents = '';

    /**
     * Write 
     * 
     * @param mixed $ch   ???
     * @param string$data Data
     *  
     * @return integer
     * @since  1.0.0
     */
    public function write($ch, $data)
    {
        $this->contents .= $data;

        return strlen($data);
    }

    /**
     * Get contents 
     * 
     * @return string
     * @since  1.0.0
     */
    public function contents()
    {
        return $this->contents;
    }
}

