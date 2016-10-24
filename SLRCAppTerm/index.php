<?
	if(IsSet($_REQUEST["XDEBUG_SESSION_START"]))
	{
//		$_REQUEST["cm" ] = "etl";
//		$HTTP_RAW_POST_DATA = '<etl it="0" um="0" fe="161480400" vb="999545302" vn="1000252536.54" db="144000" dn="128979" pm="0.9889" tm="24.497" ct="1" eb="0" en="0" vx="6001" fx="161482200" vy="5397.87" fy="161480600" qx="6000.0" tx="161480500" qy="5401.2" ty="161480500" bx="5999" dx="161482300" kx="6000.0" sx="161480500" ky="5360.4" sy="161481755" ns="true" fz="false" bt="90.24" bm="99.45" sg="87" pt="14"/>';
		$HTTP_RAW_POST_DATA = 
		'<?xml version="1.0" encoding="UTF-8"?>
		<DevEUI_uplink xmlns="http://uri.actility.com/lora">
		  <Time>2015-07-09T16:06:38.49+02:00</Time> // timestamp for the packet
		  <DevEUI>00000000007E074F</DevEUI>
		  <FPort>2</FPort> //LoRaWAN port number
		  <FCntUp>11</FCntUp> // the uplink counter for this packet
		  <ADRbit>1</ADRbit>
		  <FCntDn>0</FCntDn> // the last downlink counter to the device
		  <payload_hex>00270000bd00</payload_hex> //LoRaWAN payload in hexa ascii format
		  <mic_hex>38e7a3b9</mic_hex> // MIC in hexa ascii format
		  <Lrcid>00000065</Lrcid>
		  <LrrRSSI>-60.000000</LrrRSSI>
		  <LrrSNR>9.750000</LrrSNR>
		  <SpFact>7</SpFact>
		  <SubBand>G1</SubBand>
		  <Channel>LC2</Channel>
		  <DevLrrCnt>3</DevLrrCnt> // number of LRRs which received this packet
		  <Lrrid>08040059</Lrrid>
		  <LrrLAT>48.874931</LrrLAT>
		  <LrrLON>2.333673</LrrLON>
		  <Lrrs>
		    <Lrr>
		      <Lrrid>08040059</Lrrid>
		      <LrrRSSI>-60.000000</LrrRSSI>
		      <LrrSNR>9.750000</LrrSNR>
		    </Lrr>
		    <Lrr>
		      <Lrrid>33d13a41</Lrrid>
		      <LrrRSSI>-73.000000</LrrRSSI>
		      <LrrSNR>9.750000</LrrSNR>
		    </Lrr>
		    <Lrr>
		      <Lrrid>a74e48b4</Lrrid>
		      <LrrRSSI>-38.000000</LrrRSSI>
		      <LrrSNR>9.250000</LrrSNR>
		    </Lrr>
		  </Lrrs>
		  <CustomerID>100000507</CustomerID>
		  <CustomerData>Customer data</CustomerData> // ascii customer data set by provisioning
          <ModelCfg>0</ModelCfg>
        </DevEUI_uplink>';
	}

	require_once("../config.php");
	require_once("../db/mte/mte.php");
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
	
	# multiple store
	if( $xml->DevLrrCnt > 0 ) {
		for($i=0; $i<$xml->DevLrrCnt; $i++)
		{
			$atts_array = (array) $xml->Lrrs->Lrr[$i];
			foreach ($atts_array as $key => $value) {
				$_POST['Lrr_'.$key] = $value;
			}
			$_POST['mte_new_rec'] = "new";
			$tabledit->save_rec_directly();
		}
	}
	else {
		# single store it
		$_POST['mte_new_rec'] = "new";
		$tabledit->save_rec_directly();
	}
	
	$tabledit->database_disconnect();
?>
