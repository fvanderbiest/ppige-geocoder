<?php
header("Expires: ".gmdate("D, d M Y H:i:s")." GMT") ;
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT") ;
header("Cache-Control: no-store, no-cache, must-revalidate") ;
header("Cache-Control: post-check=0, pre-check=0", false) ;
header("Pragma: no-cache") ;

session_start();


require_once("incphp/extract.php");

// Rcupration des paramtres
$extractionActive = getAdminExtractValue(EXTRACTION_ACTIVE);
if(!isset($extractionActive)) { 
  $extractionActive = NON;
  setAdminExtractValue(EXTRACTION_ACTIVE, $extractionActive);
}

$validationAuto = getAdminExtractValue(VALIDATION_AUTO);
if(!isset($validationAuto)) { 
  $validationAuto = NON; 
  setAdminExtractValue(VALIDATION_AUTO, $validationAuto);
}

$suppressionAuto = getAdminExtractValue(SUPPRESSION_AUTO);
if(!isset($suppressionAuto)) { 
  $suppressionAuto = OUI; 
  setAdminExtractValue(SUPPRESSION_AUTO, $validationAuto);
}

$heureDebut = getAdminExtractValue(HEURE_DEBUT);
$heureFin   = getAdminExtractValue(HEURE_FIN);


// Construction de la liste des filtres
$optionFiltre  = "<option value='".FILTRE_TOUTES_DEMANDES."' selected>Toutes les extractions";
$optionFiltre .= "<option value='".FILTRE_ATTENTE_VALIDATION."'>En attente de validation";
$optionFiltre .= "<option value='".FILTRE_ANNULEE."'>Annul�es";
$optionFiltre .= "<option value='".FILTRE_ATTENTE_TRAITEMENT."'>En attente de traitement";
$optionFiltre .= "<option value='".FILTRE_TRAITEMENT_EN_COURS."'>Traitement en cours";

$optionFiltre .= "<option value='".FILTRE_EMPRISE_HORS_TC."'>Emprise hors TC";
$optionFiltre .= "<option value='".FILTRE_ERREUR_ORGANISME."'>Organisme non li�";

$optionFiltre .= "<option value='".FILTRE_TERMINEE."'>Termin�es";
$optionFiltre .= "<option value='".FILTRE_EN_ERREUR."'>Termin�es avec des erreurs";
$optionFiltre .= "<option value='".FILTRE_WWW."'>Termin�es avec r�cup�ration des donn�es par internet (<=50Mo)";
$optionFiltre .= "<option value='".FILTRE_DVD."'>Termin�es avec r�cup�ration des donn�es par DVD (>50Mo)";
$optionFiltre .= "<option value='".FILTRE_DEMANDE_DVD."'>Termin�es avec demande de DVD";
$optionFiltre .= "<option value='".FILTRE_DEMANDE_DVD_NON_TRAITEE."'>Termin�es avec demande de DVD non trait�e";
$optionFiltre .= "<option value='".FILTRE_DEMANDE_DVD_ACCEPTEE."'>Termin�es avec demande de DVD accept�e";
$optionFiltre .= "<option value='".FILTRE_DEMANDE_DVD_REFUSEE."'>Termin�es avec demande de DVD refus�e";
$optionFiltre .= "<option value='".FILTRE_DVD_ENVOYE."'>Termin�es avec DVD envoy�";
$optionFiltre .= "<option value='".FILTRE_DVD_PAS_ENVOYE."'>Termin�es avec DVD non envoy�";
$optionFiltre .= "<option value='".FILTRE_MODE_MANUEL."'>Extractions manuelles";
$optionFiltre .= "<option value='".FILTRE_MODE_DALLE."'>Traitement par dalles";
$optionFiltre .= "<option value='".FILTRE_MANUEL_TERMINE."'>Extractions manuelles termin�es";
$optionFiltre .= "<option value='".FILTRE_DALLE_TERMINE."'>Extractions par dalles termin�es";


// On n'affiche par dfaut que sur le mois courant
$date       = getDate(); 
$dateDebut  = getDebutMois($date);
$dateFin    = getFinMois($date);

// Tableau des paramtres
$arrParam = array(
  'order_by' => 'id_extraction',
  'direction' => 'DESC',
  'filtre' => FILTRE_TOUTES_DEMANDES,
  'date_demande_debut' => $dateDebut,
  'date_demande_fin' => $dateFin
);

?>
<!DOCTYPE html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
  <head>
    <title>PPIGE - Administration des extractions</title>
    <script language="javascript" src="javascript/outils.js"></script>
    <script language="javascript" src="javascript/xmlhttp.js"></script>
    <script language="javascript" src="javascript/extract.js"></script>
    <script language="javascript" src="javascript/popup.js"></script>
    <script language="javascript" src="javascript/popupEPF.js"></script>
    <script language="javascript" src="javascript/popupDialog.js"></script>
    <link rel="stylesheet" href="templates/default.css" type="text/css">
    <link rel="stylesheet" href="templates/popup.css" type="text/css">
    <link rel="stylesheet" href="templates/admin_extract.css" type="text/css">
    <script language="javascript">
      arrParamAdmin = [];
<?php 
while(list($key, $value) = each($arrParam)) {
  echo "arrParamAdmin.push([\"".addslashes($key)."\", \"".addslashes($value)."\"]);\n";
}
?>

<?php 
  // $drupal_session_id = $_REQUEST['sid'];
  // echo 'drupal_session_id ="'.$drupal_session_id.'";';
  $uid = $_REQUEST['uid'];
  echo 'var uid ="'.$uid.'";';
?> 

    </script>
  </head>
  <body>
  <b>Liste des extractions</b>
  <br>
  <form>
  <div id="admin-extact-form">
    <input type="hidden" name="uid" value="<?php echo $uid ?>" />
    <select id="filtreExtract" class="selectFiltreExtract" ><?php echo $optionFiltre?></select> du
    <input type="text" name="dateDebut" id="dateDebut" value="<?php echo $dateDebut?>" class="inputDate" MAXLENGTH=10> au
    <input type="text" name="dateFin" id="dateFin" value="<?php echo $dateFin?>" class="inputDate"  MAXLENGTH=10>
    <!--<input type="submit" value="OK" style="width:30px">-->
    <a href="#" class="button" onclick="searchExtractAdmin();">OK</a>
  </div>
  <br>
  <br>
    <div id="divBoard" style="overflow:auto; border:none; width:600px; height:375px;margin:0;padding:0">
<?php 
echo br2nl(getTableExtraction($arrParam, 'orderByAdmin', 'callBackAdmin', true));
?>
    </div>    
<?php    
    if(isAdminPrivilege($uid)) {
        echo '<div style="z-index:10000;display:block;"><a title="Exporter la liste au format CSV" href="#" class="button" onclick="exporterCSV();">Export en CSV</a></div>';
    }
?>
    </form>
  </body>
</html>
