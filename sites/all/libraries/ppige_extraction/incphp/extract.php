<?php
/*********************************************************************************
  Copyright (c) 2005-2006 GEOSIGNAL - www.geosignal.fr - contact@geosignal.fr
**********************************************************************************/

session_start();

ini_set('max_execution_time', 86400);

// Si include depuis un autre script path relatif pas bon
$sPath = realpath(dirname(__FILE__));
// $sPath
// /var/www/html/portail/sites/all/libraries/ppige_extraction/incphp

require_once("/var/www/html/portail/sites/all/libraries/ppige_extraction/incphp/db/dbcon.php");
require_once("/var/www/html/portail/sites/all/libraries/ppige_extraction/incphp/phpmailer/class.phpmailer.php");
require_once("/var/www/html/portail/sites/all/libraries/ppige_extraction/incphp/outils.php");
require_once("/var/www/html/portail/sites/all/libraries/ppige_extraction/incphp/bases.php");
require_once("/var/www/html/portail/sites/all/libraries/ppige_extraction/incphp/metadata.php");
// require_once("/var/www/html/portail/sites/all/libraries/ppige_extraction/incphp/http/client.php");

require_once("/var/www/html/portail/sites/all/libraries/ppige_extraction/incphp/edit_extract.php");
require_once("/var/www/html/portail/sites/all/libraries/ppige_extraction/incphp/csv_extract.php");

define("ATTENTE_VALIDATION", "ATTENTE_VALIDATION");
define("ANNULEE", "ANNULEE");
define("ATTENTE_TRAITEMENT", "ATTENTE_TRAITEMENT");
define("TRAITEMENT_EN_COURS", "TRAITEMENT_EN_COURS");
define("TERMINEE", "TERMINEE");
define("EN_ERREUR", "EN_ERREUR");

define("ERREUR_ORGANISME", "ERREUR_ORGANISME");
define("EMPRISE_HORS_TC", "EMPRISE_HORS_TC");


define("ANNULER_EXTRACTION", "annuler_extraction");
define("VALIDER_EXTRACTION", "valider_extraction");
define("VALIDER_EXTRACTION_MANUEL", "valider_extraction_manuel");
define("DUPLIQUER_EXTRACTION", "dupliquer_extraction");
define("SUPPRIMER_FICHIER_EXTRACTION", "supprimer_fichier_extraction");
define("TERMINER_EXTRACTION", "terminer_extraction");

define("EXTRACTION_ACTIVE", "EXTRACTION_ACTIVE");
define("VALIDATION_AUTO", "VALIDATION_AUTO");
define("SUPPRESSION_AUTO", "SUPPRESSION_AUTO");
define("HEURE_DEBUT", "HEURE_DEBUT");
define("HEURE_FIN", "HEURE_FIN");

define("OUI", "OUI");
define("NON", "NON");

define("FILTRE_TOUTES_DEMANDES", "FILTRE_TOUTES_DEMANDES");
define("FILTRE_ATTENTE_VALIDATION", "FILTRE_ATTENTE_VALIDATION");
define("FILTRE_ANNULEE", "FILTRE_ANNULEE");
define("FILTRE_ATTENTE_TRAITEMENT", "FILTRE_ATTENTE_TRAITEMENT");
define("FILTRE_TRAITEMENT_EN_COURS", "FILTRE_TRAITEMENT_EN_COURS");
define("FILTRE_TERMINEE", "FILTRE_TERMINEE");
define("FILTRE_EN_ERREUR", "FILTRE_EN_ERREUR");
define("FILTRE_WWW", "FILTRE_WWW");
define("FILTRE_DVD", "FILTRE_DVD");
define("FILTRE_DEMANDE_DVD", "FILTRE_DEMANDE_DVD");
define("FILTRE_DEMANDE_DVD_NON_TRAITEE", "FILTRE_DEMANDE_DVD_NON_TRAITEE");
define("FILTRE_DEMANDE_DVD_ACCEPTEE", "FILTRE_DEMANDE_DVD_ACCEPTEE");
define("FILTRE_DEMANDE_DVD_REFUSEE", "FILTRE_DEMANDE_DVD_REFUSEE");
define("FILTRE_DVD_ENVOYE", "FILTRE_DVD_ENVOYE");
define("FILTRE_DVD_PAS_ENVOYE", "FILTRE_DVD_PAS_ENVOYE");
define("FILTRE_MODE_MANUEL", "FILTRE_MODE_MANUEL");
define("FILTRE_MODE_DALLE", "FILTRE_MODE_DALLE");

define("FILTRE_EMPRISE_HORS_TC", "FILTRE_EMPRISE_HORS_TC");
define("FILTRE_ERREUR_ORGANISME", "FILTRE_ERREUR_ORGANISME");

define("FILTRE_MANUEL_TERMINE", "FILTRE_MANUEL_TERMINE");
define("FILTRE_DALLE_TERMINE", "FILTRE_DALLE_TERMINE");


define("TAILLE_LIMITE", 52428800); // 50Mo

function supprimerFichierExtraction($idExtraction) {
  $conn = dbConnect();
  
  $res = pg_query($conn, "SELECT * FROM projet_extraction WHERE id_extraction=".$idExtraction);
  if(!$res) { return array(true, pg_last_error($conn)); }
  $row = pg_fetch_assoc($res);
  if(!$row) { return array(true, "demande introuvable."); }
  $erreurSuppr = false;
  // Suppression des zip
  if($row['fichier_zip']) {
    $arrZip = unserialize($row['fichier_zip']);
    foreach($arrZip as $zip) { 
        supprimerFichier($zip['nom']); 
        if( file_exists($zip['nom']) ) $erreurSuppr = true;
    }
    if($erreurSuppr === false) {
        $res = pg_query($conn, "UPDATE projet_extraction SET date_suppression=now(), fichier_supprimer=TRUE, fichier_zip='' WHERE id_extraction=".$idExtraction);
        if(!$res) { return array(true, pg_last_error($conn)); }
    } else {
        return array(true, 'Impossible de supprimer les fichiers.');
    }
  }
  
  // Suppression du dossier temporaire (normalement inutile)
  if($row['dossier_temp'] && is_dir($row['dossier_temp'])) {      
    $cmd = "rm -r -f \"".$row['dossier_temp']."\"";
    exec($cmd, $output);
  }      
  
  //dbClose($conn);
  
  return array(false, "");
}


/**
*	addExtraction : version avec Panier
*  Appel depuis Drupal
**/
function addExtractionFromCart($oid, $uid, $strVectors, $strRasters, $vector, $raster, $projection, $insee, $communes, $hostname, $extent, $droits) {
    
    $tc = getTC($oid);
    
    // Ajout de la demande
	$conn = dbConnect();
	$tmp_extent = $extent["tmp_extent"];
	$extent_extraction = $extent["extent"];
	$extent_carte = $extent["extent"];
    
	$emprise = "etendue"; // partielle Nb Communes
	if(!$insee || $insee == "" || $insee == $tc) {
		$emprise = "totalite"; // TC complet
	}
	
	$etat_demande = "ATTENTE_VALIDATION";
	if(empty($tmp_extent)/* && strtolower($zone) != "totalite"*/) {
		$etat_demande = "ERREUR_ORGANISME";
	}

    $strVectors = utf8_decode($strVectors);
    $strRasters = utf8_decode($strRasters);
    $droits = utf8_decode($droits);
    
	$attente_vecteur = "true";
	$attente_raster = "true";
	
	if(!$strVectors || $strVectors == "") {
		$attente_vecteur = "false";
	}
	
	if(!$strRasters || $strRasters == "") {
		$attente_raster = "false";
	}
    
    // Utilisé par l'extracteur pour récup des options sur les formats et projections
    $format_vecteur = ($vector && trim($vector != "")) ? $vector : "shp";
    $format_raster = ($raster && trim($raster != "")) ? $raster : "ecw";
    
    $ids_projections = array(
        'Lambert 93' => 5, 
        'Conique conforme 50'=> 13,
        'Lambert II étendu'=> 1, 
        'Lambert 1 nord'=> 12, 
        'Lambert 1 carto'=> 11, 
        'WGS84'=> 10, 
        'RGF93'=> 14, 
        'ETRS89'=> 15, 
    );

    $ids_vecteurs = array(
        'shp' => 1,
        'mif' => 3,  
        'tab' => 2 
    );

    $ids_rasters = array(
        'ecw' => 8, 
        'tiff' => 7, 
        'jpeg2000' => 9 
    );
  
    $id_projection = isset($ids_projections[$projection]) ? $ids_projections[$projection] : 5;
    $id_format_vecteur = isset($ids_vecteurs[$format_vecteur]) ? $ids_vecteurs[$format_vecteur] : 1;
    $id_format_raster = isset($ids_rasters[$format_raster]) ? $ids_rasters[$format_raster] : 8;
    //
    
	$sql = "INSERT INTO extraction.projet_extraction 
                                    (id_organisme, id_contact, date_demande, date_annulation, date_validation, date_fin_traitement, etat_demande,     zone_geograpique, titre_carte, couche_vecteur,                                                                                     couche_raster,                                                                                        format_vecteur,    format_raster,     systeme_projection, fichier_zip, demande_dvd, num_courrier_suivi, extent_extraction,     fichier_licence, fichier_map, commune,      extent_carte,      remote_addr, date_debut_traitement,  process_id, message_erreur, attente_raster,    dossier_temp, dossier_contact, fichier_supprimer, date_suppression, dvd_envoye,  demande_dvd_validee, date_dvd_envoye, taille_fichier, process_id_zip, temp_extent,   attente_vecteur,   id_format_vecteur,   id_format_raster,   id_projection,   organisme, contact, status_vecteur, status_raster, mode, the_geom_tc, taille_reelle, insee_extraction, droits) VALUES 
                                    ($oid,              $uid,           'now()',             NULL,                 NULL,                NULL,                       '$etat_demande', '$emprise',              NULL,         ".(($strVectors && trim($strVectors != "")) ? "'$strVectors'" : "NULL").", ".(($strRasters && trim($strRasters != "")) ? "'$strRasters'" : "NULL").", '$format_vecteur', '$format_raster', '$projection',            NULL,         NULL,               NULL,                     '$extent_extraction', NULL,               NULL,          '$communes', '$extent_carte',  '$hostname',  NULL,                             NULL,         NULL,                   $attente_raster, NULL,               NULL,                NULL,                    NULL,                   NULL,            NULL,                           NULL,                     NULL,          NULL,                '$tmp_extent', $attente_vecteur, $id_format_vecteur, $id_format_raster, $id_projection, NULL,         NULL,     NULL,                NULL,             NULL,  NULL,              NULL,         '$insee',              '$droits');";

    // pg_set_client_encoding($conn, "UTF8");
	$res = pg_query($conn, $sql);
	
	if(!$res) { return array(true, pg_last_error($conn)); }
	
	/* Récupération de l'identifiant de l'extraction */
	$sqlId = "select * from extraction.projet_extraction where id_contact=$uid order by id_extraction DESC limit 1;";
	$id = pg_query($conn, $sqlId);
	$extraction = pg_fetch_assoc($id);
	dbClose($conn);

	return $extraction;
}

function sendExtractionNotification($ext, $foncier) {
// debug($ext);
// echo "Notification par mail désactivée en mode dev.";
// return ;
// exit ;

	$ini = parse_ini_file("config/config.ini");
	$foncier = ($foncier == 'yes');
	
	if($foncier) {
		$foncierTxt = "
		INFORMATIONS IMPORTANTES :

		Votre demande comporte des données foncières qui sont soumises à validation de la part de la DGALN.
		A ce titre, pour terminer le processus de validation, veuillez faire parvenir le document <a href='http://www.ppige-npdc.fr/portail/doc/Acte_Engagementdynamique2014_cle5e6851.pdf'>DGALN-AD</a> dument rempli par courrier électronique, en respectant scrupuleusement les 4 règles suivantes :
		1-Objet du message : [PPIGE] Demande de téléchargement de fichiers fonciers à partir du site de la PPIGE 
		2-Destinataire principal : autorisations-fichiers-fonciers@developpement-durable.gouv.fr 
		3-Destinataires en copie : fichiers-fonciers@developpement-durable.gouv.fr et ppige@epf-npdc.fr
		4-Pièce jointe : le document DGALN-AD dument rempli. 

		Votre demande ne pourra être traitée correctement et dans les plus brefs délais si vous ne respectez pas ces étapes

		-	Nous vous invitons à consulter également le document <a href='http://www.ppige-npdc.fr/portail/doc/AE-DGFIP-DGALN.pdf'>AE-DGFiP-DGALN</a>
		";
		// $foncierFiles = Array("/var/www/html/portail/doc/Documents.zip");
		//A ce titre, 
		//-	Pour terminer le processus de validation, veuillez nous faire parvenir à ".(isset($ini['mailFichiersFonciers']) ? $ini['mailFichiersFonciers'] : $ini['fromMail'])." ".(isset($ini['mailFichiersFonciersCopie']) ? "et en copie à ".$ini['mailFichiersFonciersCopie'] : "")." le document <a href='http://dev.ppige-npdc.fr/portail/doc/AE-DGALN-AD.doc'>DGALN-AD</a> dument rempli. Votre demande ne pourra être traitée sans ce document.
		//-	Nous vous invitons à consulter également le document <a href='http://dev.ppige-npdc.fr/portail/doc/AE-DGFIP-DGALN.pdf'>AE-DGFiP-DGALN</a>

		$foncierFiles = Array("/var/www/html/portail/doc/AE-DGFIP-DGALN.pdf", "/var/www/html/portail/doc/Acte_Engagementdynamique2014_cle5e6851.pdf");
	} else {
		$foncierTxt = "";
		$foncierFiles = Array();
	}


    if(!is_array($ext)) return false;
    if(count($ext)==0) return false;
    
    $uid = $ext[0]['id_contact'];
    $ids = array();
    
	/* Récupération des infos du contact */
	$conn = dbConnect3();
	$sql = "select * from v_users where uid=$uid limit 1";
	// pg_set_client_encoding($conn, "UTF8");
	$contact = pg_fetch_assoc(pg_query($conn, $sql));

    $desc  = "<table border=1>";
    $desc .= "<tr>
                    <th>Données</th>
                    <th>Droits</th>
                    <th>Zone</th>
                    <th>Format-Projection</th>
                    <th>N°</th>
                   </tr>";
    
    // Boucle sur les extractions
    foreach($ext as $row) {
        $id_extraction = $row["id_extraction"];	
        $ids[] = $id_extraction;
        
        $vectorLayers = unserialize(utf8_encode($row['couche_vecteur']));
        $vlayers = $base = "";
        $alayers = array();
        if(is_array($vectorLayers)) {
            foreach($vectorLayers as $layer) {
                $alayers[] = $layer["name"];
                $base = $layer["base"];
            }
            // $vlayers = "<br>Vecteur : <br> - ".implode("<br> - ", $alayers);
            $vlayers = "<br> - ".implode("<br> - ", $alayers);
        }
        
        $rasterLayers = unserialize(utf8_encode($row['couche_raster']));
        $rlayers = "";
        $alayers = array();
        if(is_array($rasterLayers)) {
            foreach($rasterLayers as $layer) {
                $alayers[] = $layer["name"];
                $base = $layer["base"];
            }
            // $rlayers = "<br>Raster : <br> - ".implode("<br> - ", $alayers);
            $rlayers = "<br> - ".implode("<br> - ", $alayers);
        }
        
        $donnees = utf8_decode("<b>".$base."</b>".$vlayers." ".$rlayers);
        
        $droits = $row['droits'];
        
        $zone = "";
        if($row['zone_geograpique'] == "etendue") {
            // $zone = $row['commune'] ;
            $nbc = count(explode(";",$row['insee_extraction']));
            $zone = $nbc. " commune";
            if($nbc > 1) $zone .= "s";
        }else {
            $zone = "Territoire de compétence complet";
        }
        
        $vformat = $row['format_vecteur'];
        $rformat = $row['format_raster'];
        $projection = $row['systeme_projection'];
        $sep = "";
        if($vformat != "" && $rformat != "" ) $sep = " / ";
        $format = $vformat . $sep . $rformat." - ".$projection;
        
        $desc .= "<tr>
                        <td>$donnees</td>
                        <td>$droits</td>
                        <td>$zone</td>
                        <td>$format</td>
                        <td>$id_extraction</td>
                       </tr>";
    }
    
    $desc  .= "</table>";
    
    $mailObjet = "Demande d'extraction";
    
$mailMessage = 
"
Bonjour ".$contact["libelle_contact"].", 

Votre commande est enregistr&eacute;e. Nous l'&eacute;tudions pour validation.

".$foncierTxt."Voici le r&eacute;sum&eacute; de votre commande :


$desc


Cordialement,
L'&eacute;quipe PPIGE
ppige@epf-npdc.fr
03 28 07 25 30 

";

$mailMessageAdmin = 
"
Bonjour, 

Un utilisateur vient de demander une extraction.
Il s'agit de: 

nom : ".$contact["nom_contact"]." 
pr&eacute;nom : ".$contact["prenom_contact"]."
uid: $uid
organisme : ".$contact["libelle_organisme"]."
num&eacute;ros des demandes : ".implode(", ", $ids)."

Voici une copie du mail envoy&eacute; &agrave; l'utilisateur :

--------------------------------------------------

$mailMessage

";
	
    if(!envoieMailWithSmtp($contact["email_contact"], $mailObjet, $mailMessage, $ini['fromMail'], $foncierFiles)) {
        return array(true, "Envoi du mail à ".$contact["email_contact"]." impossible.");
    } else {
        $adminMail = getAdminMail();
        envoieMailWithSmtp($adminMail, $mailObjet, $mailMessageAdmin, $ini['fromMail']);
    }
}


function dupliquerExtraction($idExtraction) {
  $conn = dbConnect();
  
  // On récupère la demande
  $res = pg_query($conn, "SELECT * FROM projet_extraction WHERE id_extraction=$idExtraction");
  if(!$res) { return array(true, pg_last_error($conn)); }
  $row = pg_fetch_assoc($res);
  if(!$row) { return array(true, "demande introuvable."); }  
  
  /*echo "<pre>";	  
  print_r($row);*/

  // On récupère le paramètre de validation automatique
  $validationAuto = getAdminExtractValue(VALIDATION_AUTO, NON);

  // Etat de l'extraction par défaut
  $etatDemande = ($validationAuto == OUI ? ATTENTE_TRAITEMENT : ATTENTE_VALIDATION);

  // Date de validation
  $dateValidation = ($etatDemande == ATTENTE_TRAITEMENT ? "'now()'" : "NULL");
  
  
  //echo "==> ".$req = "INSERT INTO projet_extraction (id_organisme,id_contact,date_demande,date_validation,etat_demande,zone_geograpique,extent_carte,extent_extraction,titre_carte,couche_vecteur,couche_raster,format_vecteur,format_raster,systeme_projection,fichier_licence,fichier_map,commune,remote_addr,id_format_vecteur,id_format_raster,id_projection) VALUES (".$row['id_organisme'].",".$row['id_contact'].",now(),$dateValidation,'$etatDemande','".addslashes($row['zone_geograpique'])."','".$row['extent_carte']."','".$row['extent_extraction']."','".addslashes($row['titre_carte'])."','".addslashes($row['couche_vecteur'])."','".addslashes($row['couche_raster'])."','".addslashes($row['format_vecteur'])."','".addslashes($row['format_raster'])."','".addslashes($row['systeme_projection'])."','".$row['fichier_licence']."','".$row['fichier_map']."','".addslashes($row['commune'])."','".$row['remote_addr']."','".$row['id_format_vecteur']."','".$row['id_format_raster']."','".$row['id_projection']."')" ;
  
  
  // Ajout de la demande
	$res = pg_query($conn, "INSERT INTO projet_extraction (id_organisme,id_contact,date_demande,date_validation,etat_demande,zone_geograpique,extent_carte,extent_extraction,titre_carte,couche_vecteur,couche_raster,format_vecteur,format_raster,systeme_projection,fichier_licence,fichier_map,commune,remote_addr,id_format_vecteur,id_format_raster,id_projection) VALUES (".$row['id_organisme'].",".$row['id_contact'].",now(),$dateValidation,'$etatDemande','".addslashes($row['zone_geograpique'])."','".$row['extent_carte']."','".$row['extent_extraction']."','".addslashes($row['titre_carte'])."','".addslashes($row['couche_vecteur'])."','".addslashes($row['couche_raster'])."','".addslashes($row['format_vecteur'])."','".addslashes($row['format_raster'])."','".addslashes($row['systeme_projection'])."','".$row['fichier_licence']."','".$row['fichier_map']."','".addslashes($row['commune'])."','".$row['remote_addr']."','".$row['id_format_vecteur']."','".$row['id_format_raster']."','".$row['id_projection']."')");
	if(!$res) { return array(true, pg_last_error($conn)); }  
  
  //dbClose($conn);
  
  return array(false, "");
}

function validerExtraction($idExtraction) {
  $conn = dbConnect();
  
  // On récupère l'état de la demande
  $res = pg_query($conn, "SELECT id_contact, etat_demande FROM projet_extraction WHERE id_extraction=$idExtraction");
  if(!$res) { return array(true, pg_last_error($conn)); }
  $row = pg_fetch_assoc($res);
  if(!$row) { return array(true, "demande introuvable."); }  	  
  $etatDemande = $row['etat_demande'];
  $idContact = $row["id_contact"];
  
  // Si la demande n'est pas déjà validée
  if($etatDemande != ATTENTE_TRAITEMENT) {
    // On s'assure qu'elle est en attente de validation ou annulée 	  
 	  if($etatDemande != ATTENTE_VALIDATION && $etatDemande != ANNULEE) { return array(true, "la demande ne peut pas être validée."); }
 	  
    // Met à jour l'état de la demande
	$sql = "UPDATE projet_extraction SET etat_demande='".ATTENTE_TRAITEMENT."', mode='auto', date_validation=now() WHERE id_extraction=$idExtraction AND (etat_demande='".ATTENTE_VALIDATION."' OR etat_demande='".ANNULEE."')";
	//print $sql;
    $res = pg_query($conn, $sql);        
	
  	if(!$res) { return array(true, pg_last_error($conn)); }

    
    //JMA
    // Envoi du mail    
    //Récup les infos du contact
    $conn2 = dbConnect3(); // Drupal
    $sql2 = "SELECT  realname.realname as ayant_droit, users.mail as email_contact
                    FROM users 
                    LEFT JOIN field_data_field_organisme ON users.uid = field_data_field_organisme.entity_id
                    LEFT JOIN node ON node.nid = field_data_field_organisme.field_organisme_target_id
                    LEFT JOIN realname ON users.uid = realname.uid 
                    WHERE users.uid = $idContact";             
    $res2 = pg_query($conn2, $sql2);
    $row2 = @pg_fetch_assoc($res2);
    
    $ini = parse_ini_file("config/config.ini");
    $mailObjet = "Validation d’extraction N°$idExtraction";
    $mailMessage = 
"
Bonjour ".$row2['ayant_droit'].",

Votre demande n° $idExtraction a été acceptée. Elle est maintenant en attente ou en cours de traitement sur notre extracteur.

Dès que votre demande sera prête, un mail d’avertissement vous sera envoyé.

Cordialement,
L'équipe PPIGE
ppige@epf-npdc.fr
03 28 07 25 30 

";

    $mailMessageAdmin = 
"
Bonjour,

La PPIGE vient de valider une extraction.
Il s'agit de l’extraction $idExtraction.

Voici une copie du mail envoyé à l’utilisateur :

--------------------------------------------------

$mailMessage

";
    
    if(!envoieMailWithSmtp($row2['email_contact'], $mailObjet, $mailMessage, $ini['fromMail'])) {
        return array(true, "Envoi du mail à ".$row2['email_contact']." impossible.");
    } else {
        $adminMail = getAdminMail();
        envoieMailWithSmtp($adminMail, $mailObjet, $mailMessageAdmin, $ini['fromMail']);
    }
  }
  
  //dbClose($conn);
  
  return array(false, "");
}

function validerExtractionManuel($idExtraction) {
  $conn = dbConnect();
  
  // On récupère l'état de la demande
  $res = pg_query($conn, "SELECT id_contact, etat_demande FROM projet_extraction WHERE id_extraction=$idExtraction");
  if(!$res) { return array(true, pg_last_error($conn)); }
  $row = pg_fetch_assoc($res);
  if(!$row) { return array(true, "demande introuvable."); }  	  
  $etatDemande = $row['etat_demande'];
  $idContact = $row["id_contact"];
  
  // Si la demande n'est pas déjà validée
  if($etatDemande != ATTENTE_TRAITEMENT) {
    // On s'assure qu'elle est en attente de validation ou annulée 	  
 	  if($etatDemande != ATTENTE_VALIDATION && $etatDemande != ANNULEE) { return array(true, "la demande ne peut pas être validée."); }
 	  
    // Met à jour l'état de la demande
	$sql = "UPDATE projet_extraction SET etat_demande='".ATTENTE_TRAITEMENT."', mode='manuel', date_validation=now(), demande_dvd=TRUE, demande_dvd_validee=TRUE WHERE id_extraction=$idExtraction AND (etat_demande='".ATTENTE_VALIDATION."' OR etat_demande='".ANNULEE."')";
	//print $sql;
    $res = pg_query($conn, $sql);        
	
  	if(!$res) { return array(true, pg_last_error($conn)); }

    
    //JMA
    // Envoi du mail    
    //Récup les infos du contact
    $conn2 = dbConnect3(); // Drupal
    $sql2 = "SELECT  realname.realname as ayant_droit, users.mail as email_contact
                    FROM users 
                    LEFT JOIN field_data_field_organisme ON users.uid = field_data_field_organisme.entity_id
                    LEFT JOIN node ON node.nid = field_data_field_organisme.field_organisme_target_id
                    LEFT JOIN realname ON users.uid = realname.uid 
                    WHERE users.uid = $idContact";             
    $res2 = pg_query($conn2, $sql2);
    $row2 = @pg_fetch_assoc($res2);
    
    $ini = parse_ini_file("config/config.ini");
    $mailObjet = "Validation d’extraction N°$idExtraction";
    $mailMessage = 
"
Bonjour ".$row2['ayant_droit'].",

Votre demande n° $idExtraction a été acceptée. 

Toutefois, au vu du territoire demandé (emprise régionale ou départementale) et du volume de données, nous vous invitons à venir retirer la donnée directement auprès de l’équipe PPIGE. 

Pour ce faire, merci de nous contacter soit par mail, soit par téléphone, afin de convenir d’un rendez-vous, et de vous munir d’un disque dur ou autre support pour copier vos extractions.

Cordialement,
L'équipe PPIGE
ppige@epf-npdc.fr
03 28 07 25 30 

";

    $mailMessageAdmin = 
"
Bonjour,

La PPIGE vient de valider une extraction en mode 'manuel'.
Il s'agit de l’extraction $idExtraction.

Voici une copie du mail envoyé à l’utilisateur :

--------------------------------------------------

$mailMessage

";
    
    if(!envoieMailWithSmtp($row2['email_contact'], $mailObjet, $mailMessage, $ini['fromMail'])) {
        return array(true, "Envoi du mail à ".$row2['email_contact']." impossible.");
    } else {
        $adminMail = getAdminMail();
        envoieMailWithSmtp($adminMail, $mailObjet, $mailMessageAdmin, $ini['fromMail']);
    }
  }
  
  //dbClose($conn);
  
  return array(false, "");
}

function annulerExtraction($idExtraction) {
  
    //JMA
    // Récup les infos de l'extraction
    $conn = dbConnect();
    $sql = "select pe.id_contact,pe.id_extraction AS id_extraction, pe.etat_demande AS etat_demande from projet_extraction as pe where id_extraction = '".$idExtraction."'";        
    $res1 = pg_query($conn, $sql);
    $rows1 = pg_fetch_assoc($res1);
    $idContact = $rows1["id_contact"];    
    
    //Récup les infos du contact
    $conn2 = dbConnect3(); // Drupal
    $sql2 = "SELECT  
                    node.title as libelle_organisme, 
                    realname.realname as ayant_droit,
                    users.mail as email_contact
                FROM users 
                LEFT JOIN field_data_field_organisme ON users.uid = field_data_field_organisme.entity_id
                LEFT JOIN node ON node.nid = field_data_field_organisme.field_organisme_target_id
                LEFT JOIN realname ON users.uid = realname.uid 
                WHERE users.uid = $idContact";             
    $res2 = pg_query($conn2, $sql2);
    $rows2 = @pg_fetch_assoc($res2);
    
    $row = false;
    if( (is_array($rows1) && count($rows1) > 0) && (is_array($rows2) && count($rows2) > 0) ) {
        $row = $rows1 + $rows2;
    }
// printr($row);
    
    if(!$row) { return array(true, "demande introuvable."); }  	  
    $etatDemande = $row['etat_demande'];
    
    // Si la demande n'est pas déjà annulée
    if($etatDemande != ANNULEE) {
        // On s'assure qu'elle est en attente de validation ou en attente de traitement
        if($etatDemande != ATTENTE_VALIDATION && $etatDemande != ATTENTE_TRAITEMENT) { return array(true, "la demande ne peut pas être annulée."); }
        //else{return array(true, "test");}

        // Met à jour l'état de la demande
        $res = pg_query($conn, "UPDATE projet_extraction SET etat_demande='".ANNULEE."', date_annulation=now() WHERE id_extraction=$idExtraction AND (etat_demande='".ATTENTE_VALIDATION."' OR etat_demande='".ATTENTE_TRAITEMENT."')");        
        if(!$res) { return array(true, pg_last_error($conn)); }

        $ini = parse_ini_file("config/config.ini");

        // Envoi du mail
        $cur_user_id = $_REQUEST['uid'];  
        $admin_role = isAdminPrivilege($cur_user_id);
        
        if($admin_role) {
            // ANNULATION PAR ADMIN
            $mailObjet = "Refus d'extraction N°$idExtraction";
            $mailMessage = 
"
Bonjour ".$row['ayant_droit'].",

Il n'a pas été possible de répondre favorablement à votre demande d'extraction n° $idExtraction.
Il peut s'agir d'une erreur lors de votre identification, lors de l’attribution des droits de votre organisme, de vos droits, ou d'une erreur de délimitation de périmètre. Nous vous invitons à renouveler votre demande ou à nous contacter.

Cordialement,
L'équipe PPIGE
ppige@epf-npdc.fr
03 28 07 25 30

";
            $mailMessageAdmin =
"
Bonjour,

La PPIGE vient de refuser une extraction.
Il s'agit de l’extraction $idExtraction.


Voici une copie du mail envoyé à l’utilisateur :

--------------------------------------------------

$mailMessage

";

        } else {
            // ANNULATION PAR AYANT DROIT
            $mailObjet = "Refus d'extraction N°$idExtraction par l'AD";
            $mailMessage = 
"
Bonjour,

".$row['ayant_droit'].", de ".$row['libelle_organisme']." vient d'annuler une extraction.
Il s'agit de l’extraction $idExtraction.

Cordialement,
L'équipe PPIGE
ppige@epf-npdc.fr
03 28 07 25 30

";
            $mailMessageAdmin =
"
Bonjour,

".$row['ayant_droit'].", de ".$row['libelle_organisme']." vient de refuser une extraction.
Il s'agit de l’extraction $idExtraction.


Voici une copie du mail envoyé à l’utilisateur :

--------------------------------------------------

$mailMessage

";
        }
        
        
        if(!envoieMailWithSmtp($row['email_contact'], $mailObjet, $mailMessage, $ini['fromMail'])) {
            return array(true, "Envoi du mail à ".$row['email_contact']." impossible.");
        } else {
            $adminMail = getAdminMail();
            envoieMailWithSmtp($adminMail, $mailObjet, $mailMessageAdmin, $ini['fromMail']);
        }
    }

    //dbClose($conn);
  
    return array(false, "");
}


function updateDemandeDVD($idExtraction, $champ, $value) {
  $conn = dbConnect();
  $sendMail = false;
  
  switch($champ) {
  case "demande_dvd":
    $set = "demande_dvd=".($value ? $value : "NULL").", demande_dvd_validee=NULL, dvd_envoye=NULL, date_dvd_envoye=NULL, num_courrier_suivi=NULL";
    $sendMail = ($value == "TRUE");
    break;
  case "demande_dvd_validee":
    $set = "demande_dvd_validee=".($value ? $value : "NULL").", dvd_envoye=NULL, date_dvd_envoye=NULL, num_courrier_suivi=NULL";
    $sendMail = $value;
    break;
  case "dvd_envoye":
    $set = "dvd_envoye=".($value ? $value : "NULL").", date_dvd_envoye=NULL, num_courrier_suivi=NULL";
    $sendMail = ($value == "TRUE");
    break;
  case "date_dvd_envoye":
    $set = "date_dvd_envoye='".addslashes($value)."'";
    break;
  case "num_courrier_suivi":
    $set = "num_courrier_suivi='".addslashes($value)."'";
    break;
  }

  $res = pg_query($conn, "UPDATE projet_extraction SET ".$set." WHERE id_extraction=".$idExtraction);
  if(!$res) { return array(true, pg_last_error($conn)); }

  // Envoi d'un mail
  if($sendMail) {
    $ini = parse_ini_file("config/config.ini");
    
    //JMA
    // Récup les infos de l'extraction
    
    $conn = dbConnect();
    $sql =  "select 
                    pe.id_contact,
                    pe.id_extraction AS id_extraction, 
                    pe.etat_demande AS etat_demande, 
                    pe.mode, 
                    pe.zone_geograpique, 
                    pe.couche_vecteur, 
                    pe.couche_raster, 
                    pe.systeme_projection, 
                    pe.format_vecteur, 
                    pe.format_raster, 

                    pe.commune, 
                    pe.insee_extraction, 
                    pe.taille_fichier
                from projet_extraction as pe 
                where id_extraction = '".$idExtraction."'";        
    $res1 = pg_query($conn, $sql);
    $rows1 = pg_fetch_assoc($res1);
    $idContact = $rows1["id_contact"];
    
    //Récup les infos du contact
    $conn2 = dbConnect3(); // Drupal                    
    $sql2 = "SELECT  
                    node.title as libelle_organisme, 
                    realname.realname as ayant_droit, 
                    field_orga_adresse_value as adresse_organisme, 
                    field_orga_code_postal_value as codepostal_organisme, 
                    field_orga_ville_value as ville_organisme, 
                    field_data_field_telephone.field_telephone_value as telephone,
                    users.mail as email_contact, 
                    users.uid as user_id, 
                    field_data_field_organisme.field_organisme_target_id as org_id
                FROM users 
                LEFT JOIN field_data_field_telephone ON users.uid = field_data_field_telephone.entity_id
                LEFT JOIN field_data_field_organisme ON users.uid = field_data_field_organisme.entity_id
                LEFT JOIN field_data_field_orga_ville ON field_data_field_organisme.field_organisme_target_id = field_data_field_orga_ville.entity_id
                LEFT JOIN field_data_field_orga_code_postal ON field_data_field_organisme.field_organisme_target_id = field_data_field_orga_code_postal.entity_id
                LEFT JOIN field_data_field_orga_adresse ON field_data_field_organisme.field_organisme_target_id = field_data_field_orga_adresse.entity_id
                LEFT JOIN node ON node.nid = field_data_field_organisme.field_organisme_target_id
                LEFT JOIN realname ON users.uid = realname.uid 
                WHERE users.uid = $idContact";             
    
    $res2 = pg_query($conn2, $sql2);
    $rows2 = @pg_fetch_assoc($res2);
    
    $row = false;
    if( (is_array($rows1) && count($rows1) > 0) && (is_array($rows2) && count($rows2) > 0) ) {
        $row = $rows1 + $rows2;
    }
    
    if(!$row) { return array(true, "Demande d'extration introuvable."); }
    // printr($row);
    // exit;
    //
    
    $adminMail = getAdminMail();
    
    
    switch($champ) {
    case "demande_dvd":
      $mailObjet = "Commande de DVD N°".$idExtraction;
      $mailMessage = 
"
Bonjour ".$row['ayant_droit'].",

Vous venez de commander un CD/DVD ou disque dur pour la demande n° $idExtraction.
Votre demande est étudiée par ATARAXIE, notre prestataire, qui vous contactera pour confirmation.

Cordialement,
L'équipe PPIGE
ppige@epf-npdc.fr
03 28 07 25 30

";  


    $zone_geograpique = getZoneGeoLibelle($row['zone_geograpique']);
    
        $zone = "";
        if($row['zone_geograpique'] == "etendue") {
            // $zone = $row['commune'] ;
            $nbc = count(explode(";",$row['insee_extraction']));
            $zone = $nbc. " commune";
            if($nbc > 1) $zone .= "s";
        }else {
            $zone = "Territoire de compétence complet";
        }

    $coucheVecteur = unserialize(utf8_encode($row['couche_vecteur']));
    $themeVecteur = array();
    // $baseVecteur = array();
    for($i=0; $i<count($coucheVecteur); $i++) {
      if(!in_array($coucheVecteur[$i]['description'], $themeVecteur)) { $themeVecteur[] = ($coucheVecteur[$i]['description'] != "") ? $coucheVecteur[$i]['description'] : $coucheVecteur[$i]['name']; }
    }
    $themeVecteur = implode(", ", $themeVecteur);

    $coucheRaster = unserialize(utf8_encode($row['couche_raster']));
    $themeRaster = array();
    for($i=0; $i<count($coucheRaster); $i++) {
      if(!in_array($coucheRaster[$i]['name'], $themeRaster)) { $themeRaster[] = $coucheRaster[$i]['name']; }
    }
    $themeRaster = implode(", ", $themeRaster);
    
    $systeme_projection = getProjectionLibelle($row['systeme_projection']);
    $format_vecteur = getFormatLibelle($row['format_vecteur']);
    $format_raster = getFormatLibelle($row['format_raster']);
    
    $mailMessageAdmin = 
"
Bonjour,

Un utilisateur vient de commander un CD/DVD ou disque dur.
Il s'agit de:

nom : ".$row['ayant_droit']."
uid : ".$idContact."
organisme : ".$row['libelle_organisme']."
téléphone : ".$row['telephone']."
numéro de la demande : ".$idExtraction."

Voici le résumé de sa commande :

Données : 
$themeVecteur

$themeRaster

Droits : 
Zone géographique : $zone 	

Projection : $systeme_projection	
Format vecteur : $format_vecteur
Format raster : $format_raster

Voici une copie du mail envoyé à l’utilisateur :

--------------------------------------------------

$mailMessage

";
           
      envoieMailWithSmtp($row['email_contact'], $mailObjet, $mailMessage, $ini['fromMail']);
      envoieMailWithSmtp($adminMail, $mailObjet, $mailMessageAdmin, $ini['fromMail']);
      break;
      
    case "demande_dvd_validee":
      if($value == "FALSE") {
        // $mailObjet = "[PPIGE] Votre demande de DVD pour l'extraction N°".$idExtraction." a été refusée";
        // $mailMessage = "Bonjour ".$row['ayant_droit']."<br>\nIl n'a pas été possible de répondre favorablement à votre demande de DVD pour l'extraction numéro ".$idExtraction.".<br>\n<br>\nNous vous invitons à nous contacter.<br>\n<br>\nCordialement<br>\nL'équipe PPIGE<br>\nppige@epf-npdc.fr<br>\n03 28 07 25 30";
        //envoieMail($row['email_contact'], $mailObjet, $mailMessage, $ini['fromMail']);
        // envoieMailWithSmtp($row['email_contact'], $mailObjet, $mailMessage, $ini['fromMail']);
        // envoieMailWithSmtp($ini['adminMail'], $mailObjet, $mailMessage, $ini['fromMail']);
      } else {
        $mailObjet = "Confirmation d'envoi de CD/DVD";
        $mailMessage = 
"
Bonjour ".$row['ayant_droit'].",

Suite à notre entretien téléphonique, nous vous confirmons la gravure sur CD/DVD ou disque dur de la commande n°$idExtraction et son envoi imminent à votre adresse :

".$row['ayant_droit']." 
".$row['libelle_organisme']."
".$row['adresse_organisme']."
".$row['codepostal_organisme']." ".$row['ville_organisme']." 

Si cette adresse n’est pas conforme, nous vous invitons à nous contacter.

Cordialement,
L'équipe PPIGE
ppige@epf-npdc.fr
03 28 07 25 30

";
        $mailMessageAdmin = 
"
Bonjour,

Le(s) CD/DVD de l’extraction n° $idExtraction a (ont) été validé(s).

Voici une copie du mail envoyé à l’utilisateur :

--------------------------------------------------

$mailMessage

";
        envoieMailWithSmtp($row['email_contact'], $mailObjet, $mailMessage, $ini['fromMail']);
        envoieMailWithSmtp($adminMail, $mailObjet, $mailMessageAdmin, $ini['fromMail']);
      }
      break;
      
    case "dvd_envoye":
      $mailObjet = "CD/DVD Extraction N°".$idExtraction." expédié";
      $mailMessage = 
"
Bonjour ".$row['ayant_droit']."

Le CD/DVD correspondant à votre demande d'extraction n° $idExtraction a été expédié.
Vous devriez le recevoir d'ici 3 à 4 jours.
Vous trouverez les informations relatives au numéro de suivi du courrier dans votre espace ayant droit 'Mes extractions' sur le site www.ppige-npdc.fr.

Cordialement,
L'équipe PPIGE
ppige@epf-npdc.fr
03 28 07 25 30

";
      $mailMessageAdmin = 
"
Bonjour,

Le(s) CD/DVD correspondant(s) à l’extraction n° $idExtraction a (ont) été expédié(s).

Voici une copie du mail envoyé à l’utilisateur :

--------------------------------------------------

$mailMessage

";
        // Pas de notification par mail en mode manuel
        if($row['mode'] != "manuel") {
          envoieMailWithSmtp($row['email_contact'], $mailObjet, $mailMessage, $ini['fromMail']);
          envoieMailWithSmtp($adminMail, $mailObjet, $mailMessageAdmin, $ini['fromMail']);
        }
      break;
    }
  }
    
  //dbClose($conn);

  return array(false, "");
}

/**
* Fonction pour mettre l'etat de l'extraction à terminer
* update de la table projet_extraction
* @param: {Integer} id de l'extraction à terminer
* @return: {Array} tableau de l'etat de mise à jour
* @author: Jck 11/08/22
**/
function terminerExtraction($idExtraction){
    $conn = dbConnect();
    if($idExtraction != ''){
       // Met à jour l'état de la demande à TERMINE
        $sql = "UPDATE projet_extraction 
                    SET etat_demande = 'TERMINEE', 
                    date_fin_traitement = now(),
                    attente_raster = false,
                    attente_vecteur = false,
                    status_vecteur = 'TERMINE',
                    status_raster = 'TERMINE' 
                WHERE id_extraction = '".$idExtraction."'";
        
        $res = pg_query($conn, $sql);        
        
        if(!$res) {
            return array(true, pg_last_error($conn));
        }

        //JMA
        // Envoi du mail  

        //Récup les infos du contact
        $sql1 = "select id_contact from projet_extraction where id_extraction = '".$idExtraction."'";        
        $res1 = pg_query($conn, $sql1);
        $rows1 = pg_fetch_assoc($res1);
        $idContact = $rows1["id_contact"];
        
        $conn2 = dbConnect3(); // Drupal
        $sql2 = "SELECT  realname.realname as ayant_droit, users.mail as email_contact
                        FROM users 
                        LEFT JOIN field_data_field_organisme ON users.uid = field_data_field_organisme.entity_id
                        LEFT JOIN node ON node.nid = field_data_field_organisme.field_organisme_target_id
                        LEFT JOIN realname ON users.uid = realname.uid 
                        WHERE users.uid = $idContact";             
        $res2 = pg_query($conn2, $sql2);
        $row2 = @pg_fetch_assoc($res2);
        
        $ini = parse_ini_file("config/config.ini");
        $mailObjet = "Extraction N°".$idExtraction." prête";
        $mailMessage = 
"
Bonjour ".$row2['ayant_droit'].",

Votre demande n°$idExtraction est terminée.

Nous vous invitons à venir retirer la donnée auprès de l’équipe PPIGE.

Pour ce faire, merci de nous contacter soit par mail, soit par téléphone, afin de convenir d’un rendez-vous, et de vous munir, si ce n’est déjà fait, d’un disque dur ou d’un autre support pour copier vos extractions.

Cordialement,
L'équipe PPIGE
ppige@epf-npdc.fr
03 28 07 25 30 

";

    $mailMessageAdmin = 
"
Bonjour,

La PPIGE vient de terminer  une extraction en mode 'manuel'.
Il s'agit de l’extraction $idExtraction


Voici une copie du mail envoyé à l’utilisateur :

--------------------------------------------------

$mailMessage

";
    
        if(!envoieMailWithSmtp($row2['email_contact'], $mailObjet, $mailMessage, $ini['fromMail'])) {
            return array(true, "Envoi du mail à ".$row2['email_contact']." impossible.");
        } else {
            $adminMail = getAdminMail();
            envoieMailWithSmtp($adminMail, $mailObjet, $mailMessageAdmin, $ini['fromMail']);
        }
              
        return array(false, ""); 
    }
}

function updateDemande($idExtraction, $champ, $value) {
  $conn = dbConnect();
  $sendMail = false;
  
  switch($champ) {
      // case "champ_specif":
        // $set = sql specifique
      default :
        $set = $champ. "='".addslashes($value)."'";
  }

  $res = pg_query($conn, "UPDATE projet_extraction SET ".$set." WHERE id_extraction=".$idExtraction);
  if(!$res) { return array(true, pg_last_error($conn)); }
  
  return array(false, "");
}
  
function isAdminPrivilege($uid) {
    if ($uid == 1) return true;
    
    $conn2 = dbConnect3();
    $is_admin_role = "SELECT rid FROM users_roles WHERE uid = $uid ";    
    $res = @pg_query($conn2, $is_admin_role);
    while ($row = @pg_fetch_assoc($res)) {
      if ($row['rid'] == 6) {
        return true;
      }
    }
    
    return false;
}

function getTableExtraction($queryParam, $jsFuncOrderBy, $jsFuncBouton, $admin) {
  $ini = parse_ini_file("config/config.ini");

  // $drupal_session_id = $_REQUEST['sid'];
  $cur_user_id = $_REQUEST['uid'];
    
  $conn 	= dbConnect();
  
  $admin_role = isAdminPrivilege($cur_user_id);
  /*
  if ($cur_user_id == 1) {
	$admin_role = 1;
  }else {
    if($cur_user_id != ""){
        $is_admin_role = "SELECT rid FROM users_roles WHERE uid = $cur_user_id ";    
        $conn2 = dbConnect3();
        $res = @pg_query($conn2, $is_admin_role);
        while ($row = @pg_fetch_assoc($res)) {
    	  if ($row['rid'] == 6) {
    		$admin_role = 1;
    	  }
    	}
    }
  }
  */
  
  $where = ' WHERE 1=1 ';
  
  // TODO A VOIR SI UTILE !
  /*
  if (!$admin_role){
	$where .= " AND (node_profile.type = 'profile') AND (node_profile.vid = content_type_profile.vid) AND (node_profile.uid = " . $cur_user_id . ")";
  }else{
	// $where .= " AND (node_profile.type = 'profile') AND (node_profile.vid = content_type_profile.vid) ";
	$where .= "";
  }
  */
  
  // Filtre sur les demande de l'utilisateur uniquement
  if (!$admin_role){
	$queryParam['id_contact'] = $cur_user_id;
  }
  
  if(isset($queryParam['id_contact']) && $queryParam['id_contact']) {
	$where .= " AND  pe.id_contact={$queryParam['id_contact']}";
  }
  
  if(isset($queryParam['date_demande_debut']) && $queryParam['date_demande_debut']) {
	$where .=" AND TO_DATE(TO_CHAR(pe.date_demande,'DD/MM/YYYY'),'DD/MM/YYYY')>=TO_DATE('{$queryParam['date_demande_debut']}','DD/MM/YYYY')";
  }
  
  if(isset($queryParam['date_demande_fin']) && $queryParam['date_demande_fin']) {
	$where .= " AND TO_DATE(TO_CHAR(pe.date_demande,'DD/MM/YYYY'),'DD/MM/YYYY')<=TO_DATE('{$queryParam['date_demande_fin']}','DD/MM/YYYY')";
  }
  
  if(isset($queryParam['filtre'])) {
    switch($queryParam['filtre']) {
    case FILTRE_TOUTES_DEMANDES:
      $where .= " AND pe.etat_demande LIKE '%'";
      break;
    case FILTRE_ATTENTE_VALIDATION:
      $where .= " AND pe.etat_demande LIKE '".ATTENTE_VALIDATION."'";
      break;
    case FILTRE_ANNULEE:
      $where .= " AND pe.etat_demande LIKE '".ANNULEE."'";
      break;
    case FILTRE_ATTENTE_TRAITEMENT:
      $where .= " AND pe.etat_demande LIKE '".ATTENTE_TRAITEMENT."'";
      break;
    case FILTRE_TRAITEMENT_EN_COURS:
      $where .= " AND pe.etat_demande LIKE '".TRAITEMENT_EN_COURS."'";
      break;
    case FILTRE_TERMINEE:
      $where .= " AND pe.etat_demande LIKE '".TERMINEE."'";
      break;
    case FILTRE_EN_ERREUR:
      $where .= " AND pe.etat_demande LIKE '".EN_ERREUR."'";
      break;
    case FILTRE_WWW:
      $where .= " AND pe.etat_demande LIKE '".TERMINEE."' AND pe.taille_fichier<=".TAILLE_LIMITE;
      break;
    case FILTRE_DVD:
      $where .= " AND pe.etat_demande LIKE '".TERMINEE."' AND pe.taille_fichier>".TAILLE_LIMITE;
      break;
    case FILTRE_DEMANDE_DVD:
      $where .= " AND pe.etat_demande LIKE '".TERMINEE."' AND pe.demande_dvd=TRUE";
      break;
    case FILTRE_DEMANDE_DVD_NON_TRAITEE:
      $where .= " AND pe.etat_demande LIKE '".TERMINEE."' AND pe.demande_dvd=TRUE AND pe.demande_dvd_validee IS NULL";
      break;      
    case FILTRE_DEMANDE_DVD_ACCEPTEE:
      $where .= " AND pe.etat_demande LIKE '".TERMINEE."' AND pe.demande_dvd=TRUE AND pe.demande_dvd_validee=TRUE";
      break;
    case FILTRE_DEMANDE_DVD_REFUSEE:
      $where .= " AND pe.etat_demande LIKE '".TERMINEE."' AND pe.demande_dvd=TRUE AND pe.demande_dvd_validee=FALSE";
      break;
    case FILTRE_DVD_ENVOYE:
      $where .= " AND pe.etat_demande LIKE '".TERMINEE."' AND pe.demande_dvd=TRUE AND pe.demande_dvd_validee=TRUE AND pe.dvd_envoye=TRUE";
      break;
    case FILTRE_DVD_PAS_ENVOYE:
      $where .= " AND pe.etat_demande LIKE '".TERMINEE."' AND pe.demande_dvd=TRUE AND pe.demande_dvd_validee=TRUE AND (pe.dvd_envoye IS NULL OR pe.dvd_envoye=FALSE)";
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
  }

  $orderBy = '';
  if(isset($queryParam['order_by'])) { $orderBy = "{$queryParam['order_by']}"; }
  if($orderBy != '') { $orderBy = "ORDER BY $orderBy"; }
  
  $direction = "";
  if(isset($queryParam['direction'])) { $direction = $queryParam['direction']; }
      
  $jsParam = '';
  foreach($queryParam as $key => $value) {
    if($jsParam != '') { $jsParam .= ','; }
    $jsParam .= "[\"$key\",\"$value\"]";  
  }
  $jsParam = "[$jsParam]";


// requete JMA 2012 !
 $query = "SELECT ";
 $query .= "  pe.id_contact,";
 $query .= "  pe.id_organisme,";
 $query .= "  to_char(pe.date_demande, 'DD/MM/YYYY HH24:MI') AS date_demande_str, ";
 $query .= "  to_char(pe.date_fin_traitement, 'DD/MM/YYYY HH24:MI') AS date_fin_traitement_str, ";
 $query .= "  to_char(pe.date_annulation, 'DD/MM/YYYY HH24:MI') AS date_annulation_str, ";
 $query .= "  to_char(pe.date_validation, 'DD/MM/YYYY HH24:MI') AS date_validation_str, ";
 $query .= "  to_char(pe.date_debut_traitement, 'DD/MM/YYYY HH24:MI') AS date_debut_traitement_str, ";
 $query .= "  to_char(pe.date_fin_traitement, 'DD/MM/YYYY HH24:MI') AS date_fin_traitement_str, ";
 $query .= "  to_char(pe.date_suppression, 'DD/MM/YYYY HH24:MI') AS date_suppression_str, ";
 $query .= "  pe.date_dvd_envoye, ";
 $query .= "  pe.etat_demande AS etat_demande, ";
 $query .= "  pe.id_extraction AS id_extraction, ";
 $query .= "  pe.systeme_projection, ";
 $query .= "  pe.remote_addr, ";
 $query .= "  pe.message_erreur, ";
 $query .= "  pe.extent_carte, ";
 $query .= "  pe.extent_extraction, ";
 $query .= "  pe.fichier_zip, ";
 $query .= "  pe.fichier_supprimer, ";
 $query .= "  pe.format_vecteur, ";
 $query .= "  pe.format_raster, ";
 $query .= "  pe.zone_geograpique, ";
 $query .= "  pe.dossier_contact, ";
 $query .= "  pe.commune, ";
 $query .= "  pe.insee_extraction, ";
 $query .= "  pe.taille_fichier, ";
 $query .= "  pe.mode,";
 $query .= "  pe.demande_dvd,";
 $query .= "  pe.demande_dvd_validee,";
 $query .= "  pe.dvd_envoye,";
 $query .= "  pe.num_courrier_suivi,";
 $query .= "  pe.couche_vecteur, ";
 $query .= "  pe.couche_raster, ";
 $query .= "  pe.droits ";
 $query .= " FROM projet_extraction as pe ";
 $query .= " $where $orderBy $direction";
 
  // echo "==>".$query ;
  
  $table = "";
  $table .= "<table border=0 cellpadding=0 cellspacing=1 class='pmeTableExtract'>";
/*
if($admin) {
  $table .= "<tr>";
  $table .= "<td colspan=6>".$query."</td>";
  $table .= "</tr>";
}
*/
  $table .= "<tr>";
  $table .= "<td class='pmeTitre pmeCellPM'>&nbsp;</td>";
  $table .= "<td class='pmeTitre pmeCellId'><a href='#' onclick='$jsFuncOrderBy($jsParam, \"id_extraction\");'>N°</a></td>";
  $table .= "<td class='pmeTitre pmeCellDate'><a href='#' onclick='$jsFuncOrderBy($jsParam, \"date_demande\");'>Date demande</a></td>";
  $table .= "<td class='pmeTitre pmeCellDate'><a href='#' onclick='$jsFuncOrderBy($jsParam, \"date_fin_traitement\");'>Date de fin de traitement</a></td>";
  $table .= "<td class='pmeTitre pmeCellEtat'><a href='#' onclick='$jsFuncOrderBy($jsParam, \"etat_demande\");'>Etat extraction</a></td>";
  $table .= "<td class='pmeTitre pmeCellOrganisme'><a href='#' onclick='$jsFuncOrderBy($jsParam, \"libelle_organisme\");'>Organisme</a></td>";
  $table .= "<td class='pmeTitre pmeCellContact'><a href='#' onclick='$jsFuncOrderBy($jsParam, \"(projet_contact.nom_contact||chr(32)||projet_contact.prenom_contact)\");'>Contact</a></td>";
  $table .= "</tr>";
  $table .= "<tr>";
  $table .= "<td colspan=7></td>";
  $table .= "</tr>";

  $conn = dbConnect();
  $res = pg_query($conn, $query);
  
  // JMA -------------------
  // recup la liste de tous les users
  $users_id = @pg_fetch_all_columns($res, 0);
  
  if( is_array($users_id) ) {
    //Récup les infos des contacts
    $sql2 = "SELECT  node.title as libelle_organisme, realname.realname as ayant_droit, field_orga_adresse_value as adresse_organisme, field_orga_code_postal_value as codepostal_organisme, field_orga_ville_value as ville_organisme, users.mail as email_contact, users.uid as user_id, field_data_field_organisme.field_organisme_target_id as org_id
                    FROM users 
                    LEFT JOIN field_data_field_organisme ON users.uid = field_data_field_organisme.entity_id
                    LEFT JOIN field_data_field_orga_ville ON field_data_field_organisme.field_organisme_target_id = field_data_field_orga_ville.entity_id
                    LEFT JOIN field_data_field_orga_code_postal ON field_data_field_organisme.field_organisme_target_id = field_data_field_orga_code_postal.entity_id
                    LEFT JOIN field_data_field_orga_adresse ON field_data_field_organisme.field_organisme_target_id = field_data_field_orga_adresse.entity_id
                    LEFT JOIN node ON node.nid = field_data_field_organisme.field_organisme_target_id
                    LEFT JOIN realname ON users.uid = realname.uid 
                    WHERE users.uid in (".implode(",", $users_id).")";             
    $conn2 = dbConnect3();
    $res2 = @pg_query($conn2, $sql2);
    
    $rows2 = array();
    $rows2raw = @pg_fetch_all($res2);
    if(is_array($rows2raw)){
        foreach($rows2raw as $row2) {
            $rows2[$row2['user_id']] = $row2;
        }
    }
    
    // printr($rows2);
  }
  // ---
        
  $_SESSION["query_extraction"] = $query ;
  $_SESSION["query_extraction_users"] = $rows2 ;
  
  
  while ($row = @pg_fetch_assoc($res)) {

    // Union des infos sur user et organisme aux infos de la demande d'extraction
    $urow = $rows2[$row['id_contact']];
    if( (is_array($row) && count($row) > 0) && (is_array($urow) && count($urow) > 0) ) {
        $row += $urow;
    }
    //
    
    $table .= "<tr>";
    $table .= "<td valign='top' class='pmeItem pmeCellPM'><a href='#' id='aExt{$row['id_extraction']}' onclick='altExt({$row['id_extraction']});'>+</a></td>";
    $table .= "<td valign='top' class='pmeItem pmeCellId'>{$row['id_extraction']}</td>";
    $table .= "<td valign='top' class='pmeItem pmeCellDate'>{$row['date_demande_str']}</td>";
    $table .= "<td valign='top' class='pmeItem pmeCellDate'>{$row['date_fin_traitement_str']}</td>";
    $table .= "<td valign='top' class='pmeItem pmeCellEtat'>".getEtatDemandeLibelle($row['etat_demande'], $row['attente_raster'])."</td>";
    $table .= "<td valign='top' class='pmeItem pmeCellOrganisme'>".($row['libelle_organisme'])."</td>";
    $table .= "<td valign='top' class='pmeItem pmeCellContact'>".($row['ayant_droit'])."</td>";
    $table .= "</tr>";
    
    $table .= "<tr>";
    $table .= "<td colspan=7>";
    $table .= "<div id='divExt{$row['id_extraction']}' style='display:none;'>";

    switch($row['etat_demande']) {
        case ATTENTE_VALIDATION:
          $table .= getTableExtractAttenteValidation($row, $jsFuncBouton, $admin,$admin_role);
          break;
        case ANNULEE:
          $table .= getTableExtractAnnulee($row, $jsFuncBouton, $admin,$admin_role);
          break;      
        case ATTENTE_TRAITEMENT:
          $table .= getTableExtractAttenteTraitement($row, $jsFuncBouton, $admin,$admin_role);
          break;      
        case TRAITEMENT_EN_COURS:
          $table .= getTableExtractTraitementEnCours($row, $jsFuncBouton, $admin,$admin_role);
          break; 
        case TERMINEE:
          $table .= getTableExtractTerminee($row, $jsFuncBouton, $admin, $ini,$admin_role);
          break;
        case EN_ERREUR:
          $table .= getTableExtractEnErreur($row, $jsFuncBouton, $admin,$admin_role);
          break;
          case ERREUR_ORGANISME:
            $table .= getTableExtractAttenteTraitement($row, $jsFuncBouton, $admin,$admin_role);
            break;
        case EMPRISE_HORS_TC:
            $table .= getTableExtractAttenteTraitement($row, $jsFuncBouton, $admin,$admin_role);
            break;

    }

    $table .= "</div>";
    $table .= "</td>";
    $table .= "</tr>";
  }
  //dbClose($conn);
  $table .= "</table>";
  
  return $table;
}

// function getTrTitreCarte($row) {
  // return "<tr><td valign='top' class='pmeTdDetailExtract'>Carte</td><td valign='top'>:</td><td class='pmeTdDetailExtract'><input type='text' class='pmeTxtTitreCarte' value='".htmlentities($row['titre_carte'], ENT_QUOTES)."' READONLY></td></tr>";
// }

function getTrZoneGeo($row) {
  return "<tr><td valign='top' class='pmeTdDetailExtract'>Zone géographique</td><td valign='top'>:</td><td class='pmeTdDetailExtract'><input type='text' class='pmeTxtZoneGeo' value='".htmlentities(getZoneGeoLibelle($row['zone_geograpique']), ENT_QUOTES)."' READONLY></td></tr>";
}
function getTrDroits($row) {
  return "<tr><td valign='top' class='pmeTdDetailExtract'>Droits</td><td valign='top'>:</td><td class='pmeTdDetailExtract'><input type='text' class='pmeTxtZoneGeo' value='".htmlentities($row['droits'], ENT_QUOTES)."' READONLY></td></tr>";
}

function getTrCommune($row) {
    
  /*echo "<pre>";  
  print_r($row);
  echo "</pre>";*/
  
  $str_commune = '';
  if($row['zone_geograpique'] == "etendue") {
    /*$commune = unserialize($row['commune']);
    for($i=0; $i<count($commune); $i++) {
      if($str_commune != '') { $str_commune .= ", "; }
      $str_commune .= $commune[$i];
    }*/
    $str_commune = $row['commune'] ;
  }else {
    $str_commune = "Territoire de compétence complet";
  }
  //return "<tr><td valign='top' class='pmeTdDetailExtract'>Communes</td><td valign='top'>:</td><td class='pmeTdDetailExtract'><textarea class='pmeTxtComm' READONLY>".htmlentities(utf8_decode($str_commune), ENT_QUOTES)."</textarea></td></tr>";
  return "<tr><td valign='top' class='pmeTdDetailExtract'>Communes</td><td valign='top'>:</td><td class='pmeTdDetailExtract'><textarea class='pmeTxtComm' READONLY>".$str_commune."</textarea></td></tr>";
}

function getTrProjection($row) {
  return "<tr><td valign='top' class='pmeTdDetailExtract'>Projection</td><td valign='top'>:</td><td class='pmeTdDetailExtract'><input type='text' class='pmeTxtProj' value='".htmlentities($row['systeme_projection'], ENT_QUOTES)."' READONLY></td></tr>";
}

function getTrFormatVecteur($row) {
  return "<tr><td valign='top' class='pmeTdDetailExtract'>Format vecteur</td><td valign='top'>:</td><td class='pmeTdDetailExtract'><input type='text' class='pmeTxtFormat' value='".htmlentities($row['format_vecteur'], ENT_QUOTES)."' READONLY></td></tr>";
}

function getTrFormatRaster($row) {
  return "<tr><td valign='top' class='pmeTdDetailExtract'>Format raster</td><td valign='top'>:</td><td class='pmeTdDetailExtract'><input type='text' class='pmeTxtFormat' value='".htmlentities($row['format_raster'], ENT_QUOTES)."' READONLY></td></tr>";
}

function getTrBaseVecteur($row) {
  $base = array();
  $couche = unserialize(utf8_encode($row['couche_vecteur']));
  // $cc = print_r($couche, true);
  
  for($i=0; $i<count($couche); $i++) {
    if(!in_array(utf8_decode($couche[$i]['group']), $base)) { $base[] = utf8_decode($couche[$i]['group']); }
  }
  $base = array_unique($base);
  return "<tr><td valign='top' class='pmeTdDetailExtract'>Bases vecteur</td><td valign='top'>:</td><td class='pmeTdDetailExtract'><textarea class='pmeTxtBase' READONLY>".htmlentities(join(", ", $base), ENT_QUOTES)."</textarea></td></tr>";
}

function getTrBaseRaster($row) {
  $base = array();
  $couche = unserialize(utf8_encode($row['couche_raster']));
  for($i=0; $i<count($couche); $i++) {
    if(!in_array($couche[$i]['name'], $base)) { $base[] = $couche[$i]['name']; }
  }
  return "<tr><td valign='top' class='pmeTdDetailExtract'>Bases raster</td><td valign='top'>:</td><td class='pmeTdDetailExtract'><textarea class='pmeTxtBase' READONLY>".htmlentities(join(", ", utf8_decode($base)), ENT_QUOTES)."</textarea></td></tr>";
}

function getTrGroupVecteur($row) {
  $group = array();
  $couche=array();
  $couche = unserialize(utf8_encode($row['couche_vecteur']));
  
  for($i=0; $i<count($couche); $i++) {
    if(!in_array($couche[$i]['description'], $group)) { $group[] = ($couche[$i]['description'] != "") ? $couche[$i]['description'] : $couche[$i]['name']; }
  }
  return "<tr><td valign='top' class='pmeTdDetailExtract'>Thèmes vecteur</td><td valign='top'>:</td><td class='pmeTdDetailExtract'><textarea class='pmeTxtGroup' READONLY>".utf8_decode(join(", ", $group))."</textarea></td></tr>";
}

function getTrGroupRaster($row) {
  $group = array();
  $couche = unserialize(utf8_encode($row['couche_raster']));
  for($i=0; $i<count($couche); $i++) {
    if(!in_array($couche[$i]['name'], $group)) { $group[] = $couche[$i]['name']; }
  }
  return "<tr><td valign='top' class='pmeTdDetailExtract'>Thèmes raster</td><td valign='top'>:</td><td class='pmeTdDetailExtract'><textarea class='pmeTxtGroup' READONLY>".htmlentities(utf8_decode(join(", ", $group)), ENT_QUOTES)."</textarea></td></tr>";
}


function getTrBoutons($row, $jsFuncBouton, $validation, $annulation, $duplication, $suppressionFichier, $isAdmin=false, $edition=true) {  
  $boutons = array();
  $message = false;
  if($validation && $isAdmin) {
  
    // Test si demande d'extraction trop grande : bloque la validation en mode automatique
	if($row["couche_raster"]) {
		$ini = parse_ini_file("config/config.ini");
		$extent = unserialize($row["extent_extraction"]);
		$width = $extent[2] - $extent[0];
		$height = $extent[3] - $extent[1];
		$surface = $width * $height;
        
        // Testera la surface
        $maxArea = $ini["maxArea"];

        // Teste la couche : filtre ne concerne que quelques couches (ortho, parcellaire, france_raster)
        $isLayerToCheck = false;
        $layerToCheck = explode(",", $ini["layerToCheck"]);
        foreach($layerToCheck as $lyr) {
            if(wildcard_match($lyr, $row["couche_raster"])){
                $isLayerToCheck = true;
                break;
            }
        }
        
		if($isLayerToCheck && ($surface >= $maxArea) ) {
			$message = "<span style=\"color:red;\">Extraction automatique désactivée (la superficie dépasse ".($maxArea / 1000000)." km²)</span>";
		} else {
			$boutons[] = "<a href='#' class='pmeBouton' onclick='actExt({$row['id_extraction']}, \"".VALIDER_EXTRACTION."\", \"$jsFuncBouton\");'>Valider l'extraction</a>"; 
		}		
	} else {
		$boutons[] = "<a href='#' class='pmeBouton' onclick='actExt({$row['id_extraction']}, \"".VALIDER_EXTRACTION."\", \"$jsFuncBouton\");'>Valider l'extraction</a>"; 
	}
    $boutons[] = "<a href='#' class='pmeBouton' onclick='actExt({$row['id_extraction']}, \"".VALIDER_EXTRACTION_MANUEL."\", \"$jsFuncBouton\");'>Valider comme extraction Manuelle</a>"; 
  }
  
  // modif Lds 12/03/01: ajout bouton pour editer l'extraction
  // if($edition && $isAdmin) { $boutons[] = "<a href='#' class='pmeBouton' onclick='openExtractWindow(\"edit_extract.php?id={$row['id_extraction']}\",\"\",1050,600)'>Editer l'extraction</a>"; }
  if($annulation) { $boutons[] = "<a href='#' class='pmeBouton' onclick='actExt({$row['id_extraction']}, \"".ANNULER_EXTRACTION."\", \"$jsFuncBouton\");'>Annuler l'extraction</a>"; }
  if($duplication) { $boutons[] = "<a href='#' class='pmeBouton' onclick='actExt({$row['id_extraction']}, \"".DUPLIQUER_EXTRACTION."\", \"$jsFuncBouton\");'>Dupliquer l'extraction</a>"; }
  if($suppressionFichier) { $boutons[] = "<a href='#' class='pmeBouton' onclick='actExt({$row['id_extraction']}, \"".SUPPRIMER_FICHIER_EXTRACTION."\", \"$jsFuncBouton\");'>Supprimer le(s) fichier(s)</a>"; }
  
  // modif Jck 11/08/22 pour ajouter un bouton terminer extraction pour les modes manuels
  if(is_array($row) && isset($row['mode']) ) { 
      $etat_demande = $row['etat_demande'];
      $mode = $row['mode'];
      if($mode == "manuel" && $etat_demande != TERMINEE) {
          $boutons[] = "<a href='#' class='pmeBouton' onclick='actExt({$row['id_extraction']}, \"".TERMINER_EXTRACTION."\", \"$jsFuncBouton\");'>Terminer Extraction</a>"; 
      }
  }
  
  $return = "";
  if($message) {
	$return .= "<tr class='pmeTrDetailExtractBtn'><td td colspan=3 class='pmeTdDetailExtract'>$message</td></tr>";
  }
  
  if(count($boutons)) { 
	$return .= "<tr class='pmeTrDetailExtractBtn'><td colspan=3 class='pmeTdDetailExtract'>".join("<div style='display:inline; margin-left:10px;'></div>", $boutons)."</td></tr>"; 
  } 
  return $return;
}

function getTrDateAnnulation($row) {
  return "<tr><td valign='top' class='pmeTdDetailExtract'>Annulée le</td><td valign='top'>:</td><td class='pmeTdDetailExtract'><input type='text' class='pmeTxtFormat' value='".htmlentities($row['date_annulation_str'], ENT_QUOTES)."' READONLY></td></tr>";
}

function getTrDateValidation($row) {
  return "<tr><td valign='top' class='pmeTdDetailExtract'>Validée le</td><td valign='top'>:</td><td class='pmeTdDetailExtract'><input type='text' class='pmeTxtFormat' value='".htmlentities($row['date_validation_str'], ENT_QUOTES)."' READONLY></td></tr>";
}

function getTrDateDebutTraitement($row) {
  return "<tr><td valign='top' class='pmeTdDetailExtract'>Débuté le</td><td valign='top'>:</td><td class='pmeTdDetailExtract'><input type='text' class='pmeTxtFormat' value='".htmlentities($row['date_debut_traitement_str'], ENT_QUOTES)."' READONLY></td></tr>";
}

function getTrDateFinTraitement($row) {
  return "<tr><td valign='top' class='pmeTdDetailExtract'>Terminée le</td><td valign='top'>:</td><td class='pmeTdDetailExtract'><input type='text' class='pmeTxtFormat' value='".htmlentities($row['date_fin_traitement_str'], ENT_QUOTES)."' READONLY></td></tr>";
}

function getTrMsgErreur($row, $admin) {
  if($admin) { return "<tr><td valign='top' class='pmeTdDetailExtract'>Erreur</td><td valign='top'>:</td><td class='pmeTdDetailExtract'><textarea class='pmeTxtError' READONLY>".htmlentities($row['message_erreur'], ENT_QUOTES)."</textarea></td></tr>"; }
  return "";
}

function getTrFichier($row, $jsFuncBouton, $admin, $ini) {
    
  /*echo "<pre>";  
  print_r($row);*/
 
  // OpenID
  $cur_user_id = $_REQUEST['uid'];
  $openid_identifier = "?openid_identifier=http://".$_SERVER['HTTP_HOST']."/portail/user/".$cur_user_id."/identity";
  //  
  
  $inf = pathinfo($row['dossier_contact']);
  /*echo "<pre>";  
  print_r($inf);*/
  // $ftp = formatPath($ini['ftpDownloadPW'], $inf['basename']);
  $ftp = formatPath($ini['ftpDownloadPW'], "");
  $http = formatPath($ini['httpDownload'], $inf['basename']);  

  if($row['fichier_supprimer'] == "t") {
      $buffer = "Fichiers supprimés le ".$row['date_suppression_str'];
      return "<tr><td valign='top' class='pmeTdDetailExtract'>Fichier(s)</td><td valign='top'>:</td><td class='pmeTdDetailExtract'><div class='pmeDroite'>".$buffer."</div></td></tr>";
  } 
  
  $arrZip = unserialize($row['fichier_zip']);
  $buffer = "";
  if(!is_array($arrZip)) return "<tr><td></td></tr>" ;
  foreach($arrZip as $zip) {
    if($admin) {
      $infadmin = pathinfo($zip['nom']);
      $buffer .= "<div><input type='text' class='pmeTxtFic' value='".htmlentities($zip['nom'], ENT_QUOTES)."' READONLY>&nbsp;&nbsp;".htmlentities("(".format_sizes($zip['taille'], 2).")", ENT_QUOTES);
    } else {
      $inf = pathinfo($zip['nom']);
      $buffer .= "<div><input type='text' class='pmeTxtSmallFic' value='".htmlentities($inf['basename'], ENT_QUOTES)."' READONLY>&nbsp;&nbsp;".htmlentities("(".format_sizes($zip['taille'], 2).")", ENT_QUOTES);
    }
    if($row['taille_fichier'] <= TAILLE_LIMITE) { // 50Mo
      $fileTar = basename($zip['nom']) ;
      /*$buffer .= "<div style='display:inline; margin-left:10px;'></div><a href='".formatPath($ftp, $inf['basename'])."' class='pmeBouton' title='Cliquez ici pour télécharger le fichier en FTP'>FTP</a>";
      $buffer .= "<div style='display:inline; margin-left:10px;'></div><a href='".formatPath($http, $inf['basename'])."' class='pmeBouton' title='Cliquez ici pour télécharger le fichier en HTTP'>HTTP</a>";*/
      $buffer .= "<div style='display:inline; margin-left:10px;'></div><a href='".formatPath($ftp, $fileTar)."' class='pmeBouton' title='Cliquez ici pour télécharger le fichier en FTP'>FTP</a>";
      $buffer .= "<div style='display:inline; margin-left:10px;'></div><a href='".formatPath($http, $fileTar).$openid_identifier."' class='pmeBouton' title='Cliquez ici pour télécharger le fichier en HTTP'>HTTP</a>";
    }
    elseif($admin) {
      $fileTar = basename($zip['nom']) ;
      /*$buffer .= "<div style='display:inline; margin-left:10px;'></div><a href='".formatPath($ftp, $infadmin['basename'])."' class='pmeBouton' title='Cliquez ici pour télécharger le fichier en FTP'>FTP</a>";
      $buffer .= "<div style='display:inline; margin-left:10px;'></div><a href='".formatPath($http, $infadmin['basename'])."' class='pmeBouton' title='Cliquez ici pour télécharger le fichier en HTTP'>HTTP</a>";*/
      $buffer .= "<div style='display:inline; margin-left:10px;'></div><a href='".formatPath($ftp, $fileTar)."' class='pmeBouton' title='Cliquez ici pour télécharger le fichier en FTP'>FTP</a>";
      $buffer .= "<div style='display:inline; margin-left:10px;'></div><a href='".formatPath($http, $fileTar).$openid_identifier."' class='pmeBouton' title='Cliquez ici pour télécharger le fichier en HTTP'>HTTP</a>";
    }
    $buffer .= "</div>";
  }
  return "<tr><td valign='top' class='pmeTdDetailExtract'>Fichier(s)</td><td valign='top'>:</td><td class='pmeTdDetailExtract'><div class='pmeDroite'>".$buffer."</div></td></tr>";
}

function getTrTailleFichier($row, $admin, $isAdmin=false) {
  if($row['fichier_supprimer'] == "t") return '';
  
  // JMA Mode manuel : saisie de la taille
  $buffer = "<tr><td valign='top' class='pmeTdDetailExtract'>Taille totale</td><td valign='top'>:</td><td class='pmeTdDetailExtract'>";
  if($isAdmin && $row['mode'] == "manuel"){
        $buffer .= "<input type='text' id='pmeTxtFormat".$row['id_extraction']."' class='pmeTxtDateEnvoiDVD' onchange='updateTaille(".$row['id_extraction'].", \"taille_fichier\", this);' value='".htmlentities($row['taille_fichier'], ENT_QUOTES)."'> (octets)";
        $buffer .= "<input type='text' class='pmeTxtFormat' value='".htmlentities(format_sizes($row['taille_fichier'], 2), ENT_QUOTES)."' READONLY>";
  } else {
        $buffer .= "<input type='text' class='pmeTxtFormat' value='".htmlentities(format_sizes($row['taille_fichier'], 2), ENT_QUOTES)."' READONLY>";
  }
  $buffer .= "</td></tr>";
  return $buffer;
}

function getTrDemandeDVD($row, $admin, $isAdmin=false) {
  $fichierSupp = ($row['fichier_supprimer'] == "t");
  $mode = $row['mode'];
  
  // Modif 10/01/2009  GEOSIGNAL BAS
  $fichierSupp = false;
  
  $buffer  = "<select class='pmeSelectOuiNon' onchange='altDvd(".$row['id_extraction'].",this,\"".$row['demande_dvd']."\",".$admin.");'".((!$admin && $row['demande_dvd_validee']) || $fichierSupp ? "" : "")."><option value='' ".(!$row['demande_dvd'] ? "selected" : "").">--</option><option value='FALSE' ".($row['demande_dvd'] == "f" ? "selected" : "").">non</option><option value='TRUE' ".($row['demande_dvd'] == "t" ? "selected" : "").">oui</option></select>";
  $buffer .= "<div id='pmeDVD".$row['id_extraction']."' style='display:".($row['demande_dvd'] == "t" ? "inline" : "none").";'>";
  if($admin || ($row['demande_dvd'] == "t" && $row['demande_dvd_validee'])) {
    $buffer .= "<div style='display:inline; margin-left:5px;'></div>";
    if($isAdmin)$buffer .= "acceptée <select id='pmeDemDVDValid".$row['id_extraction']."' class='pmeSelectOuiNon' onchange='altDvd2(".$row['id_extraction'].",this);'".(!$admin || $fichierSupp ? "" : "")."><option value='' ".(!$row['demande_dvd_validee'] ? "selected" : "").">--</option><option value='FALSE'".($row['demande_dvd_validee'] == "f" ? "selected" : "").">non</option><option value='TRUE'".($row['demande_dvd_validee'] == "t" ? "selected" : "").">oui</option></select>";
    $buffer .= "<div id='pmeDVD2".$row['id_extraction']."' style='display:".($row['demande_dvd_validee'] == "t" ? "inline" : "none").";'>";          
    if($admin || $row['demande_dvd_validee'] == "t") {
      $label_dvd_envoye = "envoyée ";
      if($mode == "manuel") $label_dvd_envoye = "remis ";
      $buffer .= "<div style='display:inline; margin-left:5px;'></div>";
      $buffer .= "$label_dvd_envoye <select id='pmeDemDVDEnv".$row['id_extraction']."' class='pmeSelectOuiNon' onchange='altDvd3(".$row['id_extraction'].",this);'".(!$admin || $fichierSupp ? "" : "")."><option value='' ".(!$row['dvd_envoye'] ? "selected" : "").">--</option><option value='FALSE'".($row['dvd_envoye'] == "f" ? "selected" : "").">non</option><option value='TRUE'".($row['dvd_envoye'] == "t" ? "selected" : "").">oui</option></select>";
      $buffer .= "<div id='pmeDVD3".$row['id_extraction']."' style='display:".($row['dvd_envoye'] == "t" ? "inline" : "none").";'>";
      if($admin || $row['dvd_envoye'] == "t") {
        $label_date_dvd_envoye = "date d'envoi ";
        if($mode == "manuel") $label_date_dvd_envoye = "date de remise ";
        $buffer .= "<div style='margin-left:5px;'></div>";
        $buffer .= "$label_date_dvd_envoye <input type='texte' id='pmeDemDVDDate".$row['id_extraction']."' class='pmeTxtDateEnvoiDVD' onchange='updateDemandeDvd(".$row['id_extraction'].", \"date_dvd_envoye\", this);' value='".htmlentities($row['date_dvd_envoye'], ENT_QUOTES)."' maxlength=10".(!$admin || $fichierSupp ? " readonly" : "").">";
        if($mode != "manuel"){
            $buffer .= "<div style='margin-left:5px;'></div>";
            $buffer .= "courrier n° &nbsp;&nbsp;&nbsp;&nbsp;<input type='texte' id='pmeDemDVDSuivi".$row['id_extraction']."' class='pmeTxtNumCourrir' onchange='updateDemandeDvd(".$row['id_extraction'].", \"num_courrier_suivi\", this);' value='".htmlentities($row['num_courrier_suivi'], ENT_QUOTES)."'".(!$admin || $fichierSupp ? " readonly" : "").">";
        }
      }
      $buffer .= "</div>"; // pmeDVD3
    }
    $buffer .= "</div>"; // pmeDVD2
  }
  $buffer .= "</div>"; // pmeDVD  
  return "<tr><td valign='top' class='pmeTdDetailExtract'>Demande de support physique</td><td valign='top'>:</td><td class='pmeTdDetailExtract'><div class='pmeDroite'>".$buffer."</div></td></tr>";
}

function getTrMode($row) {
  if($row['mode'] == "auto" || $row['mode'] == ""){
    $mode = "automatique";
  }else if(stristr($row['mode'], 'dalle')) {
    $mode = "traitement par dalles";
  }else {
    $mode = "manuel";
  }
  return "<tr><td valign='top' class='pmeTdDetailExtract'>Mode extraction</td><td valign='top'>:</td><td class='pmeTdDetailExtract'><input type='text' class='pmeTxtZoneGeo' value='".$mode."' READONLY></td></tr>";
}

function getTrEmailContact($row) {
  return "<tr><td valign='top' class='pmeTdDetailExtract'>Email</td><td valign='top'>:</td><td class='pmeTdDetailExtract'><input type='text' class='pmeTxtEmail' value='".htmlentities($row['email_contact'], ENT_QUOTES)."' READONLY></td></tr>";
}

function getTrAdresseOrganisme($row) {
  $buffer = "";
  if($row['adresse_organisme']) { $buffer .= htmlentities(trim($row['adresse_organisme']), ENT_QUOTES)."<br>"; }
  if($row['adresse2_organisme']) { $buffer .= htmlentities(trim($row['adresse2_organisme']), ENT_QUOTES)."<br>"; }
  if($row['adresse3_organisme']) { $buffer .= htmlentities(trim($row['adresse3_organisme']), ENT_QUOTES)."<br>"; }
  if($row['codepostal_organisme']) { $buffer .= htmlentities(trim($row['codepostal_organisme']), ENT_QUOTES)."<br>"; }
  if($row['ville_organisme']) { $buffer .= htmlentities(trim($row['ville_organisme']), ENT_QUOTES)."\n"; }
  return "<tr><td valign='top' class='pmeTdDetailExtract'>Adresse</td><td valign='top'>:</td><td class='pmeTdDetailExtract'><textarea class='pmeTxtAddresse' rows=4 READONLY>".$buffer."</textarea></td></tr>";
}


function getTableExtractTerminee($row, $jsFuncBouton, $admin, $ini, $admin_role) {
  $table = "<table border=0 cellpadding=1 cellspacing=0 class='pmeTableDetailExtract'>";
  $table .= getTrDateValidation($row);
  $table .= getTrDateDebutTraitement($row);
  $table .= getTrDateFinTraitement($row);
  $table .= getTrFichier($row, $jsFuncBouton, $admin, $ini);
  $table .= getTrTailleFichier($row, $admin, $admin_role);
  $table .= getTrDemandeDVD($row, $admin, $admin_role);
  $table .= getTrZoneGeo($row);
  $table .= getTrDroits($row);
  // $table .= getTrTitreCarte($row);
  $table .= getTrBaseVecteur($row);
  $table .= getTrGroupVecteur($row);
  $table .= getTrGroupRaster($row);
  $table .= getTrFormatVecteur($row);
  $table .= getTrFormatRaster($row);
  $table .= getTrProjection($row);    
  $table .= getTrCommune($row);
  $table .= getTrEmailContact($row);
  $table .= getTrAdresseOrganisme($row);
  $table .= getTrMode($row) ;
  $table .= getTrBoutons($row, $jsFuncBouton, false, false, $admin, !$row['fichier_supprimer'],$admin_role);
  $table .= "</table>";
  return $table;
}

function getTableExtractEnErreur($row, $jsFuncBouton, $admin, $admin_role) {
  $table = "<table border=0 cellpadding=1 cellspacing=0 class='pmeTableDetailExtract'>";
  $table .= getTrDateValidation($row);
  $table .= getTrDateDebutTraitement($row);
  $table .= getTrDateFinTraitement($row);
  $table .= getTrZoneGeo($row);
  $table .= getTrDroits($row);
  // $table .= getTrTitreCarte($row);
  $table .= getTrGroupVecteur($row);
  $table .= getTrGroupRaster($row);
  $table .= getTrFormatVecteur($row);
  $table .= getTrFormatRaster($row);
  $table .= getTrProjection($row);    
  $table .= getTrCommune($row);
  $table .= getTrMsgErreur($row, $admin);
  $table .= getTrEmailContact($row);
  $table .= getTrAdresseOrganisme($row);
  $table .= getTrMode($row) ;
  $table .= getTrBoutons($row, $jsFuncBouton, false, false, $admin, $false, $admin_role);
  $table .= "</table>";
  return $table;
}

function getTableExtractTraitementEnCours($row, $jsFuncBouton, $admin,$admin_role) {
  $table = "<table border=0 cellpadding=1 cellspacing=0 class='pmeTableDetailExtract'>";
  $table .= getTrDateValidation($row);
  $table .= getTrDateDebutTraitement($row);
  $table .= getTrZoneGeo($row);
  $table .= getTrDroits($row);
  // $table .= getTrTitreCarte($row);
  $table .= getTrGroupVecteur($row);
  $table .= getTrGroupRaster($row);
  $table .= getTrFormatVecteur($row);
  $table .= getTrFormatRaster($row);
  $table .= getTrProjection($row);    
  $table .= getTrCommune($row);
  $table .= getTrEmailContact($row);
  $table .= getTrAdresseOrganisme($row);
  $table .= getTrMode($row) ;
  $table .= getTrBoutons($row, $jsFuncBouton, false, false, false, false,$admin_role);
  $table .= "</table>";
  return $table;
}

function getTableExtractAttenteTraitement($row, $jsFuncBouton, $admin, $admin_role) {
  $table = "<table border=0 cellpadding=1 cellspacing=0 class='pmeTableDetailExtract'>";
  $table .= getTrDateValidation($row);
  $table .= getTrZoneGeo($row);
  $table .= getTrDroits($row);
  // $table .= getTrTitreCarte($row);
  $table .= getTrGroupVecteur($row);
  $table .= getTrGroupRaster($row);
  $table .= getTrFormatVecteur($row);
  $table .= getTrFormatRaster($row);
  $table .= getTrProjection($row);    
  $table .= getTrCommune($row);
  $table .= getTrEmailContact($row);
  $table .= getTrAdresseOrganisme($row);
  $table .= getTrMode($row) ;
  // $table .= getTrBoutons($row, $jsFuncBouton, $admin, true, false, false,$admin_role);
  $table .= getTrBoutons($row, $jsFuncBouton, false, true, false, false,$admin_role);
  $table .= "</table>";
  return $table;
}

function getTableExtractAnnulee($row, $jsFuncBouton, $admin, $admin_role) {
  $table = "<table border=0 cellpadding=1 cellspacing=0 class='pmeTableDetailExtract'>";
  $table .= getTrDateAnnulation($row);
  $table .= getTrZoneGeo($row);
  $table .= getTrDroits($row);
  // $table .= getTrTitreCarte($row);
  $table .= getTrGroupVecteur($row);
  $table .= getTrGroupRaster($row);
  $table .= getTrFormatVecteur($row);
  $table .= getTrFormatRaster($row);
  $table .= getTrProjection($row);    
  $table .= getTrCommune($row);
  $table .= getTrEmailContact($row);
  $table .= getTrAdresseOrganisme($row);
  $table .= getTrMode($row) ;
  $table .= getTrBoutons($row, $jsFuncBouton, $admin, false, false, false, $admin_role);
  $table .= "</table>";
  return $table;
}

function getTableExtractAttenteValidation($row, $jsFuncBouton, $admin,$admin_role) {
  $table = "<table border=0 cellpadding=1 cellspacing=0 class='pmeTableDetailExtract'>";
  $table .= getTrZoneGeo($row);
  $table .= getTrDroits($row);
  // $table .= getTrTitreCarte($row);
  $table .= getTrGroupVecteur($row);
  $table .= getTrGroupRaster($row);
  $table .= getTrFormatVecteur($row);
  $table .= getTrFormatRaster($row);
  $table .= getTrProjection($row);    
  $table .= getTrCommune($row);
  $table .= getTrEmailContact($row);
  $table .= getTrAdresseOrganisme($row);
  $table .= getTrMode($row) ;
  $table .= getTrBoutons($row, $jsFuncBouton, $admin, true, false, false,$admin_role);
  $table .= "</table>";
  return $table;
}

function getFormatLibelle($format) {
  return $format ;
  /*switch($format) {
  case "shp":      
    return "Shapefile ESRI";
  case "mif":      
    return "MapInfo MIF/MID";
  case "tiff":
    return "GéoTiff";
  case "ecw":
    return "ECW";
  }
  return "?";*/
}

function getProjectionLibelle($projection) {
  return $projection ;
  /*switch($projection) {
  case "Lambert II etendu":      
    return "Lambert II étendu";
  case "Lambert zone I":      
    return "Lambert zone I";
  case "Lambert I carto":
    return "Lambert I carto";
  case "Lambert 93":
    return "Lambert 93";
  }
  return "?";*/
}

function getZoneGeoLibelle($zoneGeo) {
  switch($zoneGeo) {
  case "etendue":      
    return "partielle";
  case "totalite":      
    return "complète";
  }
  return "?";
}

function getEtatDemandeLibelle($etatDemande, $attenteRaster="") {
  switch($etatDemande) {
  case ATTENTE_VALIDATION:      
    return "En attente de validation";
  case ANNULEE:      
    return "Extraction annulée";
  case ATTENTE_TRAITEMENT:      
    return "En attente de traitement";
  case TRAITEMENT_EN_COURS:      
    return "En cours de traitement";
  case TERMINEE:      
    return "Extraction terminée";
  case EN_ERREUR:
    return "Extraction en erreur";
  case ERREUR_ORGANISME:
    return "Organisme non lié";
  case EMPRISE_HORS_TC:
    return "Emprise hors TC";
  }
  return "?";
}


function getAdminExtractValue($name, $defValue = NULL) {
  $conn = dbConnect();
  $res = pg_query($conn, "SELECT value FROM projet_param_extraction WHERE name='".addslashes($name)."'");
  $row = pg_fetch_assoc($res);
  $value = ($row ? $row['value'] : $defValue);
  //dbClose($conn);
  return $value;
}

function setAdminExtractValue($name, $value) {
  $conn = dbConnect();
  $res = pg_query($conn, "SELECT count(*) AS param_existe FROM projet_param_extraction WHERE name='".addslashes($name)."'");
  $row = pg_fetch_assoc($res);
  if($row['param_existe'] == 0) {
    $query = "INSERT INTO projet_param_extraction (name,value) VALUES ('".addslashes($name)."','".addslashes($value)."')";
  } else {
    $query = "UPDATE projet_param_extraction SET value='".addslashes($value)."' WHERE name='".addslashes($name)."'";  
  }
	$res = pg_query($conn, $query);
	if(!$res) { return array(true, pg_last_error($conn)); }
  //dbClose($conn);
  return array(false,"");
}

function updateMifFile($mifFic, $epsg) {
  $result = false;
  $updated = false;
  $hr = fopen($mifFic, "r");
  if($hr) {
    $mifTmp = $mifFic.".tmp";
    $hw = fopen($mifTmp, "w");
    if($hw) {
      while(!feof($hr)) {
        $line = fgets($hr);
        if(!$updated) {
          if(strtoupper(substr($line, 0, 9)) == "COORDSYS ") {
            switch($epsg) {
            case "27561": // Lambert zone I
              $line = "CoordSys Earth Projection 3, 1002, \"m\", 0, 49.5, 48.598522778, 50.39591167,600000, 200000\n";
              break;
            case "27571": // Lambert I carto
              $line = "CoordSys Earth Projection 3, 1002, \"m\", 0, 49.5, 48.598522778, 50.39591167,600000, 1200000\n";
              break;
            case "27572": // Lambert II etendu
              $line = "CoordSys Earth Projection 3, 1002, \"m\", 0, 46.8, 45.89891889, 47.69601444,600000, 2200000\n";
              break;
            case "2154": // Lambert 93
              $line = "CoordSys Earth Projection 3, 33, \"m\", 3, 46.5, 44, 49, 700000, 6600000\n";
              break;
            }
            $updated = true;
          }
        }
        fputs($hw, $line);
      }
      fclose($hw);
    }
    fclose($hr);
    if($updated) {
      unlink($mifFic);
      $result = rename($mifTmp, $mifFic);
    }
  }
  return $result;
}

function getPgBooleanLibelle($pgBool) {
  switch($pgBool) {
  case "t":
    return "OUI";
  case "f":
    return "NON";
  }
  return "";
}

/**
* 
**/
function decode($str) {
	// if(preg_match("/[©Ã]/", $str)) { // ou bien Â®
	if(preg_match("/[Ã]/", $str)) { // ou bien Â®
		$str = utf8_decode($str);
	}
	return $str;
}

if( !function_exists("printr") ) {
    function printr($str) {
        echo "<pre>";
        print_r($str);
        echo "</pre>";
    }
}

// JMA
function getAdminMail() {
  $conn = dbConnect3(); // Drupal
  $query = "SELECT users.mail FROM users, users_roles WHERE users_roles.rid='6' AND users_roles.uid=users.uid";

  $res = pg_query($conn, $query);
  $mails = @pg_fetch_all_columns($res, 0);
  if($mails & is_array($mails)) {
      $adminMail = implode(", ", $mails);
  } else {
      $ini = parse_ini_file("config/config.ini");
      $adminMail = $ini['adminMail'];
  }

  return $adminMail;
}
/*
    //JMA recup nom communes
    if($form_state['storage']['insee-extract'] != "") {
        $insee = $form_state['storage']['insee-extract'];
// printr($insee);
        $insee = str_replace(",",";",$insee);
        $insee = "'".str_replace(";","','",$insee)."'";

        $sql = "SELECT nom FROM bdad_commune WHERE code_insee IN ($insee)";
// printr($sql);
        $lstCommune = db_query($sql)->fetchCol();
        
        if(is_array($lstCommune)) {
            $lstCommune = implode(", ",$lstCommune) ; 
// printr($lstCommune);
            
            $query = "UPDATE projet_extraction SET commune = :com WHERE id_extraction = " . $last_id;
            $update = db_query($query, array(':com' => $lstCommune));	
        }
    }
    //
*/
	function getCommuneTC($idOrganisme){
	    if($idOrganisme == '') return false;
        
        $conn = dbConnect(); // Extraction
        $conn3 = dbConnect3(); // Drupal
        
	    $strSQL = "
            select 
                c.field_territoire_de_competence_value as tc 
            from 
                field_data_field_territoire_de_competence as c
            where 
                c.entity_id= ".$idOrganisme.";
        ";
        
	    $res = pg_query($conn3, $strSQL);
        if(!$res) return false;
        $row = pg_fetch_assoc($res);

	    if(is_array($row)) {
	        $strTerritoire = $row['tc'] ;
	        if($strTerritoire != '') {
                $insee = "'".str_replace(";","','",$strTerritoire)."'";

                $sql = "SELECT nom FROM bdad_commune WHERE code_insee IN ($insee)";
                $res2 = pg_query($conn, $sql);
                $row2 = pg_fetch_all_columns($res2);
                    
                if(is_array($row2)) {
                    $lstCommune = implode(", ",$row2) ; 
                }
            
	            return array(
                    "commune" => $lstCommune,
                    "insee" => str_replace(";",",",$strTerritoire)
                );
	        }
	    }

        return false ;
	    
	}
    
	function getTC($idOrganisme){
	    if($idOrganisme == '') return false;
        $conn3 = dbConnect3(); // Drupal
        
	    $strSQL = "
            select 
                c.field_territoire_de_competence_value as tc 
            from 
                field_data_field_territoire_de_competence as c
            where 
                c.entity_id= ".$idOrganisme.";
        ";
        
	    $res = pg_query($conn3, $strSQL);
        if(!$res) return false;
        $row = pg_fetch_assoc($res);

	    if(is_array($row)) {
	        return $row['tc'] ;
	    }

        return false;
	}

	function writeLog($mess) {
        $date = date("D M j G:i:s Y"); 
		$dateFile = date("Y-m-d");
        @error_log("[$date] $mess\n", 3, "/var/www/html/portail/sites/all/libraries/ppige_extraction/incphp/logs/$dateFile.log");
	}
	
require_once("/var/www/html/portail/sites/all/libraries/ppige_extraction/incphp/arch_extract.php");
?>