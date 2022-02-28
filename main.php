<?php

/**
 * @author James Standbridge <james.standbridge.git@gmail.com>
 */

define("DEV_DATA_DIR", __DIR__."/data/");

require 'vendor/autoload.php';

use Boeki\PogestSwapScript\XML\Reader;
use Boeki\PogestSwapScript\SQL\Manager;

$path = DEV_DATA_DIR."articles_JARCNT.xml";
$xml_content = file_get_contents($path);

$xml = new Reader($xml_content);

$products = $xml->getProducts();

$databases = [
    ["code" => "BIO301", "manager" => null, "name" => "bn25189-1664-7x8n4j-dev"],
];

foreach($databases as $key => $database) {
    $databases[$key]['manager'] = new Manager(
        "localhost", 
        "bn25189-1664-7x8n4j", 
        "8NahAnfy4eDkUa1G0pgIedxTqQBw8u", 
        $database['name'] 
    );
}

foreach($products as $product) {
    foreach($databases as $database) {
        $sql_product = $database['manager']->getProduct($product['code_article']);
        if(!$sql_product) {
            //insert
            $database['manager']->insertArticle($product);
        } else {
            $coldUpdate = !compareProductsCold($product, $sql_product);
            $hotUpdate = false;
            
            if($coldUpdate || $hotUpdate) {
                //update
                $database['manager']->updateArticle($product, $coldUpdate, $hotUpdate);
            }
        }
    }
}

//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


function compareProductsCold(array $p1, array $p2): bool
{
    $columns = ["code_article", "nom", "description", "description_courte", "marque", "unite", "increment", "libelles", "sku", "poids", "status", "classe_tva"];

    foreach($columns as $column) {
        if($p1[$column] != $p2[$column]) {
            return false;
        }
    }
    return true;
}