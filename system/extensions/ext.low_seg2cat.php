<?php
/*
=====================================================
 This extension was created by Lodewijk Schutte
 - freelance@loweblog.com
 - http://loweblog.com/freelance/
=====================================================
 File: ext.low_seg2cat.php
-----------------------------------------------------
 Purpose: Register category info according to URI segments
=====================================================
*/

if ( ! defined('EXT'))
{
	exit('Invalid file request');
}

class low_seg2cat
{
	var $settings       = array();

	var $name           = 'Low Seg2Cat';
	var $version        = '1.0.0';
	var $description    = 'Registers Category information according to URI Segments';
	var $settings_exist = 'n';
	var $docs_url       = 'http://loweblog.com/freelance/article/ee-low-seg2cat-extension/';
			
	// -------------------------------
	// Constructor
	// -------------------------------
	function low_seg2cat($settings='')
	{
		$this->settings = $settings;
	}
	// END segments_to_categories
	
	
	// --------------------------------
	//  Settings
	// --------------------------------  
	function settings()
	{
		// no settings...
		return array();
	}
	// END settings

	
	// --------------------------------
	//  Create stack and variables
	// -------------------------------- 
	function create_stack()
	{
		global $IN, $DB, $PREFS;
		
		// Only continue if we have segments to check
		if (empty($IN->SEGS)) return;

		// initiate some vars
		$site = $PREFS->ini('site_id');
		$data = $cats = $segs = array();
		$data['segment_category_ids'] = '';
		
		// loop through segments and set data array thus: segment_1_category_id etc
		foreach ($IN->SEGS AS $nr => $seg)
		{
			$data['segment_'.$nr.'_category_id']   = '';
			$data['segment_'.$nr.'_category_name'] = '';
			$segs[] = $DB->escape_str($seg);
		}
		
		// put segments in sql IN query; retrieve categories that match
		$sql_segs = "'".implode("','", $segs)."'";
		$sql = "SELECT
				cat_id, cat_url_title, cat_name
			FROM
				exp_categories
			WHERE
				cat_url_title
			IN
				({$sql_segs})
			AND
				site_id = '{$site}'
		";
		$query = $DB->query($sql);
		
		// if we have matching categories, continue...
		if ($query->num_rows)
		{
			// initiate typography class for category title
			if (!class_exists('Typography'))
			{
				require PATH_CORE.'core.typography'.EXT;
			}

			$TYPE = new Typography;

			// flip segment array to get 'segment_1' => '1'
			$ids = array_flip($IN->SEGS);
			
			// loop through categories
			foreach ($query->result AS $row)
			{
				// overwrite values in data array
				$data['segment_'.$ids[$row['cat_url_title']].'_category_id']   = $row['cat_id'];
				$data['segment_'.$ids[$row['cat_url_title']].'_category_name'] = $TYPE->light_xhtml_typography($row['cat_name']);
				$cats[] = $row['cat_id'];
			}
			
			// create inclusive stack of all category ids present in segments
			$data['segment_category_ids'] = implode('&',$cats);
		}
		
		// register global variables
		$IN->global_vars = array_merge($IN->global_vars,$data);
	}
	// END create_stack()
	
	
	// --------------------------------
	//  Activate Extension
	// --------------------------------
	function activate_extension()
	{
		global $DB, $PREFS;
		
		$DB->query(
			$DB->insert_string(
				$PREFS->ini('db_prefix').'_extensions',
				array(
					'extension_id' => '',
					'class'        => __CLASS__,
					'method'       => "create_stack",
					'hook'         => "sessions_end",
					'settings'     => '',
					'priority'     => 1,
					'version'      => $this->version,
					'enabled'      => "y"
				)
			)
		); // end db->query
	}
	// END activate_extension
	 
	 
	// --------------------------------
	//  Update Extension
	// --------------------------------  
	function update_extension($current='')
	{
		global $DB, $PREFS;
		
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		$DB->query("UPDATE ".$PREFS->ini('db_prefix')."_extensions 
							  SET version = '".$DB->escape_str($this->version)."' 
							  WHERE class = '".__CLASS__."'");
	}
	// END update_extension

	// --------------------------------
	//  Disable Extension
	// --------------------------------
	function disable_extension()
	{
		global $DB, $PREFS;
		
		$DB->query("DELETE FROM ".$PREFS->ini('db_prefix')."_extensions WHERE class = '".__CLASS__."'");
	}
	// END disable_extension
	 
}
// END CLASS
?>