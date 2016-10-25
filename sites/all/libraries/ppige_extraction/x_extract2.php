<?php
/*********************************************************************************
  Copyright (c) 2005-2006 GEOSIGNAL - www.geosignal.fr - contact@geosignal.fr
**********************************************************************************/
require_once("incphp/extract.php");


/*echo "<pre>";
print_r($_GET);
echo "</pre>";
exit;*/

$mode = $_GET['mode'];

// *****************************************
// Récupération des datasets (RasterTank)
// *****************************************
if($mode == 'load_datasets') {
  $datasets = loadDatasets();
  echo "{method:'_popupExtract.loadDatasets', datasets:'$datasets'}";

// *****************************************
// Récupération du fichier map "complet"
// *****************************************
} elseif($mode == 'load_complet') {
  $complet = loadComplet();
  echo "{method:'_popupExtract.loadComplet', complet:'$complet'}";

// *****************************************
// Gestion des demandes de DVD
// *****************************************
} elseif($mode == 'demande_dvd') {
  list($err, $msg) = updateDemandeDVD($_GET['id'], $_GET['champ'], $_GET['value']);
  if($err) { exit("{method:'actExtReturn', error:'".addslashes(replaceNL($msg, ' '))."'}"); }
  echo "{method:'actExtReturn'}";
  
// *****************************************
// Visualisation des extractions
// *****************************************
} elseif($mode == 'visualiser_extraction') {
  $queryParam = array();  
  if(isset($_GET['idContact'])) { $queryParam['id_contact'] = $_GET['idContact']; }
  if(isset($_GET['filtre'])) { $queryParam['filtre'] = $_GET['filtre']; }
  if(isset($_GET['date_demande_debut'])) { $queryParam['date_demande_debut'] = $_GET['date_demande_debut']; }
  if(isset($_GET['date_demande_fin'])) { $queryParam['date_demande_fin'] = $_GET['date_demande_fin']; }
  $queryParam['order_by'] = isset($_GET['order_by']) ? $_GET['order_by'] : "id_extraction";
  $queryParam['direction'] = isset($_GET['direction']) ? $_GET['direction'] : "DESC";
  $table = getTableExtraction($queryParam, $_GET['funcOrderBy'], $_GET['funcCallBack'], $_GET['admin']); 
  echo "{method:'".$_GET['funcResponse']."', table:'".addslashes(replaceNL($table))."'}";
  
// *****************************************
// Nouvelle demande d'extraction
// *****************************************
} elseif($mode == 'ajouter_extraction') {
  list($err, $msg) = addExtraction($_GET['idOrganisme'], $_GET['idContact'], $_GET['source'], $_GET['zoneGeo'], $_GET['themes'], $_GET['formatV'], $_GET['formatR'], $_GET['projection'], $_GET['licence'], $_GET['extent']);
  if($err) { exit("{method:'".$_GET['funcResponse']."', error:'".addslashes(replaceNL($msg, ' '))."'}"); }
  echo "{method:'".$_GET['funcResponse']."'}";
  
// *****************************************
// Annulation d'une extraction
// *****************************************
} elseif($mode == ANNULER_EXTRACTION) {
  list($err, $msg) = annulerExtraction($_GET['id']);
  if($err) { exit("{method:'actExtReturn', error:'".addslashes(replaceNL($msg, ' '))."'}"); }
  echo "{method:'actExtReturn', func:function(){".$_GET['func']."({action:\"".$mode."\",id:".$_GET['id']."});}}";

// *****************************************
// Validation d'une extraction
// *****************************************
} elseif($mode == VALIDER_EXTRACTION) {
  list($err, $msg) = validerExtraction($_GET['id']);
  if($err) { exit("{method:'actExtReturn', error:'".addslashes(replaceNL($msg, ' '))."'}"); }
  echo "{method:'actExtReturn', func:function(){".$_GET['func']."({action:\"".$mode."\",id:".$_GET['id']."});}}";  

// *****************************************
// Validation d'une extraction
// *****************************************
} elseif($mode == VALIDER_EXTRACTION_MANUEL) {
  list($err, $msg) = validerExtractionManuel($_GET['id']);
  if($err) { exit("{method:'actExtReturn', error:'".addslashes(replaceNL($msg, ' '))."'}"); }
  echo "{method:'actExtReturn', func:function(){".$_GET['func']."({action:\"".$mode."\",id:".$_GET['id']."});}}";  

// *****************************************
// Edition d'une extraction (modif lds)
// *****************************************
} elseif ($mode == update_extract) {
  $queryParam = array();  
  if (isset($_GET['date_demande'])) { $queryParam['date_demande'] = $_GET['date_demande']; }  
  if (isset($_GET['date_debut_traitement'])) { $queryParam['date_debut_traitement'] = $_GET['date_debut_traitement']; }
  if (isset($_GET['date_fin_traitement'])) { $queryParam['date_fin_traitement'] = $_GET['date_fin_traitement']; }
  if (isset($_GET['date_validation'])) { $queryParam['date_validation'] = $_GET['date_validation']; }
  if (isset($_GET['date_annulation'])) { $queryParam['date_annulation'] = $_GET['date_annulation']; }
  if (isset($_GET['date_suppression'])) { $queryParam['date_suppression'] = $_GET['date_suppression']; }
  if (isset($_GET['titre_carte'])) { $queryParam['titre_carte'] = $_GET['titre_carte']; }
  if (isset($_GET['commune'])) { $queryParam['commune'] = $_GET['commune']; }
  if (isset($_GET['fichier_map'])) { $queryParam['fichier_map'] = $_GET['fichier_map']; }
  if (isset($_GET['extent_carte'])) { $queryParam['extent_carte'] = $_GET['extent_carte']; }
  if (isset($_GET['couche_vecteur'])) { $queryParam['couche_vecteur'] = $_GET['couche_vecteur']; }
  if (isset($_GET['couche_raster'])) { $queryParam['couche_raster'] = $_GET['couche_raster']; }
  if (isset($_GET['format_vecteur'])) { $queryParam['format_vecteur'] = $_GET['format_vecteur']; }
  if (isset($_GET['format_raster'])) { $queryParam['format_raster'] = $_GET['format_raster']; }
  if (isset($_GET['systeme_projection'])) { $queryParam['systeme_projection'] = $_GET['systeme_projection']; }
  if (isset($_GET['attente_vecteur'])) { $queryParam['attente_vecteur'] = $_GET['attente_vecteur']; }
  if (isset($_GET['attente_raster'])) { $queryParam['attente_raster'] = $_GET['attente_raster']; }
  if (isset($_GET['fichier_zip'])) { $queryParam['fichier_zip'] = $_GET['fichier_zip']; }
  if (isset($_GET['fichier_supprime'])) { $queryParam['fichier_supprimer'] = $_GET['fichier_supprime']; }
  if (isset($_GET['taille_fichier'])) { $queryParam['taille_fichier'] = $_GET['taille_fichier']; }
  if (isset($_GET['demande_dvd'])) { $queryParam['demande_dvd'] = $_GET['demande_dvd']; }
  if (isset($_GET['demande_dvd_validee'])) { $queryParam['demande_dvd_validee'] = $_GET['demande_dvd_validee']; }
  if (isset($_GET['dvd_envoye'])) { $queryParam['dvd_envoye'] = $_GET['dvd_envoye']; }
  if (isset($_GET['date_dvd_envoye'])) { $queryParam['date_dvd_envoye'] = $_GET['date_dvd_envoye']; }
  if (isset($_GET['num_courrier_suivi'])) { $queryParam['num_courrier_suivi'] = $_GET['num_courrier_suivi']; }
  if (isset($_GET['fichier_licence'])) { $queryParam['fichier_licence'] = $_GET['fichier_licence']; }
  //if (isset($_GET['remote_addr'])) { $queryParam['remote_addr'] = $_GET['remote_addr']; }
  if (isset($_GET['dossier_contact'])) { $queryParam['dossier_contact'] = $_GET['dossier_contact']; }
  if (isset($_GET['etat_demande'])) { $queryParam['etat_demande'] = $_GET['etat_demande']; }
  if (isset($_GET['zone_geograpique'])) { $queryParam['zone_geograpique'] = $_GET['zone_geograpique']; }
  if (isset($_GET['extent_extr'])) { $queryParam['extent_extraction'] = $_GET['extent_extr']; }
  
  
  if (isset($_GET['id_format_vecteur'])) { $queryParam['id_format_vecteur'] = $_GET['id_format_vecteur']; }
  if (isset($_GET['id_format_raster'])) { $queryParam['id_format_raster'] = $_GET['id_format_raster']; }
  if (isset($_GET['status_vecteur'])) { $queryParam['status_vecteur'] = $_GET['status_vecteur']; }
  if (isset($_GET['status_raster'])) { $queryParam['status_raster'] = $_GET['status_raster']; }
  if (isset($_GET['fichier_licence'])) { $queryParam['fichier_licence'] = $_GET['fichier_licence']; }
  if (isset($_GET['fichier_map'])) { $queryParam['fichier_map'] = $_GET['fichier_map']; }
  if (isset($_GET['remote_addr'])) { $queryParam['remote_addr'] = $_GET['remote_addr']; }
  if (isset($_GET['process_id'])) { $queryParam['process_id'] = $_GET['process_id']; }
  if (isset($_GET['dossier_temp'])) { $queryParam['dossier_temp'] = $_GET['dossier_temp']; }
  if (isset($_GET['process_id_zip'])) { $queryParam['process_id_zip'] = $_GET['process_id_zip']; }
  if (isset($_GET['id_projection'])) { $queryParam['id_projection'] = $_GET['id_projection']; }
  if (isset($_GET['organisme'])) { $queryParam['organisme'] = $_GET['organisme']; }
  if (isset($_GET['contact'])) { $queryParam['contact'] = $_GET['contact']; }
  if (isset($_GET['taille_reelle'])) { $queryParam['taille_reelle'] = $_GET['taille_reelle']; } 
  if (isset($_GET['insee_extraction'])) { $queryParam['insee_extraction'] = $_GET['insee_extraction']; }
    
  list($err, $msg) = updateExtraction($_GET['id'],$queryParam);
  if ($err) { exit("{method:'actExtReturn', error:'".addslashes(replaceNL($msg, ' '))."'}"); }
  echo "{method:'actExtReturn', func:function(){".$_GET['func']."({action:\"".$mode."\",id:".$_GET['id']."});}}";

// *****************************************
// Duplication d'une extraction
// *****************************************
} elseif($mode == DUPLIQUER_EXTRACTION) {
  list($err, $msg) = dupliquerExtraction($_GET['id']);
  if($err) { exit("{method:'actExtReturn', error:'".addslashes(replaceNL($msg, ' '))."'}"); }
  echo "{method:'actExtReturn', func:function(){".$_GET['func']."({action:\"".$mode."\",id:".$_GET['id']."});}}";

// *****************************************
// Suppression des fichiers d'une extraction
// *****************************************
} elseif($mode == SUPPRIMER_FICHIER_EXTRACTION) {
  list($err, $msg) = supprimerFichierExtraction($_GET['id']);
  if($err) { exit("{method:'actExtReturn', error:'".addslashes(replaceNL($msg, ' '))."'}"); }
  echo "{method:'actExtReturn', func:function(){".$_GET['func']."({action:\"".$mode."\",id:".$_GET['id']."});}}";  

// *****************************************
// Terminer extraction Jck 11/08/22
// *****************************************
}elseif($mode == TERMINER_EXTRACTION) {
  list($err, $msg) = terminerExtraction($_GET['id']);
  if($err) { exit("{method:'actExtReturn', error:'".addslashes(replaceNL($msg, ' '))."'}"); }
  echo "{method:'actExtReturn', func:function(){".$_GET['func']."({action:\"".$mode."\",id:".$_GET['id']."});}}";  

// *****************************************
// Export des données en CSV
// *****************************************
} elseif($mode == "exporter_csv") {
  list($err, $msg) = exporterCSV($_GET['filtre'], $_GET['date_demande_debut'], $_GET['date_demande_fin']);
  if($err) { exit("{method:'exporterCSV', error:'".addslashes(replaceNL($msg, ' '))."'}"); }
  echo "{method:'exporterCSV', fichier:'".$msg."'}";

// *****************************************
// Modification d'un paramètre d'administration des extractions
// *****************************************
} elseif($mode == 'modifier_param') {
  list($err, $msg) = setAdminExtractValue($_GET['name'], $_GET['value']);
  if($err) { exit("{method:'setParamExtractReturn', error:'".addslashes(replaceNL($msg, ' '))."'}"); }  
  echo "{method:'setParamExtractReturn'}";    

// 
} elseif($mode == 'update') {
  list($err, $msg) = updateDemande($_GET['id'], $_GET['champ'], $_GET['value']);
  if($err) { exit("{method:'actExtReturn', error:'".addslashes(replaceNL($msg, ' '))."'}"); }
  echo "{method:'actExtReturn', func:}";
}

?>
