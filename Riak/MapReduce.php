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
 * @see     ____file_see____
 * @since   1.0.0
 */

namespace Riak;

/**
 * The RiakMapReduce object allows you to build up and run a map/reduce operation on Riak.
 * 
 * @see   ____class_see____
 * @since 1.0.0
 */
class MapReduce
{
    /**
     * Riak client 
     * 
     * @var   \Riak\Client
     * @see   ____var_see____
     * @since 1.0.0
     */
    protected $client;

    protected $phases = array();

    protected $inputs = array();

    protected $inputMode;

    protected $keyFilters = array();

    /**
     * Constructor
     * 
     * @param \Riak\Client $client Client
     *  
     * @return void
     * @see    ____func_see____
     * @since  1.0.0
     */
    public function __construct(\Riak\Client $client)
    {
        $this->client = $client;
    }

    // {{{ Operations

    /**
     * Add inputs to a map/reduce operation. This method takes three
     * different forms, depending on the provided inputs. You can
     * specify either  a RiakObject, a string bucket name, or a bucket,
     * key, and additional argument.
     * 
     * @param \Riak\Object|\Riak\Bucket $arg1 Riak object or bucket
     * @param string                    $arg2 Key
     * @param mixed                     $arg3 Additional argument
     *  
     * @return \Riak\MapReduce
     * @see    ____func_see____
     * @since  1.0.0
     */
    public function add($arg1, $arg2 = null, $arg3 = null)
    {
        if (func_num_args() != 1) {
            $result = $this->addBucketKeyData($arg1, (string) $arg2, $arg3);

        } elseif ($arg1 instanceof \Riak\Object) {
            $result = $this->addObject($arg1);

        } else {
            $result = $this->addBucket($arg1);
        }

        return $result;
    }

    /**
     * Begin a map/reduce operation using a Search. This command will
     * return an error unless executed against a Riak Search cluster.
     * 
     * @param string $bucket Bucket name
     * @param string $query  The Query to execute. (Lucene syntax)
     *  
     * @return \Riak\MapReduce
     * @see    ____func_see____
     * @since  1.0.0
     */
    public function search($bucket, $query)
    {
        $this->inputs = array(
            'module'   => 'riak_search',
            'function' => 'mapred_search',
            'arg'      => array($bucket, $query)
        );

        return $this;
    }

    /**
     * Add a link phase to the map/reduce operation.
     *
     * @param string  $bucket Bucket name (default '_', which means all buckets) OPTIONAL
     * @param string  $tag    Tag (default '_', which means all buckets) OPTIONAL
     * @param boolean $keep   Flag whether to keep results from this stage in the map/reduce. (default FALSE, unless this is the last step in the phase)
     *  
     * @return \Riak\MapReduce
     * @see    ____func_see____
     * @since  1.0.0
     */
    public function link($bucket = '_', $tag = '_', $keep = false)
    {
        $this->phases[] = new \Riak\LinkPhase($bucket, $tag, $keep);

        return $this;
    }

    /**
     * Add a map phase to the map/reduce operation.
     * 
     * @param string|array $function Either a named Javascript function (ie: "Riak.mapValues"), or an anonymous javascript function (ie: "function(...) { ... }" or an array ["erlang_module", "function"].
     * @param array        $options  An optional associative array containing "language", "keep" flag, and/or "arg". OPTIONAL
     *  
     * @return \Riak\MapReduce
     * @see    ____func_see____
     * @since  1.0.0
     */
    public function map($function, array $options = array())
    {
        $language = is_array($function) ? 'erlang' : 'javascript';
        $this->phases[] = new \Riak\MapReducePhase(
            'map',
            $function,
            \Riak\Utils::get_value('language', $options, $language),
            \Riak\Utils::get_value('keep', $options, false),
            \Riak\Utils::get_value('arg', $options, null)
        );

        return $this;
    }

    /**
     * Add a reduce phase to the map/reduce operation.
     * 
     * @param string|array $function Either a named Javascript function (ie: "Riak.mapValues"), or an anonymous javascript function (ie: "function(...) { ... }" or an array ["erlang_module", "function"].
     * @param array        $options  An optional associative array containing "language", "keep" flag, and/or "arg". OPTIONAL
     *  
     * @return \Riak\MapReduce
     * @see    ____func_see____
     * @since  1.0.0
     */
    public function reduce($function, array $options = array())
    {
        $language = is_array($function) ? 'erlang' : 'javascript';
        $this->phases[] = new \Riak\MapReducePhase(
            'reduce',
            $function,
            \Riak\Utils::get_value('language', $options, $language),
            \Riak\Utils::get_value('keep', $options, false),
            \Riak\Utils::get_value('arg', $options, null)
        );

        return $this;
    }

    /**
     * Add object 
     * 
     * @param \Riak\Object $obj Object
     *  
     * @return \Riak\MapReduce
     * @see    ____func_see____
     * @since  1.0.0
     */
    protected function addObject(\Riak\Object $obj)
    {
        return $this->addBucketKeyData($obj->bucket->name, $obj->key);
    }

    /**
     * Add bucket 
     * 
     * @param \Riak\Bucket $bucket Bucket
     *  
     * @return \Riak\MapReduce
     * @see    ____func_see____
     * @since  1.0.0
     */
    protected function addBucket(\Riak\Bucket $bucket)
    {
        $this->inputMode = 'bucket';
        $this->inputs = $bucket;

        return $this;
    }

    /**
     * Add bucket key data 
     * 
     * @param string $bucket Bucket name
     * @param string $key    Key
     * @param mixed  $data   Additional data OPTIONAL
     *  
     * @return void
     * @see    ____func_see____
     * @since  1.0.0
     */
    protected function addBucketKeyData($bucket, $key, $data = null) {
        if ('bucket' == $this->inputMode) {
            throw new \Exception('Already added a bucket, can\'t add an object.');
        }

        $this->inputs[] = array($bucket, $key, $data);

        return $this;
    }

    // }}}
}
