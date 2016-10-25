<?php

function br2nl($text, $tags = "br") {
  $tags = explode(" ", $tags);

  foreach($tags as $tag)
  {
    $text = eregi_replace("<" . $tag . "[^>]*>", "\n", $text);
    $text = eregi_replace("</" . $tag . "[^>]*>", "\n", $text);
  }

  return($text);
}

function conformeCSV($str) {
  return str_replace(array("\r\n", "\n\r", "\n", "\r", ";"), " ", $str);  
}

//http://stackoverflow.com/questions/2305362/php-search-string-with-wildcards
//if string contents special characters, e.g. \.+*?^$|{}/'#, they should be \-escaped
function wildcard_match($pattern, $subject) {
  $pattern = strtr($pattern, array(
    '*' => '.*?', // 0 or more (lazy) - asterisk (*)
    '?' => '.', // 1 character - question mark (?)
  ));
  return preg_match("/$pattern/", $subject);
}

function envoieMail($mailDest, $mailObjet, $mailMessage, $mailFrom) {
  // Pour envoyer un mail HTML, l'en-tête Content-type doit être défini
  $headers  = 'MIME-Version: 1.0' . "\r\n";
  $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
  
  // En-têtes additionnels
  $headers .= 'To: <' . $mailDest . '>' . "\r\n";
  $headers .= 'From: <' . $mailFrom . '>' . "\r\n";

	return mail($mailDest, $mailObjet, "<html><body>".$mailMessage."</body></html>", $headers);
}

// Conflit si include avec drupal format_size()
function format_sizes($size, $round=0) {
  //Size must be bytes! 
  $sizes = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'); 
  for ($i=0; $size > 1024 && $i < count($sizes) - 1; $i++) $size /= 1024; 
  return round($size,$round).$sizes[$i]; 
} 

function tmpdir($path, $prefix) {
  // Use PHP's tmpfile function to create a temporary
  // directory name. Delete the file and keep the name.
  $tempname = tempnam($path,$prefix);
  if (!$tempname)
    return false;
  
  if (!unlink($tempname))
    return false;
  
  // Create the temporary directory and returns its name.
  if (mkdir($tempname))
    return $tempname;
  
  return false;
} 

function getSize($file) {
  return trim(`stat -c%s "$file"`);
} 

function copieFichier($source, $destination) {
  if(file_exists($source) && !file_exists($destination)) { copy($source, $destination); }
}

function supprimerFichier($fn) {
  if(file_exists($fn)) {
    $wd = getcwd(); // Save the current directory
    $fwd = dirname($fn);
    chdir($fwd);    
    // chmod($fwd, 0777);
    // chown($fwd, $user);

// $userInfo = posix_getpwuid(posix_getuid());
// $user = $userInfo['name']; 
// printr($userInfo) ;
    // chmod($fn, 0777);
    // chown($fn, $user);
    
        unlink($fn);
        // $cmd = "rm $fn";
        // passthru($cmd);
        // echo $cmd;
    chdir($wd);
  }
}

function formatFileName($fn) {
  return str_replace(" ", "_", strtolower(texteSansAccent($fn)));
}

function formatDirName($dn) {
  return eregi_replace("[^a-z0-9]", "_", strtolower(texteSansAccent($dn)));
}

function getDebutMois($d) {
  return tsToStr(mktime(0, 0, 0, $d['mon'], 1, $d['year']));

}

function getFinMois($d) {
  return tsToStr(mktime(0, 0, 0, $d['mon']+1, 0, $d['year']));
}

function tsToStr($ts) {
  return date("d/m/Y", $ts);
}

function formatPath($rep, $fic) {
  if($rep != '' && $fic != '') {
    if($rep[strlen($rep)-1] != '/' && $fic[0] != '/') { return $rep."/".$fic; }
    if($rep[strlen($rep)-1] == '/' && $fic[0] == '/') { return substr($rep, 0, -1).$fic; }
  } 
  return $rep.$fic;
}

function randomString($randStringLength) {
  $timestring = microtime();
  $secondsSinceEpoch=(integer) substr($timestring, strrpos($timestring, " "), 100);
  $microseconds=(double) $timestring;
  $seed = mt_rand(0,1000000000) + 10000000 * $microseconds + $secondsSinceEpoch;
  mt_srand($seed);
  $randstring = "";
  $len = intval($randStringLength/2)+($randStringLength%2);
  for($i=0; $i<$len; $i++) {
    $randstring .= mt_rand(0, 9);
    $randstring .= chr(ord('A') + mt_rand(0, 5));  
  }
  return(substr($randstring, 0, $randStringLength));
} 
 
function arrayPhpToClassJs($arrayPhp) {
  $str = '';
  foreach($arrayPhp as $key=>$value) {
    if($str != '') { $str .= ','; }
    if(is_array($value)) {
      $str .= "$key:".arrayPhpToClassJs($value);
    } else {
      $str .= "$key:\"$value\"";
    }
  }
  return '{'.$str.'}';
}

function arrayPhpToArrayJs($arrayPhp) {
  $str = '';
  foreach($arrayPhp as $item) {
    if($str != '') { $str .= ','; }
    if(is_array($item)) {
      $str .= arrayPhpToArrayJs($item);
    } else {
      $str .= "\"$item\"";
    }
  }
  return "[$str]"; 
}

function groupsToJs($groups) {
  $str = '';
  foreach($groups as $grp) {
    if($str != '') { $str .= ','; }
    $str .= '["'.$grp->getGroupName().'","'.$grp->getDescription().'","'.$grp->getBaseName().'",'.arrayPhpToArrayJs($grp->getGroupType()).']';
  }
  return "[$str]";
}

//Return a string extent from a box2d extent 
function box2dToExtent($box2d){
  $res = substr($box2d, 4, strlen($box2d)-5); 
  $res = strtr($res, " ", ","); 
  $aRes = explode(",", $res); 
  $extent = $aRes[0].",".$aRes[1].",".$aRes[2].",".$aRes[3];  
  return $extent; 
}

function texteSansAccent($texte){ 
  $accent='ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËéèêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ'; 
  $noaccent='AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn'; 
  $texte = strtr($texte,$accent,$noaccent); 
  return $texte;
} 

function writeFile($fileName, $str, $mode='w') {
  $h = fopen($fileName, $mode);
  fputs($h, $str);
  fclose($h);
}

function str_repl_nline($sReplace, $sString) {
   return str_replace(array("\r\n", "\n\r", "\n", "\r"), $sReplace, $sString);
}

function formatDateFr($today) {
  $jours = array('Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi');
  $mois  = array('janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juiller', 'août', 'septembre', 'octobre', 'novembre', 'décembre');
  
  return $jours[$today['wday']].' '.$today['mday'].' '.$mois[$today['mon']-1].' '.$today['year'];
}

function getFile($fileName) {
  $handle = fopen($fileName, 'r');
  $contenu = fread($handle, filesize($fileName));
  fclose($handle);
  return $contenu;
}

function getFileExtention($fileName) {
  $path_parts = pathinfo($fileName);
  return $path_parts['extension'];
}

function getFileName($path, $fileName) {
  $i = 0;
  $fn = $fileName;
  while(file_exists("$path$fn")) {
    $path_parts = pathinfo($fn);
    $path_parts['basename_we'] = substr($path_parts['basename'], 0, -(strlen($path_parts['extension']) + ($path_parts['extension'] == '' ? 0 : 1)));
    $fn = $path_parts['basename_we'].$i.($path_parts['extension'] == '' ? '' : '.'.$path_parts['extension']);
    $i++;    
  }
  return $fn; 
}

function listingDirectory($path) {
  $files = array();
  if ($handle = opendir($path)) {
    while (false !== ($file = readdir($handle))) {
      if($file != '.' && $file != '..') { $files[] = $file; }
    }
    closedir($handle);
  }
  return $files;
}

function replaceNL($str, $replace='') {
  return str_replace(array("\r\n", "\n\r", "\n", "\r"), $replace, $str);
}

// Retourne true si un tableau est séquentiel, false sinon
function isSequentialArray($arr) {
  if(count($arr)) {
    $keys = array_keys($arr);
    $max_length = count($arr) - 1;
    if(strcmp($keys[0], 0) == 0 and strcmp($keys[$max_length], $max_length) == 0) {
      for($i = 1; $i < $max_length; $i++) {
        if(strcmp($keys[$i], $i)) { return false; }
      }
      return true;
    }
    return false;
  }
}

// Décrit une variable php en son équivalent javascript
function toJSON($value) {
  $json = '';

  if(is_object($value)) {
    $classMethods = get_class_methods($value);

    if(in_array('jsonmethod', $classMethods) || in_array('JSONMethod', $classMethods)) {
      $JSONProperties = $value->JSONMethod();
      foreach($JSONProperties as $propertyName => $propertyValue) {
        if($json != '') { $json .= ','; }
        $json .= "$propertyName:".toJSON($propertyValue);
      }
      $json = '{'.$json.'}';

    } else {
      $classVars = get_object_vars($value);
      foreach($classVars as $varName => $varValue) {
        if($json != '') { $json .= ','; }
        $json .= "$varName:".toJSON($varValue);
      }
      $json = '{'.$json.'}';
    }

  } elseif(is_array($value)) {
    if(isSequentialArray($value)) {
      foreach($value as $val) {
        if($json != '') { $json .= ','; }
        $json .= toJSON($val);
      }
      $json = "[$json]";

    } else {
      foreach($value as $key => $val) {
        if($json != '') { $json .= ','; }
        $json .= "$key:".toJSON($val);
      }
      $json = '{'.$json.'}';
    }

  } else {
    $json = "'".addslashes(replaceNL($value))."'";
  }

  return $json;
}

// Ce script va effacer les balises HTML, les javascript
// et les espaces. Il remplace aussi quelques entités HTML
// courante en leur équivalent texte.
function HtmlToText($html) {
  $search = array ('@<script[^>]*?>.*?</script>@si', // Supprime le javascript
                   '@<[\/\!]*?[^<>]*?>@si',          // Supprime les balises HTML
                   '@([\r\n])[\s]+@',                // Supprime les espaces
                   '@&(quot|#34);@i',                // Remplace les entités HTML
                   '@&(amp|#38);@i',
                   '@&(lt|#60);@i',
                   '@&(gt|#62);@i',
                   '@&(nbsp|#160);@i',
                   '@&(iexcl|#161);@i',
                   '@&(cent|#162);@i',
                   '@&(pound|#163);@i',
                   '@&(copy|#169);@i',
                   '@&#(\d+);@e');                    // Evaluation comme PHP

  $replace = array ('',
                   '',
                   '\1',
                   '"',
                   '&',
                   '<',
                   '>',
                 ' ',
                 chr(161),
                 chr(162),
                 chr(163),
                 chr(169),
                 'chr(\1)');
  
  return preg_replace($search, $replace, $html);
}


/**
* Fonction d'envoie de mail avec phpmailer
* utilisant le smtp
* @parameters: 
            - mail destinataire
            - objet du mail
            - message à envoyer
            - mail de l'expéditeur(mail from)
* @return: {boolean} true si le mail a été bien envoyé, false sinon
**/
function envoieMailWithSmtp($mailDest, $mailObjet, $mailMessage, $mailFrom, $attachments = Array()){

	// Mod DEV redirect mail pour validation
    if($_SERVER['REMOTE_ADDR'] == "80.14.140.201") {
        $mailDest = "j.margail@memoris.fr";
    }
	//

    if($mailDest == '') return false ;
    
    $bRetour = false;
    $mail = new PHPmailer();
    $mail->IsSendMail(true);
    $mail->IsSMTP(true) ;
    //$mail->Host='91.206.199.194';
    $mail->IsHTML(true); // send as HTML
    
    $mail->From = $mailFrom ;
    $mail->FromName = 'PPIGE' ;
    
    $mails = explode(",", $mailDest);
    foreach($mails as $mailTo) {
        if(trim($mailTo) != "") $mail->AddAddress($mailTo);
    }
    
    //if($mailReplyTo != '') $mail->AddReplyTo($mailReplyTo);	
    $mail->Subject = $mailObjet ;
    $mail->Body = nl2br( $mailMessage );
	foreach($attachments as $attachment)
		$mail->AddAttachment($attachment); // Suppression de la fonction DEPRECATED set_magic_quotes_runtime incphp\phpmailer\class.phpmailer.php:1471 et 1475
	
    if(!$mail->Send()){ //Teste le return code de la fonction
      $bRetour = false; //failure
    }
    else{	  
      $bRetour = true; // success
    }
    //$mail->SmtpClose();
    unset($mail);
    
    return $bRetour ;
}

?>
