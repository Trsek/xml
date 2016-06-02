<?
	# db filename
	define(DB_NAME,      "data/xmldata.sqlite");
	define(DB_SLRC_NAME, "SLRCAppTerm/data/xmldata.sqlite");
	
	# the fields of db
	$db_fields = null;
	$db_fields['epe']  = array( 'id','it','ic','um','fe','vb','vn','qb','qn','pm','tm','ns');
	$db_fields['elc']  = array( 'id','it','ic','um','fe','vb','vn','db','dn','qb','qn','eb','en','pm','tm','ns','fz','pt');
	$db_fields['etl']  = array( 'id','it','um','fe','vb','vn','db','dn','pm','tm','ct','eb','en','vx','fx','vy','fy','qx',
					            'tx','qy','ty','bx','dx','kx','sx','ky','sy','ns','fz','bt','bm','sg','pt');
	$db_fields['rlg']  = array( 'id','it','um','fe','tp','era');
	$db_fields['vrl']  = array( 'id','ic','tp','rl','nrl','um');
	$db_fields['vdf']  = array( 'id','ic','tp','df','um');
	$db_fields['elcc'] = array( 'id','it','ic','um','fe','Vb','Vn','e','veb','ven','ee','cfv','z','met','eta','pro','ibu',
			                    'nbu','ipe','npe','c6','h2','n2','co2','pcs','pci','den','db','dn','eh','qb','qn','eb','en',
			                    'eeh','pm','tm','nt','qbh','qnh','qbl','qnl','ns','pt','fz','imp','tfvb','tfvn','tfe','zeq',
			                    'dbd','dnd','ehd','ebd','dbm','dnm','ehm','ebm');
	
?>