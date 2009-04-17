<?php
/* ===========================================================================
ext.md_weegee.php ---------------------------
Show thumbs on the edit page list. Use it when using a weblog as gallery.
            
INFO ---------------------------
Developed by: Ryan Masuga, masugadesign.com
Created:   Jul 08 2008
Last Mod:  Apr 17 2009

Related Thread: http://expressionengine.com/forums/viewthread/84513/
=============================================================================== */
if ( ! defined('EXT')) { exit('Invalid file request'); }

if ( ! defined('MD_WG_version')){
	define("MD_WG_version",			"1.0.2");
	define("MD_WG_docs_url",		"http://masugadesign.com/the-lab/scripts/weegee/");
	define("MD_WG_addon_id",		"MD Weegee");
	define("MD_WG_extension_class",	"Md_weegee");
	define("MD_WG_cache_name",		"mdesign_cache");
}

class Md_weegee
{
	var $settings		= array();
	var $name           = 'MD Weegee';
	var $version        = MD_WG_version;
	var $description    = 'Show image thumbnails on the edit page list.';
	var $settings_exist = 'y';
	var $docs_url       = MD_WG_docs_url;

// --------------------------------
//  PHP 4 Constructor
// --------------------------------
	function Md_weegee($settings='')
	{
		$this->__construct($settings);
	}

// --------------------------------
//  PHP 5 Constructor
// --------------------------------
	function __construct($settings='')
	{
		$this->settings = $settings;
	}

	// --------------------------------
	//  Change Settings
	// --------------------------------  
 	function settings()
	{
		$settings = array();
		$settings['field_id'] = '';
		$settings['thumb_width'] = '';
		$settings['check_for_updates'] = array('s', array('y' => "Yes", 'n' => "No"), 'y');

		return $settings;
	} 





	
	// --------------------------------
	//  Activate Extension
	// --------------------------------
	function activate_extension()
	{
		global $DB, $PREFS;
		
		$default_settings = serialize(
			array(
				
			  'field_id' => "",
			  'thumb_width' => "80",
				'check_for_updates'	=> 'y'
			)
		);
		

	
		$hooks = array(
		  'edit_entries_additional_tableheader' => 'edit_entries_additional_tableheader',
		  'edit_entries_additional_celldata'    => 'edit_entries_additional_celldata',
			// allow to work with LG Addon Updater
		  'lg_addon_update_register_source'     => 'lg_addon_update_register_source',
		  'lg_addon_update_register_addon'      => 'lg_addon_update_register_addon'
		);
		
		foreach ($hooks as $hook => $method)
		{
			$sql[] = $DB->insert_string( 'exp_extensions', 
				array('extension_id' 	=> '',
					'class'			=> get_class($this),
					'method'		=> $method,
					'hook'			=> $hook,
					'settings'		=> $default_settings,
					'priority'		=> 10,
					'version'		=> $this->version,
					'enabled'		=> "y"
				)
			);
		}

		// run all sql queries
		foreach ($sql as $query)
		{
			$DB->query($query);
		}
		return TRUE;
	}	
	
	// --------------------------------
	//  Disable Extension
	// -------------------------------- 
	function disable_extension()
	{
		global $DB;
		$DB->query("DELETE FROM exp_extensions WHERE class = '" . get_class($this) . "'");
	}
	
	// --------------------------------
	//  Update Extension
	// --------------------------------  
	function update_extension($current='')
	{
		global $DB;
		
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		$DB->query("UPDATE exp_extensions
		            SET version = '".$DB->escape_str($this->version)."'
		            WHERE class = '".get_class($this)."'");
	}
	// END
// ============================================================================


	// --------------------------------
	//  Add Image Table Heading
	// --------------------------------
	
	function edit_entries_additional_tableheader()
	{
		global $DSP, $LANG, $EXT;
		
		if (empty($this->settings['field_id'])) {
		  return;
		}
		
		$LANG->fetch_language_file('md_weegee');
		$extra = ($EXT->last_call !== FALSE) ? $EXT->last_call : '';
		return $extra.$DSP->table_qcell('tableHeadingAlt', $LANG->line('image'));
	}
	// END


	function lg_addon_update_register_source($sources)
	{
		global $EXT;
		if($EXT->last_call !== FALSE)
			$sources = $EXT->last_call;
		// must be in the following format:
		/*
		<versions>
			<addon id='LG Addon Updater' version='2.0.0' last_updated="1218852797" docs_url="http://site.com/" />
		</versions>
		*/
		if($this->settings['check_for_updates'] == 'y')
		{
			$sources[] = 'http://masugadesign.com/versions/';
		}
		return $sources;
	}

	function lg_addon_update_register_addon($addons)
	{
		global $EXT;
		if($EXT->last_call !== FALSE)
			$addons = $EXT->last_call;
		if($this->settings['check_for_updates'] == 'y')
		{
			$addons[MD_LS_addon_id] = $this->version;
		}
		return $addons;
	}


	// ---------------------------------
	//  Add thumbnail for Entries
	// ---------------------------------
	
	function edit_entries_additional_celldata($row)
	{	
		global $DSP, $LANG, $EXT, $DB;
		
		global $img_i;
		 
		 if (empty($img_i))
		 {
		 	$img_i = 0;
		 }
		
		if (empty($this->settings['field_id'])) {
		  return;
		}
		
		$image="-";
		
		$uploadprefs = $DB->query('SELECT f.*, u.url as theurl FROM exp_weblog_fields f, exp_upload_prefs u WHERE f.field_id = '.$this->settings['field_id'].' AND f.field_list_items=u.id');
		
		$customfield = 'field_id_'.$this->settings['field_id'];
		
		$query = $DB->query("SELECT ".$customfield." FROM exp_weblog_data WHERE entry_id='".$row['entry_id']."'");
		
		$theimage = "";
		$thefileuploadpath = "";

			foreach($uploadprefs->result as $uploadpref)
		{
		  $thefileuploadpath = $uploadpref['theurl'];
	  }

			foreach($query->result as $file_field)
		{
		  $theimage = $file_field[$customfield];
	  }
	if ($theimage !="") {
		  $image = '<img src="'.$thefileuploadpath.$theimage.'" alt="" width="'.$this->settings['thumb_width'].'" valign="middle" />';
		} 
							
		$style = ($img_i % 2) ? 'tableCellOne' : 'tableCellTwo'; $img_i++;
		
		$extra = ($EXT->last_call !== FALSE) ? $EXT->last_call : '';
		
		return $extra.$DSP->table_qcell($style, $image);
	}

/* END class */
}
/* End of file ext.md_weegee.php */
/* Location: ./system/extensions/ext.md_weegee.php */ 