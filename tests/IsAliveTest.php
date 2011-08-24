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

class IsAliveTest extends PHPUnit_Framework_TestCase
{
    public function testIsAlive()
    {
        $client = new \Riak\Client(HOST, PORT);
        $this->assertTrue($client->isAlive(), 'check server live status');
    }
}

