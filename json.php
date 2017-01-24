<?php
require_once("../config.php");

# is it JSON format?
function isJson($value)
{
	if( !empty($value))
	{
		if( $value[0] == '{')
			return true;
	}
	return false;
}

# convert JSON to XML
function array2xml($array, $xml = false)
{
	global $map_fields;
	
	if( $xml === false)
		$xml = new SimpleXMLElement('<DevEUI_uplink/>');

	foreach($array as $key => $value)
	{
		if( !empty($map_fields[$key]))
			$key = $map_fields[$key];
		
		if( is_array($value))
			array2xml($value[0], $xml->addChild($key));
		else
			$xml->addChild($key, $value);
	}

	return $xml->asXML();
}
?>