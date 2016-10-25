<?php



function getExtentExtract($idOrganisme, $zoneGeo, $extent) {
  $ini = parse_ini_file(realpath("config/config.ini"));  
  $geoBuffer = (isset($ini['geoBuffer']) ? $ini['geoBuffer'] : 0);
  $conn = dbConnect();
  $rs = pg_query($conn, "SELECT extent(buffer(box3d(c.the_geom), $geoBuffer)) AS ext FROM commune AS c INNER JOIN projet_ville_associee AS pva ON c.insee_comm=pva.insee_comm WHERE pva.id_organisme=$idOrganisme;");
  $row = pg_fetch_assoc($rs);
  if($row) {
    $ext = formatPgExtent($row['ext']);
    if($zoneGeo == 'etendue') { $ext = getExtentIntersect($ext, $extent); }
  }
  //dbClose($conn);
  return $ext;
}

function getLayers($map, $themes) {
  $arrVecteur = array();
  $arrRaster = array();
  $arrTheme = split(';', $themes);    
  for($i=0; $i<count($arrTheme); $i++) {
    list($type, $theme) = explode(',', $arrTheme[$i]);
    if($type == 'VECTEUR') {
      $layersIndex = $map->getLayersIndexByGroup($theme);
      foreach($layersIndex as $index) {
        $layer = $map->getLayer($index);  
        $tableName = getTableName($layer);
        $arrVecteur[] = array('group'=>$theme, 'name'=>$layer->name, 'table'=>$tableName, 'description'=>$layer->getMetaData("DESCRIPTION"), 'base'=>getBaseName($tableName));
      }
    } else { // RASTER
      list($datasetId, $productType) = explode("#", $theme);
      $arrRaster[] = array('name'=>$productType, 'dataset_id'=>$datasetId);
    }
  }
  return array($arrVecteur, $arrRaster);
}

function getBaseName($tableName) {
  return (isset($_SESSION['bases_EPF'][$tableName]) ? $_SESSION['bases_EPF'][$tableName] : "");    
}

function getTableName($layer) {
  $arr = explode(' from ', $layer->data);
  $flds = trim($arr[1]);
  if (substr($flds, 0, 1) == '(') {
    $arr = preg_split('/as ([a-z]|_|[A-Z]|[0-9])+ using unique /i', $arr[2]);
    $arr = explode(' where ', $arr[0]);
    $tableName = trim(rtrim(rtrim($arr[0]), ')'));
  } else {
    $tableName = $layer->name;
  }
  return $tableName;
}

function getCatList($metaData) {
  $catList = "";
  $cats = getAttr($metaData, 'cat');
  foreach($cats as $cat) {
    $groupList = '';
    $category = addslashes($cat[0]);
    $groups = $cat[1];
    foreach($groups as $group) {
      $group = trim($group);
      if($groupList != '') { $groupList .= ","; }
      $groupList .= "\"$group\"";
    }
    if($catList != '') { $catList .= ","; }
    $catList .= "[\"$category\",[$groupList]]";
  }
  return "[$catList]";
}

function getActifGroups($map) {
  $actifGroups = '';
  $actifGrps = $map->getMetaData('actifGroups');
  $actifGrps = explode(",", $actifGrps);
  foreach($actifGrps as $group) {
    $group = trim($group);
    if($actifGroups != '') { $actifGroups .= ","; }
    $actifGroups .= "\"$group\"";
  }
  return "[$actifGroups]";
}

function getGroupDescription($map, $group) {
  $layers = $map->getLayersIndexByGroup($group);
  if($layers && count($layers)) {
    $layer = $map->getLayer($layers[0]);
    $description = $layer->getMetaData("DESCRIPTION");
    if($description != "") {
      $ini = parse_ini_file(realpath("config/config.ini"));
      if($ini['map2unicode']) { $description = utf8_encode($description); }
    } else {
      $description = $layer->name;
    }
    return preg_replace(array("/\\\/", "/\|/"), array("", ""), trim($description));  // ESCAPE APOSTROPHES (SINGLE QUOTES) IN NAME WITH BACKSLASH
  }
}

function getGroupType($map, $group) {
  $groupType = "";
  $layers = $map->getLayersIndexByGroup($group);
  if($layers) {
    $arrType = array();
    $strType = "";
    for($i=0; $i<count($layers); $i++) {
      switch($layers[$i]->type) {
      case MS_LAYER_POINT:
        $strType = "MS_LAYER_POINT";
        break;
      case MS_LAYER_LINE:   
        $strType = "MS_LAYER_LINE";
        break;
      case MS_LAYER_POLYGON:   
        $strType = "MS_LAYER_POLYGON";
        break;
      case MS_LAYER_RASTER:   
        $strType = "MS_LAYER_RASTER";
        break;
      case MS_LAYER_ANNOTATION:   
        $strType = "MS_LAYER_ANNOTATION";
        break;
      case MS_LAYER_QUERY:   
        $strType = "MS_LAYER_QUERY";
        break;
      case MS_LAYER_CIRCLE:   
        $strType = "MS_LAYER_CIRCLE";
        break;
      case MS_LAYER_TILEINDEX: 
        $strType = "MS_LAYER_TILEINDEX";
        break;
      }
      if($strType != "" && !in_array($strType, $arrType)) { $arrType[] = $strType; }      
    }
    for($i=0; $i<count($arrType); $i++) {
      if($groupType != '') { $groupType .= ','; }
      $groupType .= "\"{$arrType[$i]}\"";
    }
  }
  return "[$groupType]";
}

function getGroupList($map) {
  $groupList = '';
  $allGroups = $map->getMetaData('allGroups');
  $allGroups = explode(",", $allGroups);
  foreach($allGroups as $group) {
    $group = trim($group);
    $description = getGroupDescription($map, $group);      
    if(isset($description)) {
      $type = getGroupType($map, $group);
      //$baseName = getGroupBaseName($map, $group);
      $baseName = '';
      $description = addslashes($description);
      if($groupList != '') { $groupList .= ','; }
      $groupList .= "[\"$group\",\"$description\",\"$baseName\", $type]";
    }
  }
  return "[$groupList]";
}

function getMapFileName($source) {
  switch($source) {
  case 'complet':
    $mapFile = realpath("config/complet.map");
    break;
  default:
    $mapFile = $_SESSION['mapFile'];
    break;
  }
  return $mapFile;
}

function getCodeEPSG($projection) {
  switch($projection) {
  case "Lambert zone I":
    return '27561';
  case 'Lambert I carto':
    return '27571';
  case 'Lambert II etendu':
    return '27572';
  case 'Lambert 93':
    return '2154';
  }
}

function formatPgExtent($ext) {
  // $ext = BOX(xmin ymin,xmax ymax)
  $res = substr($ext, 4, strlen($ext)-5);
  $res = strtr($res, ",", " ");
  return explode(" ", $res);
}

function getExtentIntersect($extentA, $extentB) {
  $x1 = max($extentA[0], $extentB[0]);
  $y1 = max($extentA[1], $extentB[1]);
  $x2 = min($extentA[2], $extentB[2]);
  $y2 = min($extentA[3], $extentB[3]);
  return array(min($x1, $x2), min($y1, $y2), max($x1, $x2), max($y1, $y2));
}

function getCommune($idOrganisme, $extent) {
  $commune = array();
  $conn = dbConnect();
  //echo "requete ===> SELECT c.nom_comm FROM commune AS c INNER JOIN projet_ville_associee AS pva ON c.insee_comm=pva.insee_comm WHERE (c.the_geom && 'BOX3D({$extent[0]} {$extent[1]},{$extent[2]} {$extent[3]})'::box3d) and pva.id_organisme=$idOrganisme ORDER BY c.nom_comm;";
  $rs = pg_query($conn, "SELECT c.nom_comm FROM commune AS c INNER JOIN projet_ville_associee AS pva ON c.insee_comm=pva.insee_comm WHERE (c.the_geom && 'BOX3D({$extent[0]} {$extent[1]},{$extent[2]} {$extent[3]})'::box3d) and pva.id_organisme=$idOrganisme ORDER BY c.nom_comm;");
  while($row = pg_fetch_assoc($rs)) { $commune[] = $row['nom_comm']; }
  //dbClose($conn);
  //echo "<pre>" ;
  //print_r($commune);
  return $commune;
}


function addExtraction($idOrganisme, $idContact, $source, $zoneGeo, $themes, $formatV, $formatR, $projection, $licence, $extent) {

  $ini = parse_ini_file("config/config.ini");  

  // Objet map
  $mapFile = getMapFileName($source);
  $map = ms_newMapObj($mapFile);

  // Fichier map (nom cours)
  $fichierMap = basename($mapFile);

  // Titre de la carte
  $titreCarte = $map->getMetaData('title');

  // Extent de la carte
  $extentCarte = explode(" ", $extent);

  // Extent de l'extraction
  $extentExtraction = getExtentExtract($idOrganisme, $zoneGeo, $extentCarte);
  if(!isset($extentExtraction)) { return array(true, "erreur d'extent."); }

  // Couches
  list($couchesV, $couchesR) = getLayers($map, $themes);

  // Récupération des communes
  $commune = getCommune($idOrganisme, $extentExtraction);
  
  // On serialize
  $extentCarte = serialize($extentCarte);
  $extentExtraction = serialize($extentExtraction);
  $couchesV = serialize($couchesV);
  $couchesR = serialize($couchesR);
  $commune = serialize($commune); 

  // On récupère le paramètre de validation automatique
  $validationAuto = getAdminExtractValue(VALIDATION_AUTO, NON);

  // Etat de l'extraction par défaut
  $etatDemande = ($validationAuto == OUI ? ATTENTE_TRAITEMENT : ATTENTE_VALIDATION);

  // Date de validation
  $dateValidation = ($etatDemande == ATTENTE_TRAITEMENT ? "'now()'" : "NULL");

  // Ajout de la demande
	$conn = dbConnect();
	$sql = "INSERT INTO projet_extraction (id_organisme,id_contact,date_demande,date_validation,etat_demande,zone_geograpique,extent_carte,extent_extraction,titre_carte,couche_vecteur,couche_raster,format_vecteur,format_raster,systeme_projection,fichier_licence,fichier_map,commune,remote_addr) VALUES ($idOrganisme,$idContact,now(),$dateValidation,'$etatDemande','".addslashes($zoneGeo)."','$extentCarte','$extentExtraction','".addslashes($titreCarte)."','".addslashes($couchesV)."','".addslashes($couchesR)."','".addslashes($formatV)."','".addslashes($formatR)."','".addslashes($projection)."','$licence','$fichierMap','".addslashes($commune)."','{$_SERVER['REMOTE_ADDR']}')";
	//echo $sql;
	$res = pg_query($conn, $sql);
	if(!$res) { return array(true, pg_last_error($conn)); }
  //dbClose($conn);

  // Envoi d'un mail
  $res = pg_query($conn, "SELECT to_char(projet_extraction.date_demande, 'DD/MM/YYYY HH24:MI') AS date_demande_str, projet_extraction.*, projet_organisme.libelle_organisme, projet_contact.nom_contact, projet_contact.prenom_contact, projet_contact.email_contact, (projet_contact.nom_contact||' '||projet_contact.prenom_contact) AS ayant_droit, projet_contact_libelle.complet_contact_libelle FROM projet_extraction INNER JOIN projet_organisme ON projet_organisme.id_organisme=projet_extraction.id_organisme INNER JOIN projet_contact ON projet_contact.id_contact=projet_extraction.id_contact LEFT JOIN projet_contact_libelle ON projet_contact.id_contact_libelle=projet_contact_libelle.id_contact_libelle WHERE projet_extraction.id_contact=".$idContact." ORDER BY projet_extraction.id_extraction DESC LIMIT 1");
  if(!$res) { return array(true, pg_last_error($conn)); }
  if(!($row = pg_fetch_assoc($res))) { return array(true, "Demande d'extraction introuvable."); }
  $mailObjet = "[PPIGE] Demande de téléchargement N°".$row['id_extraction'];
  $mailMessage = $row['complet_contact_libelle']." ".$row['ayant_droit'].", de l'organisme ".$row['libelle_organisme']." a fait le ".$row['date_demande_str']." une demande de téléchargement.<br>\nElle porte le numéro ".$row['id_extraction'];
  envoieMail($ini['adminMail'], $mailObjet, $mailMessage, $row['email_contact']);

  return array(false, "");      
}


function loadComplet() {
  $mapFileComplet = realpath("config/complet.map");
  $map = ms_newMapObj($mapFileComplet);
  $metaData = readMetaData($mapFileComplet);
  $groupList = getGroupList($map);
  $actifGroups = getActifGroups($map);
  $catList = getCatList($metaData);
  $complet = addslashes("{groupList:$groupList, actifGroups:$actifGroups, catList:$catList}");
  return $complet;
}

function loadDatasets() {
  $datasets = array();
  $link = myDbConnect();
  $result = mysql_query("SELECT dataset_id, product_type FROM datasets WHERE product_type <> '' ORDER BY product_type", $link);
  while($row = mysql_fetch_assoc($result)) {
    $dataset_id = $row['dataset_id'];
    $product_type = $row['product_type'];
    if(!isset($datasets[$product_type])) { $datasets[$product_type] = "$dataset_id#$product_type"; }
  }
  $str_datasets = '';
  foreach($datasets as $product_type => $dataset_id) {
    if($product_type == 'bdparcellaire') { $product_type = 'BDParcellaire - uniquement en Tif (2010)'; }
    if($product_type == 'Orthophoto') { $product_type = 'Orthophoto (2005)'; }
    if($product_type == 'Scan100') { $product_type = 'Scan100 (2007)'; }
    if($product_type == 'Scan25') { $product_type = 'Scan25 (2008)'; }
    if($product_type == 'Scan25 EDR') { $product_type = 'Scan25 EDR (2008)'; }
    if($product_type == 'ScanReg') { $product_type = 'ScanReg (2008)'; }
	
    if($str_datasets != '') { $str_datasets .= ','; }
    $str_datasets .= "{value:\"$dataset_id\",text:\"$product_type\"}";
  }
  $str_datasets = "[{$str_datasets}]";
  mysql_free_result($result);
  mysql_close($link);
  return $str_datasets;
}


function setCron($idLigne, $heure, $minute, $jourMois, $jourSemaine, $mois, $commande, $commentaire) {
	$debut = '# Les lignes suivantes sont gerees automatiquement via un script PHP. Merci de ne pas les editer manuellement';
	$fin = '# Les lignes suivantes ne sont plus gerees automatiquement';

  $section = array();
  $newCrontab = array();
	$modifie = false;
	$dansSection = false;
  $clefLigne = "# [".$idLigne."]";
  
	// on récupère le crontab courant
	exec('crontab -l', $oldCrontab);

  foreach($oldCrontab as $ligne) {
    if($ligne == $debut) { 
      $dansSection = true;
      continue; 
    }
    if($ligne == $fin) { 
      $dansSection = false;
      continue; 
    }
    if($dansSection) {
      $len = count($section);
      if($len) {
        if(substr($section[$len-1], 0, strlen($clefLigne)) == $clefLigne) {
          $section[$len-1] = $clefLigne." ".$commentaire; 
          $ligne = $minute.' '.$heure.' '.$jourMois.' '.$mois.' '.$jourSemaine.' '.$commande;
          $modifie = true;
        }
      }
      $section[] = $ligne;
    } else {
      $newCrontab[] = $ligne;
    }
  }
  
  array_unshift($section, $debut);
  if(!$modifie) {
    array_push($section, $clefLigne." ".$commentaire);
    array_push($section, $minute.' '.$heure.' '.$jourMois.' '.$mois.' '.$jourSemaine.' '.$commande);     
  }
  array_push($section, $fin);
  
  $cronFile = "tmp_cron";

	$f = fopen($cronFile, 'w');
	fwrite($f, implode("\n", array_merge($section, $newCrontab)));
	fclose($f);

	exec("crontab ".$cronFile,$atest);  
}		


function rasterTank($globalRequestId, $datasetId, $tlx, $tly, $brx, $bry, $capture, $proj, $path) {
  GLOBAL $host; // déclaré dans incphp/db/dbcon.php

  
  // On ajoute la requête
	/*
	$http = new Net_HTTP_Client();
	$rep = $http->connect("localhost", 80);
	$url = '/'.basename(getcwd())."/rastertank/epf_capture.php?global_request_id=$globalRequestId&dataset_id=$datasetId&tlx=$tlx&tly=$tly&brx=$brx&bry=$bry&capture=$capture&proj=$proj&path=$path";
	$status = $http->get($url);
  	$body = $http->getBody();
	$http->disconnect();
	*/
	
	$url = 'http://'.$host.'/'.basename(getcwd())."/rastertank/epf_capture.php?global_request_id=$globalRequestId&dataset_id=$datasetId&tlx=$tlx&tly=$tly&brx=$brx&bry=$bry&capture=$capture&proj=$proj&path=$path";
	//$url = 'http://localhost/viewer/test.php';
	echo $url;
	$body = readfile($url);
	
  	return $body;
}

function getDatasetsId($raster, $epsg) {
  $datasetsId = array();
  $link = myDbConnect2();
  foreach($raster as $r) {
    $result = mysql_query("select dataset_id from datasets, coordinate_reference_systems where coordinate_reference_system_id=coordinate_reference_system_global and epsg_crs_code=$epsg and product_type='".$r['name']."'", $link);
    if(mysql_num_rows($result) == 0) {
      $datasetsId[] = array('name'=>$r['name'], 'dataset_id'=>$r['dataset_id']);
    } else {
      $row = mysql_fetch_assoc($result);
      $datasetsId[] = array('name'=>$r['name'], 'dataset_id'=>$row['dataset_id']);
    }
    mysql_free_result($result);
  }
  mysql_close($link);
  return $datasetsId;
}

function getRasterTankStatus($processId) {
  $link = myDbConnect2();
  $result = mysql_query("select status, error_msg from requests where global_request_id like '".$processId."_%'", $link);
  $nbRows = mysql_num_rows($result);
  $realized = 0;
  $arrError = array();
  while($row = mysql_fetch_assoc($result)) {
    if($row['status'] == "FINISHED" || $row['status'] == "CANCELLED") {
      $realized++;
    } elseif($row['status'] == "ERROR" || $row['status'] == "UNKNOWN") {  
      $arrError[] = $row['error_msg'];
      $realized++;
    }
  }
  mysql_free_result($result);
  mysql_close($link);  
  return array('realized'=>($realized == $nbRows), 'error'=>(count($arrError)>0), 'msgError'=>$arrError);
}

function getDateFin($heureFin) {
  if(is_numeric($heureFin)) { return false; }
  $heures = intval($heureFin / 60);
  $minutes = $heureFin % 60;
  $timestampActuel = time();
  $timestampFin = mktime($heures, $minutes);
  if($timestampFin > $timestampActuel) { return $timestampFin; }
  $dateActuelle = getDate($timestampActuel);
  return mktime($heures, $minutes, 0, $dateActuelle['mon'], $dateActuelle['mday']+1, $dateActuelle['year']);
}


function createZipExtract($pathTemp, $zipName) {
  // Listage du répertoire et tri par taille décroissante
  $arrFic = array();    
  if(!($dh = opendir($pathTemp))) { return array(true, "Ouverture du répertoire $pathTemp a échoué."); }
  while(($file = readdir($dh)) !== false) {
    $fic = formatPath($pathTemp, $file);
    if(is_file($fic)) { $arrFic[$file] = getSize($fic); }
  }
  closedir($dh);
  arsort($arrFic);

  // Taille max non compressée (2 Giga)
  $maxFileSize = 2147483648;

  // Affectation des fichiers dans les zips (algo glouton)
  $arrZip = array();
  $arrFicKeys = array_keys($arrFic);  
  for($i=0; $i<count($arrFicKeys); $i++) {
    $ficI = $arrFicKeys[$i];
    if($arrFic[$ficI] == -1) { continue; }    
    $sizeI = $arrFic[$ficI];          
    $index = count($arrZip);
    $arrZip[] = array($ficI); 
    if($sizeI >= $maxFileSize) { continue; }
    $taille = $sizeI;    
    for($j=($i+1); $j<count($arrFicKeys); $j++) {
      $ficJ = $arrFicKeys[$j];      
      if($arrFic[$ficJ] == -1) { continue; }      
      $sizeJ = $arrFic[$ficJ];      
      if(($taille+$sizeJ) > $maxFileSize) { continue; }      
      $taille += $sizeJ;      
      $arrZip[$index][] = $ficJ;      
      $arrFic[$ficJ] = -1;
    }      
  }

  // Création des zip
  $arrResult = array('taille'=>0, 'fichier'=>array());
  $nbZip = count($arrZip);
  $padLen = strlen(strval($nbZip));
  $inf = pathinfo($zipName);
  $inf['filename'] = ($inf['extension'] ? substr($inf['basename'], 0, strlen($inf['basename'])-strlen($inf['extension'])-1) : $inf['basename']);
  for($i=0; $i<$nbZip; $i++) {
    $aZipper = tmpdir($pathTemp, "");
    if(!$aZipper) { return array(true, "La création du répertoire $aZipper a échoué."); }
    $nomZip = ($nbZip > 1 ? formatPath($inf['dirname'], $inf['filename']."_".str_pad($i+1, $padLen, "0", STR_PAD_LEFT).".".$inf['extension']) : $zipName);
    for($j=0; $j<count($arrZip[$i]); $j++) {
      $src = formatPath($pathTemp, $arrZip[$i][$j]);
      $dst = formatPath($aZipper, $arrZip[$i][$j]);
      if(!rename($src, $dst)) { return array(true, "Le déplacement du fichier $src vers $dst a échoué."); }
    }    
    $cmd = "zip -1 -j -r \"".$nomZip."\" \"".$aZipper."\"";
    exec($cmd, $output);
    if(!file_exists($nomZip)) { return array(true, "La création du fichier $nomZip a échoué."); }
    $zipLen = getSize($nomZip);
    $arrResult['fichier'][] = array('nom'=>$nomZip, 'taille'=>$zipLen);
    $arrResult['taille'] += $zipLen;
    $cmd = "rm -r -f \"$aZipper\""; 
    exec($cmd, $output);            
  }

  return array(false, $arrResult);
}


function copieTableAttributaire($table, $extension, $shema, $query, $pathTemp) {
  global $host, $user, $pass, $db, $host3;
  
  if(!count($shema)) { return array(false, ""); }

  // Vérification de la structure
  $msgError = "La structure des tables attributaires de la couche $table est incorrecte.";
  $arrLink = array();
  $arrField = array();
  $arrTableRight = array();
  for($i=0; $i<count($shema); $i++) {
    // 2 cellules (gauche, droite) par item
    if(count($shema[$i]) != 2) { return array(true, $msgError); }
    list($tableLeft, $fieldLeft) = explode(".", $shema[$i][0]);
    list($tableRight, $fieldRight) = explode(".", $shema[$i][1]);
    $tableLeft = ltrim($tableLeft);
    $fieldLeft = ltrim($fieldLeft);
    $tableRight = rtrim($tableRight);
    $fieldRight = rtrim($fieldRight);
    // La table à gauche du 1er item doit être obligatoirement la table de la couche
    if($i == 0 && $tableLeft != $table) { return array(true, $msgError); }
    // Pas de tables ou de champs vides
    if($tableLeft == "" || $fieldLeft == "" || $tableRight == "" || $fieldRight == "") { return array(true, $msgError); }
    // La table de la couche ne peut pas être à droite
    if($tableRight == $table) { return array(true, $msgError); }
    // Les tables à gauche doivent être d'abord défini à droite (sauf la table de la couche)
    if($tableLeft != $table && !in_array($tableLeft, $arrTableRight)) { return array(true, $msgError); }
    // Les tables à droite ne peuvent pas y être définies plus d'une fois
    if(in_array($tableRight, $arrTableRight)) { return array(true, $msgError); }

    if($tableLeft == $table && !in_array($fieldLeft, $arrField)) { $arrField[] = $fieldLeft; }
    $arrTableRight[] = $tableRight;
    $arrLink[] = array('left'=>array('table'=>$tableLeft, 'field'=>$fieldLeft), 'right'=>array('table'=>$tableRight, 'field'=>$fieldRight));
  }

	$conn = dbConnect();

  $arrTableTemp = array();

  // Création de la table temporaire de référence (celle de la couche)
	$res = pg_query($conn, sprintf($query, implode(",", $arrField)." INTO ".$table.$extension));
	if(!$res) { return array(true, pg_last_error($conn)); }
	$arrTableTemp[] = $table.$extension;

  $erreur = false;
  $msgErr = '';

  // Création des tables attributaires temporaires
  foreach($arrLink as $link) {
  	$res = pg_query($conn, "SELECT ".$link['right']['table'].".* INTO ".$link['right']['table'].$extension." FROM ".$link['right']['table']." INNER JOIN ".$link['left']['table'].$extension." ON ".$link['right']['table'].".".$link['right']['field']."=".$link['left']['table'].$extension.".".$link['left']['field']);
  	if($res) {
      $arrTableTemp[] = $link['right']['table'].$extension;
    } else {
      $erreur = true;
      $msgErr = pg_last_error($conn);
      break;
    }
  }

  // Exportation des tables (si pas d'erreur)
  if(!$erreur) {
    $ini = parse_ini_file(realpath("config/config.ini"));
    
    foreach($arrTableTemp as $tableTemp) {
      if($tableTemp != $table.$extension) {
        $fname = formatPath($pathTemp, substr($tableTemp, 0, -strlen($extension)).".shp");
        $cmd = $ini['pgsql2shp']." -h $host -u $user -P $pass -f \"$fname\" $db ".$tableTemp;
        exec($cmd, $output);
        if(!file_exists($fname)) {
          $output = implode("\n", $output);
          if(stristr($output, "empty table") === FALSE) {
            $erreur = true;
            $msgErr = "La création du fichier $fname a échoué : $output";
            break;
          }          
        }    
      }
    }
  }

  // Suppression des tables temporaires
	$res = pg_query($conn, "DROP TABLE ".implode(",", $arrTableTemp));

  //dbClose($conn);

  return array($erreur, $msgErr);
}


?>