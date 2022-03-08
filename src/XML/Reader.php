<?php

namespace Boeki\PogestSwapScript\XML;

use \SimpleXMLIterator;


class Reader {

    private $xmlIterator;

    public function __construct(string $content)
    {
        $this->xmlIterator = new \SimpleXMLIterator($content);
        $this->xmlIterator->rewind();
    }

    public function applyHotContent(array $products, string $curr_store_code): array
    {
        while($this->xmlIterator->valid()) {
            $xmlProduct = $this->xmlIterator->current();

            $sku = $xmlProduct->attributes()->article->__toString();
            $pti = $xmlProduct->children()->tarifVente->prixTTC->__toString();
            $stock_qty = $xmlProduct->children()->reappro->stockDispo->__toString();
            $store_code = $xmlProduct->attributes()->site->__toString();


            $origine = $xmlProduct->children()->producteur;
            $origine = $origine ? $origine->__toString() : null;

            $categorie = $xmlProduct->children()->categorie;
            $categorie = $categorie ? $categorie->__toString() : null;
            
            $calibre = $xmlProduct->attributes()->calibre;
            $calibre = $calibre ? $calibre->__toString() : null;
            

            if($store_code === $curr_store_code) {
                $products[$sku]["hot_content"] = array(
                    "pti" => $pti,
                    "stock_qty" => $stock_qty,
                    "store_code" => $store_code,
                    "origine" => $origine,
                    "categorie" => $categorie,
                    "calibre" => $calibre
                );
            }
    
            $this->xmlIterator->next();
        }

        return $products;
    }

    public function getHotProducts(string $curr_store_code): array
    {
        $products = [];

        while($this->xmlIterator->valid()) {
            $xmlProduct = $this->xmlIterator->current();

            $sku = $xmlProduct->attributes()->article->__toString();
            $pti = $xmlProduct->children()->tarifVente->prixTTC->__toString();
            $stock_qty = $xmlProduct->children()->reappro->stockDispo->__toString();
            $store_code = $xmlProduct->attributes()->site->__toString();

            $origine = $xmlProduct->children()->pilotage->producteur;
            $origine = $origine ? $origine->__toString() : null;

            $categorie = $xmlProduct->children()->pilotage->categorie;
            $categorie = $categorie ? $categorie->__toString() : null;
            
            $calibre = $xmlProduct->children()->pilotage->calibre;
            $calibre = $calibre ? $calibre->__toString() : null;

            if($store_code === $curr_store_code) {
                $products[$sku] = array(
                    "code_article" => $sku,
                    "quantite" => $stock_qty,
                    "prix" => $pti,
                    "store_code" => $store_code,
                    "origine" => $origine,
                    "categorie" => $categorie,
                    "calibre" => $calibre
                );
            }
    
            $this->xmlIterator->next();
        }

        return $products;
    }

    public function getProducts(): array
    {
        $products = [];

        while($this->xmlIterator->valid()) {
            $xmlProduct = $this->xmlIterator->current();

            $code_article = $xmlProduct->attributes()->uuid->__toString();

            $nom = $xmlProduct->children()->DG->fiche->libelle;
            $nom = $nom ? $nom->__toString() : null;

            $description = $this->determineWebDescription($xmlProduct->children()->DG->descriptions);

            $description_courte = $xmlProduct->children()->DG->fiche->libCaisse;
            $description_courte = $description_courte ? $description_courte->__toString() : null;

            $marque_code = $xmlProduct->children()->DG->fiche->marque->attributes()->code;
            $marque = $xmlProduct->children()->DG->fiche->marque;

            $marque = $marque ? $marque->__toString() : null;
            $marque_code = $marque_code ? $marque_code->__toString() : null;

            if($marque_code !== "0000") {
                //conversion en camelcase avec conservation des espaces pour coller
                //avec le format des marques du magento de bio
                $marque = ucwords(strtolower($marque), " \t\r\n\f\v'");
            } else {
                $marque = null;
            }

            $unite = $this->determineUnite($xmlProduct->children()->DG->fiche->objet);
            
            $increment = $this->determineIncrement($xmlProduct->children()->DG->fiche->objet);

            $libelles = $this->determineLibelles($xmlProduct->children()->DG->descriptions);

            $sku = $this->determineSku($xmlProduct->children()->DG->GTIN->code);

            $poids = $xmlProduct->children()->DG->caracteristiques->poidsNet;
            $poids = $poids ? $poids->__toString() : null;

            $status = $xmlProduct->children()->DC->centrale->pilotagePV->eCommerce;
            $status = $status ? $status->__toString() : null;
            
            $classe_tva = $this->determineTaxe($xmlProduct->children()->DG->taxes);
            $disponibilite_stock = $this->determineDisponibiliteStock($xmlProduct->children()->DG->fiche->objet);

            $products[$code_article] = array(
                "code_article" => $code_article,
                "nom" => $nom,
                "description" => $description,
                "description_courte" => $description_courte,
                "marque" => $marque,
                "unite" => $unite,
                "increment" => $increment,
                "libelles" => $libelles,
                "sku" => $sku,
                "poids" => $poids,
                "status" => boolVal($status) ? 1 : 0,
                "classe_tva" => $classe_tva,
                "disponibilite_stock" => $disponibilite_stock
            );
    
            $this->xmlIterator->next();
        }

        return $products;
    }

    private function determineDisponibiliteStock($objet): ?string
    {
        foreach($objet->caracteristique as $caracteristique) {
            if($caracteristique->attributes()->code->__toString() === "web_dispo")
                return $caracteristique->__toString();
        }
        return null;
    }

    private function determineTaxe($taxes): ?string
    {
        foreach($taxes->taxe as $taxe) {
            if($taxe->attributes()->type->__toString() === "TVA")
                return $taxe->attributes()->taux->__toString();
        }
        return null;
    }

    private function determineSku($code): ?string
    {
        if($code) {
            if($code->attributes()->type->__toString() && $code->attributes()->principal && $code->attributes()->principal->__toString()) {
                return $code->attributes()->value->__toString();
            }
        }
        return null;
    }

    private function determineLibelles($descriptions): ?string
    {
        foreach($descriptions->description as $description) {
            if($description->type->__toString() === "LBQ")
                return $description->description->__toString();
        }
        return null;
    }

    private function determineIncrement($objet): ?string
    {
        foreach($objet->caracteristique as $caracteristique) {
            if($caracteristique->attributes()->code->__toString() === "web_increm")
                return $caracteristique->__toString();
        }
        return null;
    }

    private function determineUnite($objet): ?string
    {
        foreach($objet->caracteristique as $caracteristique) {
            if($caracteristique->attributes()->code->__toString() === "poids_tare")
                return $caracteristique->attributes()->unite->__toString();
        }
        return null;
    }

    private function determineWebDescription($descriptions): ?string
    {
        foreach($descriptions->description as $description) {
            if($description->type->__toString() === "WEB")
                return $description->description->__toString();
        }
        return null;
    }
}