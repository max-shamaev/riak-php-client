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

class UseCasesTest extends PHPUnit_Framework_TestCase
{
    public function testIsAlive()
    {
        $client = new \Riak\Client(HOST, PORT);
        $this->assertTrue($client->isAlive(), 'check server live status');
    }

    public function testStoreAndGet()
    {
        $client = new \Riak\Client(HOST, PORT);
        $bucket = $client->bucket('bucket');

        $rand = rand();
        $obj = $bucket->newObject('foo', $rand);
        $obj->store();

        $obj = $bucket->get('foo');

        $this->assertTrue($obj->exists(), 'object must be exists');
        $this->assertEquals('bucket', $obj->getBucket()->getName(), 'check bucket name');
        $this->assertEquals('foo', $obj->getKey(), 'check key name');
        $this->assertEquals($rand, $obj->getData(), 'check cell data');
    }

    public function testStoreAndGetWithoutKey()
    {
        $client = new \Riak\Client(HOST, PORT);
        $bucket = $client->bucket('bucket');

        $rand = rand();
        $obj = $bucket->newObject(null, $rand);
        $obj->store();

        $key = $obj->getKey();

        $obj = $bucket->get($key);

        $this->assertTrue($obj->exists(), 'object must be exists');
        $this->assertEquals('bucket', $obj->getBucket()->getName(), 'check bucket name');
        $this->assertEquals($key, $obj->getKey(), 'check key name');
        $this->assertEquals($rand, $obj->getData(), 'check cell data');
    }

    public function testBinaryStoreAndGet()
    {
        $client = new \Riak\Client(HOST, PORT);
        $bucket = $client->bucket('bucket');

        // Store as binary, retrieve as binary, then compare...
        $rand = rand();
        $obj = $bucket->newBinary('foo1', $rand);
        $obj->store();
        $obj = $bucket->getBinary('foo1');
        $this->assertTrue($obj->exists(), 'object must be exists');
        $this->assertEquals($rand, $obj->getData(), 'check cell data');

        //Store as JSON, retrieve as binary, JSON-decode, then compare...
        $data = array(rand(), rand(), rand());
        $obj = $bucket->newObject('foo2', $data);
        $obj->store();
        $obj = $bucket->getBinary('foo2');
        $this->assertEquals($data, json_decode($obj->getData()), 'check cell JSON data');
    }

    public function testMissingObject()
    {
        $client = new \Riak\Client(HOST, PORT);
        $bucket = $client->bucket('bucket');
        $obj = $bucket->get("missing");
        $this->assertFalse($obj->exists(), 'object must be NOT exists');
        $this->assertNull($obj->getData(), 'check cell data - must be null');
    }

    public function testDelete()
    {
        $client = new \Riak\Client(HOST, PORT);
        $bucket = $client->bucket('bucket');

        $rand = rand();
        $obj = $bucket->newObject('foo', $rand);
        $obj->store();

        $obj = $bucket->get('foo');
        $this->assertTrue($obj->exists(), 'object must be exists');

        $obj->delete();
        $obj->reload();
        $this->assertFalse($obj->exists(), 'object must be NOT exists - cell is removed');
    }

    public function testSetBucketProperties()
    {
        $client = new \Riak\Client(HOST, PORT);
        $bucket = $client->bucket('bucket');

        // Test setting allow mult...
        $bucket->setAllowMultiples(true);
        $this->assertTrue($bucket->getAllowMultiples(), 'check allowMultiples property - checked');

        // Test setting nval...
        $bucket->setNVal(3);
        $this->assertEquals(3, $bucket->getNVal(), 'check NVal');

        // Test setting multiple properties...
        $bucket->setProperties(array('allow_mult' => false, 'n_val' => 2));
        $this->assertFalse($bucket->getAllowMultiples(), 'check allowMultiples property - unchecked');
        $this->assertEquals(2, $bucket->getNVal(), 'check new NVal');
    }

    public function testSiblings()
    {
        # Set up the bucket, clear any existing object...
        $client = new \Riak\Client(HOST, PORT);
        $bucket = $client->bucket('multiBucket');
        $bucket->setAllowMultiples('true');
        $obj = $bucket->get('foo');
        $obj->delete();

        # Store the same object multiple times...
        for ($i = 0; $i < 5; $i++) {
            $client = new \Riak\Client(HOST, PORT);
            $bucket = $client->bucket('multiBucket');
            $obj = $bucket->newObject('foo', rand());
            $obj->store();
        }

        # Make sure the object has 5 siblings...
        $this->assertTrue($obj->hasSiblings(), 'check hasSiblings flag');
        $this->assertEquals(5, $obj->getSiblingCount(), 'check siblings count');

        # Test getSibling()/getSiblings()...
        $siblings = $obj->getSiblings();
        $obj3 = $obj->getSibling(3);
        $this->assertEquals($siblings[3]->getData(), $obj3->getData(), 'check equal data');

        # Resolve the conflict, and then do a get...
        $obj3 = $obj->getSibling(3);
        $obj3->store();
        $obj->reload();
        $this->assertEquals($obj->getData(), $obj3->getData(), 'check equal data after update');

        # Clean up for next test...
        $obj->delete();
    }

    public function testJavascriptSourceMap()
    {
        # Create the object...
        $client = new \Riak\Client(HOST, PORT);
        $bucket = $client->bucket('bucket');
        $bucket->newObject('foo', 2)->store();

        # Run the map...
        $result = $client
            ->add('bucket', 'foo')
            ->map('function (v) { return [JSON.parse(v.values[0].data)]; }')
            ->run();
        $this->assertEquals(array(2), $result, 'check result');
    }

    public function testJavascriptNamedMap()
    {
        # Create the object...
        $client = new \Riak\Client(HOST, PORT);
        $bucket = $client->bucket('bucket');
        $bucket->newObject('foo', 2)->store();

        # Run the map...
        $result = $client
            ->add('bucket', 'foo')
            ->map('Riak.mapValuesJson')
            ->run();
        $this->assertEquals(array(2), $result, 'check result');
    }

    public function testJavascriptSourceMapReduce()
    {
        # Create the object...
        $client = new \Riak\Client(HOST, PORT);
        $bucket = $client->bucket('bucket');
        $bucket->newObject('foo', 2)->store();
        $bucket->newObject('bar', 3)->store();
        $bucket->newObject('baz', 4)->store();

        # Run the map...
        $result = $client
            ->add('bucket', 'foo')
            ->add('bucket', 'bar')
            ->add('bucket', 'baz')
            ->map('function (v) { return [1]; }')
            ->reduce('Riak.reduceSum')
            ->run();
        $this->assertEquals(3, $result[0], 'check result');
    }

}

