<?php
header("Expires: ".gmdate("D, d M Y H:i:s")." GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
session_start();

require_once("incphp/extract.php");

if (isset($_GET['deconnexion'])) { unset($_SESSION['admin_ppige']); }
if (isset($_REQUEST['id'])) $idExtraction = $_REQUEST['id'];
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
  <head>
    <title>PPIGE: edition de l'extraction n&deg; <?php echo $idExtraction ?></title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" href="templates/edit_extract.css" type="text/css" />
    <script language="javascript" src="javascript/xmlhttp.js"></script>
    <script language="javascript" src="javascript/extract.js"></script>    
  </head>
  <body class="edit-extract-wrap">
    <form id="editExtractionForm-<?php echo $idExtraction?>">
        <?php echo editerExtraction($idExtraction); ?>
    </form>
  </body>
</html>