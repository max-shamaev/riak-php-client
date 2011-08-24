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
 * The RiakObject holds meta information about a Riak object, plus the object's data.
 * 
 * @since 1.0.0
 */
class Object
{
    /**
     * Client 
     * 
     * @var   \Riak\Client
     * @since 1.0.0
     */
    protected $client;

    /**
     * Bucket 
     * 
     * @var   \Riak\Bucket
     * @since 1.0.0
     */
    protected $bucket;

    /**
     * An optional key. If not specified, then key is generated by server when store(...) is called.
     * 
     * @var   string
     * @since 1.0.0
     */
    protected $key;

    /**
     * Jsonize data or not
     * 
     * @var   boolean
     * @since 1.0.0
     */
    protected $jsonize = true;

    /**
     * headers 
     * 
     * @var   array
     * @since 1.0.0
     */
    protected $headers = array();

    /**
     * links 
     * 
     * @var   array
     * @since 1.0.0
     */
    protected $links = array();

    /**
     * siblings 
     * 
     * @var   mixed
     * @since 1.0.0
     */
    protected $siblings = null;

    /**
     * Object exists flag
     * 
     * @var   boolean
     * @since 1.0.0
     */
    protected $exists = false;

    /**
     * Object's data
     * 
     * @var   string|array
     * @since 1.0.0
     */
    protected $data;

    /**
     * Constructor
     * 
     * @param \Riak\Client $client Client
     * @param \Riak\Bucket $bucket Bucket
     * @param string       $key    An optional key. If not specified, then key is generated by server when store(...) is called. OPTIONAL
     *  
     * @return void
     * @since  1.0.0
     */
    public function __construct(\Riak\Client $client, \Riak\Bucket $bucket, $key = null)
    {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->key = $key;
    }

    /**
     * Get client 
     * 
     * @return \Riak\Client
     * @since  1.0.0
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Get bucket 
     * 
     * @return \Riak\Bucket
     * @since  1.0.0
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * Get key 
     * 
     * @return string
     * @since  1.0.0
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Get the data stored in this object. Will return a associative
     * array, unless the object was constructed with newBinary(...) or
     * getBinary(...), in which case this will return a string.
     * 
     * @return string|array
     * @since  1.0.0
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the data stored in this object. This data will be
     * JSON encoded unless the object was constructed with
     * newBinary(...) or getBinary(...).
     * 
     * @param mixed $data The data to store
     *  
     * @return mixed
     * @since  1.0.0
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this->data;
    }

    /**
     * Get jsonize flag
     * 
     * @return boolean
     * @since  1.0.0
     */
    public function getJsonize()
    {
        return $this->jsonize;
    }

    /**
     * Set jsonize flag
     * 
     * @param boolean $jsonize Flag
     *  
     * @return void
     * @since  1.0.0
     */
    public function setJsonize($jsonize)
    {
        $this->jsonize = $jsonize;
    }

    /**
     * Get the HTTP status from the last operation on this object.
     * 
     * @return integer
     * @since  1.0.0
     */
    public function status()
    {
        return intval($this->headers['httpCode']);
    }

    /**
     * Return true if the object exists, false otherwise. Allows you to detect a get(...) or getBinary(...) operation where the object is missing.
     * 
     * @return boolean
     * @since  1.0.0
     */
    public function exists()
    {
        return $this->exists;
    }

    /**
     * Get the content type of this object. This is either text/json, or the provided content type if the object was created via newBinary(...).
     * 
     * @return string
     * @since  1.0.0
     */
    public function getContentType()
    {
        return $this->headers['content-type'];
    }

    /**
     * Set the content type of this object.
     * 
     * @param string $contentType The new content type
     *  
     * @return \Riak\Object
     * @since  1.0.0
     */
    public function setContentType($contentType)
    {
        $this->headers['content-type'] = $contentType;

        return $this;
    }

    /**
     * Add a link to a RiakObject.
     * 
     * @param object $obj Either a RiakObject or a RiakLink object
     * @param string $tag Optional link tag. (default is bucket name, ignored if $obj is a RiakLink object.) OPTIONAL
     *  
     * @return \Riak\Object
     * @since  1.0.0
     */
    public function addLink($obj, $tag = null)
    {
        $newlink = ($obj instanceof \Riak\Link) ? $obj : new \Riak\Link($obj->bucket->name, $obj->key, $tag);
        $this->removeLink($newlink);
        $this->links[] = $newlink;

        return $this;
    }

    /**
     * Remove a link to a RiakObject. 
     * 
     * @param object $obj Either a RiakObject or a RiakLink object
     * @param string $tag Optional link tag. (default is bucket name, ignored if $obj is a RiakLink object.) OPTIONAL
     *  
     * @return \Riak\Object
     * @since  1.0.0
     */
    public function removeLink($obj, $tag = null)
    {
        $oldlink = ($obj instanceof \Riak\Link) ? $obj : new \Riak\Link($obj->bucket->name, $obj->key, $tag);

        $a = array();
        foreach ($this->links as $link) {
            if (!$link->isEqual($oldlink)) {
                $a[] = $link;
            }
        }

        $this->links = $a;

        return $this;
    }

    /**
     * Return an array of RiakLink objects.
     * 
     * @return array
     * @since  1.0.0
     */
    public function getLinks()
    {
        foreach ($this->links as $link) {
            $link->client = $this->client;
        }

        return $this->links;
    }

    /**
     * Store the object in Riak. When this operation completes, the
     * object could contain new metadata and possibly new data if Riak
     * contains a newer version of the object according to the object's
     * vector clock.
     * 
     * @param integer $w  W-value, wait for this many partitions to respond before returning to client OPTIONAL
     * @param integer $dw DW-value, wait for this many partitions to confirm the write before returning to client OPTIONAL
     *  
     * @return \Riak\Object
     * @since  1.0.0
     */
    public function store($w = null, $dw = null)
    {
        // Use defaults if not specified...
        $w = $this->bucket->getW($w);
        $dw = $this->bucket->getDW($w);

        // Construct the URL...
        $params = array('returnbody' => 'true', 'w' => $w, 'dw' => $dw);
        $url = \Riak\Utils::buildRestPath($this->client, $this->bucket, $this->key, array(), $params);

        // Construct the headers...
        $headers = array(
            'Accept: text/plain, */*; q=0.5',
            'Content-Type: ' . $this->getContentType(),
            'X-Riak-ClientId: ' . $this->client->getClientID()
        );

        // Add the vclock if it exists...
        if ($this->vclock() != null) {
            $headers[] = 'X-Riak-Vclock: ' . $this->vclock();
        }

        // Add the Links...
        foreach ($this->links as $link) {
            $headers[] = 'Link: ' . $link->toLinkHeader($this->client);
        }

        $content = $this->jsonize
            ? json_encode($this->getData())
            : $this->getData();

        $method = $this->key ? 'PUT' : 'POST';

        // Run the operation.
        $response = \Riak\Utils::httpRequest($method, $url, $headers, $content);
        $this->populate($response, array(200, 201, 300));

        return $this;
    }

    /**
     * Reload the object from Riak. When this operation completes, the
     * object could contain new metadata and a new value, if the object
     * was updated in Riak since it was last retrieved.
     * 
     * @param integer $r R-Value, wait for this many partitions to respond before returning to client OPTIONAL
     *  
     * @return \Riak\Object
     * @since  1.0.0
     */
    public function reload($r = null)
    {
        // Do the request...
        $r = $this->bucket->getR($r);
        $url = \Riak\Utils::buildRestPath($this->client, $this->bucket, $this->key, array(), array('r' => $r));
        $response = \Riak\Utils::httpRequest('GET', $url);
        $this->populate($response, array(200, 300, 404));

        // If there are siblings, load the data for the first one by default...
        if ($this->hasSiblings()) {
            $this->setData($this->getSibling(0)->getData());
        }

        return $this;
    }

    /**
     * Delete this object from Riak.
     * 
     * @param integer $dw DW-value. Wait until this many partitions have deleted the object before responding OPTIONAL
     *  
     * @return \Riak\Object
     * @since  1.0.0
     */
    public function delete($dw = null)
    {
        // Use defaults if not specified...
        $dw = $this->bucket->getDW($dw);

        // Construct the URL...
        $url = \Riak\Utils::buildRestPath($this->client, $this->bucket, $this->key, array(), array('dw' => $dw));

        // Run the operation...
        $response = \Riak\Utils::httpRequest('DELETE', $url);
        $this->populate($response, array(204, 404));

        return $this;
    }

    /**
     * Return true if this object has siblings.
     * 
     * @return boolean
     * @since  1.0.0
     */
    public function hasSiblings()
    {
        return $this->getSiblingCount() > 0;
    }

    /**
     * Get the number of siblings that this object contains.
     * 
     * @return integer
     * @since  1.0.0
     */
    public function getSiblingCount()
    {
        return count($this->siblings);
    }

    /**
     * Retrieve a sibling by sibling number.
     * 
     * @param integer $i Sibling number
     * @param integer $r R-Value. Wait until this many partitions have responded before returning to client OPTIONAL
     *  
     * @return \Riak\Object
     * @since  1.0.0
     */
    public function getSibling($i, $r = null)
    {
        // Use defaults if not specified.
        $r = $this->bucket->getR($r);

        // Run the request...
        $vtag = $this->siblings[$i];
        $params = array('r' => $r, 'vtag' => $vtag);
        $url = \Riak\Utils::buildRestPath($this->client, $this->bucket, $this->key, array(), $params);
        $response = \Riak\Utils::httpRequest('GET', $url);

        // Respond with a new object...
        $obj = new \Riak\Object($this->client, $this->bucket, $this->key);
        $obj->jsonize = $this->jsonize;
        $obj->populate($response, array(200));

        return $obj;
    }

    /**
     * Retrieve an array of siblings.
     * 
     * @param integer $r R-Value. Wait until this many partitions have responded before returning to client OPTIONAL
     *  
     * @return array
     * @since  1.0.0
     */
    public function getSiblings($r = null)
    {
        $a = array();
        for ($i = 0; $i < $this->getSiblingCount(); $i++) {
            $a[] = $this->getSibling($i, $r);
        }

        return $a;
    }

    /**
     * Start assembling a Map/Reduce operation.
     * 
     * @return \Riak\MapReduce
     * @since  1.0.0
     */
    public function add()
    {
        $mr = new \Riak\MapReduce($this->client);
        $mr->add($this->bucket->name, $this->key);

        return call_user_func_array(array($mr, 'add'), func_get_args());
    }

    /**
     * Start assembling a Map/Reduce operation.
     * 
     * @return \Riak\MapReduce
     * @since  1.0.0
     */
    public function link()
    {
        $mr = new \Riak\MapReduce($this->client);
        $mr->add($this->bucket->name, $this->key);

        return call_user_func_array(array($mr, 'link'), func_get_args());
    }

    /**
     * Start assembling a Map/Reduce operation.
     * 
     * @return \Riak\MapReduce
     * @since  1.0.0
     */
    public function map()
    {
        $mr = new \Riak\MapReduce($this->client);
        $mr->add($this->bucket->name, $this->key);

        return call_user_func_array(array($mr, 'map'), func_get_args());
    }

    /**
     * Start assembling a Map/Reduce operation.
     * 
     * @return \Riak\MapReduce
     * @since  1.0.0
     */
    public function reduce()
    {
        $mr = new \Riak\MapReduce($this->client);
        $mr->add($this->bucket->name, $this->key);

        return call_user_func_array(array($mr, 'reduce'), func_get_args());
    }

    /**
     * Given the output of \Riak\Utils::httpRequest and a list of
     * statuses, populate the object. Only for use by the Riak client
     * library.
     * 
     * @param array $response         Response
     * @param array $expectedStatuses Extepcted statuses
     *  
     * @return \Riak\Object
     * @since  1.0.0
     * @throws \Exception
     */
   public function populate($response, array $expectedStatuses)
    {
        $this->clear();

        // If no response given, then return.
        if (!isset($response)) {
            return $this;
        }

        // Update the object...
        $this->headers = $response[0];
        $this->data = $response[1];
        $status = $this->status();

        // Check if the server is down (status==0)
        if (0 == $status) {
            throw new \Exception(
                'Could not contact Riak Server: http://' . $this->client->host . ':' . $this->client->port . '!'
            );
        }

        // Verify that we got one of the expected statuses. Otherwise, throw an exception.
        if (!in_array($status, $expectedStatuses)) {
            throw new \Exception(
                'Expected status ' . implode(' or ', $expectedStatuses) . ', received ' . $status
            );
        }

        // If 404 (Not Found), then clear the object.
        if (404 == $status) {
            $this->clear();

            return $this;
        }

        // If we are here, then the object exists...
        $this->exists = true;

        // Parse the link header...
        if (array_key_exists('link', $this->headers)) {
            $this->populateLinks($this->headers['link']);
        }

        // If 300 (Siblings), then load the first sibling, and store the rest.
        if (300 == $status) {
            $siblings = explode("\n", trim($this->data));
            array_shift($siblings); // Get rid of 'Siblings:' string.
            $this->siblings = $siblings;
            $this->exists = true;

        } else {

            if (201 == $status) {
                $pathParts = explode('/', $this->headers['location']);
                $this->key = array_pop($pathParts);
            }

            // Possibly json_decode...
            if (($status == 200 || $status == 201) && $this->jsonize) {
                $this->data = json_decode($this->data, true);
            }
        }

        return $this;
    }

    /**
     * Reset this object.
     * 
     * @return \Riak\Object
     * @since  1.0.0
     */
    protected function clear()
    {
        $this->headers = array();
        $this->links = array();
        $this->data = null;
        $this->exists = false;
        $this->siblings = null;

        return $this;
    }

    /**
     * Get the vclock of this object.
     * 
     * @return string
     * @since  1.0.0
     */
    protected function vclock()
    {
        return array_key_exists('x-riak-vclock', $this->headers) ? $this->headers['x-riak-vclock'] : null;
    }

    /**
     * Populate links 
     * 
     * @param string $linkHeaders Links as string, from HTTP headers
     *  
     * @return \Riak\Object
     * @since  1.0.0
     */
    protected function populateLinks($linkHeaders)
    {
        foreach (explode(',', trim($linkHeaders)) as $linkHeader) {
            if (preg_match('/\<\/([^\/]+)\/([^\/]+)\/([^\/]+)\>; ?riaktag="([^"]+)"/S', trim($linkHeader), $matches)) {
                $this->links[] = new RiakLink($matches[2], $matches[3], $matches[4]);
            }
        }

        return $this;
    }
}