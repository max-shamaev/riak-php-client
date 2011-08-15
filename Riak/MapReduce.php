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
 * The RiakMapReduce object allows you to build up and run a map/reduce operation on Riak.
 * 
 * @since 1.0.0
 */
class MapReduce
{
    /**
     * Riak client 
     * 
     * @var   \Riak\Client
     * @since 1.0.0
     */
    protected $client;

    /**
     * Phases 
     * 
     * @var   array
     * @since 1.0.0
     */
    protected $phases = array();

    /**
     * Inputs 
     * 
     * @var   array
     * @since 1.0.0
     */
    protected $inputs = array();

    /**
     * Input mode 
     * 
     * @var   string
     * @since 1.0.0
     */
    protected $inputMode;

    /**
     * Key filters 
     * 
     * @var   array
     * @since 1.0.0
     */
    protected $keyFilters = array();

    /**
     * Constructor
     * 
     * @param \Riak\Client $client Client
     *  
     * @return void
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
     * @since  1.0.0
     */
    public function link($bucket = '_', $tag = '_', $keep = false)
    {
        $this->phases[] = new \Riak\Link\Phase($bucket, $tag, $keep);

        return $this;
    }

    /**
     * Add a map phase to the map/reduce operation.
     * 
     * @param string|array $function Either a named Javascript function (ie: "Riak.mapValues"), or an anonymous javascript function (ie: "function(...) { ... }" or an array ["erlang_module", "function"].
     * @param array        $options  An optional associative array containing "language", "keep" flag, and/or "arg". OPTIONAL
     *  
     * @return \Riak\MapReduce
     * @since  1.0.0
     */
    public function map($function, array $options = array())
    {
        $language = is_array($function) ? 'erlang' : 'javascript';
        $this->phases[] = new \Riak\MapReduce\Phase(
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
     * @since  1.0.0
     */
    public function reduce($function, array $options = array())
    {
        $language = is_array($function) ? 'erlang' : 'javascript';
        $this->phases[] = new \Riak\MapReduce\Phase(
            'reduce',
            $function,
            \Riak\Utils::get_value('language', $options, $language),
            \Riak\Utils::get_value('keep', $options, false),
            \Riak\Utils::get_value('arg', $options, null)
        );

        return $this;
    }

    /**
     * Add a key filter to the map/reduce operation.  If there are already
     * existing filters, an "and" condition will be used to combine them.
     * Alias for keyFilterAnd
     *  
     * @return \Riak\MapReduce
     * @since  1.0.0
     */
    public function keyFilter()
    {
        $args = func_get_args();
        array_unshift($args, 'and');

        return call_user_func_array(array($this, 'keyFilterOperator'), $args);
    }

    /**
     * Add a key filter to the map/reduce operation.  If there are already
     * existing filters, an "and" condition will be used to combine them.
     *
     * @return \Riak\MapReduce
     * @since  1.0.0
     */
    public function keyFilterAnd()
    {
        $args = func_get_args();
        array_unshift($args, 'and');

        return call_user_func_array(array($this, 'keyFilterOperator'), $args);
    }

    /**
     * Add a key filter to the map/reduce operation.  If there are already
     * existing filters, an "or" condition will be used to combine them.
     *
     * @return \Riak\MapReduce
     * @since  1.0.0
     */
    public function keyFilterOr()
    {
        $args = func_get_args();
        array_unshift($args, 'or');

        return call_user_func_array(array($this, 'keyFilterOperator'), $args);
    }

    /**
     * Adds a key filter to the map/reduce operation.  If there are already
     * existing filters, the provided conditional operator will be used
     * to combine with the existing filters.
     * 
     * @param string $operator Operator (usually "and" or "or")
     *  
     * @return \Riak\MapReduce
     * @since  1.0.0
     */
    public function keyFilterOperator($operator)
    {
        if ('bucket' != $this->inputMode) {
          throw new \Exception('Key filters can only be used in bucket mode');
        }

        $filters = func_get_args();
        array_shift($filters);

        if (count($this->keyFilters) > 0) {
            $this->keyFilters = array(
                array(
                    $operator,
                    $this->keyFilters,
                    $filters
                )
            );

        } else {
            $this->keyFilters = $filters;
        }

        return $this;
    }

    /**
     * Run the map/reduce operation. Returns an array of results, or an
     * array of \Riak\Link objects if the last phase is a link phase.
     * 
     * @param integer $timeout Timeout in seconds OPTIONAL
     *  
     * @return array
     * @since  1.0.0
     */
    public function run($timeout = null)
    {
        $numPhases = count($this->phases);

        // If there are no phases, then just echo the inputs back to the user.
        if (0 == $numPhases) {
            $this->reduce(array('riak_kv_mapreduce', 'reduce_identity'));
            $numPhases = 1;
            $linkResultsFlag = true;

        } else {
            $linkResultsFlag = false;
        }

        /**
         * Convert all phases to associative arrays. Also,
         * if none of the phases are accumulating, then set the last one to
         * accumulate.
         */
        $keepFlag = false;
        $query = array();
        for ($i = 0; $i < $numPhases; $i++) {
            $phase = $this->phases[$i];
            if ($i == ($numPhases - 1) && !$keepFlag) {
                $phase->keep = true;
            }

            if ($phase->keep) {
                $keepFlag = true;
            }

            $query[] = $phase->to_array();
        }

        // Add key filters if applicable
        if ('bucket' == $this->inputMode && count($this->keyFilters) > 0) {
            $this->inputs = array(
                'bucket'      => $this->inputs,
                'key_filters' => $this->keyFilters,
            );
        }

        // Construct the job, optionally set the timeout...
        $job = array('inputs' => $this->inputs, 'query' => $query);
        if (isset($timeout)) {
            $job['timeout'] = $timeout;
        }
        $content = json_encode($job);

        // Do the request...
        $url = 'http://' . $this->client->host . ':' . $this->client->port . '/' . $this->client->mapred_prefix;
        $response = \Riak\Utils::httpRequest('POST', $url, array(), $content);
        $result = json_decode($response[1]);

        // If the last phase is NOT a link phase, then return the result.
        $linkResultsFlag |= (end($this->phases) instanceof \Riak\Link\Phase);

        if ($linkResultsFlag) {

            // If the last phase IS a link phase, then convert the results to \Riak\Link objects.
            $a = array();
            foreach ($result as $r) {
                $tag = isset($r[2]) ? $r[2] : null;
                $link = new \Riak\Link($r[0], $r[1], $tag);
                $link->client = $this->client;
                $a[] = $link;
            }
            $result = $a;
        }

        return $result;
    }

    /**
     * Add object 
     * 
     * @param \Riak\Object $obj Object
     *  
     * @return \Riak\MapReduce
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
