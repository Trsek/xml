<?
require_once("config.php");
require_once("db/mte/mte.php");

if(IsSet($_REQUEST["XDEBUG_SESSION_START"]))
{
	$_FILES['xmlfile']['name'] = "SMS_Export_2016-06-07_1403.xml";
	$_FILES['xmlfile']['tmp_name'] = dirname(DB_SLRC_NAME) ."/SMS_Export_2016-06-07_1403.xml";
}

# rows store to XML, delete it and save back
function sort_tables()
{
	global $db_fields;
	# open the database
	$db = new PDO('sqlite:'. DB_SLRC_NAME);
	
	# the database tables
	foreach ($db_fields as $db_tbl_name => $db_tbl_column)
	{
		$sql = "CREATE TABLE ". $db_tbl_name ."_sort (id INTEGER PRIMARY KEY";
		$columns = "";
		$have_fe = false;
		foreach($db_tbl_column as $column)
		{
			if( $column != 'id') {
				$sql .= ",". $column ." TEXT";
				$columns .= (empty($columns)? "": ","). $column;
			}
			if( $column == 'fe')
				$have_fe = true;
		}
		$sql .= ")";

		# don't have order column
		if( $have_fe == false )
			continue;

		# insert sort values to temporary table
		$db->exec($sql);
		$db->exec("INSERT INTO ". $db_tbl_name ."_sort (". $columns .") SELECT ". $columns ." FROM ". $db_tbl_name ." ORDER by fe");

		# rename table
		$db->exec("DROP TABLE ". $db_tbl_name);
		$db->exec("ALTER TABLE ". $db_tbl_name ."_sort RENAME TO ". $db_tbl_name);
	}
	$db = null;
}
?>