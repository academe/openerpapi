<?php

namespace Academe\OpenErpApi;

/**
 * Class XmlRpcClient Interface
 * @package Academe\OpenErpApi
 * @todo Create an interface for use by multiple clients.
 * @todo Take a look at how the returning data structures change between the XML
 * and the JSON APIs. They will be identical at some level, but I suspect not at
 * this level, and so will need to be normalised.
 */
interface RpcClientInterface
{
    /**
     * @param $path
     */
    public function setPath($path);

    /**
     * @param $method
     * @param array $params
     * @return mixed|\SimpleXMLElement|string
     */
    public function call($method, $params = array());
}
