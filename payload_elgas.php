<?php
# special payload parser for Elgas

	define(Uchar,  0);
	define(Ulong,  1);
	define(Double, 2);
	define(Ushort, 3);
	define(String, 4);
	define(Hex,    5);

	define(PAY_NAME, 0);
	define(PAY_LEN,  1);
	define(PAY_TYPE, 2);

	define(DATEOFFSET1970, 946684800);

	$payload_elgas = array(
		array('Message_Type',   1, Uchar),
		array('ID_device',     16, String),
		array('TimeStamp',      4, Ulong),		// seconds from 1.1.2000
		array('Vm_t',           8, Double),
		array('Vm_t_1',         8, Double),
		array('Vm_t_2',         8, Double),
		array('Battery_device', 1, Uchar),
		array('Battery_modem',  1, Uchar),
		array('RSSI',           1, Uchar),
		array('Resserved',      1, Uchar),
		array('CRC_modbus',     2, Hex)
	);

	$payload_graph = array('Vm_t', 'Vm_t_1', 'Vm_t_2', 'Battery_device', 'Battery_modem', 'RSSI');
	
# replace column in lora table
function payload_added($fields)
{
	global $payload_elgas;

	$inserted = false;
	empty($fields_answer);

	foreach($fields as $column)
	{
		$fields_answer[] = $column;
		if( $column == 'payload_hex')
		{
			$inserted = true;
			foreach ($payload_elgas as $pay)
				$fields_answer[] = $pay[PAY_NAME];
		}
	}

	if($inserted == false)
	{
		foreach ($payload_elgas as $pay)
			$fields_answer[] = $pay[PAY_NAME];
	}

	return $fields_answer;
}


# added column for graph presentation
function payload_added_graph($db_graph)
{
	global $payload_graph;

	foreach ($payload_graph as $pay_graph)
		$db_graph[] = $pay_graph;

	return $db_graph;
}
	

# parsing payload
function payload_elgas($pay_value)
{
	global $payload_elgas;

	empty($value);
	foreach($payload_elgas as $pay)
	{
		# not more
		if( 2*$pay[PAY_LEN] > strlen($pay_value))
			break;
			
		switch ($pay[PAY_TYPE])
		{
			case Uchar:
				$value[ $pay[PAY_NAME]] = hexdec(substr($pay_value, 0, 2*$pay[PAY_LEN]));
				break;
			case Ulong:
				$value[ $pay[PAY_NAME]] = unpack("L", pack('H*',substr($pay_value, 0, 2*$pay[PAY_LEN])))[1];
				break;
			case Ushort:
				$value[ $pay[PAY_NAME]] = hexdec(substr($pay_value, 0, 2*$pay[PAY_LEN]));
				break;
			case Double:
				$value[ $pay[PAY_NAME]] = unpack("d", pack('H*',substr($pay_value, 0, 2*$pay[PAY_LEN])))[1];
				break;
			case String:
				$value[ $pay[PAY_NAME]] = trim(hex2bin(substr($pay_value, 0, 2*$pay[PAY_LEN])));
				break;
			case Hex:
			default:
				$value[ $pay[PAY_NAME]] = substr($pay_value, 0, 2*$pay[PAY_LEN]);
				break;
		}
		//
		if( $pay[PAY_NAME] == 'TimeStamp' )
			$value[ $pay[PAY_NAME]] = Date("Y.m.d H:i:s", $value[ $pay[PAY_NAME]] + DATEOFFSET1970);

		// cut first n-bytes
		$pay_value = substr($pay_value, 2*$pay[PAY_LEN], strlen($pay_value) - 2*$pay[PAY_LEN]);
	}

	return $value;
}
