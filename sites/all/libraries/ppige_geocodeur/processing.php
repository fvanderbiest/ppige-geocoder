<?php
	error_reporting("E_ALL");
	ini_set('display_errors', true);
	ini_set("memory_limit","1024M");
	
	$_SERVER["REMOTE_ADDR"] = "127.0.0.1"; // Evite les PHP Notice
	// 35s pour 10 adresses
	define('DRUPAL_ROOT', "/var/www/html/portail/");
	require_once DRUPAL_ROOT . '/includes/bootstrap.inc';	
	require_once(DRUPAL_ROOT."sites/default/settings.php");
	require_once(DRUPAL_ROOT."sites/all/libraries/ppige_extraction/incphp/db/dbcon.php");
	require_once(DRUPAL_ROOT."sites/all/libraries/ppige_extraction/incphp/phpmailer/class.phpmailer.php");
	require_once(DRUPAL_ROOT."sites/all/libraries/ppige_extraction/incphp/outils.php");
	require_once(DRUPAL_ROOT."sites/all/libraries/PHPExcel/PHPExcel.php");
	
	drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
	
	$logFile = "/var/log/geocoder.log";
	
	if(!function_exists("gcError")) {
		function gcError($msg) {
			echo $msg;
			exit;
		}
	}
	
	if(!function_exists("gcLog")) {
		function gcLog($data) {
			global $logFile;
			if(is_array($data)) {
				$data = print_r($data, true);
			}
			file_put_contents($logFile, "\n".$data, FILE_APPEND);
		}
	}
	
	gcLog("[".date("Y-m-d_H-i")."] Début d'un géocodage");
	
	/**
	 * Initialisation des variables
	 */
	global $user;
	
	$sendMail = true;
	$sqlQuery = true;
	$removeTmp = true;
	$sendEndMail = true;
	
	$fileFid = $argv[1];
	$projection = $argv[2];
	$format_de_sortie = $argv[3];
	$csvSeparator = $argv[4];
	$userUid = $argv[5];
	$hostname = $argv[6];
	
	// print_r($argv); 
	
	$fiabilityLimit = 0.8;
		
	$reqTpl = '<XLS xmlns:gml="http://www.opengis.net/gml" xmlns="http://www.opengis.net/xls" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" version="1.2" xsi:schemaLocation="http://www.opengis.net/xls http://schemas.opengis.net/ols/1.2/olsAll.xsd">
	<RequestHeader srsName="epsg:'.$projection.'"/>
	<Request requestID="1" version="1.2" methodName="LocationUtilityService">
		<GeocodeRequest returnFreeForm="false">
			<Address countryCode="StreetAddress">
				<StreetAddress>
					<Street>{street}</Street>
				</StreetAddress>
				<Place type="Municipality">{place}</Place>
				<PostalCode>{postalCode}</PostalCode>
			</Address>
		</GeocodeRequest>
	</Request>
	</XLS>';
	$valToReplace = Array("{street}", "{place}", "{postalCode}");
	
	$fromMail = "ppige@epf-npdc.fr";
	
	$ignUrl = "http://wxs.ign.fr/m8sf05x3s9g1c2w81qp1s4ic/geoportail/ols";
	$ogr2ogr = "/usr/local/bin/ogr2ogr";
	
	$tmpDir = "/san/ppige/ayant-droits/tmp/";
	$saveDir = "/san/ppige/ayant-droits/stockage/";
	
	$vrtTplFile = "/var/www/html/portail/sites/all/libraries/ppige_geocodeur/convert.vrt";
	
	$projLabel = Array(2154 => "Lambert 93",
		3950 => "Lambert 93 CC50",
		27561 => "Lambert 1 Nord",
		27571 => "Lambert 1 Carto",
		4326 => "WGS 84",
		4258 => "ETRS 89"
	);
	
	$separator = Array(
		"pointvirgule" => ";",
		"virgule" => ","
	);
	
	/**
	 * Notification du début de traitement
	 */
	$tmpDir = $tmpDir.microtime(true)."/";
	mkdir($tmpDir);
	
	$drupalCnx = dbConnect3();
	$cartoCnx = dbConnect2();
	
	// DEVELOPPEMENT
	// pg_set_client_encoding($drupalCnx, "UTF8");
	// pg_set_client_encoding($cartoCnx, "UTF8");
	
	
	$sql = "SELECT orga.field_organisme_target_id idorga, rlnm.realname realname, n.title organisme, u.mail mail
		FROM users u 
		INNER JOIN realname rlnm ON u.uid = rlnm.uid
		INNER JOIN field_data_field_organisme orga ON rlnm.uid = orga.entity_id
		INNER JOIN node n ON orga.field_organisme_target_id = n.nid
		WHERE u.uid = ".$userUid;
		
	
	$res = pg_query($drupalCnx, $sql);		
	$info = pg_fetch_assoc($res);
	$folder = $info["organisme"].".".$info["realname"];

	$folder = str_replace(
		Array('Á','À','Â','Ä','Ã','Å','Ç','É','È','Ê','Ë','Í','Ï','Î','Ì','Ñ','Ó','Ò','Ô','Ö','Õ','Ú','Ù','Û','Ü','Ý','á','à','â','ä','ã','å','ç','é','è','ê','ë','í','ì','î','ï','ñ','ó','ò','ô','ö','õ','ú','ù','û','ü','ý','ÿ'),
		Array('A','A','A','A','A','A','C','E','E','E','E','I','I','I','I','N','O','O','O','O','O','U','U','U','U','Y','a','a','a','a','a','a','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','u','u','u','u','y','y'),
		$folder
	);
	$folder = str_replace(Array("'", " ", "-"), Array("", ".", "_"), $folder);
	
	$finalFolder = $saveDir.strtolower($folder);
	if(!is_dir($finalFolder))
		mkdir($finalFolder);
	
	$sql = "SELECT nextval('projet_extraction_id_extraction_seq'::regclass) nextid";
	if($sqlQuery)
		$res = pg_query($cartoCnx, $sql);
	$row = pg_fetch_assoc($res);
	$idExt = $row["nextid"];
	
	$sql = "INSERT INTO extraction.projet_extraction (id_extraction, id_organisme, id_contact, date_demande, date_validation, etat_demande, zone_geograpique, format_vecteur, systeme_projection, demande_dvd, commune, remote_addr, date_debut_traitement, attente_raster, dossier_temp, dossier_contact, status_vecteur,mode) VALUES (".$idExt.", ".$info["idorga"].", ".$userUid.", now(), now(), 'TRAITEMENT_EN_COURS', 'géocodage', '".$format_de_sortie."', '".$projLabel[$projection]."', FALSE, 'Géocodage', '".$hostname."', now(), FALSE, '".$tmpDir."', '".$finalFolder."', 'EN_COURS', 'auto')";
	if($sqlQuery)
		pg_query($cartoCnx, $sql);
	
	/**
	 * Envoi du mail pour confirmer le traitement
	 */
	$mailObjet = "Geocodage en cours N°$idExt";
	$mailMessage = 
	"
	Bonjour ".$info["realname"].",

	Votre demande de géocodage n°$idExt est en cours de traitement.
	
	Consultez votre compte régulièrement, le géocodage n'est pas une opération particulièrement longue.

	Cordialement,
	L'équipe PPIGE
	ppige@epf-npdc.fr
	03 28 07 25 30 

	";
	
	if($sendMail)
		envoieMailWithSmtp($info["mail"], utf8_decode($mailObjet), utf8_decode($mailMessage), $fromMail);
	
	
	
	/**
	 * Traitement du fichier selon son extension
	 */
	$file = file_load($fileFid);
	if($file == null)
		gcError("Echec du téléchargement du fichier");
	$filePath = drupal_realpath($file->uri);
	
	if(!file_exists($filePath)) {
		$filePath = "/var/www/html/portail/sites/default/files/".str_replace("public://", "", $file->uri);
		if(!file_exists($filePath))
			gcError("Le fichier $filePath n'existe pas");
	}
	$fileExt = pathinfo($filePath, PATHINFO_EXTENSION);	
	
	
	// On extrait les adresses du fichier : soit un tableur Excel soit un CSV (= fichier text)
	$nameField = false;
	$descField = false;
	$addrList = Array();
	if($fileExt == "xls") {
		$objPHPExcel = PHPExcel_IOFactory::load($filePath);
		$sheet = $objPHPExcel->getSheet(0);
		foreach($sheet->getRowIterator() as $row) {
			// On boucle sur les cellule de la ligne
			$addr = Array();
			foreach ($row->getCellIterator() as $cell)
				$addr[] = $cell->getValue();
			$addrList[] = $addr;
			if(isset($addr[3])) $nameField = true;
			if(isset($addr[4])) $descField = true;
		}
	} else {
		$fileRes = fopen($filePath, 'r');
		while(($csvLine = fgetcsv($fileRes, 0, $separator[$csvSeparator])) !== false) {
			$addr = Array(trim($csvLine[0]), trim($csvLine[1]), trim($csvLine[2]));
			$addr[] = (isset($csvLine[3]))? trim($csvLine[3]) : "";
			$addr[] = (isset($csvLine[4]))? trim($csvLine[4]) : "";
			
			$addrList[] = $addr;
			
			if(isset($csvLine[3])) $nameField = true;
			if(isset($csvLine[4])) $descField = true;
		}
		fclose($fileRes);
	}
	
	/**
	 * Boucle sur les adresses, requête du Géocodeur et jointure avec la BD Adresse
	 */
	// Initialisation de l'objet Curl
	$curlCon = curl_init($ignUrl);
	$reqOpt = Array(
		CURLOPT_REFERER => "www.dev.ppige-npdc.fr",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13'
	);
	curl_setopt_array($curlCon, $reqOpt);
	// print_val($addrList);
	// Boucle sur les adresses
	$addrFound = Array();
	$addrNotFound = Array();
	foreach($addrList as $addr) {
		$reqXml = str_replace($valToReplace, Array($addr[0], $addr[2], $addr[1]), $reqTpl);
		// gcLog($reqXml);
		$reqData = http_build_query(
			Array(
				'xls' => $reqXml
			)
		);

		curl_setopt($curlCon, CURLOPT_URL, $ignUrl."?".$reqData);
		
		$addrXml = curl_exec($curlCon);
		$addrObj = new SimpleXMLElement($addrXml);
		$addrObj = $addrObj->Response->GeocodeResponse->GeocodeResponseList;
		
		// S'il y a au moins une réponse on prend uniquement la première
		if($addrObj["numberOfGeocodedAddresses"] != "0") {
			$resGeo = $addrObj->GeocodedAddress[0];
			
			// Indice de fiabilité
			$fiability = (float) $resGeo->GeocodeMatchCode["accuracy"];
			if($fiability > $fiabilityLimit) {
				$coord = $resGeo->xpath("gml:Point/gml:pos");
				$coord = explode(" ", $coord[0][0]);
				
				// Requête sur la BD Adresse (adresse la plus proche)
				$sql = "SELECT gid, numero, nom_ld, code_insee, distance, parcelle FROM (SELECT gid, numero, nom_ld, code_insee, parcelle, ST_Distance(bda.the_geom, ST_Transform(ST_SetSRID(ST_MakePoint(".$coord[0].", ".$coord[1]."), ".$projection."), 2154)) distance FROM bdad_adresse bda) t ORDER BY distance LIMIT 1";
				
				$res = pg_query($cartoCnx, $sql);
				$adrList = pg_fetch_all($res);
				
				$gid = $adrList[0]["parcelle"];
				
				$addrFound[] = Array(
					($resGeo->Address->StreetAddress->Building["number"])? $resGeo->Address->StreetAddress->Building["number"]->__toString() : "0",
					$resGeo->Address->StreetAddress->Street->__toString(),
					$resGeo->Address->Place[5]->__toString(),
					substr($resGeo->GeocodeMatchCode["accuracy"], 0, 7),
					($projection == "4326")? $coord[1] : $coord[0],
					($projection == "4326")? $coord[0] : $coord[1],
					$gid,
					(isset($addr[3]))? $addr[3] : "",
					(isset($addr[4]))? $addr[4] : ""
				);
				
				continue;
			}
		}
		
		// On log les adresses introuvables
		$addrNotFound[] = Array(
			$addr[0],
			$addr[1],
			$addr[2],
			(isset($addr[3]))? $addr[3] : "",
			(isset($addr[4]))? $addr[4] : ""
		);
		
		
	}
	curl_close($curlCon);
	
	
	/**
	 * Création des fichiers de sortie (adresses trouvées et non trouvées)
	 * On créé d'abord un csv qui est commun à tout les formats
	 */
	if(count($addrFound) != 0) {
		$colName = Array("NUMERO_RUE", "NOM_RUE", "COMMUNE", "FIABILITE", "X", "Y", "GID_BDAD");
		if($nameField) $colName[] = "NOM";
		if($descField) $colName[] = "DESCRIPTION";
		$foundCsv = $tmpDir."adresses_trouvees.csv";
		$csvRes = fopen($foundCsv, 'w');
		fputcsv($csvRes, $colName, ";", '"');
		foreach($addrFound as $addr) {
			fputcsv($csvRes, $addr, ";", '"');
		}
		fclose($csvRes);
	}
	
	if(count($addrNotFound) != 0) {
		$colName = Array("ADRESSE", "CODE_POSTAL", "VILLE");
		if($nameField) $colName[] = "NOM";
		if($descField) $colName[] = "DESCRIPTION";
		$notFoundCsv = $tmpDir."adresses_non_trouvees.csv";
		$csvRes = fopen($notFoundCsv, 'w');
		fputs($csvRes, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
		fputcsv($csvRes, $colName, ";", '"');
		foreach($addrNotFound as $addr) {
			fputcsv($csvRes, $addr, ";", '"');
		}
		fclose($csvRes);
	}
	
	/**
	 * Conversion du fichier des adresses trouvées si nécessaire
	 */
	if($format_de_sortie != "csv" && $format_de_sortie != "txt") {
		$vrtFile = $tmpDir."convert.vrt";
		$vrt = file_get_contents($vrtTplFile);
		
		// On ajoute les champs NOM et DESCRIPTION si nécessaire
		file_put_contents($vrtFile, str_replace(
			Array(
				"{csv_file}",
				"{projection}",
				"{name_field}",
				"{desc_field}"
			), Array(
				$foundCsv,
				$projection,
				($nameField)? '<Field name="NOM" src="NOM" type="String" />' : '',
				($descField)? '<Field name="DESCRIPTION" src="DESCRIPTION" type="String" />' : ''
			),$vrt)
		);
		
		switch($format_de_sortie) {
			case "shp":
				$cmd = $ogr2ogr.' -s_srs EPSG:'.$projection.' -t_srs EPSG:'.$projection.' -f "ESRI Shapefile" '.$tmpDir.'adresses_trouvees.shp '.$vrtFile;
			break;
			case "mif":
				$cmd = $ogr2ogr.' -s_srs EPSG:'.$projection.' -t_srs EPSG:'.$projection.' -f "MapInfo File" '.$tmpDir.'adresses_trouvees.mif '.$vrtFile;
			break;
			case "tab":
				$cmd = $ogr2ogr.' -s_srs EPSG:'.$projection.' -t_srs EPSG:'.$projection.' -f "MapInfo File" '.$tmpDir.'adresses_trouvees.tab '.$vrtFile;
		}
		exec($cmd);
		unlink($vrtFile);
		unlink($foundCsv);
	}
	
	/**
	 * On créé l'archive avec les résultats
	 */
	$finalFile = $finalFolder."/geocodage_".date("Y-m-d_H-i")."_epsg".$projection.".zip";
	
	$zip = new ZipArchive();
	
	if ($zip->open($finalFile, ZipArchive::CREATE)!==TRUE)
		gcError("Impossible d'ouvrir le fichier <$filename>\n");
	
	$tmpDirRes = opendir($tmpDir);
	while(false !== ($fichier = readdir($tmpDirRes))) {
		if($fichier != '.' && $fichier != '..') {
			$zip->addFile($tmpDir.$fichier, $fichier);
		}
	}
	
	$zip->close();
	
	/**
	 * On envoie un mail pour indiqué la fin de traitement
	 */
	/**
	 * Envoi du mail pour confirmer le traitement
	 */
	$mailObjet = "Geocodage N°$idExt terminé";
	$mailMessage = 
	"
	Bonjour ".$info["realname"].",

	Le traitement de votre géocodage n°$idExt est arrivé à son terme.
	
	Vous pouvez dès à présent télécharger le résultat depuis votre compte PPIGE.

	Cordialement,
	L'équipe PPIGE
	ppige@epf-npdc.fr
	03 28 07 25 30 

	";
	
	if($sendMail)
		envoieMailWithSmtp($info["mail"], utf8_decode($mailObjet), utf8_decode($mailMessage), $fromMail);
	
	/**
	 * On indique la fin du traitement et on transfert les fichiers dans le dossier finale
	 */
	$fileSize = filesize($finalFile);
	$sql = "UPDATE extraction.projet_extraction SET
		date_fin_traitement = now(),
		etat_demande = 'TERMINEE',
		status_vecteur = 'TERMINEE',
		taille_fichier = ".$fileSize.",
		fichier_zip = '".serialize(Array(0 => Array(
			"nom" => $finalFile,
			"taille" => $fileSize
		)))."'
		WHERE id_extraction = ".$idExt;
	
	gcLog("[".date("Y-m-d_H-i")."] Fin d'un géocodage. Fichier disponible : $finalFile");
	
	if($sqlQuery)
		pg_query($cartoCnx, $sql);
	
	if($removeTmp)
		file_unmanaged_delete_recursive($tmpDir);
?>