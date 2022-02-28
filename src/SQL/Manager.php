<?php

namespace Boeki\PogestSwapScript\SQL;

use Simplon\Mysql\PDOConnector;
use Simplon\Mysql\Mysql;
use Simplon\Mysql\MysqlQueryIterator;



class Manager {

    const PRICE_ATTRIBUTE_ID = 75;

    public function __construct(string $host, string $user, string $password, string $database)
    {
        $this->dbConn = new \mysqli($host, $user, $password, $database);
        if ($this->dbConn->connect_errno) {
            echo "Échec lors de la connexion à MySQL : (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
        }
    }

    public function getProduct(string $code_article)
    {
        $sql = sprintf("SELECT * FROM progest_swap_product WHERE code_article = %s", $code_article);
        return $this->dbConn->query($sql)->fetch_array(MYSQLI_ASSOC);
    }

    public function insertArticle(array $article, bool $updateCold, bool $updateHot)
    {
        $sql = sprintf(
            "INSERT INTO progest_swap_product VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %s, '%s', '%s', %s, %s, '%s', %s, %s, '%s', '%s', '%s', %s, %s, %s, %s, %s)",
            $article["code_article"],
            addslashes($article["nom"]),
            addslashes($article["description"]),
            addslashes($article["description_courte"]),
            $article["marque"],
            $article["unite"],
            $article["increment"],
            addslashes($article["libelles"]),
            null,
            null,
            $article["sku"],
            $article["poids"],
            $article["status"] ? 1 : 0,
            null,
            null,
            "null",
            "null",
            $article["classe_tva"],
            "null",
            $article["disponibilite_stock"] ? 1 : 0,
            "null",
            "null",
            "null",
            $updateCold ? "'".(new \DateTime())->format('Y-m-d H:i:s')."'" : "null",
            $updateHot ? "'".(new \DateTime())->format('Y-m-d H:i:s')."'" : "null",
            "null",
            "null",
            "null"
        );
        $res = $this->dbConn->query($sql);
        
        if(!$res) dump($sql);

        return $res;
    }
}