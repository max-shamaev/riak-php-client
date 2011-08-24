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
 * The RiakClient object holds information necessary to connect to
 * Riak. The Riak API uses HTTP, so there is no persistent
 * connection, and the RiakClient object is extremely lightweight.
 * 
 * @since 1.0.0
 */
class Client
{
    /**
     * Hostname or IP address
     * 
     * @var   string
     * @since 1.0.0
     */
    protected $host;

    /**
     * Port number
     * 
     * @var   integer
     * @since 1.0.0
     */
    protected $port;

    /**
     * Interface prefix
     * 
     * @var   string
     * @since 1.0.0
     */
    protected $prefix;

    /**
     * MapReduce prefix
     * 
     * @var   string
     * @since 1.0.0
     */
    protected $mapredPrefix;

    /**
     * Client ID 
     * 
     * @var   string
     * @since 1.0.0
     */
    protected $clientid;

    /**
     * R-value setting for this RiakClient
     * 
     * @var   integer
     * @since 1.0.0
     */
    protected $r = 2;

    /**
     * W-value setting for this RiakClient
     * 
     * @var   integer
     * @since 1.0.0
     */
    protected $w = 2;

    /**
     * DW-value for this ClientOBject
     * 
     * @var   integer
     * @since 1.0.0
     */
    protected $dw = 2;

    /**
     * Constructor
     * 
     * @param string  $host         Hostname or IP address (default '127.0.0.1') OPTIONAL
     * @param integer $port         Port number (default 8098) OPTIONAL
     * @param string  $prefix       Interface prefix (default "riak") OPTIONAL
     * @param string  $mapredPrefix MapReduce prefix (default "mapred") OPTIONAL
     *  
     * @return void
     * @since  1.0.0
     */
    public function __construct($host = '127.0.0.1', $port = 8098, $prefix = 'riak', $mapredPrefix = 'mapred')
    {
        $this->host = $host;
        $this->port = intval($port);
        $this->prefix = $prefix;
        $this->mapredPrefix = $mapredPrefix;
        $this->clientid = 'php_' . base64_encode(rand(1, 1073741824));
    }

    /**
     * Get server host 
     * 
     * @return string
     * @since  1.0.0
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Get server port 
     * 
     * @return integer
     * @since  1.0.0
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Get prefix 
     * 
     * @return string
     * @since  1.0.0
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    // {{{ Service properties getters / setters

    /**
     * Get the R-value setting for this RiakClient.
     * 
     * @return integer
     * @since  1.0.0
     */
    public function getR()
    {
        return $this->r;
    }

    /**
     * Set the R-value for this RiakClient. This value will be used
     * for any calls to get(...) or getBinary(...) where where 1) no
     * R-value is specified in the method call and 2) no R-value has
     * been set in the RiakBucket.
     * 
     * @param integer $r The R value
     *  
     * @return \Riak\Client
     * @since  1.0.0
     */
    public function setR($r)
    {
        $this->r = $r;

        return $this;
    }

    /**
     * Get the W-value setting for this RiakClient
     * 
     * @return integer
     * @since  1.0.0
     */
    public function getW()
    {
        return $this->w;
    }

    /**
     * Set the W-value for this RiakClient. See setR(...) for a
     * description of how these values are used.
     * 
     * @param integer $w The W value
     *  
     * @return \Riak\Client
     * @since  1.0.0
     */
    public function setW($w)
    {
        $this->w = $w;

        return $this;
    }

    /**
     * Get the DW-value for this ClientOBject
     * 
     * @return integer
     * @since  1.0.0
     */
    public function getDW()
    {
        return $this->dw;
    }

    /**
     * Set the DW-value for this RiakClient. See setR(...) for a
     * description of how these values are used.
     * 
     * @param integer $dw The DW value
     *  
     * @return \Riak\Client
     * @since  1.0.0
     */
    public function setDW($dw)
    {
        $this->dw = $dw;

        return $this;
    }

    /**
     * Get the clientID for this RiakClient.
     * 
     * @return string
     * @since  1.0.0
     */
    public function getClientID()
    {
        return $this->clientid;
    }

    /**
     * Set the clientID for this RiakClient. Should not be called
     * unless you know what you are doing.
     * 
     * @param string $clientid The new clientID
     *  
     * @return \Riak\Client
     * @since  1.0.0
     */
    public function setClientID($clientid)
    {
        $this->clientid = $clientid;

        return $this;
    }

    // }}}

    // {{{ Common routines

    /**
     * Get the bucket by the specified name. Since buckets always exist, this will always return a RiakBucket.
     * 
     * @param string $name Bucket name
     *  
     * @return \Riak\Bucket
     * @since  1.0.0
     */
    public function bucket($name)
    {
        return new \Riak\Bucket($this, $name);
    }

    /**
     * Get all buckets
     * 
     * @return array(\Riak\Bucket)
     * @since  1.0.0
     */
    public function buckets()
    {
        $url = \Riak\Utils::buildRestPath($this);
        $response = \Riak\Utils::httpRequest('GET', $url . '?buckets=true');
        $response = json_decode($response[1], true);
        $buckets = array();
        if ($response && isset($response['buckets'])) {
            foreach ($response['buckets'] as $name) {
                $buckets[] = $this->bucket($name);
            }
        }

        return $buckets;
    }

    /**
     * Check if the Riak server for this RiakClient is alive.
     * 
     * @return boolea
     * @since  1.0.0
     */
    public function isAlive()
    {
        $response = \Riak\Utils::httpRequest('GET', 'http://' . $this->host . ':' . $this->port . '/ping');

        return isset($response) && 'OK' == $response[1];
    }

    // }}}

    // {{{ Map/Reduce and link functions

    /**
     * Start assembling a Map/Reduce operation.
     * 
     * @return \Riak\MapReduce
     * @since  1.0.0
     */
    public function add()
    {
        return call_user_func_array(
            array(new \Riak\MapReduce($this), __METHOD__),
            func_get_args()
        );
    }

    /**
     * Start assembling a Map/Reduce operation. This command will
     * return an error unless executed against a Riak Search cluster.
     * 
     * @return \Riak\MapReduce
     * @since  1.0.0
     */
    public function search()
    {
        return call_user_func_array(
            array(new \Riak\MapReduce($this), __METHOD__),
            func_get_args()
        );
    }

    /**
     * Start assembling a Map/Reduce operation.
     * 
     * @return \Riak\MapReduce
     * @since  1.0.0
     */
    public function link()
    {
        return call_user_func_array(
            array(new \Riak\MapReduce($this), __METHOD__),
            func_get_args()
        );
    }

    /**
     * Start assembling a Map/Reduce operation.
     *
     * @return \Riak\MapReduce
     * @since  1.0.0
     */
    public function map()
    {
        return call_user_func_array(
            array(new \Riak\MapReduce($this), __METHOD__),
            func_get_args()
        );
    }

    /**
     * Start assembling a Map/Reduce operation.
     *
     * @return \Riak\MapReduce
     * @since  1.0.0
     */
    public function reduce()
    {
        return call_user_func_array(
            array(new \Riak\MapReduce($this), __METHOD__),
            func_get_args()
        );
    }

    // }}}
}
