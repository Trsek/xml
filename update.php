<?
require_once("config.php");
require_once("db/mte/mte.php");

# maintenance routine
$tabledit = new MySQLtabledit();
$count_update = 0;

# put to db again
if ($handle = opendir(dirname(DB_SLRC_NAME)))
{
	while (false !== ($file = readdir($handle)))
	{
		if( pathinfo($file, PATHINFO_EXTENSION) == "xml")
		{
			$_REQUEST['cm'] = explode("_", $file)[0];
			$xml = @new SimpleXMLElement(dirname(DB_SLRC_NAME) .'/' .$file, LIBXML_COMPACT, TRUE);
			# normallize xml attributes
			$atts_array = (array) $xml->attributes();
			$atts_array = $atts_array['@attributes'];
	
			# add to post
			$_POST = null;
			foreach ($db_fields[$_REQUEST['cm']] as $key)
			{
				if( in_array($key, $db_time_stamp))
					$atts_array[$key] = modify_date($atts_array[$key]);
				
				if( $key != 'id')
					$_POST[$key] = $atts_array[$key];
			}

			# database settings:
			$tabledit->database_connect_quick(DB_SLRC_NAME, $_REQUEST['cm']);
			$tabledit->primary_key = "id";
			# store it
			$_POST['mte_new_rec'] = "new";
			$tabledit->save_rec_directly();
			#
			$count_update++;
		}
	}
	closedir($handle);
}

$tabledit->database_disconnect();

echo "Updated SQL from $count_update xml files.<br>";

?>