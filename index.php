<?php
if(IsSet($_REQUEST["XDEBUG_SESSION_START"]))
{
//	$_REQUEST['sms'] = "1";
//	$_REQUEST['reset'] = "1";
	$_REQUEST['tbl']='lora';
	$_GET['start']='0';
	$_GET['sort']='pm';
	$_GET['ad']='a';
	$_GET['search']='';
	$_GET['f']='';
//	$_GET['mte_a']='edit';
	$_GET['id']='2';
}

session_start();
require_once("config.php");
require_once("db/mte/mte.php");
$tabledit = new MySQLtabledit();

# need a reset db
if(( $_REQUEST['s'] == "reset") || isset($_REQUEST['reset']))
	require_once ("reset.php");
	
# insert all db from xml files
if( ($_REQUEST['s'] == "update") || isset($_REQUEST['update']))
	require_once ("update.php");
	
# import SMS from MyPhoneExplorer Export
if( ($_REQUEST['s'] == "sms") || isset($_REQUEST['sms']))
	require_once ("import.php");

# sort all tables
if( ($_REQUEST['s'] == "sort") || isset($_REQUEST['sort'])) {
	require_once ("sort.php");
	sort_tables();
}

# pocet zaznamov na jednej strane
if( isset($_REQUEST['count']))
	$tabledit->num_rows_list_view = $_REQUEST['count'];
	
# autorefresh
if( isset($_REQUEST['autorefresh'])) {
	header("Refresh: 7; URL=". $_SERVER['REQUEST_URI']);
}

# tbl define
$tbl = empty($_REQUEST['tbl'])? "lora": $_REQUEST['tbl']; 

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
$tabledit->database_connect_quick(DB_SLRC_NAME, $tbl);
$tabledit->primary_key = "id";
$tabledit->fields_required = array("id");
$tabledit->chart_column = $db_graph;
$tabledit->insert_button("#", "lora", "tbl=lora");
$tabledit->insert_button("#", "actual (epe)", "tbl=epe");
$tabledit->insert_button("#", "hourly (elc)", "tbl=elc");
$tabledit->insert_button("#", "daily (etl)", "tbl=etl");
$tabledit->insert_button("#", "log (rlg)", "tbl=rlg");
$tabledit->insert_button("#", "set (conf)", "tbl=conf");
$tabledit->insert_button("#", "import SMS", "sms");
$tabledit->insert_button("#", "reset db", "reset");
$tabledit->do_it( basename(__FILE__));
$tabledit->database_disconnect();

# bude graf
if( !empty($_REQUEST['graph']))
	echo "<div align='center'><img src='graph.php?tbl=".$tbl."&column=".$_REQUEST['graph']."'></div>";

# autorefresh
echo '<form method="GET" action="?"><div align="right">
	<input type="checkbox" name="autorefresh" '. (!empty($_REQUEST['autorefresh'])? 'checked':'') .' onchange="this.form.submit()">autorefresh
	</div></form>';

# connection settings
echo "<br><div align='center'>This server IP is: ". $_SERVER['SERVER_ADDR'] .':'. $_SERVER['SERVER_PORT'] ."</div>";
echo "
	<div align='center'>Software by Zdeno Sekerak (c) 2016</div>
	</body>
	</html>"
	;

?>
