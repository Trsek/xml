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
			try {
				$xml = @new SimpleXMLElement(dirname(DB_SLRC_NAME) .'/' .$file, LIBXML_COMPACT, TRUE);
			}
			catch(Exception $e) {
				continue;					
			}
			# normallize xml attributes
			$atts_array = (array) $xml->attributes();
			$atts_array = $atts_array['@attributes'];

			# propably lora
			if( empty($atts_array))
				$atts_array = (array) $xml;

			# special parsing for elgas payload
			if( !empty($atts_array['payload_hex'])) {
				foreach(payload_elgas($atts_array['payload_hex']) as $payload_key => $payload_value)
					$atts_array[$payload_key] = $payload_value;
			}
				
			# add to post
			$_POST = null;
			foreach ($db_fields[$_REQUEST['cm']] as $key)
			{
				if( in_array($key, $db_time_stamp))
					$atts_array[$key] = modify_date($atts_array[$key]);

				if( $key == 'Time')
					$_POST['fe'] = modify_lora_date($atts_array[$key]);
				
				if( $key != 'id')
					$_POST[$key] = $atts_array[$key];
			}

			# database settings:
			$tabledit->database_connect_quick(DB_SLRC_NAME, $_REQUEST['cm']);
			$tabledit->primary_key = "id";
			
			# multiple store
			if( $xml->DevLrrCnt > 0 ) {
				for($i=0; $i<$xml->DevLrrCnt; $i++)
				{
					$atts_array = (array) $xml->Lrrs->Lrr[$i];
					foreach ($atts_array as $key => $value) 
					{
						if( in_array('Lrr_'.$key, $db_fields[$_REQUEST['cm']])) {
							$_POST['Lrr_'.$key] = $value;
						}
					}
				}
			}

			# multiple store json
			if( isset($xml->gws)) {
				$atts_array = (array) $xml->gws;
				foreach ($atts_array as $key => $value)
				{
					if( in_array($key, $db_fields[$_REQUEST['cm']])) {
						$_POST[$key] = $value;
					}
				}
			}
				
			# single store it
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