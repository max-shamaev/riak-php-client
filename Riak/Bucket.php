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
 * The RiakBucket object allows you to access and change information
 * about a Riak bucket, and provides methods to create or retrieve
 * objects within the bucket.
 * 
 * @since 1.0.0
 */
class Bucket
{
    /**
     * Client 
     * 
     * @var   \Riak\Client
     * @since 1.0.0
     */
    protected $client;

    /**
     * Bucket name 
     * 
     * @var   string
     * @since 1.0.0
     */
    protected $name;

    /**
     * R-Value
     * 
     * @var   integer
     * @since 1.0.0
     */
    protected $r;

    /**
     * W-value
     * 
     * @var   integer
     * @since 1.0.0
     */
    protected $w;

    /**
     * DW-value
     * 
     * @var   integer
     * @since 1.0.0
     */
    protected $dw;

    /**
     * Constructor
     * 
     * @param \Riak\Client $client Cline
     * @param string       $name   Bucket name
     *  
     * @return void
     * @since  1.0.0
     */
    public function __construct(\Riak\Client $client, $name)
    {
        $this->client = $client;
        $this->name = $name;
    }

    /**
     * Get name 
     * 
     * @return string
     * @since  1.0.0
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the R-value for this bucket, if it is set, otherwise return
     * the R-value for the client.
     * 
     * @param integer $r R-value for this bucket OPTIONAL
     *  
     * @return integer
     * @since  1.0.0
     */
    public function getR($r = null)
    {
        if (!isset($r)) {
            $r = isset($this->r) ? $this->r : $this->client->getR();
        }

        return $r;
    }

    /**
     * Set the R-value for this bucket. get(...) and getBinary(...) operations that do not specify an R-value will use this value.
     * 
     * @param integer $r R-value
     *  
     * @return \Riak\Bucket
     * @since  1.0.0
     */
    public function setR($r)
    {
        $this->r = $r;

        return $this;
    }

    /**
     * Get the W-value for this bucket, if it is set, otherwise return
     * the W-value for the client.
     *
     * @param integer $w W-value for this bucket OPTIONAL
     *
     * @return integer
     * @since  1.0.0
     */
    public function getW($w = null)
    {
        if (!isset($w)) {
            $w = isset($this->w) ? $this->w : $this->client->getW();
        }

        return $w;
    }

    /**
     * Set the W-value for this bucket. get(...) and getBinary(...) operations that do not specify an W-value will use this value.
     *
     * @param integer $w W-value
     *
     * @return \Riak\Bucket
     * @since  1.0.0
     */
    public function setW($w)
    {
        $this->w = $w;

        return $this;
    }

    /**
     * Get the DW-value for this bucket, if it is set, otherwise return
     * the DW-value for the client.
     *
     * @param integer $dw DW-value for this bucket OPTIONAL
     *
     * @return integer
     * @since  1.0.0
     */
    public function getDW($dw = null)
    {
        if (!isset($dw)) {
            $dw = isset($this->dw) ? $this->dw : $this->client->getDW();
        }

        return $dw;
    }

    /**
     * Set the DW-value for this bucket. get(...) and getBinary(...) operations that do not specify an DW-value will use this value.
     *
     * @param integer $dw DW-value
     *
     * @return \Riak\Bucket
     * @since  1.0.0
     */
    public function setDW($dw)
    {
        $this->dw = $dw;

        return $this;
    }

    /**
     * Create a new Riak object that will be stored as JSON.
     * 
     * @param string $key  Name of the key
     * @param object $data The data to store OPTIONAL
     *  
     * @return \Riak\Object
     * @since  1.0.0
     */
    public function newObject($key, $data = null)
    {
        $obj = new \Riak\Object($this->client, $this, $key);
        $obj->setData($data);
        $obj->setContentType('text/json');
        $obj->jsonize = true;

        return $obj;
    }

  /**
   * Create a new Riak object that will be stored as plain text/binary.
   * @param  string $key - Name of the key.
   * @param  object $data - The data to store.
   * @param  string $content_type - The content type of the object. (default 'text/json')
   * @return RiakObject
   */

    /**
     * Create a new Riak object that will be stored as plain text/binary.
     * 
     * @param string $key         Name of the key
     * @param object $data        The data to store
     * @param string $contentType The content type of the object OPTIONAL
     *  
     * @return \Riak\Object
     * @since  1.0.0
     */
    public function newBinary($key, $data, $contentType = 'text/json')
    {
        $obj = new \Riak\Object($this->client, $this, $key);
        $obj->setData($data);
        $obj->setContentType($contentType);
        $obj->jsonize = false;

        return $obj;
    }

    /**
     * Retrieve a JSON-encoded object from Riak.
     * 
     * @param string  $key Name of the key
     * @param integer $r   R-Value of the request OPTIONAL
     *  
     * @return \Riak\Object
     * @since  1.0.0
     */
    public function get($key, $r = null)
    {
        $obj = new \Riak\Object($this->client, $this, $key);
        $obj->jsonize = true;

        return $obj->reload($this->getR($r));
    }

    /**
     * Retrieve a binary/string object from Riak.
     * 
     * @param string  $key Name of the key
     * @param integer $r   R-Value of the request OPTIONAL
     *  
     * @return \Riak\Object
     * @since  1.0.0
     */
    public function getBinary($key, $r = null)
    {
        $obj = new \Riak\Object($this->client, $this, $key);
        $obj->jsonize = false;

        return $obj->reload($this->getR($r));
    }

    /**
     * Set the N-value for this bucket, which is the number of replicas
     * that will be written of each object in the bucket. Set this once
     * before you write any data to the bucket, and never change it
     * again, otherwise unpredictable things could happen. This should
     * only be used if you know what you are doing.
     * 
     * @param integer $nval The new N-Val
     *  
     * @return void
     * @since  1.0.0
     */
    public function setNVal($nval)
    {
        return $this->setProperty('n_val', $nval);
    }

    /**
     * Retrieve the N-value for this bucket.
     * 
     * @return integer
     * @since  1.0.0
     */
    public function getNVal()
    {
        return $this->getProperty('n_val');
    }

    /**
     * If set to true, then writes with conflicting data will be stored
     * and returned to the client. This situation can be detected by
     * calling hasSiblings() and getSiblings(). This should only be used
     * if you know what you are doing.
     * 
     * @param boolean $bool True to store and return conflicting writes
     *  
     * @return void
     * @since  1.0.0
     */
    public function setAllowMultiples($bool)
    {
        return $this->setProperty('allow_mult', $bool);
    }

    /**
     * Retrieve the 'allow multiples' setting.
     * 
     * @return boolean
     * @since  1.0.0
     */
    public function getAllowMultiples()
    {
        return 'true' == $this->getProperty('allow_mult');
    }

    /**
     * Set a bucket property. This should only be used if you know what you are doing.
     * 
     * @param string $key   Property to set
     * @param mixed  $value Property value
     *  
     * @return void
     * @since  1.0.0
     */
    public function setProperty($key, $value)
    {
        return $this->setProperties(array($key => $value));
    }

    /**
     * Retrieve a bucket property.
     * 
     * @param string $key The property to retrieve
     *  
     * @return mixed
     * @since  1.0.0
     */
    public function getProperty($key)
    {
        $props = $this->getProperties();

        return array_key_exists($key, $props) ? $props[$key] : null;
    }

    /**
     * Set multiple bucket properties in one call. This should only be used if you know what you are doing.
     * 
     * @param array $props An associative array of $key=>$value
     *  
     * @return void
     * @since  1.0.0
     * @throws \Exception
     */
    public function setProperties(array $props)
    {
        // Construct the URL, Headers, and Content...
        $url = \Riak\Utils::buildRestPath($this->client, $this);
        $headers = array('Content-Type: application/json');
        $content = json_encode(array('props' => $props));

        // Run the request...
        $response = \Riak\Utils::httpRequest('PUT', $url, $headers, $content);

        // Handle the response...
        if (!isset($response)) {
            throw \Exception('Error setting bucket properties.');
        }

        // Check the response value...
        $status = $response[0]['http_code'];
        if ($status != 204) {
            throw \Exception('Error setting bucket properties.');
        }
    }

    /**
     * Retrieve an associative array of all bucket properties. 
     * 
     * @return array
     * @since  1.0.0
     * @throws \Exception
     */
    public function getProperties()
    {
        // Run the request...
        $params = array('props' => 'true', 'keys' => 'false');
        $url = \Riak\Utils::buildRestPath($this->client, $this, null, null, $params);
        $response = RiakUtils::httpRequest('GET', $url);

        // Use a RiakObject to interpret the response, we are just interested in the value.
        $obj = new \Riak\Object($this->client, $this, null);
        $obj->populate($response, array(200));
        if (!$obj->exists()) {
            throw \Exception('Error getting bucket properties.');
        }

        $props = $obj->getData();

        return $props['props'];
    }

    /**
     * Retrieve an array of all keys in this bucket.
     * Note: this operation is pretty slow.
     * 
     * @return array
     * @since  1.0.0
     */
    public function getKeys()
    {
        $params = array('props' => 'false', 'keys' => 'true');
        $url = \Riak\Utils::buildRestPath($this->client, $this, null, null, $params);
        $response = \Riak\Utils::httpRequest('GET', $url);

        // Use a RiakObject to interpret the response, we are just interested in the value.
        $obj = new \Riak\Object($this->client, $this, null);
        $obj->populate($response, array(200));
        if (!$obj->exists()) {
            throw \Exception("Error getting bucket properties.");
        }
        $keys = $obj->getData();

        return array_map('urldecode', $keys['keys']);
    }
}

