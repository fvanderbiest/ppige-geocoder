<?php
//JML
// Connection parameters
// $host2 = "localhost";
// $host = "localhost";

// $dbcon_user = "postgres";
// $dbcon_pass = "pg";
// $dbcon_db = "carto_rgf93";
// $dbcon_db2 = "Drupal";

// JDS - Connection parameters to MySQL DataBase
$mysqlServer = "ppige-web.ataraxie.fr";
$mysqlServer2 = "ppige-web.ataraxie.fr";
$mysqlUser = 'root';
$mysqlPassWord = 'ataR00Tppige';
$mysqlBaseRT = 'rt';

// JDS - Connection to MySQL DataBase (bva1 à bva1)
function myDbConnect() {
  GLOBAL $mysqlServer, $mysqlUser, $mysqlPassWord, $mysqlBaseRT;
  
  if(!($link = mysql_connect($mysqlServer, $mysqlUser, $mysqlPassWord))) {
    die("Could not open connection to MySQL database server");
  }
  $db_selected = mysql_select_db($mysqlBaseRT, $link);
  if (!$db_selected) {
    die("Could not selected $mysqlBaseRT : ".mysql_error());
  }
  
  return $link;
}

// JDS - Connection to MySQL DataBase (bva2 à bva1)
function myDbConnect2() {
  GLOBAL $mysqlServer2, $mysqlUser, $mysqlPassWord, $mysqlBaseRT;
  
  if(!($link = mysql_connect($mysqlServer2, $mysqlUser, $mysqlPassWord))) {
    die("Could not open connection to MySQL database server");
  }
  $db_selected = mysql_select_db($mysqlBaseRT, $link);
  if (!$db_selected) {
    die("Could not selected $mysqlBaseRT : ".mysql_error());
  }
  
  return $link;
}

//JML - Connection to PostGis DataBase
function dbConnect() {
	// global $host, $dbcon_user, $dbcon_pass, $dbcon_db;
    $host = "localhost";
    $dbcon_db = "carto_rgf93";
    $dbcon_user = "postgres";
    $dbcon_pass = "pg";

	if(!($dbHandle = pg_connect("host=$host dbname=$dbcon_db user=$dbcon_user password=$dbcon_pass"))) {
		die("Could not open connection to PostGis database server");
	}
    pg_send_query($dbHandle, "SET CLIENT_ENCODING TO 'LATIN9'");
	return $dbHandle;
}

//JML - Connection to PostGis DataBase (pour connexion de BVA2 vers BVA1)
function dbConnect2() {
	// global $host2, $dbcon_user, $dbcon_pass, $dbcon_db;
    $host = "localhost";
    $dbcon_db = "carto_rgf93";
    $dbcon_user = "postgres";
    $dbcon_pass = "pg";

	if(!($dbHandle = pg_connect("host=$host dbname=$dbcon_db user=$dbcon_user password=$dbcon_pass port=5432"))) {
		die("Could not open connection to PostGis BVA2 database server");
	}
    pg_send_query($dbHandle, "SET CLIENT_ENCODING TO 'LATIN1'");
	return $dbHandle;
}
/*

edit BPL 30/09/2010
connexion à la base PG contenant les données carto

*/
function dbConnect3() {
	// global $host2, $dbcon_user, $dbcon_pass, $dbcon_db2;
    $host = "localhost";
    $dbcon_db = "Drupal";
    $dbcon_user = "postgres";
    $dbcon_pass = "pg";

	if(!($dbHandle = pg_connect("host=$host dbname=$dbcon_db user=$dbcon_user password=$dbcon_pass port=5432"))) {
		die("impossible d'ouvrir une connexion à la base $dbcon_db2");
	}
    pg_send_query($dbHandle, "SET CLIENT_ENCODING TO 'LATIN1'");
	return $dbHandle;
}
function dbClose($dbHandle) {
	// Close database connection
	return pg_close($dbHandle);
}
/*
//TBO - Connection to PostGis DataBase Drupal
function dbConnect4() {
	if(!($dbHandle = pg_connect("host=localhost dbname=Drupal user=postgres password=pg"))) {
		die("Could not open connection to Drupal database server");
	}
    pg_send_query($dbHandle, "SET CLIENT_ENCODING TO 'LATIN1'");
	return $dbHandle;
}

//TBO - Connection to PostGis DataBase Drupal
function dbConnect5() {
	if(!($dbHandle = pg_connect("host=localhost dbname=carto_rgf93 user=postgres password=pg"))) {
		die("Could not open connection to Drupal database server");
	}
    pg_send_query($dbHandle, "SET CLIENT_ENCODING TO 'LATIN1'");
	return $dbHandle;
}
*/

/*
 * Function taken from PEAR DB.php
 * @author Tomas V.V.Cox <cox@idecnet.com>
 */
function parseDSN($dsn)
{
    if (is_array($dsn)) {
        return $dsn;
    }

    $parsed = array(
        'phptype'  => false,
        'dbsyntax' => false,
        'username' => false,
        'password' => false,
        'protocol' => false,
        'hostspec' => false,
        'port'     => false,
        'socket'   => false,
        'database' => false
    );

    // Find phptype and dbsyntax
    if (($pos = strpos($dsn, '://')) !== false) {
        $str = substr($dsn, 0, $pos);
        $dsn = substr($dsn, $pos + 3);
    } else {
        $str = $dsn;
        $dsn = NULL;
    }

    // Get phptype and dbsyntax
    // $str => phptype(dbsyntax)
    if (preg_match('|^(.+?)\((.*?)\)$|', $str, $arr)) {
        $parsed['phptype']  = $arr[1];
        $parsed['dbsyntax'] = (empty($arr[2])) ? $arr[1] : $arr[2];
    } else {
        $parsed['phptype']  = $str;
        $parsed['dbsyntax'] = $str;
    }

    if (empty($dsn)) {
        return $parsed;
    }

    // Get (if found): username and password
    // $dsn => username:password@protocol+hostspec/database
    if (($at = strrpos($dsn,'@')) !== false) {
        $str = substr($dsn, 0, $at);
        $dsn = substr($dsn, $at + 1);
        if (($pos = strpos($str, ':')) !== false) {
            $parsed['username'] = rawurldecode(substr($str, 0, $pos));
            $parsed['password'] = rawurldecode(substr($str, $pos + 1));
        } else {
            $parsed['username'] = rawurldecode($str);
        }
    }

    // Find protocol and hostspec

    // $dsn => proto(proto_opts)/database
    if (preg_match('|^([^(]+)\((.*?)\)/?(.*?)$|', $dsn, $match)) {
        $proto       = $match[1];
        $proto_opts  = (!empty($match[2])) ? $match[2] : false;
        $dsn         = $match[3];

    // $dsn => protocol+hostspec/database (old format)
    } else {
        if (strpos($dsn, '+') !== false) {
            list($proto, $dsn) = explode('+', $dsn, 2);
        }
        if (strpos($dsn, '/') !== false) {
            list($proto_opts, $dsn) = explode('/', $dsn, 2);
        } else {
            $proto_opts = $dsn;
            $dsn = null;
        }
    }

    // process the different protocol options
    $parsed['protocol'] = (!empty($proto)) ? $proto : 'tcp';
    $proto_opts = rawurldecode($proto_opts);
    if ($parsed['protocol'] == 'tcp') {
        if (strpos($proto_opts, ':') !== false) {
            list($parsed['hostspec'], $parsed['port']) = explode(':', $proto_opts);
        } else {
            $parsed['hostspec'] = $proto_opts;
        }
    } elseif ($parsed['protocol'] == 'unix') {
        $parsed['socket'] = $proto_opts;
    }

    // Get dabase if any
    // $dsn => database
    if (!empty($dsn)) {
        // /database
        if (($pos = strpos($dsn, '?')) === false) {
            $parsed['database'] = $dsn;
        // /database?param1=value1&param2=value2
        } else {
            $parsed['database'] = substr($dsn, 0, $pos);
            $dsn = substr($dsn, $pos + 1);
            if (strpos($dsn, '&') !== false) {
                $opts = explode('&', $dsn);
            } else { // database?param1=value1
                $opts = array($dsn);
            }
            foreach ($opts as $opt) {
                list($key, $value) = explode('=', $opt);
                if (!isset($parsed[$key])) { // don't allow params overwrite
                    $parsed[$key] = rawurldecode($value);
                }
            }
        }
    }

    return $parsed;
}
?>
