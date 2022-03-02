<?php

/**
 * @author James Standbridge <james.standbridge.git@gmail.com>
 */

define("EXPORT_DIR", __DIR__."/data/");
define("LOCAL_HOT_FILENAME", "articles_hot.xml");
define("LOCAL_COLD_FILENAME", "articles_cold.xml");

define("COLD_FILENAME", "articles_JARCNT");
define("HOT_FLY_FILENAME", "artstock_fly");
define("HOT_FULL_FILENAME", "artstock_fullday");

define("REMOTE_DEPOSIT_DIR", "/PUT/prep_CLICK_COLLECT");


require 'vendor/autoload.php';

use Boeki\PogestSwapScript\XML\Reader;
use Boeki\PogestSwapScript\SQL\Manager;
use JamesStandbridge\SimpleSFTP\sftp\SimpleSFTP;

$client = new SimpleSFTP("185.72.89.110", "sftpBoeki", "ngv1FZZE}Rl8tP4");
$client->cd(REMOTE_DEPOSIT_DIR);

$arg_store_code = $argv[1]; 
$arg_database = $argv[2]; 
$script_type = $argv[3]; 

if($script_type === "COLD") {
    $filename = $client->get_last_file(EXPORT_DIR.LOCAL_COLD_FILENAME, COLD_FILENAME, STR_START_WITH);
    $content = file_get_contents(EXPORT_DIR.LOCAL_COLD_FILENAME);
} else if($script_type === "HOT_FLY") {
    $filename = $client->get_last_file(EXPORT_DIR.LOCAL_HOT_FILENAME, HOT_FLY_FILENAME, STR_START_WITH);
    $content = file_get_contents(EXPORT_DIR.LOCAL_HOT_FILENAME);
} else if($script_type === "HOT_FULL") {
    $filename = $client->get_last_file(EXPORT_DIR.LOCAL_HOT_FILENAME, HOT_FULL_FILENAME, STR_START_WITH);
    $content = file_get_contents(EXPORT_DIR.LOCAL_HOT_FILENAME);
} else {
    throw new \LogicException("Script type must be in HOT_FLY, HOT_FULL or COLD");
}

//no new file, exit program
if($filename === false) {
    exit();
}


$xml = new Reader($content);

if($script_type === "COLD") {
    $products = $xml->getProducts();
} else {
    $products = $xml->getHotProducts($arg_store_code);
}

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
        if($script_type === "COLD" && $product['status'] === 1) { //only if web true && cold script
            $sql_manager->insertArticle($product);
        }
    } else {
        if($script_type === "COLD") {
            $coldUpdate = !compareProductsCold($product, $sql_product);
            //update cold
            if($coldUpdate) { //only ondatachange
                $sql_manager->updateColdArticle($product);
            }
        } else {
            $hotUpdate = !compareProductHot($product, $sql_product);
            if($hotUpdate) { //only ondatachange
                $sql_manager->updateHotArticle($product["code_article"], $product);
            }
        }
    }
}

/** ARCHIVE HANDLER */
if($script_type === "COLD") {
    $client->rename($filename, "archive/$filename");
} else if ($script_type === "HOT_FULL") {
    $files = $client->ls(true);
    foreach($files as $file) {
        if(substr_compare($file, "artstock_fly", 0, strlen("artstock_fly")) === 0) {
            $client->rename($file, "archive/$file");
        }
    }
} else if ($script_type === "HOT_FLY") {
    $files = $client->ls(true);
    foreach($files as $file) {
        if(substr_compare($file, "artstock_fullday", 0, strlen("artstock_fullday")) === 0) {
            $client->rename($file, "archive/$file");
        }
    }
}

$client->handle_archive("archive", null, 189);




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





//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