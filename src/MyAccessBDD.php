<?php
include_once("AccessBDD.php");

/**
 * Classe de construction des requêtes SQL
 * hérite de AccessBDD qui contient les requêtes de base
 * Pour ajouter une requête :
 * - créer la fonction qui crée une requête (prendre modèle sur les fonctions 
 *   existantes qui ne commencent pas par 'traitement')
 * - ajouter un 'case' dans un des switch des fonctions redéfinies 
 * - appeler la nouvelle fonction dans ce 'case'
 */
class MyAccessBDD extends AccessBDD {
	    
    /**
     * constructeur qui appelle celui de la classe mère
     */
    public function __construct(){
        try{
            parent::__construct();
        }catch(\Exception $e){
            throw $e;
        }
    }

    /**
     * demande de recherche
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return array|null tuples du résultat de la requête ou null si erreur
     * @override
     */	
    protected function traitementSelect(string $table, ?array $champs) : ?array{
        switch($table){  
            case "livre" :
                return $this->selectAllLivres();
            case "dvd" :
                return $this->selectAllDvd();
            case "revue" :
                return $this->selectAllRevues();
            case "exemplaire" :
                return $this->selectExemplairesRevue($champs);
            case "genre" :
            case "public" :
            case "rayon" :
            case "etat" :
                // select portant sur une table contenant juste id et libelle
                return $this->selectTableSimple($table);
            case "commandedocument" :
                return $this->selectCommandesDocument($champs);
            case "suivi" :
                return $this->selectTableSimple("suivi");
            case "abonnement" :
                return $this->selectAbonnementsRevue($champs);
            case "abonnementexpire" :
                return $this->selectAbonnementsExpirantBientot();
            case "utilisateur" :
                return $this->selectUtilisateur($champs);
            case "" :
                // return $this->uneFonction(parametres);
            default:
                // cas général
                return $this->selectTuplesOneTable($table, $champs);
        }	
    }

    /**
     * demande d'ajout (insert)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples ajoutés ou null si erreur
     * @override
     */	
    protected function traitementInsert(string $table, ?array $champs) : ?int{
        switch($table){
            case "livre" :
                return $this->insertLivre($champs);
            case "dvd" :
                return $this->insertDvd($champs);
            case "revue" :
                return $this->insertRevue($champs);
            case "commandedocument" :
                return $this->insertCommandeDocument($champs);
            case "abonnement" :
                return $this->insertAbonnement($champs);
            default:                    
                // cas général
                return $this->insertOneTupleOneTable($table, $champs);	
        }
    }
    
    /**
     * demande de modification (update)
     * @param string $table
     * @param string|null $id
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples modifiés ou null si erreur
     * @override
     */	
    protected function traitementUpdate(string $table, ?string $id, ?array $champs) : ?int{
        switch($table){
            case "livre" :
                return $this->updateLivre($id, $champs);
            case "dvd" :
                return $this->updateDvd($id, $champs);
            case "revue" :
                return $this->updateRevue($id, $champs);
            case "commandedocument" :
                return $this->updateSuiviCommande($id, $champs);
            case "abonnement" :
                return $this->deleteAbonnement($champs);
            case "exemplaire" :
                return $this->updateEtatExemplaire($id, $champs);
            default:                    
                // cas général
                return $this->updateOneTupleOneTable($table, $id, $champs);
        }	
    }  
    
    /**
     * demande de suppression (delete)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples supprimés ou null si erreur
     * @override
     */	
    protected function traitementDelete(string $table, ?array $champs) : ?int{
        switch($table){
            case "livre" :
                return $this->deleteLivre($champs);
            case "dvd" :
                return $this->deleteDvd($champs);
            case "revue" :
                return $this->deleteRevue($champs);
            case "commandedocument" :
                return $this->deleteCommandeDocument($champs);
            case "exemplaire" :
                return $this->deleteExemplaire($champs);
            default:                    
                // cas général
                return $this->deleteTuplesOneTable($table, $champs);	
        }
    }	    
        
    /**
     * récupère les tuples d'une seule table
     * @param string $table
     * @param array|null $champs
     * @return array|null 
     */
    private function selectTuplesOneTable(string $table, ?array $champs) : ?array{
        if(empty($champs)){
            // tous les tuples d'une table
            $requete = "select * from $table;";
            return $this->conn->queryBDD($requete);  
        }else{
            // tuples spécifiques d'une table
            $requete = "select * from $table where ";
            foreach ($champs as $key => $value){
                $requete .= "$key=:$key and ";
            }
            // (enlève le dernier and)
            $requete = substr($requete, 0, strlen($requete)-5);	          
            return $this->conn->queryBDD($requete, $champs);
        }
    }	

    /**
     * demande d'ajout (insert) d'un tuple dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples ajoutés (0 ou 1) ou null si erreur
     */	
    private function insertOneTupleOneTable(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        // construction de la requête
        $requete = "insert into $table (";
        foreach ($champs as $key => $value){
            $requete .= "$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ") values (";
        foreach ($champs as $key => $value){
            $requete .= ":$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ");";
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * demande de modification (update) d'un tuple dans une table
     * @param string $table
     * @param string\null $id
     * @param array|null $champs 
     * @return int|null nombre de tuples modifiés (0 ou 1) ou null si erreur
     */	
    private function updateOneTupleOneTable(string $table, ?string $id, ?array $champs) : ?int {
        if(empty($champs)){
            return null;
        }
        if(is_null($id)){
            return null;
        }
        // construction de la requête
        $requete = "update $table set ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);				
        $champs["id"] = $id;
        $requete .= " where id=:id;";		
        return $this->conn->updateBDD($requete, $champs);	        
    }
    
    /**
     * demande de suppression (delete) d'un ou plusieurs tuples dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples supprimés ou null si erreur
     */
    private function deleteTuplesOneTable(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        // construction de la requête
        $requete = "delete from $table where ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key and ";
        }
        // (enlève le dernier and)
        $requete = substr($requete, 0, strlen($requete)-5);   
        return $this->conn->updateBDD($requete, $champs);	        
    }
 
    /**
     * récupère toutes les lignes d'une table simple (qui contient juste id et libelle)
     * @param string $table
     * @return array|null
     */
    private function selectTableSimple(string $table) : ?array{
        $requete = "select * from $table order by libelle;";		
        return $this->conn->queryBDD($requete);	    
    }
    
    /**
     * récupère toutes les lignes de la table Livre et les tables associées
     * @return array|null
     */
    private function selectAllLivres() : ?array{
        $requete = "Select l.id, l.ISBN, l.auteur, d.titre, d.image, l.collection, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from livre l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";		
        return $this->conn->queryBDD($requete);
    }	

    /**
     * récupère toutes les lignes de la table DVD et les tables associées
     * @return array|null
     */
    private function selectAllDvd() : ?array{
        $requete = "Select l.id, l.duree, l.realisateur, d.titre, d.image, l.synopsis, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from dvd l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";	
        return $this->conn->queryBDD($requete);
    }	

    /**
     * récupère toutes les lignes de la table Revue et les tables associées
     * @return array|null
     */
    private function selectAllRevues() : ?array{
        $requete = "Select l.id, l.periodicite, d.titre, d.image, l.delaiMiseADispo, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from revue l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }	

    /**
     * récupère tous les exemplaires d'une revue
     * @param array|null $champs 
     * @return array|null
     */
    private function selectExemplairesRevue(?array $champs) : ?array{
        if(empty($champs)){
            return null;
        }
        if(!array_key_exists('id', $champs)){
            return null;
        }
        $champNecessaire['id'] = $champs['id'];
        $requete = "Select e.id, e.numero, e.dateAchat, e.photo, e.idEtat, et.libelle ";
        $requete .= "from exemplaire e join document d on e.id=d.id ";
        $requete .= "join etat et on e.idEtat=et.id ";
        $requete .= "where e.id = :id ";
        $requete .= "order by e.dateAchat DESC";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }		    
    
    /**
     * Insère un livre dans les 3 tables : document, livre_dvd, livre
     * @param array|null $champs
     * @return int|null
     */
    private function insertLivre(?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        try{
            // on démarre la transaction
            $this->conn->beginTransaction();
            
            // insertion dans la table document
            $champsDocument = [
                "id"       => $champs["Id"],
                "titre"    => $champs["Titre"],
                "image"    => $champs["Image"],
                "idGenre"  => $champs["IdGenre"],
                "idPublic" => $champs["IdPublic"],
                "idRayon"  => $champs["IdRayon"]
            ];
            $retour = $this->insertOneTupleOneTable("document", $champsDocument);
            if(is_null($retour)){
                $this->conn->rollBack();
                return null;
            }
            
            // insertion dans la table livre_dvd
            $champsLivreDvd = [
                "id" => $champs["Id"]
            ];
            $retour = $this->insertOneTupleOneTable("livres_dvd", $champsLivreDvd);
            if(is_null($retour)){
                $this->conn->rollBack();
                return null;
            }
            
            // insertion dans la table livre
            $champsLivre = [
                "id"         => $champs["Id"],
                "ISBN"       => $champs["Isbn"],
                "auteur"     => $champs["Auteur"],
                "collection" => $champs["Collection"]
            ];
            $retour = $this->insertOneTupleOneTable("livre", $champsLivre);
            if(is_null($retour)){
                $this->conn->rollBack();
                return null;
            }
            
            // les 3 insertions ont bien été faites, on valide
            $this->conn->commit();
            return 1;
            
        }catch(Exception $e){
            $this->conn->rollBack();
            return null;
        }
    }
    
    /**
     * Modifie un livre dans les tables document et livre
     * @param string|null $id
     * @param array|null $champs
     * @return int|null
     */
    private function updateLivre(?string $id, ?array $champs) : ?int{
        if(empty($champs) || is_null($id)){
            return null;
        }
        try{
            // on démarre la transaction
            $this->conn->beginTransaction();
            
            // modification dans la table document
            $champsDocument = [
                "titre"    => $champs["Titre"],
                "image"    => $champs["Image"],
                "idGenre"  => $champs["IdGenre"],
                "idPublic" => $champs["IdPublic"],
                "idRayon"  => $champs["IdRayon"]
            ];
            $retour = $this->updateOneTupleOneTable("document", $id, $champsDocument);
            if(is_null($retour)){
                $this->conn->rollBack();
                return null;
            }
            
            // modification dans la table livre
            $champsLivre = [
                "ISBN"       => $champs["Isbn"],
                "auteur"     => $champs["Auteur"],
                "collection" => $champs["Collection"]
            ];
            $retour = $this->updateOneTupleOneTable("livre", $id, $champsLivre);
            if(is_null($retour)){
                $this->conn->rollBack();
                return null;
            }
            
            // les 2 modifications ont bien été faites, on valide
            $this->conn->commit();
            return 1;
            
        }catch(Exception $e){
            $this->conn->rollBack();
            return null;
        }
    }
    
    /**
     * Supprime un livre dans les tables livre, livre_dvd et document
     * @param array|null $champs
     * @return int|null
     */
    private function deleteLivre(?array $champs) : ?int{
        if(empty($champs) || !array_key_exists('id', $champs)){
            return null;
        }
        $id = $champs["id"];
        
        // on vérifie qu'il n'y a pas d'exemplaires rattachés
        $requeteExemplaire = "select count(*) as nb from exemplaire where id=:id;";
        $resultat = $this->conn->queryBDD($requeteExemplaire, ["id" => $id]);
        if(is_null($resultat) || $resultat[0]["nb"] > 0){
            return null;
        }
        
        // on vérifie qu'il n'y a pas de commandes rattachées
        $requeteCommande = "select count(*) as nb from commandedocument where idLivreDvd=:id;";
        $resultat = $this->conn->queryBDD($requeteCommande, ["id" => $id]);
        if(is_null($resultat) || $resultat[0]["nb"] > 0){
            return null;
        }
        
        try{
            // on démarre la transaction
            $this->conn->beginTransaction();
            
            // suppression dans livre
            $retour = $this->deleteTuplesOneTable("livre", ["id" => $id]);
            if(is_null($retour)){
                $this->conn->rollBack();
                return null;
            }
            
            // suppression dans livre_dvd
            $retour = $this->deleteTuplesOneTable("livres_dvd", ["id" => $id]);
            if(is_null($retour)){
                $this->conn->rollBack();
                return null;
            }
            
            // suppression dans document
            $retour = $this->deleteTuplesOneTable("document", ["id" => $id]);
            if(is_null($retour)){
                $this->conn->rollBack();
                return null;
            }
            
            // les 3 suppressions ont bien été faites, on valide
            $this->conn->commit();
            return 1;
            
        }catch(Exception $e){
            $this->conn->rollBack();
            return null;
        }
    }
    
    /**
     * Insère un dvd dans les 3 tables : document, livre_dvd, dvd
     * @param array|null $champs
     * @return int|null
     */
    private function insertDvd(?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        try{
            // on démarre la transaction
            $this->conn->beginTransaction();
            
            // insertion dans la table document
            $champsDocument = [
                "id"       => $champs["Id"],
                "titre"    => $champs["Titre"],
                "image"    => $champs["Image"],
                "idGenre"  => $champs["IdGenre"],
                "idPublic" => $champs["IdPublic"],
                "idRayon"  => $champs["IdRayon"]
            ];
            $retour = $this->insertOneTupleOneTable("document", $champsDocument);
            if(is_null($retour)){
                $this->conn->rollBack();
                return null;
            }
            
            // insertion dans la table livre_dvd
            $champsLivreDvd = ["id" => $champs["Id"]];
            $retour = $this->insertOneTupleOneTable("livres_dvd", $champsLivreDvd);
            if(is_null($retour)){
                $this->conn->rollBack();
                return null;
            }
            
            // insertion dans la table dvd
            $champsDvd = [
                "id"          => $champs["Id"],
                "duree"       => $champs["Duree"],
                "realisateur" => $champs["Realisateur"],
                "synopsis"    => $champs["Synopsis"]
            ];
            $retour = $this->insertOneTupleOneTable("dvd", $champsDvd);
            if(is_null($retour)){
                $this->conn->rollBack();
                return null;
            }
            
            // les 3 insertions ont bien été faites, on valide
            $this->conn->commit();
            return 1;
            
        }catch(Exception $e){
            $this->conn->rollBack();
            return null;
        }
    }
    
    /**
     * Modifie un dvd dans les tables document et dvd
     * @param string|null $id
     * @param array|null $champs
     * @return int|null
     */
    private function updateDvd(?string $id, ?array $champs) : ?int{
        if(empty($champs) || is_null($id)){
            return null;
        }
        try{
            // on démarre la transaction
            $this->conn->beginTransaction();
            
            // modification dans la table document
            $champsDocument = [
                "titre"    => $champs["Titre"],
                "image"    => $champs["Image"],
                "idGenre"  => $champs["IdGenre"],
                "idPublic" => $champs["IdPublic"],
                "idRayon"  => $champs["IdRayon"]
            ];
            $retour = $this->updateOneTupleOneTable("document", $id, $champsDocument);
            if(is_null($retour)){
                $this->conn->rollBack();
                return null;
            }
            
            // modification dans la table dvd
            $champsDvd = [
                "duree"       => $champs["Duree"],
                "realisateur" => $champs["Realisateur"],
                "synopsis"    => $champs["Synopsis"]
            ];
            $retour = $this->updateOneTupleOneTable("dvd", $id, $champsDvd);
            if(is_null($retour)){
                $this->conn->rollBack();
                return null;
            }
            
            // les 2 modifications ont bien été faites, on valide
            $this->conn->commit();
            return 1;
            
        }catch(Exception $e){
            $this->conn->rollBack();
            return null;
        }
    }
    
    /**
     * Supprime un dvd dans les tables dvd, livre_dvd et document
     * @param array|null $champs
     * @return int|null
     */
    private function deleteDvd(?array $champs) : ?int{
        if(empty($champs) || !array_key_exists('id', $champs)){
            return null;
        }
        $id = $champs["id"];
        
        // on vérifie qu'il n'y a pas d'exemplaires rattachés
        $requeteExemplaire = "select count(*) as nb from exemplaire where id=:id;";
        $resultat = $this->conn->queryBDD($requeteExemplaire, ["id" => $id]);
        if(is_null($resultat) || $resultat[0]["nb"] > 0){
            return null;
        }
        
        // on vérifie qu'il n'y a pas de commandes rattachées
        $requeteCommande = "select count(*) as nb from commandedocument where idLivreDvd=:id;";
        $resultat = $this->conn->queryBDD($requeteCommande, ["id" => $id]);
        if(is_null($resultat) || $resultat[0]["nb"] > 0){
            return null;
        }
        
        try{
            // on démarre la transaction
            $this->conn->beginTransaction();
            
            // suppression dans dvd
            $retour = $this->deleteTuplesOneTable("dvd", ["id" => $id]);
            if(is_null($retour)){ $this->conn->rollBack(); return null; }
            
            // suppression dans livre_dvd
            $retour = $this->deleteTuplesOneTable("livres_dvd", ["id" => $id]);
            if(is_null($retour)){ $this->conn->rollBack(); return null; }
            
            // suppression dans document
            $retour = $this->deleteTuplesOneTable("document", ["id" => $id]);
            if(is_null($retour)){ $this->conn->rollBack(); return null; }
            
            // les 3 suppressions ont bien été faites, on valide
            $this->conn->commit();
            return 1;
            
        }catch(Exception $e){
            $this->conn->rollBack();
            return null;
        }
    }
    
    /**
     * Insère une revue dans les 2 tables : document, revue
     * @param array|null $champs
     * @return int|null
     */
    private function insertRevue(?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        try{
            // on démarre la transaction
            $this->conn->beginTransaction();
            
            // insertion dans la table document
            $champsDocument = [
                "id"       => $champs["Id"],
                "titre"    => $champs["Titre"],
                "image"    => $champs["Image"],
                "idGenre"  => $champs["IdGenre"],
                "idPublic" => $champs["IdPublic"],
                "idRayon"  => $champs["IdRayon"]
            ];
            $retour = $this->insertOneTupleOneTable("document", $champsDocument);
            if(is_null($retour)){
                $this->conn->rollBack();
                return null;
            }
            
            // insertion dans la table revue
            $champsRevue = [
                "id"              => $champs["Id"],
                "periodicite"     => $champs["Periodicite"],
                "delaiMiseADispo" => $champs["DelaiMiseADispo"]
            ];
            $retour = $this->insertOneTupleOneTable("revue", $champsRevue);
            if(is_null($retour)){
                $this->conn->rollBack();
                return null;
            }
            
            // les 2 insertions ont bien été faites, on valide
            $this->conn->commit();
            return 1;
            
        }catch(Exception $e){
            $this->conn->rollBack();
            return null;
        }
    }
    
    /**
     * Modifie une revue dans les tables document et revue
     * @param string|null $id
     * @param array|null $champs
     * @return int|null
     */
    private function updateRevue(?string $id, ?array $champs) : ?int{
        if(empty($champs) || is_null($id)){
            return null;
        }
        try{
            // on démarre la transaction
            $this->conn->beginTransaction();
            
            // modification dans la table document
            $champsDocument = [
                "titre"    => $champs["Titre"],
                "image"    => $champs["Image"],
                "idGenre"  => $champs["IdGenre"],
                "idPublic" => $champs["IdPublic"],
                "idRayon"  => $champs["IdRayon"]
            ];
            $retour = $this->updateOneTupleOneTable("document", $id, $champsDocument);
            if(is_null($retour)){
                $this->conn->rollBack();
                return null;
            }
            
            // modification dans la table revue
            $champsRevue = [
                "periodicite"     => $champs["Periodicite"],
                "delaiMiseADispo" => $champs["DelaiMiseADispo"]
            ];
            $retour = $this->updateOneTupleOneTable("revue", $id, $champsRevue);
            if(is_null($retour)){
                $this->conn->rollBack();
                return null;
            }
            
            // les 2 modifications ont bien été faites, on valide
            $this->conn->commit();
            return 1;
            
        }catch(Exception $e){
            $this->conn->rollBack();
            return null;
        }
    }
    
    /**
     * Supprime une revue dans les tables revue et document
     * @param array|null $champs
     * @return int|null
     */
    private function deleteRevue(?array $champs) : ?int{
        if(empty($champs) || !array_key_exists('id', $champs)){
            return null;
        }
        $id = $champs["id"];
        
        // on vérifie qu'il n'y a pas d'exemplaires rattachés
        $requeteExemplaire = "select count(*) as nb from exemplaire where id=:id;";
        $resultat = $this->conn->queryBDD($requeteExemplaire, ["id" => $id]);
        if(is_null($resultat) || $resultat[0]["nb"] > 0){
            return null;
        }
        
        // on vérifie qu'il n'y a pas d'abonnements rattachés
        $requeteCommande = "select count(*) as nb from abonnement where idRevue=:id;";
        $resultat = $this->conn->queryBDD($requeteCommande, ["id" => $id]);
        if(is_null($resultat) || $resultat[0]["nb"] > 0){
            return null;
        }
        
        try{
            // on démarre la transaction
            $this->conn->beginTransaction();
            
            // suppression dans revue
            $retour = $this->deleteTuplesOneTable("revue", ["id" => $id]);
            if(is_null($retour)){ $this->conn->rollBack(); return null; }
            
            // suppression dans document
            $retour = $this->deleteTuplesOneTable("document", ["id" => $id]);
            if(is_null($retour)){ $this->conn->rollBack(); return null; }
            
            // les 2 suppressions ont bien été faites, on valide
            $this->conn->commit();
            return 1;
            
        }catch(Exception $e){
            $this->conn->rollBack();
            return null;
        }
    }
    
    /**
    * récupère toutes les commandes d'un document (livre ou dvd)
    * @param array|null $champs
    * @return array|null
    */
    private function selectCommandesDocument(?array $champs) : ?array{
        if(empty($champs)){
            return null;
        }
        if(!array_key_exists('id', $champs)){
            return null;
        }
        $champNecessaire['id'] = $champs['id'];
        $requete = "select cd.id, cd.nbExemplaire, cd.idLivreDvd, cd.idSuivi, s.libelle as suivi, ";
        $requete .= "c.dateCommande, c.montant ";
        $requete .= "from commandedocument cd ";
        $requete .= "join commande c on cd.id = c.id ";
        $requete .= "join suivi s on cd.idSuivi = s.id ";
        $requete .= "where cd.idLivreDvd = :id ";
        $requete .= "order by c.dateCommande DESC";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }
    
    /**
    * Insère une commande dans les tables commande et commandedocument
    */
    private function insertCommandeDocument(?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        try{
            $this->conn->beginTransaction();

            $champsCommande = [
                "id"            => $champs["Id"],
                "dateCommande"  => $champs["DateCommande"],
                "montant"       => $champs["Montant"]
            ];
            $retour = $this->insertOneTupleOneTable("commande", $champsCommande);
            if(is_null($retour)){
                $this->conn->rollBack();
                return null;
            }

            $champsCommandeDocument = [
                "id"           => $champs["Id"],
                "nbExemplaire" => $champs["NbExemplaire"],
                "idLivreDvd"   => $champs["IdLivreDvd"],
                "idSuivi"      => $champs["IdSuivi"]
            ];
            $retour = $this->insertOneTupleOneTable("commandedocument", $champsCommandeDocument);
            if(is_null($retour)){
                $this->conn->rollBack();
                return null;
            }

            $this->conn->commit();
            return 1;

        }catch(Exception $e){
            $this->conn->rollBack();
            return null;
        }
    }

    /**
     * Modifie le suivi d'une commande
     */
    private function updateSuiviCommande(?string $id, ?array $champs) : ?int{
        if(empty($champs) || is_null($id)){
            return null;
        }
        $champsUpdate = [
            "idSuivi" => $champs["IdSuivi"]
        ];
        return $this->updateOneTupleOneTable("commandedocument", $id, $champsUpdate);
    }

    /**
     * Supprime une commande uniquement si elle n'est pas livrée
     */
    private function deleteCommandeDocument(?array $champs) : ?int{
        if(empty($champs) || !array_key_exists('id', $champs)){
            return null;
        }
        $id = $champs["id"];

        // on vérifie que la commande n'est pas livrée ou réglée
        $requete = "select idSuivi from commandedocument where id=:id;";
        $resultat = $this->conn->queryBDD($requete, ["id" => $id]);
        if(is_null($resultat) || empty($resultat)){
            return null;
        }
        if($resultat[0]["idSuivi"] == "00003" || $resultat[0]["idSuivi"] == "00004"){
            return null;
        }

        try{
            $this->conn->beginTransaction();

            $retour = $this->deleteTuplesOneTable("commandedocument", ["id" => $id]);
            if(is_null($retour)){ $this->conn->rollBack(); return null; }

            $retour = $this->deleteTuplesOneTable("commande", ["id" => $id]);
            if(is_null($retour)){ $this->conn->rollBack(); return null; }

            $this->conn->commit();
            return 1;

        }catch(Exception $e){
            $this->conn->rollBack();
            return null;
        }
    }
    
    /**
    * Récupère tous les abonnements d'une revue
    */
    private function selectAbonnementsRevue(?array $champs) : ?array{
        if(empty($champs) || !array_key_exists('id', $champs)){
            return null;
        }
        $champNecessaire['id'] = $champs['id'];
        $requete = "select a.id, a.dateFinAbonnement, a.idRevue, ";
        $requete .= "c.dateCommande, c.montant, d.titre ";
        $requete .= "from abonnement a ";
        $requete .= "join commande c on a.id = c.id ";
        $requete .= "join revue r on a.idRevue = r.id ";
        $requete .= "join document d on r.id = d.id ";
        $requete .= "where a.idRevue = :id ";
        $requete .= "order by c.dateCommande DESC";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * Insère un abonnement dans les tables commande et abonnement
     */
    private function insertAbonnement(?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        try{
            $this->conn->beginTransaction();

            $champsCommande = [
                "id"           => $champs["Id"],
                "dateCommande" => $champs["DateCommande"],
                "montant"      => $champs["Montant"]
            ];
            $retour = $this->insertOneTupleOneTable("commande", $champsCommande);
            if(is_null($retour)){
                $this->conn->rollBack();
                return null;
            }

            $champsAbonnement = [
                "id"                 => $champs["Id"],
                "dateFinAbonnement"  => $champs["DateFinAbonnement"],
                "idRevue"            => $champs["IdRevue"]
            ];
            $retour = $this->insertOneTupleOneTable("abonnement", $champsAbonnement);
            if(is_null($retour)){
                $this->conn->rollBack();
                return null;
            }

            $this->conn->commit();
            return 1;

        }catch(Exception $e){
            $this->conn->rollBack();
            return null;
        }
    }

    /**
     * Supprime un abonnement dans les tables abonnement et commande
     */
    private function deleteAbonnement(?array $champs) : ?int{
        if(empty($champs) || !array_key_exists('id', $champs)){
            return null;
        }
        $id = $champs["id"];
        try{
            $this->conn->beginTransaction();

            $retour = $this->deleteTuplesOneTable("abonnement", ["id" => $id]);
            if(is_null($retour)){ $this->conn->rollBack(); return null; }

            $retour = $this->deleteTuplesOneTable("commande", ["id" => $id]);
            if(is_null($retour)){ $this->conn->rollBack(); return null; }

            $this->conn->commit();
            return 1;

        }catch(Exception $e){
            $this->conn->rollBack();
            return null;
        }
    }
    
    /**
    * Récupère les revues dont l'abonnement se termine dans moins de 30 jours
    */
    private function selectAbonnementsExpirantBientot() : ?array{
        $requete = "select r.id, d.titre, a.dateFinAbonnement ";
        $requete .= "from abonnement a ";
        $requete .= "join revue r on a.idRevue = r.id ";
        $requete .= "join document d on r.id = d.id ";
        $requete .= "where a.dateFinAbonnement between curdate() and date_add(curdate(), interval 30 day) ";
        $requete .= "order by a.dateFinAbonnement ASC";
        return $this->conn->queryBDD($requete);
    }
    
    /**
    * Modifie l'état d'un exemplaire
    */
    private function updateEtatExemplaire(?string $id, ?array $champs) : ?int{
        if(empty($champs) || is_null($id)){
            return null;
        }
        // la clé primaire est composée de id ET numero
        $requete = "update exemplaire set idEtat=:idEtat where id=:id and numero=:numero;";
        $params = [
            "idEtat"  => $champs["IdEtat"],
            "id"      => $id,
            "numero"  => $champs["Numero"]
        ];
        return $this->conn->updateBDD($requete, $params);
    }

    /**
     * Supprime un exemplaire
     */
    private function deleteExemplaire(?array $champs) : ?int{
        if(empty($champs) || !array_key_exists('id', $champs)){
            return null;
        }
        $id = $champs["id"];
        return $this->deleteTuplesOneTable("exemplaire", ["id" => $id, "numero" => $champs["Numero"]]);
    }
    
    /**
    * Récupère un utilisateur par son login et mot de passe
    */
    private function selectUtilisateur(?array $champs) : ?array{
        if(empty($champs)){
            return null;
        }
        $requete = "select u.login, u.password, u.idService, s.libelle ";
        $requete .= "from utilisateur u ";
        $requete .= "join service s on u.idService = s.id ";
        $requete .= "where u.login = :login and u.password = :password;";
        return $this->conn->queryBDD($requete, $champs);
    }
}
