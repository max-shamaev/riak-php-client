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

namespace Riak\Link;

/**
 * The RiakLinkPhase object holds information about a Link phase in a map/reduce operation.
 * 
 * @since 1.0.0
 */
class Phase
{
    /**
     * The bucket name.
     * 
     * @var   string
     * @since 1.0.0
     */
    protected $bucket;

    /**
     * The tag.
     * 
     * @var   string
     * @since 1.0.0
     */
    protected $tag;

    /**
     * True to return results of this phase.
     * 
     * @var   boolean
     * @since 1.0.0
     */
    protected $keep = false;

    /**
     * Constructor
     * 
     * @param string  $bucket The bucket name.
     * @param string  $tag    The tag.
     * @param boolean $keep   True to return results of this phase.
     *  
     * @return void
     * @since  1.0.0
     */
    public function __construct($bucket, $tag, $keep)
    {
        $this->bucket = $bucket;
        $this->tag = $tag;
        $this->keep = $keep;
    }

    /**
     * Convert the object to an associative array. Used internally. 
     * 
     * @return array
     * @since  1.0.0
     */
    public function toArray()
    {
        $stepdef = array(
            'bucket' => $this->bucket,
            'tag'    => $this->tag,
            'keep'   => $this->keep,
        );

        return array('link' => $stepdef);
    }
}

