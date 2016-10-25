--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

SET search_path = public, pg_catalog;

--
-- Name: webform_submissions_sid_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('webform_submissions_sid_seq', 37, true);


--
-- Data for Name: webform; Type: TABLE DATA; Schema: public; Owner: postgres
--

DELETE FROM webform;

INSERT INTO webform VALUES (3401, 61, '<?php
	echo $_SESSION["resultat_geocode"];
?>', 'php_code', '<confirmation>', 1, 0, 0, 0, 0, 'Valider', -1, -1, -1, -1, 1, 0, 0, 0, 1, 'Début', 'Terminé', 0, '', '', '', '', 'filtered_html', '', '', '<?php
	require_once(DRUPAL_ROOT."/sites/all/libraries/PHPExcel/PHPExcel.php");
	
	function print_val($val) {
		echo "<pre>";
		print_r($val);
		echo "</pre>";
		exit;
	}
	
	/**
	 * Initialisation des variables
	 */
	global $user;
	
	$phpBin = "/usr/local/bin/php";
	$phpScript = "/var/www/html/portail/sites/all/libraries/ppige_geocodeur/processing.php";
	
	// DEVELOPPEMENT
	$userUid = $user->uid;
	$hostname = $user->hostname;
	$fileFid = $form_state["values"]["submitted"][1];
	$projection = $form_state["values"]["submitted"][2];
	$format_de_sortie = $form_state["values"]["submitted"][3];
	$csvSeparator = $form_state["values"]["submitted"][10];
	
	$threadCmd = $phpBin." -q  $phpScript $fileFid $projection $format_de_sortie $csvSeparator $userUid $hostname > /var/log/geocoder.log 2>&1 &";
	exec($threadCmd);
	
	
	/**
	 * Calcule de la durée de traitement
	 */
	$file = file_load($fileFid);
	if($file == null)
		$_SESSION["resultat_geocode"] = "Echec du téléchargement du fichier, veuillez réessayer";
	$filePath = $file->uri;
	
	if(!file_exists($filePath))
		$_SESSION["resultat_geocode"] = "Echec du téléchargement du fichier, veuillez réessayer";
	
	$fileExt = pathinfo($filePath, PATHINFO_EXTENSION);	
	
	$addrList = Array();
	if($fileExt == "xls") {
		$objPHPExcel = PHPExcel_IOFactory::load($filePath);
		$sheet = $objPHPExcel->getSheet(0);
		$nbAddr = $sheet->getHighestRow();
	} else {
		$nbAddr = count(file($filePath));
	}
	
	$seconds = $nbAddr * 3.3;
	
	$heures = floor($seconds / 3600);
	$minutes = floor(($seconds - ($hours*3600)) / 60);
	$seconds = floor($seconds % 60);
	
	$temps_traitement = "";
	if($hours!=0)
		$temps_traitement .= $heures."h";
	
	if($minutes!=0)
		$temps_traitement .= $minutes."min";
	
	if($seconds!=0)
		$temps_traitement .= $seconds."s";
	
	$_SESSION["resultat_geocode"] = "<p><strong>Votre demande de géocodage a bien été transmise. Le résultat sera prêt dans environ ".$temps_traitement."</strong></p>";
?>');


--
-- Data for Name: webform_component; Type: TABLE DATA; Schema: public; Owner: postgres
--

DELETE FROM webform_component;

INSERT INTO webform_component VALUES (3401, 6, 0, 'confirmation', 'Confirmation', 'markup', '<p><span style="color: #222222; font-family: ''Droid sans'', ''Helvetica neue'', Arial, sans-serif; font-size: 12px; line-height: 18px; text-align: justify;">Vous êtes sûr de vouloir faire ça?! Vraiment sûr??</span></p>', 'a:2:{s:6:"format";s:13:"filtered_html";s:7:"private";i:0;}', 0, 15);
INSERT INTO webform_component VALUES (3401, 10, 0, 'separateur', 'Séparateur', 'select', 'pointvirgule', 'a:14:{s:5:"items";s:25:"pointvirgule|;
virgule|,";s:8:"multiple";i:0;s:13:"title_display";s:6:"inline";s:7:"private";i:0;s:15:"wrapper_classes";s:0:"";s:11:"css_classes";s:0:"";s:6:"aslist";i:1;s:7:"optrand";i:0;s:12:"other_option";N;s:10:"other_text";s:8:"Autre...";s:11:"description";s:0:"";s:11:"custom_keys";b:0;s:14:"options_source";s:0:"";s:8:"analysis";b:1;}', 0, 9);
INSERT INTO webform_component VALUES (3401, 1, 0, 'fichier_adresse', 'Chargement du fichier adresses', 'file', '', 'a:6:{s:9:"directory";s:9:"geodocage";s:13:"title_display";s:6:"inline";s:7:"private";i:0;s:15:"wrapper_classes";s:0:"";s:11:"css_classes";s:0:"";s:9:"filtering";a:3:{s:4:"size";s:4:"2 MB";s:5:"types";a:3:{i:0;s:3:"txt";i:1;s:3:"xls";i:2;s:3:"csv";}s:13:"addextensions";s:3:"csv";}}', 1, 8);
INSERT INTO webform_component VALUES (3401, 2, 0, 'projection', 'Projection', 'select', '2154', 'a:7:{s:5:"items";s:109:"2154|Lambert 93
3950|Lambert 93 CC50
27561|Lambert 1 Nord
27571|Lambert 1 Carto
4326|WGS 84
4258|ETRS 89";s:8:"multiple";i:0;s:13:"title_display";s:6:"before";s:7:"private";i:0;s:15:"wrapper_classes";s:0:"";s:11:"css_classes";s:0:"";s:6:"aslist";i:0;}', 1, 10);
INSERT INTO webform_component VALUES (3401, 3, 0, 'format_de_sortie', 'Format de sortie', 'select', 'shp', 'a:7:{s:5:"items";s:51:"shp|Shape
mif|Mif/Mid
tab|TAB
csv|CSV
txt|Texte";s:8:"multiple";i:0;s:13:"title_display";s:6:"before";s:7:"private";i:0;s:15:"wrapper_classes";s:0:"";s:11:"css_classes";s:0:"";s:6:"aslist";i:0;}', 1, 11);
INSERT INTO webform_component VALUES (3401, 9, 0, 'text_lancement_du_geocodage', 'Lancement du géocodage', 'markup', '<p><strong>Lancement du géocodage</strong></p>', 'a:2:{s:6:"format";s:9:"full_html";s:7:"private";i:0;}', 0, 13);
INSERT INTO webform_component VALUES (3401, 8, 0, 'presentation', 'Présentation', 'markup', '<p><strong>Text de présentation de la fonction de géocodage à définir avec PPIGE lors de la réunion de démarrage du projet</strong></p>', 'a:2:{s:6:"format";s:9:"full_html";s:7:"private";i:0;}', 0, 7);
INSERT INTO webform_component VALUES (3401, 5, 0, 'bouton_suivant', 'Bouton suivant', 'pagebreak', '', 'a:3:{s:15:"next_page_label";s:7:"Suivant";s:15:"prev_page_label";s:7:"Annuler";s:7:"private";i:0;}', 0, 14);


--
-- Data for Name: webform_conditional; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: webform_conditional_rules; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: webform_emails; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: webform_last_download; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: webform_roles; Type: TABLE DATA; Schema: public; Owner: postgres
--

DELETE FROM webform_roles webform_roles;

INSERT INTO webform_roles VALUES (3401, 1);
INSERT INTO webform_roles VALUES (3401, 2);


--
-- Data for Name: webform_submissions; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: webform_submitted_data; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- PostgreSQL database dump complete
--

