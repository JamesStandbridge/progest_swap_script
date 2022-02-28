<?php

/**
 * @author James Standbridge <james.standbridge.git@gmail.com>
 */

define("DEV_DATA_DIR", __DIR__."/data/");

require 'vendor/autoload.php';

use Boeki\PogestSwapScript\XML\Reader;

$path = DEV_DATA_DIR."articles_JARCNT.xml";
$xml_content = file_get_contents($path);

$xml = new Reader($xml_content);

$products = $xml->getProducts();

dd($products);