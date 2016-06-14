<?
require_once("config.php");
require_once("sort.php");
require_once("db/mte/mte.php");

if(IsSet($_REQUEST["XDEBUG_SESSION_START"]))
{
	$_FILES['xmlfile']['name'] = "SMS_Export_2016-06-07_1403.xml";
	$_FILES['xmlfile']['tmp_name'] = dirname(DB_SLRC_NAME) ."/SMS_Export_2016-06-07_1403.xml"; 
}

function getXMLKey($sms_body)
{
	try {
		$xml = @new SimpleXMLElement($sms_body);
		return (string)$xml['fe'];
	} 
	catch(Exception $e) {
//		echo $e->getMessage() ." = ". htmlspecialchars($sms_body, ENT_QUOTES) ."<br>";
		return "";
	}
}

# check if file already exist
function chekOrGenerFilename($filename)
{
	while( file_exists($filename)) {
		$name = explode(".", basename($filename));
		$pom  = explode("_", $name[count($name)-2]);
		# increment or add
		if( is_numeric($pom[count($pom)-1])) {
			$pom[count($pom)-1]++;
		} else {
			$pom[] = 1;
		}
		# merge
		$name[count($name)-2] = implode("_", $pom); 
		$filename = dirname($filename). "/". implode(".", $name);
	}
	return $filename;
} 

function xmlFindBase64($znakBase64)
{
	$BASE64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+//";

	for ($i=0; $i<strlen($BASE64); $i++) {
		if( $BASE64[$i] == $znakBase64 )
			return $i;
	}
	return 0;
}

# show form for choice xml file in computer 
if( empty($_FILES['xmlfile']['tmp_name'])) {
	echo "
		<form action='sms' method='POST' style='margin:0px auto;display:table;' ENCTYPE='multipart/form-data'>
			<input type='file' name='xmlfile' size=64>
			<input type='submit' name='import' value='Importuj XML'>
		</form>
		<hr>
	";
	return;
}

$local_xml = chekOrGenerFilename( dirname(DB_SLRC_NAME) ."/". $_FILES['xmlfile']['name']);
copy($_FILES['xmlfile']['tmp_name'], $local_xml);

$sms = array();
$sms_split = array();

# parse csv or XML?
if( strtolower(pathinfo($local_xml, PATHINFO_EXTENSION)) == "csv" )
{
	$csv = array_map('str_getcsv', file($local_xml));
	$xml = new SimpleXMLElement("<?xml version='1.0' encoding='UTF-8'?><sms></sms>");
	
	foreach ($csv as $csv_line)
	{
		$xml_child = $xml->addChild("sms");		// $csv_line[0]
		$xml_child->addChild("from",      $csv_line[2]);
		$xml_child->addChild("body",      $csv_line[count($csv_line)-1]);
		$xml_child->addChild("timestamp", $csv_line[5]);
		$xml_child->addChild("storage",   $csv_line[1]);
		$xml_child->addChild("nrpdus",    $csv_line[3]);
		$xml_child->addChild("pdu",       $csv_line[6]);
	}
}
else 
{
	$xml = new SimpleXMLElement($local_xml, LIBXML_COMPACT, TRUE);
}	
	
foreach ($xml->sms as $sms_text) 
{
	$sms_body = ltrim($sms_text->body[0]);

	# without prefix
	if( $sms_body[0] == '<') {
		$sms[getXMLKey($sms_body)] = $sms_body;
		continue;
	}
	
	# remove prefix
	$sms_body = $sms_body;
	$poz = strpos($sms_body, " ");
	$sms_body = ltrim(substr($sms_body, $poz+1, strlen($sms_body) - $poz));
		
	# have prefix, but undivided
	if( $sms_body[0] == '<') {
		$sms[getXMLKey($sms_body)] = $sms_body;
		continue;
	}

	# divide SMS, compute base64
	$xml_sms_magic  = xmlFindBase64($sms_body[2]);
	$xml_sms_magic |= xmlFindBase64($sms_body[1]) << 6;
	$xml_sms_magic |= xmlFindBase64($sms_body[0]) << 12;
	
	# values
	$part  = ($xml_sms_magic >> 10) & 0x0F;		# Part of message
	$total = ($xml_sms_magic >> 14) & 0x0F;     # Part < Total parts of message
	$index = $xml_sms_magic & 0x3FF;            # SMS Index
	
	$sms_split[$index][$part] = substr($sms_body, 3, strlen($sms_body) - 3);
}

# merge divide SMS
$count_bad = 0;
$count_update = 0;
foreach ($sms_split as $sms2)
{
	# check if they have all parts?
	if( empty($sms2[count($sms2)-1])) {
		$count_bad++;
		continue;
	}

	# merge - asort nefunguje
	$sms_body = "";
	for($i=0; $i < count($sms2); $i++)
		$sms_body .= $sms2[$i];
	
	$sms[getXMLKey($sms_body)] = $sms_body;
}

# sort SMS
asort($sms, SORT_STRING);

# database settings:
$tabledit = new MySQLtabledit();

# update to DB
foreach ($sms as $sms_body)
{
	try {
		$xml = @new SimpleXMLElement($sms_body);
	}
	catch(Exception $e) {
		$count_bad++;
		echo $e->getMessage() ." = ". htmlspecialchars($sms_body, ENT_QUOTES) ."<br>";
		continue;
	}
	
	# something is wrong
	if( $xml == null ) {
		$count_bad++;
		continue;
	}
	
	# normallize xml attributes
	$atts_array = (array) $xml->attributes();
	$atts_array = $atts_array['@attributes'];
	
	# tbl name
	$_REQUEST['cm'] = substr(explode(" ", $sms_body)[0], 1, 5);
	
	# add to post
	$_POST = null;
	foreach ($db_fields[$_REQUEST['cm']] as $key)
	{
		if( in_array($key, $db_time_stamp))
			$atts_array[$key] = modify_date($atts_array[$key]);
	
		if( $key != 'id')
			$_POST[$key] = $atts_array[$key];
	}
	
	# store it
	$_POST['mte_new_rec'] = "new";
	$tabledit->database_connect_quick(DB_SLRC_NAME, $_REQUEST['cm']);
	$tabledit->primary_key = "id";
	$tabledit->save_rec_directly();
	#
	$count_update++;
}
$tabledit->database_disconnect();
sort_tables();

echo "Updated SQL from $count_update SMS from file ". $_FILES['xmlfile']['name'] .".<br>";
if( $count_bad )
	echo "Mistake in '$count_bad' SMS's.<br>"
?>