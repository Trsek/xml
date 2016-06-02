<?
	if(IsSet($_REQUEST["XDEBUG_SESSION_START"]))
	{
		$_REQUEST["cm" ] = "etl";
		$HTTP_RAW_POST_DATA = '<etl it="0" um="0" fe="161480400" vb="999545302" vn="1000252536.54" db="144000" dn="128979" pm="0.9889" tm="24.497" ct="1" eb="0" en="0" vx="6001" fx="161482200" vy="5397.87" fy="161480600" qx="6000.0" tx="161480500" qy="5401.2" ty="161480500" bx="5999" dx="161482300" kx="6000.0" sx="161480500" ky="5360.4" sy="161481755" ns="true" fz="false" bt="90.24" bm="99.45" sg="87" pt="14"/>';
	}
	
	function modify_date($fe)
	{
		$cdate = mktime(substr($fe,5,2), substr($fe,7,2), 0, 1, 1, 2000+substr($fe,0,2), 0) + substr($fe,2,3)*24*60*60;
		return Date("Y.m.d H:i", $cdate);
	}
/*
	 $directory = 'data/' .Date("Ymd_His");
	 mkdir($directory, 0777, true);
	
	 @file_put_contents($directory. '/xml_globals.txt', var_export($GLOBALS, true));
	 @file_put_contents($directory. '/xml_cookie.txt',  var_export($_COOKIE,  true));
	 @file_put_contents($directory. '/xml_env.txt',     var_export($_ENV,     true));
	 @file_put_contents($directory. '/xml_files.txt',   var_export($_FILES,   true));
	 @file_put_contents($directory. '/xml_get.txt',     var_export($_GET,     true));
	 @file_put_contents($directory. '/xml_post.txt',    var_export($_POST,    true));
	 @file_put_contents($directory. '/xml_require.txt', var_export($_REQUIRE, true));
	 @file_put_contents($directory. '/xml_server.txt',  var_export($_SERVER,  true));
	 @file_put_contents($directory. '/xml_session.txt', var_export($_SESSION, true));
	 @file_put_contents($directory. '/xml_directory.txt',var_export($directory, true));
	
	 @file_put_contents($directory. '/xml_http_cookie_vars.txt', var_export($HTTP_COOKIE_VARS, true));
	 @file_put_contents($directory. '/xml_http_env_vars.txt',    var_export($HTTP_ENV_VARS, true));
	 @file_put_contents($directory. '/xml_http_get_vars.txt',    var_export($HTTP_GET_VARS, true));
	 @file_put_contents($directory. '/xml_http_post_files.txt',  var_export($HTTP_POST_FILES, true));
	 @file_put_contents($directory. '/xml_http_post_vars.txt',   var_export($HTTP_POST_VARS, true));
	 @file_put_contents($directory. '/xml_http_server_vars.txt', var_export($HTTP_SERVER_VARS, true));
	 @file_put_contents($directory. '/xml_http_session_vars.txt',var_export($HTTP_SESSION_VARS, true));
*/	
	require_once("../config.php");
	require_once("../db/mte/mte.php");
	$tabledit = new MySQLtabledit();
	
	# config branch
	if( $_GET['s'] == 'conf' ) {
		if( $_GET['pr'] == 'hora') {
			echo Date("Y,m,d,H,i,s");
			return;
		}
		return;
	}
	
	# cmdo
	if( $_GET['s'] == 'cmdo' ) {
		header('HTTP/1.1 404 Not Found', true, 404);
		return;
	}
	
	# data branch epe, etl, elc, rlg
	$xml_file = 'data/' .$_REQUEST['cm'] .Date("_Ymd_Hi") .'.xml';
	@file_put_contents($xml_file, $HTTP_RAW_POST_DATA);
	try {
		$xml = @new SimpleXMLElement($xml_file, LIBXML_COMPACT, TRUE);
	} catch (Exception $e) {
		# nema to zmysel
		$_REQUEST['cm'] = "";
	}
	
	# normallize xml attributes
	$atts_array = (array) $xml->attributes();
	$atts_array = $atts_array['@attributes'];
		
	# add to post
	$_POST = null;
	if( !empty($db_fields[$_REQUEST['cm']])) 
	{
		foreach ($db_fields[$_REQUEST['cm']] as $key)
		{
			if( in_array($key, $db_time_stamp))
				$atts_array[$key] = modify_date($atts_array[$key]);
			
			if( $key != 'id')
				$_POST[$key] = $atts_array[$key];
		}
	}

	# len tabulky ktore poznam
	# alebo niesu ziadne data na ulozenie
	if( empty($db_fields[$_REQUEST['cm']]) 
	 || empty($_POST)) 
	{
		header('HTTP/1.1 400 Bad Request', true, 400);
		return;
	}
	
	# database settings:
	$tabledit->database_connect_quick(DB_NAME, $_REQUEST['cm']);
	$tabledit->primary_key = "id";
	# store it
	$_POST['mte_new_rec'] = "new";
	$tabledit->save_rec_directly();
	$tabledit->database_disconnect();
?>
