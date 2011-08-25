# Riak PHP Client #
This is the unofficial PHP 5.3.x client for Riak.

## Documentation ##
Documentation for use of Riak clients in general can be found at<br/>
<https://wiki.basho.com/display/RIAK/Client+Libraries>

## Repositories ##

The unofficial source code for this client can be retrieved from<br/>
<http://github.com/max-shamaev/riak-php-client/>

The official source code for client can be retrieved from<br/>
<http://github.com/basho/riak-php-client/>

Riak can be obtained pre-built from<br/>
<http://downloads.basho.com/riak/>

or as source from<br/>
<http://github.com/basho/riak/>

## Installation ##
Clone this repository to fetch the latest version of this client

    git clone git://github.com/max-shamaev/riak-php-client.git

## Quick start ##
This quick example assumes that you have a local riak cluster running on port 8098

    require_once('riak-php-client/Riak.php');

    // Connect to Riak
    $client = new \Riak\Client('127.0.0.1', 8098);

    // Choose a bucket name
    $bucket = $client->bucket('test');

    // Supply a key under which to store your data
    $person = $bucket->newObject(
        'riak_developer_1',
        array(
            'name'    => "John Smith",
            'age'     => 28,
            'company' => "Facebook",
        )
    );

    // Save the object to Riak
    $person->store();

    // Fetch the object
    $person = $bucket->get('riak_developer_1');

    // Update the object
    $person->data['company'] = 'Google';
    $person->store();

## Connecting ##
Connect to a Riak server by specifying the address or hostname and port:

    # Connect to Riak
    $client = new \Riak\Client('127.0.0.1', 8098);

This method returns a \Riak\Client

## Using Buckets ##
To select a bucket, use the RiakClient::bucket() method

    # Choose a bucket name
    $bucket = $client->bucket('test');

or using the RiakBucket() constructor

    # Create a bucket
    $bucket = new \Riak\Bucket($client, 'test');

If a bucket by this name does not already exist, a new one will be created for you when you store your first key.
This method returns a \Riak\Bucket

## Creating Objects ##
Objects can be created using the \Riak\Bucket::newObject() method

    # Create an object for future storage and populate it with some data
    $person = $bucket->newObject('riak_developer_1');

or using the \Riak\Object() constructor

    # Create an object for future storage
    $person = new \Riak\Object($client, $bucket, 'riak_developer_1');

Both methods return a \Riak\Object

## Setting Object Values ##
Object data can be set using the \Riak\Object::setData() method

    # Populate object with some data
    $person->setData(
        array(
            'name'    => 'John Smith',
            'age'     => 28,
            'company' => 'Facebook',
        )
    );

or you may modify the object's data property directly (not recommended)

    # Populate object with some data
    $person->data = array(
        'name'    => 'John Smith',
        'age'     => 28,
        'company' => 'Facebook',
    );

This method returns a \Riak\Object

## Storing Objects ##
Objects can be stored using the \Riak\Object::store() method

    # Save the object to Riak
    $person->store();

This method returns a \Riak\Object

## Chaining ##
For methods like newObject(), setData() and store() which return objects of a similar class (in this case \Riak\Object), chaining can be used to perform multiple operations in a single statement.

    # Create, set, and store an object
    $data = array(
    	'name'    => 'John Smith',
    	'age'     => 28,
    	'company' => 'Facebook',
    );
    $bucket->newObject('riak_developer_1')->setData($data)->store();

## Fetching Objects ##
Objects can be retrieved from a bucket using the \Riak\Bucket::get() method

    # Save the object to Riak
    $person = $bucket->get('riak_developer_1');

This method returns a \Riak\Object

## Modifying Objects ##
Objects can be modified using the \Riak\Object::store() method

    # Update the object
    $person = $bucket->get('riak_developer_1');
    $person->data['company'] = 'Google';
    $person->store();

This method returns a \Riak\Object

## Deleting Objects ##
Objects can be deleted using the \Riak\Object::delete() method

    # Update the object
    $person = $bucket->get('riak_developer_1');
    $person->delete();

This method returns a \Riak\Object

## Adding a Link ##
Links can be added using \Riak\Object::addLink()

    # Add a link from John to Dave
    $john = $bucket->get('riak_developer_1');
    $dave = $bucket->get('riak_developer_2');
    $john->addLink($dave, 'friend')->store();

This method returns a \Riak\Object

## Removing a Link ##
Links can be removed using \Riak\Object::removeLink()

    # Remove the link from John to Dave
    $john = $bucket->get('riak_developer_1');
    $dave = $bucket->get('riak_developer_2');
    $john->removeLink($dave, 'friend')->store();

This method returns a \Riak\Object

## Retrieving Links ##
An object's links can be retrieved using \Riak\Object::getLinks()

    # Retrieve all of John's links
    $john = $bucket->get('riak_developer_1');
    $links = $john->getLinks();

This method returns an array of \Riak\Link

## Linkwalking ##
Linkwalking can be done using the \Riak\Object::link() method

    # Retrieve all of John's friends
    $john = $bucket->get('riak_developer_1');
    $friends = $john->link($bucket->name, 'friend')->run();

This method returns an array of \Riak\Link

## Dereferencing Links ##
RiakLinks can be dereferenced to the linked object using the \Riak\Link::get() method

    # Retrieve all of John's friends
    $john = $bucket->get('riak_developer_1');
    $dave = $bucket->get('riak_developer_2');
    $john->addLink($dave, 'friend')->store();
    $friends = $john->link($bucket->name, "friend")->run();
    $dave = $friends[0]->get();

This method returns a \Riak\Object

## Fetching Data With Map/Reduce ##
Data can be fetched by Map and Reduce using the \Riak\Client::add() method

    # Fetch a sorted list of all keys in a bucket
    $result = $client->add($bucket->name)
    	->map('function (v) { return [v.key]; }')
    	->reduce('Riak.reduceSort')
    	->run();

This method returns an array of data representing the result of the Map/Reduce functions.

*More examples of Map/Reduce can be found in unit_tests.php*

## Using Key Filters With Map/Reduce ##
When using Map/Reduce on a bucket, you can use key filters to determine the applicable key set using the \Riak\MapReduce::keyFilter(), \Riak\MapReduce::keyFilterAnd(), and \Riak\MapReduce::keyFilterOr() methods.

    # Retrieve the keys of all invoices from May 30, 2010
    $result = $client->add($bucket->name)
        ->keyFilter(array('tokenize', '.', 1), array('eq', 'invoice'))
        ->keyFilterAnd(array('tokenize', '.', 2), array('ends_with', '20100530'))
    	->map('function (v) { return [v.key]; }')
    	->reduce('Riak.reduceSort')
        ->run();

This method returns an array of data representing the result of the Map/Reduce functions.

## Using Search ##
Searches can be executed using the \Riak\Client::search() method

    # Create some test data
    $bucket = $client->bucket('searchbucket');
    $bucket->newObject('one', array('foo' => 'one', 'bar' => 'red'))->store();
    $bucket->newObject('two', array('foo' => 'two', 'bar' => 'green'))->store();

    # Execute a search for all objects with matching properties
    $results = $client->search('searchbucket', 'foo:one OR foo:two')->run();

This method will return null unless executed against a Riak Search cluster.

## Additional Resources ##

See tests\UseCasesTest.php for more examples.<br/>
<https://github.com/max-shamaev/riak-php-client/blob/master/tests/UseCasesTest.php>

## Documentation Maintenance

The PHP API documentation should be regenerated upon each new client release or each new non-trivial API change.

Currently the docs are generated using a tool called [PHPDoctor](http://peej.github.com/phpdoctor/), stored in the master branch of this repo, 'api' directory.

### Generating the PHP Documentation

1. Make sure your local copy of this repository is up to date with the latest release/changes.

2. Clone https://github.com/max-shamaev/phpdoctor repository

3. Now that you've got PHPDoctor, generating the new documentation is easy. The configuration is specified in the file "phpdoctor.ini". Simply tell PHPDoctor to generate the docs using that configuration:

		$ <path_to_phpdoctor_repository>/phpdoc.php phpdoctor.ini

4. This should produce a new "api" directory packed with all sorts of goodness. The next step is to update the "master" branch:

		$ git add api
		$ git commit -m "updated api docs"
		$ git push origin master

Once you push your changes to the master branch they will be synced to https://github.com/max-shamaev/riak-php-client
