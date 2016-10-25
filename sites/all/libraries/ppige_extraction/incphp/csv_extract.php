<?php

function exporterCSV($filtre, $dateDebut, $dateFin) {
  $where = '';
  if($dateDebut) { $where .= ($where != '' ? " AND " : "")."TO_DATE(TO_CHAR(projet_extraction.date_demande,'DD/MM/YYYY'),'DD/MM/YYYY')>=TO_DATE('".$dateDebut."','DD/MM/YYYY')"; }
  if($dateFin) { $where .= ($where != '' ? " AND " : "")."TO_DATE(TO_CHAR(projet_extraction.date_demande,'DD/MM/YYYY'),'DD/MM/YYYY')<=TO_DATE('".$dateFin."','DD/MM/YYYY')"; }
  switch($filtre) {
  case FILTRE_TOUTES_DEMANDES:
    if($where != '') { $where .= " AND "; }
    $where .= " etat_demande LIKE '%'";
    break;
  case FILTRE_ATTENTE_VALIDATION:
    if($where != '') { $where .= " AND "; }
    $where .= " etat_demande LIKE '".ATTENTE_VALIDATION."'";
    break;
  case FILTRE_ANNULEE:
    if($where != '') { $where .= " AND "; }
    $where .= " etat_demande LIKE '".ANNULEE."'";
    break;
  case FILTRE_ATTENTE_TRAITEMENT:
    if($where != '') { $where .= " AND "; }
    $where .= " etat_demande LIKE '".ATTENTE_TRAITEMENT."'";
    break;
  case FILTRE_TRAITEMENT_EN_COURS:
    if($where != '') { $where .= " AND "; }
    $where .= " etat_demande LIKE '".TRAITEMENT_EN_COURS."'";
    break;
  case FILTRE_TERMINEE:
    if($where != '') { $where .= " AND "; }
    $where .= " etat_demande LIKE '".TERMINEE."'";
    break;
  case FILTRE_EN_ERREUR:
    if($where != '') { $where .= " AND "; }
    $where .= " etat_demande LIKE '".EN_ERREUR."'";
    break;
  case FILTRE_WWW:
    if($where != '') { $where .= " AND "; }
    $where .= " etat_demande LIKE '".TERMINEE."' AND taille_fichier<=".TAILLE_LIMITE;
    break;
  case FILTRE_DVD:
    if($where != '') { $where .= " AND "; }
    $where .= " etat_demande LIKE '".TERMINEE."' AND taille_fichier>".TAILLE_LIMITE;
    break;
  case FILTRE_DEMANDE_DVD:
    if($where != '') { $where .= " AND "; }
    $where .= " etat_demande LIKE '".TERMINEE."' AND demande_dvd=TRUE";
    break;
  case FILTRE_DEMANDE_DVD_NON_TRAITEE:
    if($where != '') { $where .= " AND "; }
    $where .= " etat_demande LIKE '".TERMINEE."' AND demande_dvd=TRUE AND demande_dvd_validee IS NULL";
    break;      
  case FILTRE_DEMANDE_DVD_ACCEPTEE:
    if($where != '') { $where .= " AND "; }
    $where .= " etat_demande LIKE '".TERMINEE."' AND demande_dvd=TRUE AND demande_dvd_validee=TRUE";
    break;
  case FILTRE_DEMANDE_DVD_REFUSEE:
    if($where != '') { $where .= " AND "; }
    $where .= " etat_demande LIKE '".TERMINEE."' AND demande_dvd=TRUE AND demande_dvd_validee=FALSE";
    break;
  case FILTRE_DVD_ENVOYE:
    if($where != '') { $where .= " AND "; }
    $where .= " etat_demande LIKE '".TERMINEE."' AND demande_dvd=TRUE AND demande_dvd_validee=TRUE AND dvd_envoye=TRUE";
    break;
  case FILTRE_DVD_PAS_ENVOYE:
    if($where != '') { $where .= " AND "; }
    $where .= " etat_demande LIKE '".TERMINEE."' AND demande_dvd=TRUE AND demande_dvd_validee=TRUE AND (dvd_envoye IS NULL OR dvd_envoye=FALSE)";
    break;
  case FILTRE_MODE_MANUEL:
    $where .= " AND pe.mode = 'manuel'";
    break;
  case FILTRE_MODE_DALLE:
    $where .= " AND pe.mode like '%dalle%'";
    break;
  case FILTRE_EMPRISE_HORS_TC:
    $where .= " AND pe.etat_demande = '".EMPRISE_HORS_TC."'";
    break;
  case FILTRE_ERREUR_ORGANISME:
    $where .= " AND pe.etat_demande = '".ERREUR_ORGANISME."'";
    break;
  case FILTRE_MANUEL_TERMINE:
    $where .= " AND pe.etat_demande LIKE '".TERMINEE."' AND pe.mode = 'manuel'";
    break;
  case FILTRE_DALLE_TERMINE:
    $where .= " AND pe.etat_demande LIKE '".TERMINEE."' AND pe.mode like '%dalle%'";
    break;
    
  }
  if($where != '') { $where = "WHERE $where"; }

  $ini = parse_ini_file("config/config.ini");
  $name = randomString(8);

  // Création du fichier CSV
  $csvPath = formatPath($ini['tmpPath'], $name.".csv");
	if(!($h = fopen($csvPath, 'w'))) { return array(true, "Création du fichier $csvPath impossible."); }
  
  
  
  // Ecriture de l'entête
  fwrite($h, "N°;id organisme;organisme;id contact;contact;etat extraction;zone géographique;mode extraction;date demande;date annulation;date validation;date début traitement;date fin traitement;bases vecteurs;thèmes vecteur;thèmes raster;format vecteur;format raster;projection;demande support physique;demande validée;envoye/remis;date envoi/remise;numéro courrier suivi;communes;insee;Nb communes;fichier zip;taille totale;taille (octets);message erreur;fichier supprimer;date suppression;adresse IP;extent extraction;width;height\n");

  // Récupération des extractions
  
  /********************************************************************************/
  $conn = dbConnect();
  if(isset($_SESSION["query_extraction"]) && $_SESSION["query_extraction"] != "") {
    $sql = $_SESSION["query_extraction"] ;
    $rows2 = $_SESSION["query_extraction_users"];

  } else {
    $sql = "SELECT to_char(projet_extraction.date_demande, 'DD/MM/YYYY HH24:MI') AS date_demande_str, to_char(projet_extraction.date_annulation, 'DD/MM/YYYY HH24:MI') AS date_annulation_str, to_char(projet_extraction.date_validation, 'DD/MM/YYYY HH24:MI') AS date_validation_str, to_char(projet_extraction.date_debut_traitement, 'DD/MM/YYYY HH24:MI') AS date_debut_traitement_str, to_char(projet_extraction.date_fin_traitement, 'DD/MM/YYYY HH24:MI') AS date_fin_traitement_str, projet_extraction.date_dvd_envoye, projet_extraction.*, projet_organisme.libelle_organisme, (projet_contact.nom_contact||' '||projet_contact.prenom_contact) AS ayant_droit FROM projet_extraction INNER JOIN projet_organisme ON projet_organisme.id_organisme=projet_extraction.id_organisme INNER JOIN projet_contact ON projet_contact.id_contact=projet_extraction.id_contact $where ORDER BY id_extraction" ;
  }
  $res = pg_query($conn, $sql);
  if(!$res) { return array(true, pg_last_error($conn)); }    

  // Ecriture du contenu
  while($row = pg_fetch_assoc($res)) {
  

    // Union des infos sur user et organisme aux infos de la demande d'extraction
    $urow = $rows2[$row['id_contact']];
    if( (is_array($row) && count($row) > 0) && (is_array($urow) && count($urow) > 0) ) {
        $row += $urow;
    }
    //

    $coucheVecteur = unserialize(utf8_encode($row['couche_vecteur']));
    $themeVecteur = array();
    $baseVecteur = array();
    $baseRaster = array();
    for($i=0; $i<count($coucheVecteur); $i++) {
      if(!in_array($coucheVecteur[$i]['description'], $themeVecteur)) { $themeVecteur[] = ($coucheVecteur[$i]['description'] != "") ? $coucheVecteur[$i]['description'] : $coucheVecteur[$i]['name']; }
      if(!in_array($coucheVecteur[$i]['base'], $baseVecteur)) { $baseVecteur[] = $coucheVecteur[$i]['base']; }
    }

    $coucheRaster = unserialize(utf8_encode($row['couche_raster']));
    $themeRaster = array();
    for($i=0; $i<count($coucheRaster); $i++) {
      if(!in_array($coucheRaster[$i]['name'], $themeRaster)) { $themeRaster[] = $coucheRaster[$i]['name']; }
      if(!in_array($coucheRaster[$i]['base'], $baseRaster)) { $baseRaster[] = $coucheRaster[$i]['base']; }
    }

    $fichierZip = unserialize($row['fichier_zip']);
    $fileZip = array();
    for($i=0; $i<count($fichierZip); $i++) {
      $fileZip[] = $fichierZip[$i]['nom'];
    }
    
    fwrite($h, conformeCSV($row['id_extraction'])); // id
    fwrite($h, ";");
    fwrite($h, conformeCSV($row['id_organisme'])); // id organisme
    fwrite($h, ";");
    fwrite($h, conformeCSV($row['libelle_organisme'])); // organisme
    fwrite($h, ";");
    fwrite($h, conformeCSV($row['id_contact'])); // id contact
    fwrite($h, ";");
    fwrite($h, conformeCSV($row['ayant_droit'])); // contact
    fwrite($h, ";");
    fwrite($h, conformeCSV(getEtatDemandeLibelle($row['etat_demande']))); // etat
    fwrite($h, ";");
    fwrite($h, conformeCSV(getZoneGeoLibelle($row['zone_geograpique']))); // zone géographique
    fwrite($h, ";");
    fwrite($h, conformeCSV($row['mode'])); // mode - auto / manuel
    fwrite($h, ";");
    fwrite($h, conformeCSV($row['date_demande_str'])); // date demande
    fwrite($h, ";");
    fwrite($h, conformeCSV($row['date_annulation_str'])); // date annulation
    fwrite($h, ";");
    fwrite($h, conformeCSV($row['date_validation_str'])); // date validation
    fwrite($h, ";");
    fwrite($h, conformeCSV($row['date_debut_traitement_str'])); // date début traitement
    fwrite($h, ";");
    fwrite($h, conformeCSV($row['date_fin_traitement_str'])); // date fin traitement
    fwrite($h, ";");
    fwrite($h, conformeCSV(implode(", ", $baseVecteur))); // bases vecteurs
    fwrite($h, ";");
    fwrite($h, utf8_decode(conformeCSV(implode(", ", $themeVecteur)))); // thèmes vecteur
    fwrite($h, ";");
    fwrite($h, utf8_decode(conformeCSV(implode(", ", $themeRaster)))); // thèmes raster
    fwrite($h, ";");
    fwrite($h, conformeCSV(getFormatLibelle($row['format_vecteur']))); // format vecteur
    fwrite($h, ";");
    fwrite($h, conformeCSV(getFormatLibelle($row['format_raster']))); // format raster
    fwrite($h, ";");
    fwrite($h, conformeCSV(getProjectionLibelle($row['systeme_projection']))); // projection
    fwrite($h, ";");
    fwrite($h, conformeCSV(getPgBooleanLibelle($row['demande_dvd']))); // demande dvd
    fwrite($h, ";");
    fwrite($h, conformeCSV(getPgBooleanLibelle($row['demande_dvd_validee']))); // demande dvd validée
    fwrite($h, ";");
    fwrite($h, conformeCSV(getPgBooleanLibelle($row['dvd_envoye']))); // dvd envoye
    fwrite($h, ";");
    fwrite($h, conformeCSV($row['date_dvd_envoye'])); // date envoi dvd
    fwrite($h, ";");    
    fwrite($h, conformeCSV($row['num_courrier_suivi'])); // numéro courrier suivi
			

    $commune = $row['commune'];
    if($commune == "") {
        $comm = getCommuneTC($row['id_organisme']);
        $insee = "";
        $nbcomm = 0;
        if(is_array($comm)) {
            $commune = $comm['commune'];
            $insee = $comm['insee'];
            $nbcomm = count(explode(",", $comm['insee']));
        }
    } else {
        $insee = str_replace(";", ", ", $row['insee_extraction']);
        $nbcomm = count(explode(";", $row['insee_extraction']));
    }
    fwrite($h, ";");
    fwrite($h, conformeCSV(str_replace(" ", "", $commune))); // commune
    fwrite($h, ";");
    fwrite($h, conformeCSV($insee)); // commune
    fwrite($h, ";");
    fwrite($h, conformeCSV($nbcomm)); // commune
    fwrite($h, ";");
    
    fwrite($h, conformeCSV(implode(", ", $fileZip))); // fichier zip
    fwrite($h, ";");
    fwrite($h, conformeCSV(format_sizes($row['taille_fichier']))); // taille fichier
    fwrite($h, ";");
    fwrite($h, conformeCSV($row['taille_fichier'])); // taille fichier
    fwrite($h, ";");
    fwrite($h, conformeCSV($row['message_erreur'])); // message erreur
    fwrite($h, ";");
    fwrite($h, conformeCSV(getPgBooleanLibelle($row['fichier_supprimer']))); // fichier supprimer
    fwrite($h, ";");
    fwrite($h, conformeCSV($row['date_suppression_str'])); // date suppression
    fwrite($h, ";");
    fwrite($h, conformeCSV($row['remote_addr']));

	// extent extraction
	$aExtentExtraction = unserialize($row['extent_extraction']);
    fwrite($h, ";");
	if(is_array($aExtentExtraction)){
		$strExtentExtraction = implode(" ", $aExtentExtraction  );
		fwrite( $h, conformeCSV( $strExtentExtraction ) ); 
        
        $width = $aExtentExtraction[2] - $aExtentExtraction[0];
        fwrite($h, ";");
        fwrite($h, conformeCSV((int)$width)); 
        $height = $aExtentExtraction[3] - $aExtentExtraction[1];
        fwrite($h, ";");
        fwrite($h, conformeCSV((int)$height)); 
	}		 
    fwrite($h, "\n");
  }

  fclose($h);
  return array(false, formatPath($ini['tmpUrl'], $name.".csv"));
}

?>