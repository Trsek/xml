<?php
	if(IsSet($_REQUEST["XDEBUG_SESSION_START"]))
	{
//		$_REQUEST["cm" ] = "etl";
//		$HTTP_RAW_POST_DATA = '<etl it="0" um="0" fe="161480400" vb="999545302" vn="1000252536.54" db="144000" dn="128979" pm="0.9889" tm="24.497" ct="1" eb="0" en="0" vx="6001" fx="161482200" vy="5397.87" fy="161480600" qx="6000.0" tx="161480500" qy="5401.2" ty="161480500" bx="5999" dx="161482300" kx="6000.0" sx="161480500" ky="5360.4" sy="161481755" ns="true" fz="false" bt="90.24" bm="99.45" sg="87" pt="14"/>';
		$HTTP_RAW_POST_DATA = 
		'<?xml version="1.0" encoding="UTF-8"?>
		<DevEUI_uplink xmlns="http://uri.actility.com/lora">
		  <Time>2016-10-25T12:45:24.150+02:00</Time>
			<DevEUI>0004A30B001AB111</DevEUI>
			<FPort>1</FPort>
			<FCntUp>9</FCntUp>
			<ADRbit>1</ADRbit>
			<MType>4</MType>
			<FCntDn>5</FCntDn>
			<payload_hex>014964656e7469665374616e69636500001feba180000f7c63000f7383fde40000004b3e</payload_hex>
			<mic_hex>4368e1a6</mic_hex>
			<Lrcid>00000201</Lrcid>
			<LrrRSSI>-117.000000</LrrRSSI>
			<LrrSNR>-14.750000</LrrSNR>
			<SpFact>12</SpFact>
			<SubBand>G2</SubBand>
			<Channel>LC6</Channel>
			<DevLrrCnt>1</DevLrrCnt>
			<Lrrid>290000D5</Lrrid>
			<Late>0</Late>
			<LrrLAT>50.039040</LrrLAT>
			<LrrLON>15.767958</LrrLON>
			<Lrrs>
			  <Lrr>
			   <Lrrid>290000D5</Lrrid>
			   <Chain>0</Chain>
			   <LrrRSSI>-117.000000</LrrRSSI>
			   <LrrSNR>-14.750000</LrrSNR>
			   <LrrESP>-131.893097</LrrESP>
			  </Lrr>
			</Lrrs>
			<CustomerID>100000301</CustomerID>
			<CustomerData>{"alr":{"pro":"LORA/Generic","ver":"1"}}</CustomerData>
			<ModelCfg>0</ModelCfg>
			<DevAddr>001AB111</DevAddr>
		</DevEUI_uplink>';
		$HTTP_RAW_POST_DATA = '{"cmd":"gw","EUI":"0004A30B001C2189","ts":1485259823365,"fcnt":2,"port":1,"freq":867700000,"toa":2301,"dr":"SF12 BW125 4/5","ack":false,"gws":[{"rssi":-59,"snr":8.2,"ts":1485259823365,"gweui":"024B08FFFF0503C9","lat":50.0617701,"lon":15.7537326}],"data":"015069636f446174636f6d303132360000201a0c500000000000000000fdfd0000007af5"}';
	}

	require_once("../config.php");
	require_once("../db/mte/mte.php");
	require_once("../json.php");
	$tabledit = new MySQLtabledit();
	
	# config branch
	if( $_GET['cm'] == 'conf' ) {
		# je nutne analyzovat <xml> paket na parameter pr=hora (ale pri conf iny nepoznam, tak davam vzdy cas)
		echo Date("Y,m,d,H,i,s");
		return;
	}
	
	# cmdo
	if( $_GET['cm'] == 'cmdo' ) {
		header('HTTP/1.1 404 Not Found', true, 404);
		return;
	}
	
	# Lora modification
	if( !isset($_REQUEST['cm']))
		$_REQUEST['cm'] = "lora";
		
	# data branch epe, etl, elc, rlg
	$por = 0;
	do {
	  $xml_file = 'data/' .$_REQUEST['cm'] .Date("_Ymd_His_"). $por++ .'.xml';
	} while( file_exists($xml_file));

	if ( !isset($HTTP_RAW_POST_DATA))
		$HTTP_RAW_POST_DATA = file_get_contents( 'php://input' );

	# JSON modification
	if( isJson($HTTP_RAW_POST_DATA)) {
		@file_put_contents(str_replace('.xml', '.json', $xml_file), $HTTP_RAW_POST_DATA);
		$HTTP_RAW_POST_DATA = array2xml(json_decode($HTTP_RAW_POST_DATA, true));
	}
	
	try {
		@file_put_contents($xml_file, $HTTP_RAW_POST_DATA);
		$xml = @new SimpleXMLElement($xml_file, LIBXML_COMPACT, TRUE);
	} catch (Exception $e) {
		# something is wrong, fall to HTTP 400
		$_REQUEST['cm'] = "";
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
	if( !empty($db_fields[$_REQUEST['cm']])) 
	{
		foreach ($db_fields[$_REQUEST['cm']] as $key)
		{
			if( in_array($key, $db_time_stamp))
				$atts_array[$key] = modify_date($atts_array[$key]);

			if( $key == 'Time')
				$_POST['fe'] = modify_lora_date($atts_array[$key]);
				
			if( $key != 'id')
				$_POST[$key] = $atts_array[$key];
		}
	}

	# only tables which I know
	# or there are nothing to save
	if( empty($db_fields[$_REQUEST['cm']]) 
	 || empty($_POST)) 
	{
		header('HTTP/1.1 400 Bad Request', true, 400);
		return;
	}

	# database settings:
	$tabledit->database_connect_quick(DB_NAME, $_REQUEST['cm']);
	$tabledit->primary_key = "id";
	
	# multiple store xml
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
	
	# store it
	unset($_POST['id']);
	$_POST['mte_new_rec'] = "new";
	$tabledit->save_rec_directly();
	
	$tabledit->database_disconnect();
