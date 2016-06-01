<?
	if(IsSet($_REQUEST["XDEBUG_SESSION_START"]))
	{
		$_REQUEST["cm" ] = "etl";
		$HTTP_RAW_POST_DATA = '<etl it="0" um="0" fe="161480400" vb="999545302" vn="1000252536.54" db="144000" dn="128979" pm="0.9889" tm="24.497" ct="1" eb="0" en="0" vx="6001" fx="161482200" vy="5397.87" fy="161480600" qx="6000.0" tx="161480500" qy="5401.2" ty="161480500" bx="5999" dx="161482300" kx="6000.0" sx="161480500" ky="5360.4" sy="161481755" ns="true" fz="false" bt="90.24" bm="99.45" sg="87" pt="14"/>';
	}
/*
	 $directory = 'data/' .Date("Ymd_Hi");
	 mkdir($directory, 0777, true);
	
	 @file_put_contents($directory. '/xml_cookie.txt',  var_export($_COOKIE,  true));
	 @file_put_contents($directory. '/xml_env.txt',     var_export($_ENV,     true));
	 @file_put_contents($directory. '/xml_files.txt',   var_export($_FILES,   true));
	 @file_put_contents($directory. '/xml_get.txt',     var_export($_GET,     true));
	 @file_put_contents($directory. '/xml_post.txt',    var_export($_POST,    true));
	 @file_put_contents($directory. '/xml_require.txt', var_export($_REQUIRE, true));
	 @file_put_contents($directory. '/xml_server.txt',  var_export($_SERVER,  true));
	 @file_put_contents($directory. '/xml_session.txt', var_export($_SESSION, true));
	 @file_put_contents($directory. '/xml_globals.txt', var_export($GLOBALS, true));
	 @file_put_contents($directory. '/xml_directory.txt',var_export($directory, true));
	
	 @file_put_contents($directory. '/xml_http_cookie_vars.txt', var_export($HTTP_COOKIE_VARS, true));
	 @file_put_contents($directory. '/xml_http_env_vars.txt',    var_export($HTTP_ENV_VARS, true));
	 @file_put_contents($directory. '/xml_http_get_vars.txt',    var_export($HTTP_GET_VARS, true));
	 @file_put_contents($directory. '/xml_http_post_files.txt',  var_export($HTTP_POST_FILES, true));
	 @file_put_contents($directory. '/xml_http_post_vars.txt',   var_export($HTTP_POST_VARS, true));
	 @file_put_contents($directory. '/xml_http_server_vars.txt', var_export($HTTP_SERVER_VARS, true));
	 @file_put_contents($directory. '/xml_http_session_vars.txt',var_export($HTTP_SESSION_VARS, true));
*/	
	define(DB_NAME, "data/xmldata.sqlite");
	require_once("../config.php");
	require_once("../db/mte/mte.php");
	$tabledit = new MySQLtabledit();

	$xml_file = 'data/' .$_REQUEST['cm'] .Date("_Ymd_Hi") .'.xml';
	@file_put_contents($xml_file, $HTTP_RAW_POST_DATA);
	$xml = new SimpleXMLElement($xml_file, LIBXML_COMPACT, TRUE);
	
	# database settings:
	$tabledit->database_connect_quick(DB_NAME, $_REQUEST['cm']);
	$tabledit->primary_key = "id";
	
	# add to post
	$_POST = null;
	$_POST['mte_new_rec'] = "new";

	$atts_array = (array) $xml->attributes();
	$atts_array = $atts_array['@attributes'];
		
	foreach ($db_fields[$_REQUEST['cm']] as $key)
	{
		if( $key != 'id')
			$_POST[$key] = $atts_array[$key];
	}

	# store it
	$tabledit->save_rec_directly();
	$tabledit->database_disconnect();
?>