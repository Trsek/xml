<?
require_once("config.php");
require_once("db/mte/mte.php");

function modify_date($fe)
{
	$cdate = mktime(substr($fe,5,2), substr($fe,7,2), 0, 1, 1, 2000+substr($fe,0,2), 0) + substr($fe,2,3)*24*60*60;
	return Date("Y.m.d H:i", $cdate);
}

# maintenance ruotine
# put to db again
if ($handle = opendir(dirname(DB_SLRC_NAME)))
{
	while (false !== ($file = readdir($handle)))
	{
		if( pathinfo($file, PATHINFO_EXTENSION) == "xml")
		{
			$_REQUEST['cm'] = substr($file, 0, 3);
			$xml = @new SimpleXMLElement(dirname(DB_SLRC_NAME) .'/' .$file, LIBXML_COMPACT, TRUE);
			# normallize xml attributes
			$atts_array = (array) $xml->attributes();
			$atts_array = $atts_array['@attributes'];
	
			# add to post
			$_POST = null;
			foreach ($db_fields[$_REQUEST['cm']] as $key)
			{
				if( $key == "fe")
					$atts_array[$key] = modify_date($atts_array[$key]);
				
				if( $key != 'id')
					$_POST[$key] = $atts_array[$key];
			}

			# database settings:
			$tabledit = new MySQLtabledit();
			$tabledit->database_connect_quick(DB_SLRC_NAME, $_REQUEST['cm']);
			$tabledit->primary_key = "id";
			# store it
			$_POST['mte_new_rec'] = "new";
			$tabledit->save_rec_directly();
			$tabledit->database_disconnect();
		}
	}
	closedir($handle);
}

?>