<?php

/**
 * @author James Standbridge <james.standbridge.git@gmail.com>
 */

// define("COLD_DIR", dirname(__DIR__, 2)."/progest/");
// define("HOT_DIR", dirname(__DIR__, 2)."/progest/");
define("COLD_DIR", __DIR__."/data/");
define("HOT_DIR", __DIR__."/data/");

define("COLD_FILE_NAME", "articles_JARCNT.xml");
//define("HOT_FILE_NAME", "artstock.xml");
define("HOT_FILE_NAME", "artstock_fullday_20220222_174546.xml");

require 'vendor/autoload.php';

use Boeki\PogestSwapScript\XML\Reader;
use Boeki\PogestSwapScript\SQL\Manager;

$arg_store_code = $argv[1]; 
$arg_database = $argv[2]; 

$cold_content = file_get_contents(COLD_DIR.COLD_FILE_NAME);
$hot_content = file_get_contents(HOT_DIR.HOT_FILE_NAME);

$xml = new Reader($cold_content);
$products = $xml->getProducts();

$xml = new Reader($hot_content);
$products = $xml->applyHotContent($products, $arg_store_code);

$sql_manager = new Manager(
    "localhost", 
    "bn25189-1664-7x8n4j", 
    "8NahAnfy4eDkUa1G0pgIedxTqQBw8u", 
    $arg_database
);

foreach($products as $product) {
    $sql_product = $sql_manager->getProduct($product['code_article']);
    if(!$sql_product) {
        //insert
        if($product['status'] === 1) { //only if web true
            $sql_manager->insertArticle($product);
        }
    } else {
        $coldUpdate = !compareProductsCold($product, $sql_product);
        //update cold
        if($coldUpdate) { //only ondatachange
            $sql_manager->updateColdArticle($product);
        }
    }
    if(isset($product["hot_content"])) {
        dump($product["hot_content"]);
        $hotUpdate = $sql_product ? !compareProductHot($product["hot_content"], $sql_product) : true;

        //update hot
        if($hotUpdate) { //only ondatachange
            $sql_manager->updateHotArticle($product["code_article"], $product["hot_content"]);
        }
    }
}

//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

function compareProductHot(array $p1, array $p2): bool
{
    $columns = ["prix", "quantite"];

    foreach($columns as $column) {
        if($p1[$column] != $p2[$column]) {
            return false;
        }
    }
    return true;
}

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