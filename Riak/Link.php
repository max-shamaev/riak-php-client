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
 * The RiakLink object represents a link from one Riak object to another.
 * 
 * @since 1.0.0
 */
class Link
{
    /**
     * The bucket name.
     * 
     * @var   string
     * @since 1.0.0
     */
    protected $bucket;

    /**
     * The key 
     * 
     * @var   string
     * @since 1.0.0
     */
    protected $key;

    /**
     * The tag 
     * 
     * @var   string
     * @since 1.0.0
     */
    protected $tag;

    /**
     * Constructor
     * 
     * @param string $bucket The bucket name.
     * @param string $key    The key.
     * @param string $tag    The tag. OPTIONAL
     *  
     * @return void
     * @since  1.0.0
     */
    public function __construct($bucket, $key, $tag = null)
    {
        $this->bucket = $bucket;
        $this->key = $key;
        $this->tag = $tag;
        $this->client = null;
    }

  /**
   * Retrieve the RiakObject to which this link points.
   * @param integer $r - The R-value to use.
   * @return RiakObject
   */

    /**
     * Retrieve the \Riak\Object to which this link points.
     * 
     * @param integer $r The R-value to use OPTIONAL
     *
     * @return \Riak\Object
     * @since  1.0.0
     */
    public function get($r = null)
    {
        return $this->client->bucket($this->bucket)->get($this->key, $r);
    }

    /**
     * Retrieve the \Riak\Object to which this link points, as a binary.
     * 
     * @param integer $r The R-value to use OPTIONAL
     *  
     * @return \Riak\Object
     * @since  1.0.0
     */
    public function getBinary($r = null) {
        return $this->client->bucket($this->bucket)->getBinary($this->key, $r);
    }

    /**
     * Get bucket name
     * 
     * @return string
     * @since  1.0.0
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * Set bucket name
     * 
     * @param string $bucketName Bucket name
     *  
     * @return \Riak\Link
     * @since  1.0.0
     */
    public function setBucket($bucketName)
    {
        $this->bucket = $bucketName;

        return $this;
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
     * Set key 
     * 
     * @param string $key Key
     *  
     * @return \Riak\Link
     * @since  1.0.0
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Get tag 
     * 
     * @return string
     * @since  1.0.0
     */
    public function getTag()
    {
        return isset($this->tag) ? $this->tag : $this->bucket;
    }

    /**
     * Set tag 
     * 
     * @param string $tag Tag
     *  
     * @return \Riak\Link
     * @since  1.0.0
     */
    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Convert this \Riak\Link object to a link header string. Used internally.
     * 
     * @param \Riak\Client $client Client
     *  
     * @return string
     * @since  1.0.0
     */
    public function toLinkHeader(\Riak\Client $client)
    {
        return '</' .
            $client->prefix . '/' .
            urlencode($this->bucket) . '/' .
            urlencode($this->key) . '>; riaktag="' .
            urlencode($this->getTag()) . '"';
    }

    /**
     * Return true if the links are equal.
     * 
     * @param \Riak\Link $link Another link
     *  
     * @return boolean
     * @since  1.0.0
     */
    public function isEqual(\Riak\Link $link)
    {
        return ($this->bucket == $link->getBucket()) &&
            ($this->key == $link->getKey()) &&
            ($this->getTag() == $link->getTag());
    }
}
