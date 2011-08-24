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
 * Utility functions used by Riak library.
 * 
 * @since 1.0.0
 */
class Utils
{
    /**
     * Get array cell value 
     * 
     * @param string $key          Cell key
     * @param array  $array        Array
     * @param mixed  $defaultValue Default value
     *  
     * @return mixed
     * @since  1.0.0
     */
    public static function getValue($key, array $array, $defaultValue) {
        return array_key_exists($key, $array) ? $array[$key] : $defaultValue;
    }

    /**
     * Given a RiakClient, RiakBucket, Key, LinkSpec, and Params, construct and return a URL.
     * 
     * @param \Riak\Client $client Client
     * @param \Riak\Bucket $bucket Bucket OPTIONAL
     * @param string       $key    Key
     * @param array        $spec   Specifications
     * @param array        $params Query parameters
     *  
     * @return string
     * @since  1.0.0
     */
    public static function buildRestPath(
        \Riak\Client$client,
        \Riak\Bucket $bucket = null,
        $key = null,
        array $spec = array(),
        array $params = array()
    ) {
        // Build 'http://hostname:port/prefix/bucket'
        $path = 'http://'
            . $client->host . ':' . $client->port
            . '/' . $client->prefix;

        // Add '.../bucket'
        if (isset($bucket)) {
            $path .= '/' . urlencode($bucket->name);
        }

        // Add '.../key'
        if (isset($key)) {
            $path .= '/' . urlencode($key);
        }

        // Add '.../bucket,tag,acc/bucket,tag,acc'
        $s = array();
        foreach ($spec as $el) {
            $s[] = urlencode($el[0]) . ',' . urlencode($el[1]) . ',' . $el[2];
        }

        if ($s) {
            $path .= '/' . implode('/', $s);
        }

        // Add query parameters.
        if ($params) {
            $path .= '?' . http_build_query($params);
        }

        return $path;
    }

    /**
     * Given a Method, URL, Headers, and Body, perform and HTTP request,
     * and return an array of arity 2 containing an associative array of
     * response headers and the response body.
     * 
     * @param string $method         HTTP method
     * @param string $url            URL
     * @param array  $requestHeaders Request HTTP headers
     * @param mixed  $obj            Post data
     *  
     * @return array
     * @since  1.0.0
     */
    public static function httpRequest($method, $url, $requestHeaders = array(), $obj = '')
    {
        // Set up curl
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);

        if ($method == 'GET') {
            curl_setopt($ch, CURLOPT_HTTPGET, 1);

        } elseif ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $obj);

        } elseif ($method == 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $obj);

        } elseif ($method == 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        // Capture the response headers...
        $responseHeadersIO = new \Riak\StringIO();
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($responseHeadersIO, 'write'));

        // Capture the response body...
        $responseBodyIO = new \Riak\StringIO();
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, array($responseBodyIO, 'write'));

        $result = null;

        try {
            // Run the request.
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Get the headers...
            $parsedHeaders = \Riak\Utils::parseHttpHeaders($responseHeadersIO->contents());
            $responseHeaders = array('httpCode' => $httpCode);
            foreach ($parsedHeaders as $key => $value) {
                $responseHeaders[strtolower($key)] = $value;
            }

            // Get the body...
            $responseBody = $responseBodyIO->contents();

            // Return a new RiakResponse object.
            $result = array($responseHeaders, $responseBody);

        } catch (\Exception $e) {
            curl_close($ch);
            error_log('Error: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Parse an HTTP Header string into an asssociative array of response headers.
     * 
     * @param string $headers HTTP raw headers
     *  
     * @return array
     * @since  1.0.0
     */
    protected static function parseHttpHeaders($headers)
    {
        $retVal = array();
        $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/S', ' ', $headers));
        foreach ($fields as $field ) {
            if (preg_match('/([^:]+): (.+)/Sm', $field, $match)) {
                $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./Se', 'strtoupper("\0")', strtolower(trim($match[1])));
                $retVal[$match[1]] = isset($retVal[$match[1]])
                    ? array($retVal[$match[1]], $match[2])
                    : trim($match[2]);
            }
        }

        return $retVal;
    }
}

