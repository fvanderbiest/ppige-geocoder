<?php

function _InitGroupData($sNomFichier){
  $metaData = readMetaData($sNomFichier);
  for ($nCompteur=0; $nCompteur<count($metaData); $nCompteur++) {
    $md = explode('" "', $metaData[$nCompteur]);
    $md[0] = substr($md[0], 1, strlen($md[0]));
    $md[1] = substr($md[1], 0, strlen($md[1])-1);
    $attrName = array('cat', 'catalwayschecked'); // liste des attributs à ignorer
    $attrFind = strtolower(trim($md[0]));
    $attrGood = true;
    for($i=0; $i<count($attrName); $i++) {
      if ($attrFind == $attrName[$i] || substr($attrFind, 0, strlen($attrName[$i])+1) == $attrName[$i].'.') {
        $attrGood = false;
        break;
      }
    }
    if ($attrGood == true){
      $arrTmp = preg_split('/[\s,]+/', $md[1]);
      $_SESSION[trim($md[0])] = array_filter($arrTmp, "pasVide");
    }
  }
}

function pasVide($var) {
  return (isset($var) && $var != '');
}

function initCat($metaData) {
  for ($nCompteur=0; $nCompteur<count($metaData); $nCompteur++) {
    $md = explode('" "',$metaData[$nCompteur]);
    $md[0] = substr($md[0],1,strlen($md[0]));
    $md[1] = substr($md[1],0,strlen($md[1])-1);
    $attrName = 'cat';
    $attrFind = strtolower(trim($md[0]));
    if ($attrFind == $attrName || substr($attrFind, 0, strlen($attrName)+1) == $attrName.'.') {
      $CatGroup = explode("||",$md[1]);
      $Tabcate =  preg_split('/[\s,]+/',$CatGroup[1]);
      for ($nCompteurCate=1; $nCompteurCate<count($Tabcate); $nCompteurCate++) {
        $categories[$CatGroup[0]][$nCompteurCate-1] = $Tabcate[$nCompteurCate];
      }
    }
  }
  return $categories;
}

function getAttr($metaData, $attrName) {
  $arr = array();
  for ($nCompteur=0; $nCompteur<count($metaData); $nCompteur++) {
    $md = explode('" "',$metaData[$nCompteur]);
    $md[0] = substr($md[0],1,strlen($md[0]));
    $md[1] = substr($md[1],0,strlen($md[1])-1);
    $attrName = strtolower($attrName);
    $attrFind = strtolower(trim($md[0]));
    if ($attrFind == $attrName || substr($attrFind, 0, strlen($attrName)+1) == $attrName.'.') {
      $CatGroup = explode("||",$md[1]);
      $Tabcate =  preg_split('/[\s,]+/',$CatGroup[1]);
      $gprs = array();
      for ($nCompteurCate=1; $nCompteurCate<count($Tabcate); $nCompteurCate++) {
        $gprs[] = trim($Tabcate[$nCompteurCate]);
      }
      $arr[] = array(trim($CatGroup[0]), $gprs);
    }
  }
  return $arr;
}

function readMetaData($psNomFichier) {
  $sLigne = array();
  if (file_exists($psNomFichier)) {
    $tableau = file($psNomFichier);
    $bWeb = false;
    $bMetadata = false;
    while(list($cle, $val) = each($tableau)) {
      $val = trim($val);
      $keyWord = strtolower(getKeyWord($val));
      if ($keyWord == 'web') { $bWeb = true; }
      if ($bWeb == true && $keyWord == 'metadata') { $bMetadata = true; }
      if ($bWeb == true && $bMetadata == true && $keyWord != 'metadata') {
        if ($keyWord == 'end') { break; }
        $sLigne[] = strtr($val, array("\t" => " ", "  " => " "));
      }
    }
  }
  return $sLigne;
}

function getKeyWord($line) {
  $arr = preg_split("/[\s\t#]+/", trim($line));
  return $arr[0];
}

?>
