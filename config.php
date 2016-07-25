<?
	# db filename
	define(DB_NAME,      "data/xmldata.sqlite");
	define(DB_SLRC_NAME, "SLRCAppTerm/data/xmldata.sqlite");

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
	
	$db_time_stamp     = array('fe','fx','fy','tx','ty','dx','sx','sy');
	$db_graph          = array('vb','vn','db','dn','qb','qn','pm','tm','ct','eb','en','vx','vy','qx','qy','bx','kx','ky');

	# convert date to human format
	function modify_date($fe)
	{
		$cdate = mktime(substr($fe,5,2), substr($fe,7,2), 0, 1, 1, 2000+substr($fe,0,2), 0) + (substr($fe,2,3)-1)*24*60*60;
		return Date("Y.m.d H:i", $cdate);
	}
	
?>