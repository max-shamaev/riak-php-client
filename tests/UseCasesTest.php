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
        $obj = $bucket->get('missing');
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
        // Set up the bucket, clear any existing object...
        $client = new \Riak\Client(HOST, PORT);
        $bucket = $client->bucket('multiBucket');
        $bucket->setAllowMultiples('true');
        $obj = $bucket->get('foo');
        $obj->delete();

        // Store the same object multiple times...
        for ($i = 0; $i < 5; $i++) {
            $client = new \Riak\Client(HOST, PORT);
            $bucket = $client->bucket('multiBucket');
            $obj = $bucket->newObject('foo', rand());
            $obj->store();
        }

        // Make sure the object has 5 siblings...
        $this->assertTrue($obj->hasSiblings(), 'check hasSiblings flag');
        $this->assertEquals(5, $obj->getSiblingCount(), 'check siblings count');

        // Test getSibling()/getSiblings()...
        $siblings = $obj->getSiblings();
        $obj3 = $obj->getSibling(3);
        $this->assertEquals($siblings[3]->getData(), $obj3->getData(), 'check equal data');

        // Resolve the conflict, and then do a get...
        $obj3 = $obj->getSibling(3);
        $obj3->store();
        $obj->reload();
        $this->assertEquals($obj->getData(), $obj3->getData(), 'check equal data after update');

        // Clean up for next test...
        $obj->delete();
    }

    public function testJavascriptSourceMap()
    {
        // Create the object...
        $client = new \Riak\Client(HOST, PORT);
        $bucket = $client->bucket('bucket');
        $bucket->newObject('foo', 2)->store();

        // Run the map...
        $result = $client
            ->add('bucket', 'foo')
            ->map('function (v) { return [JSON.parse(v.values[0].data)]; }')
            ->run();
        $this->assertEquals(array(2), $result, 'check result');
    }

    public function testJavascriptNamedMap()
    {
        // Create the object...
        $client = new \Riak\Client(HOST, PORT);
        $bucket = $client->bucket('bucket');
        $bucket->newObject('foo', 2)->store();

        // Run the map...
        $result = $client
            ->add('bucket', 'foo')
            ->map('Riak.mapValuesJson')
            ->run();
        $this->assertEquals(array(2), $result, 'check result');
    }

    public function testJavascriptSourceMapReduce()
    {
        // Create the object...
        $client = new \Riak\Client(HOST, PORT);
        $bucket = $client->bucket('bucket');
        $bucket->newObject('foo', 2)->store();
        $bucket->newObject('bar', 3)->store();
        $bucket->newObject('baz', 4)->store();

        // Run the map...
        $result = $client
            ->add('bucket', 'foo')
            ->add('bucket', 'bar')
            ->add('bucket', 'baz')
            ->map('function (v) { return [1]; }')
            ->reduce('Riak.reduceSum')
            ->run();
        $this->assertEquals(3, $result[0], 'check result');
    }

    public function testJavascriptNamedMapReduce()
    {
        // Create the object...
        $client = new \Riak\Client(HOST, PORT);
        $bucket = $client->bucket('bucket');
        $bucket->newObject('foo', 2)->store();
        $bucket->newObject('bar', 3)->store();
        $bucket->newObject('baz', 4)->store();

        // Run the map...
        $result = $client
            ->add('bucket', 'foo')
            ->add('bucket', 'bar')
            ->add('bucket', 'baz')
            ->map('Riak.mapValuesJson')
            ->reduce('Riak.reduceSum')
            ->run();
        $this->assertEquals(array(9), $result, 'check result');
    }

    public function testJavascriptBucketMapReduce()
    {
        // Create the object...
        $client = new \Riak\Client(HOST, PORT);
        $bucket = $client->bucket('bucket_' . rand());
        $bucket->newObject('foo', 2)->store();
        $bucket->newObject('bar', 3)->store();
        $bucket->newObject('baz', 4)->store();

        // Run the map...
        $result = $client
            ->add($bucket->getName())
            ->map('Riak.mapValuesJson') 
            ->reduce('Riak.reduceSum')
            ->run();
        $this->assertEquals(array(9), $result, 'check result');
    }

    public function testJavascriptArgMapReduce()
    {
        // Create the object...
        $client = new \Riak\Client(HOST, PORT);
        $bucket = $client->bucket('bucket');
        $bucket->newObject('foo', 2)->store();

        // Run the map...
        $result = $client
            ->add('bucket', 'foo', 5)
            ->add('bucket', 'foo', 10)
            ->add('bucket', 'foo', 15)
            ->add('bucket', 'foo', -15)
            ->add('bucket', 'foo', -5)
            ->map('function(v, arg) { return [arg]; }')
            ->reduce('Riak.reduceSum')
            ->run();
        $this->assertEquals(array(10), $result, 'check result');
    }

    public function testErlangMapReduce()
    {
        // Create the object...
        $client = new \Riak\Client(HOST, PORT);
        $bucket = $client->bucket('bucket');
        $bucket->newObject('foo', 2)->store();
        $bucket->newObject('bar', 2)->store();
        $bucket->newObject('baz', 4)->store();

        // Run the map...
        $result = $client
            ->add('bucket', 'foo')
            ->add('bucket', 'bar')
            ->add('bucket', 'baz')
            ->map(array('riak_kv_mapreduce', 'map_object_value'))
            ->reduce(array('riak_kv_mapreduce', 'reduce_set_union'))
            ->run();
        $this->assertEquals(2, count($result), 'check result');
    }

    public function testMapReduceFromObject()
    {
        // Create the object...
        $client = new \Riak\Client(HOST, PORT);
        $bucket = $client->bucket('bucket');
        $bucket->newObject('foo', 2)->store();

        $obj = $bucket->get('foo');
        $result = $obj->map('Riak.mapValuesJson')->run();
        $this->assertEquals(array(2), $result, 'check result');
    }

    public function testKeyFilter()
    {
        // Create the object...
        $client = new \Riak\Client(HOST, PORT);
        $bucket = $client->bucket('filter_bucket');
        $bucket->newObject('foo_one',   array('foo' => 'one'  ))->store();
        $bucket->newObject('foo_two',   array('foo' => 'two'  ))->store();
        $bucket->newObject('foo_three', array('foo' => 'three'))->store();
        $bucket->newObject('foo_four',  array('foo' => 'four' ))->store();
        $bucket->newObject('moo_five',  array('foo' => 'five' ))->store();

        $mapred = $client
            ->add($bucket->getName())
            ->keyFilter(array('tokenize', '_', 1), array('eq', 'foo'));
        $results = $mapred->run();
        $this->assertEquals(4, count($results), 'check result');
    }

    public function testKeyFilterOperator()
    {
        // Create the object...
        $client = new \Riak\Client(HOST, PORT);
        $bucket = $client->bucket('filter_bucket');
        $bucket->newObject('foo_one',   array('foo' => 'one'  ))->store();
        $bucket->newObject('foo_two',   array('foo' => 'two'  ))->store();
        $bucket->newObject('foo_three', array('foo' => 'three'))->store();
        $bucket->newObject('foo_four',  array('foo' => 'four' ))->store();
        $bucket->newObject('moo_five',  array('foo' => 'five' ))->store();

        $mapred = $client
            ->add($bucket->getName())
            ->keyFilter(array('starts_with', 'foo'))
            ->keyFilterOr(array('ends_with', 'five'));
        $results = $mapred->run();
        $this->assertEquals(5, count($results), 'check result');
    }

    public function testStoreAndGetLinks()
    {
        // Create the object...
        $client = new \Riak\Client(HOST, PORT);
        $bucket = $client->bucket('bucket');
        $bucket->newObject('foo', 2)->
            addLink($bucket->newObject('foo1'))->
            addLink($bucket->newObject('foo2'), 'tag')->
            addLink($bucket->newObject('foo3'), 'tag2!@//$%^&*')->
            store();

        $obj = $bucket->get('foo');
        $links = $obj->getLinks();
        $this->assertEquals(3, count($links), 'check links number');
    }

    public function testLinkWalking()
    {
        // Create the object...
        $client = new \Riak\Client(HOST, PORT);
        $bucket = $client->bucket('bucket');
        $bucket->newObject('foo', 2)
            ->addLink($bucket->newObject('foo1', 'test1')->store())
            ->addLink($bucket->newObject('foo2', 'test2')->store(), 'tag')
            ->addLink($bucket->newObject('foo3', 'test3')->store(), 'tag2!@//$%^&*')
            ->store();

        $obj = $bucket->get('foo');
        $results = $obj->link('bucket')->run();
        $this->assertEquals(3, count($results), 'check results length');

        $results = $obj->link('bucket', 'tag')->run();
        $this->assertEquals(1, count($results), 'check new results length');
    }

    public function testSearchIntegration()
    {
        // Create some objects to search across...
        $client = new \Riak\Client(HOST, PORT);
        $bucket = $client->bucket('searchbucket');
        $bucket->newObject('one', array('foo' => 'one', 'bar' => 'red'))->store();
        $bucket->newObject('two', array('foo' => 'two', 'bar' => 'green'))->store();
        $bucket->newObject('three', array('foo' => 'three', 'bar' => 'blue'))->store();
        $bucket->newObject('four', array('foo' => 'four', 'bar' => 'orange'))->store();
        $bucket->newObject('five', array('foo' => 'five', 'bar' => 'yellow'))->store();

        // Run some operations...
        $results = $client->search('searchbucket', 'foo:one OR foo:two')->run();
        if (count($results) == 0) {
            $this->markTestSkipped(
                'Not running test "testSearchIntegration()"' . PHP_EOL
                . 'Please ensure that you have installed the Riak Search hook on bucket "searchbucket" by running "bin/search-cmd install searchbucket"'
            );
        }

        $this->assertEquals(2, count($results), 'check results length');

        $results = $client->search('searchbucket', '(foo:one OR foo:two OR foo:three OR foo:four) AND (NOT bar:green)')->run();
        $this->assertEquals(3, count($results), 'check new results length');
    }

}

