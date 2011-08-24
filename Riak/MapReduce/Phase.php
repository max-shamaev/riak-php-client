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

namespace Riak\MapReduce;

/**
 * The RiakMapReducePhase holds information about a Map phase or
 * Reduce phase in a RiakMapReduce operation.
 * 
 * @since 1.0.0
 */
class Phase
{
    /**
     * Phase type (map or reduce)
     * 
     * @var   string
     * @since 1.0.0
     */
    protected $type;

    /**
     * Language (erlang or javascript)
     * 
     * @var   string
     * @since 1.0.0
     */
    protected $language;

    /**
     * Function as string (javascript) or array (erlang)
     * 
     * @var   string|array
     * @since 1.0.0
     */
    protected $function;

    /**
     * True to return the output of this phase in the results 
     * 
     * @var   boolean
     * @since 1.0.0
     */
    protected $keep = false;

    /**
     * Additional value to pass into the map or reduce function
     * 
     * @var   mixed
     * @since 1.0.0
     */
    protected $arg;

    /**
     * Constructor
     * 
     * @param string  $type     Phase type (map or reduce)
     * @param mixed   $function Function as string (javascript) or array (erlang)
     * @param string  $language Language (erlang or javascript)
     * @param boolean $keep     True to return the output of this phase in the results
     * @param mixed   $arg      Additional value to pass into the map or reduce function
     *  
     * @return void
     * @since  1.0.0
     */
    public function __construct($type, $function, $language, $keep, $arg)
    {
        $this->type = $type;
        $this->language = $language;
        $this->function = $function;
        $this->keep = $keep;
        $this->arg = $arg;
    }

    /**
     * Get 'keep' flag
     * 
     * @return boolean
     * @since  1.0.0
     */
    public function getKeep()
    {
        return $this->keep;
    }

    /**
     * Set 'keep' flag
     * 
     * @param boolean $keep Keep flag value
     *  
     * @return void
     * @since  1.0.0
     */
    public function setKeep($keep)
    {
        $this->keep = $keep;
    }
    /**
     * Convert the object to an associative array. Used internally
     * 
     * @return array
     * @since  1.0.0
     */
    public function toArray()
    {
        $stepdef = array(
            'keep'     => $this->keep,
            'language' => $this->language,
            'arg'      => $this->arg,
        );

        if ('javascript' == $this->language && is_array($this->function)) {
            $stepdef['bucket'] = $this->function[0];
            $stepdef['key'] = $this->function[1];

        } elseif ('javascript' == $this->language && is_string($this->function)) {

            if (false === strpos($this->function, '{')) {
                $stepdef['name'] = $this->function;
            } else {
                $stepdef['source'] = $this->function;
            }

        } elseif ('erlang' == $this->language && is_array($this->function)) {
            $stepdef['module'] = $this->function[0];
            $stepdef['function'] = $this->function[1];
        }

        return array($this->type => $stepdef);
    }
}
