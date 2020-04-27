<?php
	require_once("payload_elgas.php");

	# db filename
	define('DB_NAME',      "data/xmldata.sqlite");
	define('DB_SLRC_NAME', "SLRCAppTerm/data/xmldata.sqlite");
	define('WEB_DISABLE_RESET', "sekerak.eu");

	date_default_timezone_set('Europe/Prague');
	
	# the fields of db
	$db_fields = null;
	$db_fields['epe']  = array( 'id','it','ic','um','fe','vb','vn','qb','qn','pm','tm','ns');
	$db_fields['elc']  = array( 'id','it','ic','um','fe','vb','vn','db','dn','qb','qn','eb','en','pm','tm','ns','fz','vr','pt');
	$db_fields['etl']  = array( 'id','it','um','fe','vb','vn','db','dn','pm','tm','ct','eb','en','vx','fx','vy','fy','qx',
					            'tx','qy','ty','bx','dx','kx','sx','ky','sy','ns','fz','bt','bm','sg','vr','pt');
	$db_fields['rlg']  = array( 'id','it','um','fe','tp','era');
	$db_fields['vrl']  = array( 'id','ic','tp','rl','nrl','um');
	$db_fields['vdf']  = array( 'id','ic','tp','df','um');
	$db_fields['elcc'] = array( 'id','it','ic','um','fe','Vb','Vn','e','veb','ven','ee','cfv','z','met','eta','pro','ibu',
			                    'nbu','ipe','npe','c6','h2','n2','co2','pcs','pci','den','db','dn','eh','qb','qn','eb','en',
			                    'eeh','pm','tm','nt','qbh','qnh','qbl','qnl','ns','vr','pt','fz','imp','tfvb','tfvn','tfe',
			                    'zeq','dbd','dnd','ehd','ebd','dbm','dnm','ehm','ebm');
	$db_fields['conf'] = array( 'id','it','pr');
	$db_fields['rcm']  = array( 'id','ic','es');
	
	$db_fields['lora'] = array( 'id','fe','Time','DevEUI','FPort','FCntUp','ADRbit','FCntDn','ack','payload_hex','mic_hex','Lrcid','LrrRSSI','LrrSNR','SpFact','SubBand','Channel','DevLrrCnt','Lrrid','LrrLAT','LrrLON','Lrr_Lrrid','Lrr_LrrRSSI','Lrr_LrrSNR','Lrr_Chain','Lrr_LrrESP','CustomerID','CustomerData','ModelCfg' );
	$db_fields['daesung'] = array( 'id','arm_id1','arm_id2','user_part','evc','fe','vm','vb','temp','press','c','qb','alarm','fw','period','crc' );
	
	$db_time_stamp     = array('fe','fx','fy','tx','ty','dx','sx','sy');
	$db_graph          = array('vb','vn','db','dn','qb','qn','pm','tm','ct','eb','en','vx','vy','qx','qy','bx','kx','ky','LrrRSSI','LrrSNR','SpFact','Lrrid','LrrLAT','LrrLON','Lrr_Lrrid','Lrr_LrrRSSI','Lrr_LrrSNR','Lrr_Chain','Lrr_LrrESP', 'temp', 'press', 'c');

	# convert json to xml
	$map_fields        = array( 'ts' => 'Time', 'EUI' => 'DevEUI', 'port' => 'FPort', 'fcnt' => 'FCntUp', 'data' => 'payload_hex', 'rssi' => 'LrrRSSI', 'snr' => 'LrrSNR', 'lat' => 'LrrLAT', 'lon' => 'LrrLON', 'freq' => 'Channel', 'dr' => 'SpFact');

	# cast for sql where
	$db_fields_cast['lora'] = array( 
	//	'fe' => DATETIME,'Time' => DATETIME, 'TimeStamp' => DATETIME,
		'FPort' => 'DECIMAL','FCntUp' => 'DECIMAL','ADRbit' => 'DECIMAL','FCntDn' => 'DECIMAL','Message_Type' => 'DECIMAL',
		'Lrcid' => 'DECIMAL','LrrRSSI' => 'DECIMAL','LrrSNR' => 'DECIMAL','SpFact' => 'DECIMAL','DevLrrCnt' => 'DECIMAL','LrrLAT' => 'DECIMAL',
		'LrrLON' => 'DECIMAL','Lrr_LrrRSSI' => 'DECIMAL','Lrr_LrrSNR' => 'DECIMAL','Lrr_Chain' => 'DECIMAL','Lrr_LrrESP' => 'DECIMAL',
		'Battery_device' => 'DECIMAL','Battery_modem' => 'DECIMAL',	'Vm_t' => 'DECIMAL', 'Vm_t1' => 'DECIMAL',
	);	
	
	# modify lora payload_hex
	$db_fields['lora'] = payload_added($db_fields['lora']);
	$db_graph = payload_added_graph($db_graph);
	
	# convert date to human format
	function modify_date($fe)
	{
		$cdate = mktime(substr($fe,5,2), substr($fe,7,2), 0, 1, 1, 2000+substr($fe,0,2)) + (substr($fe,2,3)-1)*24*60*60;
		return Date("Y.m.d H:i", $cdate);
	}
	
	# convert LORA date to human format
	function modify_lora_date($time)
	{
		$cdate = strtotime($time);
		# miliseconds unix time
		if( $cdate == false ) {
			$militime = DateTime::createFromFormat('U.u', $time/1000);
			$militime->setTimezone(new DateTimeZone(date_default_timezone_get())); 
			return $militime->format("Y.m.d H:i:s.u");
		}
		return Date("Y.m.d H:i:s", $cdate);
	}
