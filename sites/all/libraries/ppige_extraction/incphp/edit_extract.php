<?php


// modif lds 12/03/01
function editerExtraction($idExtraction) {
  
  $conn = dbConnect();
  
  $query = "SELECT ";
  $query .= "to_char(pe.date_demande, 'DD/MM/YYYY HH24:MI:SS') AS date_demande_str, ";
  $query .= "to_char(pe.date_debut_traitement, 'DD/MM/YYYY HH24:MI:SS') AS date_debut_traitement_str, ";
  $query .= "to_char(pe.date_fin_traitement, 'DD/MM/YYYY HH24:MI:SS') AS date_fin_traitement_str, ";
  $query .= "to_char(pe.date_validation, 'DD/MM/YYYY HH24:MI:SS') AS date_validation_str, ";
  $query .= "to_char(pe.date_annulation, 'DD/MM/YYYY HH24:MI:SS') AS date_annulation_str, ";
  $query .= "to_char(pe.date_suppression, 'DD/MM/YYYY HH24:MI:SS') AS date_suppression_str, ";
  $query .= "pe.id_organisme,";
  //$query .= "node_org.nid,";
  //$query .= "node_org.vid,";
  //$query .= "pe.titre_carte, ";
  $query .= "pe.date_dvd_envoye, ";
  $query .= "pe.etat_demande, ";
  $query .= "pe.id_extraction, ";
  $query .= "pe.systeme_projection, ";
  $query .= "pe.message_erreur, ";
  $query .= "pe.extent_carte, ";
  $query .= "pe.extent_extraction, ";
  $query .= "pe.fichier_zip, ";
  $query .= "pe.format_vecteur, ";
  $query .= "pe.format_raster, ";
  $query .= "pe.zone_geograpique, ";
  $query .= "pe.dossier_contact, ";
  $query .= "pe.commune, ";
  $query .= "pe.taille_fichier, ";
  $query .= "pe.attente_vecteur, ";
  $query .= "pe.attente_raster, ";
  //$query .= "pe.fichier_supprimer, ";
  //$query .= "node_profile.uid,";
  $query .= "pe.id_contact,";
  $query .= "pe.mode,";
  $query .= "pe.demande_dvd,";
  $query .= "pe.demande_dvd_validee,";
  $query .= "pe.dvd_envoye,";
  $query .= "pe.num_courrier_suivi,";
  $query .= "pe.couche_vecteur, ";
  $query .= "pe.couche_raster, ";
  
  // RJA
  $query .= "pe.id_format_vecteur, ";
  $query .= "pe.id_format_raster, ";
  $query .= "pe.status_vecteur, ";
  $query .= "pe.status_raster, ";
  $query .= "pe.fichier_licence, ";
  //$query .= "pe.fichier_map, ";
  $query .= "pe.remote_addr, ";
  //$query .= "pe.process_id, ";
  $query .= "pe.dossier_temp, ";
  //$query .= "pe.process_id_zip, ";
  $query .= "pe.temp_extent, ";
  $query .= "pe.id_projection, ";
  $query .= "pe.organisme, ";
  $query .= "pe.contact, ";
  $query .= "pe.the_geom_tc, ";
  $query .= "pe.taille_reelle, ";
  $query .= "pe.insee_extraction ";
  
  //$query .= "users.mail AS email_contact ";
  $query .= "FROM projet_extraction as pe ";
  //$query .= "INNER JOIN users ON users.uid=pe.id_contact ";
  $query .= "WHERE id_extraction=".$idExtraction;
  
  $res = pg_query($conn, $query);
  if (!$res) { return array(true, pg_last_error($conn)); }
  
  $html = '<div id="extraction-edit-'.$idExtraction.'" class="extraction-edit-inner">';
  
  while ($row = pg_fetch_assoc($res)) {
    /*****/
    $html .= getEtatDemande($row['etat_demande']);
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Date de la demande d\'extraction</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="date_demande" name="date_demande" value="'.$row['date_demande_str'].'" class="form-field form-date" />';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Date de début de traitement de la demande d\'extraction</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="date_debut_traitement" name="date_debut_traitement" value="'.$row['date_debut_traitement_str'].'" class="form-field form-date" />';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Date de fin de traitement de la demande d\'extraction</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="date_fin_traitement" name="date_fin_traitement" value="'.$row['date_fin_traitement_str'].'" class="form-field form-date" />';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Date de validation de la demande d\'extraction</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="date_validation" name="date_validation" value="'.$row['date_validation_str'].'" class="form-field form-date" />';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Date d\'annulation de la demande d\'extraction</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="date_annulation" name="date_annulation" value="'.$row['date_annulation_str'].'" class="form-field form-date" />';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Date de suppression de la demande d\'extraction</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="date_suppression" name="date_suppression" value="'.$row['date_suppression_str'].'" class="form-field form-date" />';
    $html .= '</div>';
    $html .= '</div>';
    /*****/
    /*
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Titre de la carte</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="titre_carte" name="titre_carte" value="'.utf8_decode($row['titre_carte']).'" class="form-field" />';
    $html .= '</div>';
    $html .= '</div>';
    */
    $html .= getZoneGeo($row['zone_geograpique']);
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Liste des communes à extraire</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="commune" name="commune" value="'.utf8_decode($row['commune']).'" class="form-field" />';
    $html .= '</div>';
    $html .= '</div>';  
    /*
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Nom du fichier map</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="fichier_map" name="fichier_map" value="'.$row['fichier_map'].'" class="form-field" />';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Extent de la carte</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="extent_carte" name="extent_carte" value="'.$row['extent_carte'].'" class="form-field" />';
    $html .= '</div>';
    $html .= '</div>';
    */
    
    
    //$html .= getExtentExtractAll($row['extent_extraction']);
    
    /*
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Liste des couches vecteur</label>';
    $html .= '<div class="form-element">';
    $coucheVecteur = utf8_decode($row['couche_vecteur']);
    $html .= '<textarea id="couche_vecteur" name="couche_vecteur" class="form-textarea">'.$coucheVecteur.'</textarea>';
    $html .= '</div>';
    $html .= '</div>';
    */
    
    /*
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Liste des couches raster</label>';
    $html .= '<div class="form-element">';
    $coucheRaster = utf8_decode($row['couche_raster']);
    $html .= '<input type="text" id="couche_raster" name="couche_raster" value="'.$coucheRaster.'" class="form-field" />';
    $html .= '</div>';
    $html .= '</div>';
    */
    
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Format des couches vecteur</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="format_vecteur" name="format_vecteur" value="'.$row['format_vecteur'].'" class="form-field form-date" />';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Format des couches raster</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="format_raster" name="format_raster" value="'.$row['format_raster'].'" class="form-field form-date" />';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Systeme de projection</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="systeme_projection" name="systeme_projection" value="'.$row['systeme_projection'].'" class="form-field form-date" />';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Extraction en attente du traitement des vecteur</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="checkbox" id="attente_vecteur" name="attente_vecteur" '.getCheckBoxValue($row['attente_vecteur']).' class="form-check" />';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Extraction en attente du traitement des raster</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="checkbox" id="attente_raster" name="attente_raster" '.getCheckBoxValue($row['attente_raster']).' class="form-check" />';
    $html .= '</div>';
    $html .= '</div>';
    /*****/
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Fichier archive</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="fichier_zip" name="fichier_zip" value="'.$row['fichier_zip'].'" class="form-field" />';
    $html .= '</div>';
    $html .= '</div>';
    /*
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Les fichiers extraits ont été supprimés</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="checkbox" id="fichier_supprime" name="fichier_supprime" '.getCheckBoxValue($row['fichier_supprimer']).' class="form-check" />';
    $html .= '</div>';
    $html .= '</div>';
    */
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Taille du fichier archive</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="taille_fichier" name="taille_fichier" value="'.$row['taille_fichier'].'" class="form-field form-date" />';
    $html .= '</div>';
    $html .= '</div>';
    
    /*****/
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">L\'ayant droit a fait une demande de livraison des données par DVD</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="checkbox" id="demande_dvd" name="demande_dvd" checked="'.getCheckBoxValue($row['demande_dvd']).'" class="form-check" />';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Demande de livraison des données par DVD acceptée par la ppige</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="checkbox" id="demande_dvd_validee" name="demande_dvd_validee" '.getCheckBoxValue($row['demande_dvd_validee']).' class="form-check" />';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">DVD envoyé</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="checkbox" id="dvd_envoye" name="dvd_envoye" '.getCheckBoxValue($row['dvd_envoye']).' class="form-check" />';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Date d\'envoi du DVD</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="date_dvd_envoye" name="date_dvd_envoye" value="'.$row['date_dvd_envoye'].'" class="form-field form-date" />';
    $html .= '</div>';
    
    
    $html .= '</div>';
    /*****/
    
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Numéro du courrier de suivi</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="num_courrier_suivi" name="num_courrier_suivi" value="'.$row['num_courrier_suivi'].'" class="form-field form-date" />';
    $html .= '</div>';
   
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Nom du fichier licence</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="fichier_licence" name="fichier_licence" value="'.$row['fichier_licence'].'" class="form-field" />';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Dossier de stockage des extractions de l\'ayant droit</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="dossier_contact" name="dossier_contact" value="'.$row['dossier_contact'].'" class="form-field" />';
    $html .= '</div>';
    
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Identifiant du format vecteur</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="id_format_vecteur" name="id_format_vecteur" value="'.$row['id_format_vecteur'].'" class="form-field form-date" />';
    $html .= '</div>';
    
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Identifiant du format raster</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="id_format_raster" name="id_format_raster" value="'.$row['id_format_raster'].'" class="form-field form-date" />';
    $html .= '</div>';
    
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Statut des couches vecteur</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="status_vecteur" name="status_vecteur" value="'.$row['status_vecteur'].'" class="form-field form-date" />';
    $html .= '</div>';
    
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Statut des couches raster</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="status_raster" name="status_raster" value="'.$row['status_raster'].'" class="form-field form-date" />';
    $html .= '</div>';
    
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Adresse IP de l\'internaute</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="remote_addr" name="remote_addr" value="'.$row['remote_addr'].'" class="form-field form-date" />';
    $html .= '</div>';
    /*
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Identifiant unique du processus d\'extraction</label>';
    $html .= '<div class="form-element">';
    $html .= '<input readonly type="text" id="process_id" name="process_id" value="'.$row['process_id'].'" class="form-field" />';
    $html .= '</div>';
    $html .= '</div>';
    */
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Dossier temporaire de stockage des données</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="dossier_temp" name="dossier_temp" value="'.$row['dossier_temp'].'" class="form-field" />';
    $html .= '</div>';
    $html .= '</div>';
    /*
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Identifiant unique du processus de création du fichier zip</label>';
    $html .= '<div class="form-element">';
    $html .= '<input readonly type="text" id="process_id_zip" name="process_id_zip" value="'.$row['process_id_zip'].'" class="form-field" />';
    $html .= '</div>';
    $html .= '</div>';
    */
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Identifiant de projection</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="id_projection" name="id_projection" value="'.$row['id_projection'].'" class="form-field form-date" />';
    $html .= '</div>';
    
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Organisme</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="organisme" name="organisme" value="'.utf8_decode($row['organisme']).'" class="form-field" />';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Contact</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="contact" name="contact" value="'.utf8_decode($row['contact']).'" class="form-field" />';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Taille réelle de l\'extraction</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="taille_reelle" name="taille_reelle" value="'.$row['taille_reelle'].'" class="form-field form-date" />';
    $html .= '</div>';
    
    $html .= '<div class="form-item">';
    $html .= '<label class="form-label">Codes INSEE à extraire</label>';
    $html .= '<div class="form-element">';
    $html .= '<input type="text" id="insee_extraction" name="insee_extraction" value="'.utf8_decode($row['insee_extraction']).'" class="form-field" />';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '</div>';
  }
  $html .= '<div class="form-footer form-buttons">';
  $html .= '<input type="button" class="form-button" value="Enregistrer" onclick="updateExtract('.$idExtraction.')" title="Enregistrer les modifications" />';
  $html .= '<input type="button" class="form-button form-cancel" value="Annuler" onclick="window.close()" title="Annuler les modifications" />';
  $html .= '</div>';  
  $html .= '</div>';
  
  return $html;
}

function insert_encode($str){
    return str_replace("'","''",stripslashes($str));
}

function updateExtraction($idExtraction,$arrayParam) {
  $sendMail = false;
  $set = '';
  $conn = dbConnect();
  
  if(!empty($arrayParam['date_demande'])){
    $set .= "date_demande=TO_TIMESTAMP('{$arrayParam['date_demande']}','DD/MM/YYYY HH24:MI:SS'),";
  }else{
    $set .= "date_demande=null,";
  }
  if(!empty($arrayParam['date_debut_traitement'])){
    $set .= "date_debut_traitement=TO_TIMESTAMP('{$arrayParam['date_debut_traitement']}','DD/MM/YYYY HH24:MI:SS'),";
  }else{
    $set .= "date_debut_traitement=null,";
  }
  if(!empty($arrayParam['date_validation'])){
    $set .= "date_validation=TO_TIMESTAMP('{$arrayParam['date_validation']}','DD/MM/YYYY HH24:MI:SS'),";
  }else{
    $set .= "date_validation=null,";
  }
  if(!empty($arrayParam['date_annulation'])){
    $set .= "date_annulation=TO_TIMESTAMP('{$arrayParam['date_annulation']}','DD/MM/YYYY HH24:MI:SS'),";
  }else{
    $set .= "date_annulation=null,";
  }
  if(!empty($arrayParam['date_fin_traitement'])){
    $set .= "date_fin_traitement=TO_TIMESTAMP('{$arrayParam['date_fin_traitement']}','DD/MM/YYYY HH24:MI:SS'),";
  }else{
    $set .= "date_fin_traitement=null,";
  }
  if(!empty($arrayParam['date_suppression'])){
    $set .= "date_suppression=TO_TIMESTAMP('{$arrayParam['date_suppression']}','DD/MM/YYYY HH24:MI:SS'),";
  }else{
    $set .= "date_suppression=null,";
  }
  
  $set .= "etat_demande='".$arrayParam['etat_demande']."',";
  $set .= "zone_geograpique='".$arrayParam['zone_geograpique']."',";
  //$set .= "titre_carte='".insert_encode(utf8_encode(decode($arrayParam['titre_carte'])))."',";
  $set .= "format_vecteur='".$arrayParam['format_vecteur']."',";
  $set .= "format_raster='".$arrayParam['format_raster']."',";
  $set .= "systeme_projection='".$arrayParam['systeme_projection']."',";
  $set .= "demande_dvd='".$arrayParam['demande_dvd']."',";
  //$set .= "num_courrier_suivi='".$arrayParam['num_courrier_suivi']."',";
  //$set .= "extent_extraction='".$arrayParam['extent_extraction']."',";
  $set .= "fichier_licence='".$arrayParam['fichier_licence']."',";
  //$set .= "fichier_map='".$arrayParam['fichier_map']."',";
  $set .= "commune='".insert_encode(utf8_encode(decode($arrayParam['commune'])))."',";
  //$set .= "extent_carte='".$arrayParam['extent_carte']."',";
  $set .= "fichier_zip='".$arrayParam['fichier_zip']."',";
  /*$set .= "message_erreur='".$arrayParam['message_erreur']."',";*/
  $set .= "remote_addr='".$arrayParam['remote_addr']."',";
  $set .= "dossier_temp='".$arrayParam['dossier_temp']."',";
  //$set .= "process_id='".$arrayParam['process_id']."',";
  //$set .= "process_id_zip='".$arrayParam['process_id_zip']."',";
  /*$set .= "temp_extent='".$arrayParam['temp_extent']."',";*/
  $set .= "attente_raster='".$arrayParam['attente_raster']."',";
  $set .= "dossier_contact='".$arrayParam['dossier_contact']."',";
  //$set .= "fichier_supprimer='".$arrayParam['fichier_supprimer']."',";
  $set .= "dvd_envoye='".$arrayParam['dvd_envoye']."',";
  $set .= "demande_dvd_validee='".$arrayParam['demande_dvd_validee']."',";
  $set .= "date_dvd_envoye='".$arrayParam['date_dvd_envoye']."',";
  //$set .= "taille_fichier=CAST('{$arrayParam['taille_fichier']}' AS 'bigint'),";
  $set .= "attente_vecteur='".$arrayParam['attente_vecteur']."',";
  if(!empty($arrayParam['id_format_vecteur'])){
    $set .= "id_format_vecteur='".$arrayParam['id_format_vecteur']."',";
  }else{
    $set .= "id_format_vecteur = null,";
  }
  if(!empty($arrayParam['id_format_raster'])){
    $set .= "id_format_raster='".$arrayParam['id_format_raster']."',";
  }else{
    $set .= "id_format_raster = null,";
  }
  if(!empty($arrayParam['id_projection'])){
    $set .= "id_projection='".$arrayParam['id_projection']."',";
  }else{
    $set .= "id_projection = null,";
  }
  $set .= "organisme='".insert_encode(utf8_encode(decode($arrayParam['organisme'])))."',";
  $set .= "contact='".insert_encode(utf8_encode(decode($arrayParam['contact'])))."',";
  $set .= "status_vecteur='".$arrayParam['status_vecteur']."',";
  $set .= "status_raster='".$arrayParam['status_raster']."',";
  $set .= "mode='".$arrayParam['mode']."',";
  /*$set .= "the_geom_tc='".$arrayParam['the_geom_tc']."',";*/
  if(!empty($arrayParam['taille_reelle'])){
      $set .= "taille_reelle ='".$arrayParam['taille_reelle']."'";
  }else{
        $set .= "taille_reelle = null";
  }
  $sql = "UPDATE projet_extraction SET ".$set." WHERE id_extraction=".$idExtraction;
  //echo $sql;exit;
  $res = pg_query($conn, $sql);
  if (!$res) { return array(true, pg_last_error($conn)); }
}

function getEtatDemande($demand) {
  $demandArray = Array(ATTENTE_VALIDATION,ATTENTE_TRAITEMENT,TRAITEMENT_EN_COURS,EN_ERREUR,TERMINEE,ANNULEE,ERREUR_ORGANISME,EMPRISE_HORS_TC);
  $html = '<div class="form-item">';
  $html .= '<label class="form-label">Etat de la demande d\'extraction</label>';
  $html .= '<div class="form-element">';
  $html .= '<input type="hidden" id="etatDemandeField" value="'.$demand.'" />';
  $html .= '<select onchange="onChangeListener(this)">';
  foreach ($demandArray as $val) {
    $selected = ($demand==$val) ? "selected" : "";
    $html .= '<option id="'.$val.'" '.$selected.'>'.getEtatDemandeLibelle($val).'</option>';  
  }
  $html .= '</select>';
  $html .= '</div>';
  $html .= '</div>';
  
  return $html;
}

function getZoneGeo($zone) {
  $zoneArray = Array('etendue','totalite');
  $html = '<div class="form-item">';
  $html .= '<label class="form-label">Emprise</label>';
  $html .= '<div class="form-element">';
  $html .= '<input type="hidden" id="zoneGeoField" value="'.$zone.'" >';
  $html .= '<select onchange="onChangeListener(this)" >';
  foreach ($zoneArray as $val) {
    $selected = ($zone==$val) ? "selected" : "";
    $html .= '<option id="'.$val.'" '.$selected.'>'.htmlentities(getZoneGeoLibelle($val), ENT_QUOTES).'</option>';  
  }
  $html .= '</select>';
  $html .= '</div>';
  $html .= '</div>';
  
  return $html;
}

function getCheckBoxValue($bool) {
  return ($bool=='t') ? "checked" : "";
}

function formatDateValue($date) {
  return ($date) ? "TO_DATE('{$date}','DD/MM/YYYY')" : "NULL";
}

function getExtentExtractAll($row) {
  $html .= '<div class="form-item">';
  $html .= '<label class="form-label">Extent de l\'extraction</label>';
  $html .= '<div class="form-element">';
  $html .= '<textarea id="extent_extr" name="extent_extr" class="form-textarea">'.$row.'</textarea>';
  $html .= '</div>';
  $html .= '</div>';
  
  return $html;
}

// End modif lds

?>