<?php

namespace Boeki\PogestSwapScript\SQL;

use Simplon\Mysql\PDOConnector;
use Simplon\Mysql\Mysql;
use Simplon\Mysql\MysqlQueryIterator;



class Manager 
{

    public function __construct(string $host, string $user, string $password, string $database)
    {
        $this->dbConn = new \mysqli($host, $user, $password, $database);
        if ($this->dbConn->connect_errno) {
            echo "Échec lors de la connexion à MySQL : (" . $this->dbConn->connect_errno . ") " . $this->dbConn->connect_error;
        }
    }

    public function getProduct(string $code_article)
    {
        $sql = sprintf("SELECT * FROM progest_swap_product WHERE code_article = %s", $code_article);
        return $this->dbConn->query($sql)->fetch_array(MYSQLI_ASSOC);
    }

    public function updateHotArticle(string $code_article, array $hot_content)
    {
        $q = "update_hot='".(new \DateTime())->format('Y-m-d H:i:s')."'";
        $sql = sprintf(
            "UPDATE progest_swap_product SET prix='%s', quantite='%s', origine='%s', calibre='%s', categorie='%s'%s  WHERE code_article='%s';",
            $hot_content['prix'],
            $hot_content['quantite'],
            $hot_content['origine'],
            $hot_content['calibre'],
            $hot_content['categorie'],
            $q ? ",".$q : "",
            $code_article

        );

        $res = $this->dbConn->query($sql);

        return $res;
    }

    public function updateArticle(array $article)
    {
        $q = "update_cold='".(new \DateTime())->format('Y-m-d H:i:s')."'";

        $sql = sprintf(
            "UPDATE progest_swap_product  SET nom='%s', description='%s', description_courte='%s', marque='%s', unite='%s', increment='%s', libelles='%s', sku='%s', poids='%s', status=%s, classe_tva='%s'%s WHERE code_article='%s';",
            $article['nom'],
            $article['description'],
            $article['description_courte'],
            $article['marque'],
            $article['unite'],
            $article['increment'],
            $article['libelles'],
            $article['sku'],
            $article['poids'],
            $article['status'],
            $article['classe_tva'],
            $q ? ",".$q : "",
            $article['code_article']

        );

        $res = $this->dbConn->query($sql);
        return $res;
    }

    public function insertArticle(array $article)
    {
        $sql = sprintf(
            "INSERT INTO progest_swap_product (code_article, nom, description, description_courte, marque, unite, increment, libelles, sku, poids,status,classe_tva,disponibilite_stock,bio_arbo,progest_arbo,ligne_ajoutee_le,update_cold) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',%s,'%s',%s,'%s','%s',%s,%s);",
            $article["code_article"],
            addslashes($article["nom"]),
            addslashes($article["description"]),
            addslashes($article["description_courte"]),
            $article["marque"],
            $article["unite"],
            $article["increment"],
            addslashes($article["libelles"]),
            $article["sku"],
            $article["poids"],
            $article["status"] ? 1 : 0,
            $article["classe_tva"],
            $article["disponibilite_stock"] ? 1 : 0,
            $article["bio_arbo"],
            $article["progest_arbo"],
            "'".(new \DateTime())->format('Y-m-d H:i:s')."'",
            "'".(new \DateTime())->format('Y-m-d H:i:s')."'"
        );
        $res = $this->dbConn->query($sql);
        return $res;
    }
}