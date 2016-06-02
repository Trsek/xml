<?php
if(IsSet($_REQUEST["XDEBUG_SESSION_START"]))
{
//	$_REQUEST['reset'] = "1";
	$_REQUEST['tbl']='epe';
	$_GET['start']='0';
	$_GET['ad']='d';
	$_GET['sort']='vb';
	$_GET['search']='';
	$_GET['f']='';
//	$_GET['mte_a']='edit';
	$_GET['id']='2';
}

define(DB_NAME, "SLRCAppTerm/data/xmldata.sqlite");

session_start();
require_once("config.php");
require_once("db/mte/mte.php");
$tabledit = new MySQLtabledit();

# need a reset db
if( !empty($_REQUEST['reset'])) {
	# zmazeme subor
	unlink(DB_NAME);

	# zmazeme xml subory
	if ($handle = opendir(dirname(DB_NAME)))
	{
		while (false !== ($file = readdir($handle)))
		{
			if( pathinfo($file, PATHINFO_EXTENSION) == "xml")
			{
				$filename = dirname(DB_NAME) .'/' .$file;
				if( is_file($filename))
					unlink($filename);
			}
		}
		closedir($handle);
	}
	
	# open the database
	$db = new PDO('sqlite:'. DB_NAME);
	
	# create the database tables
	foreach ($db_fields as $db_tbl_name => $db_tbl_column)
	{
		$sql = "CREATE TABLE ". $db_tbl_name ." (id INTEGER PRIMARY KEY";
		foreach($db_tbl_column as $column)
		{
			if( $column != 'id')
				$sql .= ",". $column ." TEXT"; 
		}
		$sql .= ")";
		$db->exec($sql);
	}
	$db = null;
}

# insert all db from xml files
if( $_REQUEST['s'] == "update" )
	require_once (explode('/', DB_NAME)[0] ."/update.php");


# tbl define
$tbl = empty($_REQUEST['tbl'])? "epe": $_REQUEST['tbl']; 

# the fields you want to see in "list view"
$tabledit->fields_in_list_view = $db_fields[$tbl];
	
echo "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>
	<html>
	<head>
	<meta HTTP-EQUIV='Content-Type' CONTENT='text/html; charset=UTF-8'>
	<title>XML tables</title>
	</head>
	<body>
	";


# database settings:
$tabledit->database_connect_quick(DB_NAME, $tbl);
$tabledit->primary_key = "id";
$tabledit->fields_required = array("id");
$tabledit->insert_button("#", "actual (epe)", "tbl=epe");
$tabledit->insert_button("#", "hourly (elc)", "tbl=elc");
$tabledit->insert_button("#", "daily (etl)", "tbl=etl");
$tabledit->insert_button("#", "log (rlg)", "tbl=rlg");
$tabledit->insert_button("#", "reset db", "reset=1");
$tabledit->do_it( basename(__FILE__));
$tabledit->database_disconnect();

# connection settings
echo "<br><div align='center'>This server IP is: ". $_SERVER['SERVER_ADDR'] .':'. $_SERVER['SERVER_PORT'] ."</div>";
echo "
	<div align='center'>Software by Zdeno Sekerak (c) 2016</div>
	</body>
	</html>"
	;

?>
