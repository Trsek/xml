<?php

// no direct access
if(strtolower(basename($_SERVER['PHP_SELF'])) == strtolower(basename(__FILE__))) {
	die('No access...');
}


class MySQLtabledit {

   /**	
    * 
	* MySQL Edit Table
	* 
	* Copyright (c) 2010 Martin Meijer - Browserlinux.com
	* 
	* Permission is hereby granted, free of charge, to any person obtaining a copy
	* of this software and associated documentation files (the "Software"), to deal
	* in the Software without restriction, including without limitation the rights
	* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	* copies of the Software, and to permit persons to whom the Software is
	* furnished to do so, subject to the following conditions:
	* 
	* The above copyright notice and this permission notice shall be included in
	* all copies or substantial portions of the Software.
	* 
	* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
	* THE SOFTWARE.
	* 
	*/
	
	var $version = '0.3'; // 03 jan 2011

	# text 
	var $text;

	# language
	var $language = 'en';

	# table of the database
	var $database;
	var $table;
	var $db = null;

	# the primary key of the table
	var $primary_key;
	
	# table is read only?
	var $read_only = false;
	
	# celkovy pocet/aktualny/hodnoty
	var $total_rows = 0;
	var $act_row = 0;
	var $values = null;
	var $result = null;
	
	# the fields you want to see in "list view"
	var $fields_in_list_view;

	# numbers of rows/records in "list view"
	var $num_rows_list_view = 15;

	# required fields in edit or add record
	var $fields_required;

	# help text 
	var $help_text;

	# visible name of the fields
	var $show_text;
	
	# has char icon
	var $chart_column = array();

	# additional button in menu
	var $button_menu;
	
	var $width_editor = '100%';
	var $width_input_fields = '500px';
	var $width_text_fields = '498px';
	var $height_text_fields = '200px';

	# warning no .htacces ('on' or 'off')
	var $no_htaccess_warning = 'off';


	# Forget this - working on it...
	# needed in Joomla for images/css, example: 'http://www.website.com/administrator/components/com_componentname'
	var $url_base;
	# needed in Joomla, example: 'option=com_componentname' 
	var $query_joomla_component;



	###########################
	function database_connect() {
	###########################

		$this->db = new PDO('sqlite:'. $this->database);

		if ($this->db == null) {
			die('Could not connect: ' . $this->db->errorCode());
		}
	}

	###########################
	function database_connect_quick($database, $table_name) {
	###########################
		# db table
		$this->database = $database;	
		$this->table = $table_name;

		# language (en, cs, sk)
		$this->language = 'en';
		
		# numbers of rows/records in "list view"
		$this->width_editor = '100%';
		$this->width_input_fields = '500px';
		$this->width_text_fields = '498px';
		$this->height_text_fields = '200px';
		
		$this->database_connect();
	}
	

	##############################
	function database_disconnect() {
	##############################

//		var_dump($this);
//		var_dump($_SERVER);
//		var_dump($_REQUIRE);
		$this->db = null;

	}
	
	################
	function insert_button($button_type, $button_name, $button_script) {
	################
	
			if($button_type == "#")
			{
				$this->button_menu[$button_name] = $button_script;
			}
			
		}

	################
	function do_it($url_script) {
	################
		// Sorry: in Joomla, remove the next two lines and place the language vars instead
		require_once("./db/lang/en.php");
		require_once("./db/lang/" . $this->language . ".php");


		# No cache
		if(!headers_sent()) {
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Cache-Control: post-check=0, pre-check=0', false);
			header('Pragma: no-cache');
			header("Cache-control: private");
		}
	
		if (!$this->url_base) $this->url_base = './db';

		# name of the script
		$break = explode('/', $_SERVER["SCRIPT_NAME"]);
		$this->url_script = $break[count($break) - 1];
		#run via htaccess
		if(!empty($url_script)) $this->url_script = $url_script;

		echo "Table: ". $this->table ."<br>";
		
		if ($_GET['mte_a'] == 'edit') { 
			$this->edit_rec(); 
		}
		elseif ($_GET['mte_a'] == 'new') {
			$this->edit_rec();
		}
		elseif ($_GET['mte_a'] == 'del') {
			 $this->del_rec(); 
		}
		elseif ($_POST['mte_a'] == 'save') {
			$this->save_rec();
		}
		else { 
			$this->show_list();
		}

		$this->close_and_print();

	}




	####################
	function show_list() {
	####################
		
		# message after add or edit
		$this->content_saved = $_SESSION['content_saved']; 
		$_SESSION['content_saved'] = '';
				
		# default sort (a = ascending)
		$ad = 'a';

		if ($_GET['sort'] && in_array($_GET['sort'],$this->fields_in_list_view) ) {
			if ($_GET['ad'] == 'a') $asc_des = 'ASC';
			if ($_GET['ad'] == 'd') $asc_des = 'DESC';
			$order_by = "ORDER by " . $_GET['sort'] . ' ' . $asc_des ;	
		}
		else {
			$order_by = "ORDER by $this->primary_key DESC";	
		}


		# navigation 1/3
		$start = $_GET["start"];
		if (!$start) {$start = 0;} else {$start *=1;}

		
		// build query_string
		// query_joomla_component (joomla) 
		if ($this->query_joomla_component) 
			$query_string = '&option=' . $this->query_joomla_component ;

		// table name
		$query_string .= '&tbl=' . $this->table;

		// navigation
		if( $start > 0 )
			$query_string .= '&start=' . $start;

		// sorting
		if( !empty($_GET['ad']) || !empty($_GET['sort']))
			$query_string .= '&ad=' . $_GET['ad']  . '&sort=' . $_GET['sort'] ;

		// searching
		if( !empty($_GET['search']) || !empty($_GET['f']))
			$query_string .= '&search=' . $_GET['search']  . '&f=' . $_GET['f'] ;
		
		
		# search
		if ($_GET['search'] && $_GET['f']) {

			$in_search = addslashes(stripslashes($_GET['search']));
			$in_search_field = $_GET['f'];

			if ($in_search_field == $this->primary_key) {
				$where_search = "WHERE $in_search_field = '$in_search' ";
			}
			else {
				$where_search = "WHERE $in_search_field LIKE '%$in_search%' ";
			}
		}
		
		# select
		$sql = "SELECT * FROM `$this->table` $where_search $order_by";

		# navigation 2/3
		//$hits_total = $this->db->query($sql)->fetchColumn(); 
		$hits_total = count($this->db->query($sql)->fetchAll());

		$sql .= " LIMIT $start, $this->num_rows_list_view";
		$this->values = $this->db->query($sql);
		if( $this->values == false )
			$this->values = null;

		if (count($this->values)>0) {
			$count = 0;
			foreach ($this->values as $rij) {
				$count++;
				$this_row = '';
				
				if ($background == '#eee') {$background='#fff';} 
					else {$background='#eee';}
							
				
				foreach ($rij AS $key => $value) {
					
					if($key == '0')
						continue;
					
					$sort_image = '';
					if (in_array($key, $this->fields_in_list_view)) {
						if ($count == 1) {
							
							// show nice text of a value 
							if ($this->show_text[$key]) {$show_key = $this->show_text[$key];}
								else {$show_key = $key;}

							// sorting
							if ($_GET['sort'] == $key && $_GET['ad'] == 'a') {
								$sort_image = "<IMG SRC='$this->url_base/images/sort_a.png' WIDTH=9 HEIGHT=8 BORDER=0 ALT=''>";
								$ad = 'd';
							}
							if ($_GET['sort'] == $key && $_GET['ad'] == 'd') {
								$sort_image = "<IMG SRC='$this->url_base/images/sort_d.png' WIDTH=9 HEIGHT=8 BORDER=0 ALT=''>";
								$ad = 'a';
							}

							if (in_array($key, $this->chart_column)) {
								$sort_image .= "<a href='$this->url_script?$query_string&graph=$key'><IMG SRC='$this->url_base/images/chart.png' BORDER=0 ALT=''></a>";
							}

							// remove sort  and ad and add new ones
							$query_sort = preg_replace('/&(sort|ad)=[^&]*/','', $query_string) . "&sort=$key&ad=$ad";	

							$head .= "<td NOWRAP><a href='$this->url_script?$query_sort' class='mte_head'>$show_key</a> $sort_image</td>";
						}
						if ($key == $this->primary_key) {
							$buttons = "<td NOWRAP><a href='javascript:void(0)' onclick='del_confirm($value)' title='Delete {$this->show_text[$key]} $value'><IMG SRC='$this->url_base/images/del.png' WIDTH=16 HEIGHT=16 BORDER=0 ALT=''></a>&nbsp;<a href='?$query_string&mte_a=edit&id=$value' title='Edit {$this->show_text[$key]} $value'><IMG SRC='$this->url_base/images/edit.png' WIDTH=16 HEIGHT=16 BORDER=0 ALT=''></a></td>";
							$this_row .= "<td>$value</td>";
						}
						else {
							
							$this_row .= '<td>' . substr(strip_tags($value), 0, 300) . '</td>';
						}
					}
				}
				
				$rows .= "<tr style='background:$background'>$buttons $this_row</tr>";
				
			}
		}
		else {
			$head = "<td style='padding:50px'>{$this->text['Nothing_found']}...</td>";
		}


		# navigation 3/3

		# remove start= from url
		$query_nav = preg_replace('/&(start|mte_a|id)=[^&]*/','', $query_string );	


		# this page
		$this_page = ($this->num_rows_list_view + $start)/$this->num_rows_list_view;


		# last page
		$last_page = ceil($hits_total/$this->num_rows_list_view);


		# navigatie numbers
		if ($this_page>10) {
			$vanaf = $this_page - 10;
		}
		else {$vanaf = 1;}
		if ($last_page>$this_page + 10) {
			$tot = $this_page + 10;
		}
		else {$tot = $last_page; }


		for ($f=$vanaf;$f<=$tot;$f++) {

			$nav_toon = $this->num_rows_list_view * ($f-1);

			if ($f == $this_page) {
				$navigation .= "<td class='mte_nav' style='color:#fff;background: #808080;font-weight: bold'>$f</td> "; 
			}
			else {
				$navigation .= "<td class='mte_nav' style='background: #fff'><A HREF='$this->url_script?$query_nav&start=$nav_toon'>$f</A></td>"; 
			}
		}
		if ($hits_total<$this->num_rows_list_view) { $navigation = '';}




		# Previous if
		if ($this_page > 1) {
			$last =  (($this_page - 1) * $this->num_rows_list_view ) - $this->num_rows_list_view;
			$last_page_html = "<A HREF='$this->url_script?$query_nav&start=$last' class='mte_nav_prev_next'>{$this->text['Previous']}</A>";
		}

		# Next if: 
		if ($this_page != $last_page && $hits_total>1) {
			$next =  $start + $this->num_rows_list_view;
			$next_page_html =  "<A HREF='$this->url_script?$query_nav&start=$next' class='mte_nav_prev_next'>{$this->text['Next']}</A>";
		}


		if ($navigation) {
			$nav_table = "
				<table cellspacing=5 cellpadding=0 style='border: 0px solid white'>
					<tr>
						<td style='padding-right:6px;vertical-align: middle'>$last_page_html</td>
						$navigation
						<td style='padding-left:6px;vertical-align: middle'>$next_page_html</td>
					</tr>
				</table>	
			";

			$this->nav_top = "

				<div style='margin: -10px 0 20px 0;width: $this->width_editor'>
				<center>
					$nav_table
				</center>
				</div>	
			";

			$this->nav_bottom = "
				<div style='margin: 20px 0 0 0;width: $this->width_editor'>
				<center>
					$nav_table
				</center>
				</div>
			";
		}
		
		
		
		
		# Search form + Add Record button
		foreach ($this->fields_in_list_view AS $option) {
			
			if ($this->show_text[$option]) {$show_option = $this->show_text[$option];}
			else {$show_option = $option;}

			if ($option == $in_search_field) {
					$options .= "<option selected value='$option'>$show_option</option>";
				}
				else {
					$options .= "<option value='$option'>$show_option</option>";
				}
			}
		$in_search_value = htmlentities(trim(stripslashes($_GET['search'])), ENT_QUOTES);



		$seach_form = "
			<table cellspacing=0 cellpadding=0 border=0>
			<tr>
				<td nowrap>
					<form method=get action='$this->url_script' style='padding: 15px'>
						<select name='f'>$options</select> 
						<input type='text' name='search' value='$in_search_value' style='width:200px'>
						<input type='submit' value='{$this->text['Search']}' style='width:80px; border: 1px solid #000'>
			"; 	
		if ($this->query_joomla_component) $seach_form .= "<input type='hidden' value='$this->query_joomla_component' name='option'>";
		$seach_form .= "</form>";
		
		if ($_GET['search'] && $_GET['f']) {		
			if ($this->query_joomla_component) $add_joomla = '?option=' . $this->query_joomla_component;
			$seach_form .= "<button onclick='window.location=\"$this->url_script$add_joomla\"' style='margin: 0 0 15px 15px; border: 1px solid #000;'>{$this->text['Clear_search']}</button>";
		}
		
		if( !empty($this->button_menu)) {
			
			foreach ($this->button_menu as $key => $value)
			{
				$seach_form .= "
					</td>
					<td style='padding: 15px; text-align: right; width: $this->width_editor'>
						<button onclick='window.location=\"$this->url_script?$value\"' style='margin: 0 0 15px 15px; border: 1px solid #000;'>{$key}</button>
					";
			}
		}
		
		$seach_form .= "
				</td>

			</tr>
			</table>
		";

		$this->javascript = "
			function del_confirm(id) {
				if (confirm('{$this->text['Delete']} record {$this->show_text[$this->primary_key]} ' + id + '...?')) {
					window.location='$this->url_script?$query_string&mte_a=del&id=' + id				
				}
			}
		";
		
		
		# page content
		$this->content = "
			<div style='width: $this->width_editor;background:#454545; margin: 0'>$seach_form</div>
			<table cellspacing=0 cellpadding=10 style='margin: 0; width: $this->width_editor;'>
				<tr style='background:#626262; color: #fff'><td></td>$head</tr>
				$rows
			</table>
			
			$this->nav_bottom
		";
		
		
	}


	##################
	function del_rec_all() {
	##################
		if( $this->read_only )
			return;
		
		return $this->db->query("DELETE FROM $this->table");
		
	}

	##################
	function del_rec() {
	##################

		if( $this->read_only )
			return;
		
		$in_id = $_GET['id'];

		if ($this->db->query("DELETE FROM $this->table WHERE `$this->primary_key` = '$in_id'")) {
			$this->content_deleted = "
				<div style='width: $this->width_editor'>
					<div style='padding: 10px; color:#fff; background: #FF8000; font-weight: bold'>Record {$this->show_text[$this->primary_key]} $in_id {$this->text['deleted']}</div>
				</div>
			";
			$this->show_list();
		}
		else {
			$this->content = "
			</div>
				<div style='padding:2px 20px 20px 20px;margin: 0 0 20px 0; background: #DF0000; color: #fff;'><h3>Error</h3>" .
				$this->db->errorCode(). 
				"</div><a href='$this->url_script'>List records...</a>
			</div>";
		}

	}




	###################
	function edit_rec() {
	###################

		if( $this->read_only )
			return;
		
		$in_id = $_GET['id'];

		# edit or new?
		if ($_GET['mte_a'] == 'edit') $edit=1;
		
		$count_required = 0;

		$rij = $this->db->query("SELECT * FROM `$this->table` LIMIT 1 ;");
		$total_column = $rij->columnCount();
		
		for ($counter = 0; $counter < $total_column; $counter ++) {
			$meta = $rij->getColumnMeta($counter);
			$field_type[$meta['name']] = $meta['native_type'];
		}
		
		# get field types
		/*
		foreach ($this->values as $rij) {
			extract($rij);
			$field_type[$Field] = $Type;
		}
		*/ 

		if (!$edit) {
			$rij = $field_type;
		}
		else {
			if ($edit) $where_edit = "WHERE `$this->primary_key` = $in_id";
			$rij = $this->db->query("SELECT * FROM `$this->table` $where_edit LIMIT 1 ;");
		}

		foreach ($rij as $rij2)		
		foreach ($rij2 AS $key => $value) {
			if (!in_array($key, $this->fields_in_list_view ) || ($key == '0')) continue;
			if (!$edit) $value = '';
			$field = '';
			$options = '';
			$style = '';
			$field_id = '';
			$readonly = '';
			$value_htmlentities = '';
			
			if (in_array($key, $this->fields_required)) {
				$count_required++;
				$style = "class='mte_req'";
				$field_id = "id='id_" . $count_required . "'";
			}


			$field_kind = $field_type[$key];

			# different fields
			# textarea
			if (preg_match("/text/", $field_kind)) {
				$field = "<textarea name='$key' $style $field_id>$value</textarea>";
			}
			# select/options
			elseif (preg_match("/enum\((.*)\)/", $field_kind, $matches)) {
				$all_options = substr($matches[1],1,-1);
				$options_array = explode("','",$all_options);
				foreach ($options_array AS $option) {
					if ($option == $value) {
						$options .= "<option selected>$option</option>";
					}
					else {
						$options .= "<option>$option</option>";
					}
				} 
				$field = "<select name='$key' $style $field_id>$options</select>";
			}
			# input
			elseif (!preg_match("/blob/", $field_kind)) {
				if (preg_match("/\(*(.*)\)*/", $field_kind, $matches)) {
					if ($key == $this->primary_key) {
						$style = "style='background:#ccc'";
						$readonly = 'readonly';
					}
					$value_htmlentities = htmlentities($value, ENT_QUOTES);
					if (!$edit && $key == $this->primary_key) {
						$field = "<input type='hidden' name='$key' value=''>[auto increment]";
					} 
					else {
						$field = "<input type='text' name='$key' value='$value_htmlentities' maxlength='{$matches[1]}' $style $readonly $field_id>";
					}
				}
			}
			# blob: don't show
			elseif (preg_match("/blob/", $field_kind)) {
				$field = '[<i>binary</i>]';
			}
			 
			# make table row
			if ($background == '#eee') {$background='#fff';} 
				else {$background='#eee';}
			if ($this->show_text[$key]) {$show_key = $this->show_text[$key];}
				else {$show_key = $key;}
			$rows .= "\n\n<tr style='background:$background'>\n<td><b>$show_key</b></td>\n<td>$field</td>\n<td style='width:50%'>{$this->help_text[$key]}</td>\n</tr>";
		}
		
		$this->javascript = "
			function submitform() {
				var ok = 0;
				for (f=1;f<=$count_required;f++) {
					
					var elem = document.getElementById('id_' + f);
					
					if(elem.options) {
						if (elem.options[elem.selectedIndex].text!=null && elem.options[elem.selectedIndex].text!='') {
							ok++;
						}
					}
					else {
						if (elem.value!=null && elem.value!='') {
							ok++;
						}
					}
				}

				if (ok == $count_required) {
					return true;
				}
				else {
					alert('{$this->text['Check_the_required_fields']}...')
					return false;
				}	
			}
		";


		$this->content = "
			

				<div style='width: $this->width_editor;background:#454545'>
				
					<table cellspacing=0 cellpadding=0 style='border: 0px solid white'>
						<tr>
						<td>
							<button onclick='window.location=\"{$_SESSION['hist_page']}\";' style='margin: 20px 15px 25px 15px; border: 1px solid #000;'>{$this->text['Go_back']}</button></td>
						<td>
							<form method=post action='$this->url_script' onsubmit='return submitform()'>
							<input type='submit' value='{$this->text['Save']}' style='width: 80px;border: 1px solid #000; margin: 20px 0 25px 0'></td>
						</tr>
					</table>
					
				</div>
			
				<div style='width: $this->width_editor'>
					<table cellspacing=0 cellpadding=10 style='100%; margin: 0'>
						$rows
					</table>
				</div>
		";
			
		if (!$edit) $this->content .= "<input type='hidden' name='mte_new_rec' value='1'>";
		if ($this->query_joomla_component) $this->content .= "<input type='hidden' name='option' value='$this->query_joomla_component'>";
		
		$this->content .= "
				<input type='hidden' name='mte_a' value='save'>
				
			</form>
		";

		
	}



	###################
	function save_rec_directly() {
	###################

		if( $this->read_only )
			return;
		
		$in_mte_new_rec = $_POST['mte_new_rec'];
		
		$updates = '';
		
		foreach($_POST AS $key => $value) {
			if ($key == $this->primary_key) {
				$in_id = $value;
				$where = "$key = $value";
			}
			if ($key != 'mte_a' && $key != 'mte_new_rec' && $key != 'option') {
				if ($in_mte_new_rec) {
					$insert_fields .= " `$key`,";
					$insert_values .= " '" . addslashes(stripslashes($value)) . "',";
				}
				else {
					$updates .= " `$key` = '" . addslashes(stripslashes($value)) . "' ,";
				}
			}
		}
		$insert_fields = substr($insert_fields,0,-1);
		$insert_values = substr($insert_values,0,-1);
		$updates = substr($updates,0,-1);
		

		# new record:
		if ($in_mte_new_rec) {
			$sql = "INSERT INTO `$this->table` ($insert_fields) VALUES ($insert_values); ";	
		}
		# edit record:
		else {
			$sql = "UPDATE `$this->table` SET $updates WHERE $where LIMIT 1; ";	
		}
		$result = $this->db->query($sql);
		return $result;
	}

	
	###################
	function save_rec() {
	###################
		
		if( $this->read_only )
			return;
		
		$in_mte_new_rec = $_POST['mte_new_rec'];
		
		if ($this->save_rec_directly()) {
			if ($in_mte_new_rec) {
				$saved_id = mysql_insert_id();
				$_GET['search'] = $saved_id;
				$_GET['f'] = $this->primary_key;
			}
			else {
				$saved_id = $in_id;
			}
			if ($this->show_text[$this->primary_key]) {$show_primary_key = $this->show_text[$this->primary_key];}
				else {$show_primary_key = $this->primary_key;}

			$_SESSION['content_saved'] = "
				<div style='width: $this->width_editor'>
					<div style='padding: 10px; color:#fff; background: #67B915; font-weight: bold'>Record $show_primary_key $saved_id {$this->text['saved']}</div>
				</div>
				";
			if ($in_mte_new_rec) {
				echo "<script>window.location='?start=0&f=&sort=" . $this->primary_key . "&ad=d";
				if ($this->query_joomla_component) {
					echo '&option=' . $this->query_joomla_component ;
				}
				echo "'</script>";
			}
			else {
				echo "<script>window.location='" . $_SESSION['hist_page'] . "'</script>";
			}
		}
		else {
			$this->content = "
				<div style='width: $this->width_editor'>
					<div style='padding:2px 20px 20px 20px;margin: 0 0 20px 0; background: #DF0000; color: #fff;'><h3>Error</h3>" .
					$this->db->errorCode() . 
					"</div><a href='{$_SESSION['hist_page']}'>{$this->text['Go_back']}...</a>
				</div>";
		}
	}




	##########################
	function close_and_print() {
	##########################


		# debug and warning no htaccess
		if ($this->debug) $this->debug .= '<br />';
		if (!file_exists('./.htaccess') && $this->no_htaccess_warning == 'on') $this->debug .= "{$this->text['Protect_this_directory_with']} .htaccess";

		if ($this->debug) 
		$this->debug_html = "
			<div style='width: $this->width_editor'>
				<div class='mte_mess' style='background: #DD0000'>$this->debug</div>
			</div>";


		# save page location
		$session_hist_page = $this->url_script . '?' . $_SERVER['QUERY_STRING'];
		if ($this->query_joomla_component && !preg_match("/option=$this->query_joomla_component/",$session_hist_page)) {
			$session_hist_page .= '&option=' . $this->query_joomla_component;
		}
		
		// no page history on the edit page because after refresh the Go Back is useless 
		if (!$_GET['mte_a']) {
			$_SESSION['hist_page'] = $session_hist_page;
		}


		
		if ($this->query_joomla_component) $add_joomla = '?option=' . $this->query_joomla_component;
		
		echo "
		<script language='javascript'>
			$this->javascript
		</script>

		<link href='$this->url_base/css/mte.css' rel='stylesheet' type='text/css'>

		<style type='text/css'>
			.mte_content input {
				width: $this->width_input_fields;
			}
			.mte_content textarea {
				width: $this->width_text_fields;
				height: $this->height_text_fields;
			}
		</style>	

		<div class='mte_content'>
			<div class='mte_head_1'><a href='$this->url_script$add_joomla' style='text-decoration: none;color: #797979'>$this->table_description</a></div>
			$this->nav_top
			$this->debug_html
			$this->content_saved
			$this->content_deleted
			$this->content
		</div>
		
		";	
		
	}  
}
?>
