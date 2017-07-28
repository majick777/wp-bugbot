<?php

/*
Plugin Name: WP BugBot
Plugin URI: http://wordquest.org/plugins/wp-bugbot/
Description: WP BugBot, Plugin And Theme Search Editor Engine! Search installed plugin and theme files (and core) for code from the editor screens. A bugfixers dream..!
Version: 1.7.4
Author: Tony Hayes
Author URI: http://dreamjester.net
GitHub Plugin URI: majick777/wp-bugbot
@fs_premium_only pro-functions.php
*/

if (!function_exists('add_action')) {exit;}

// --------------------
// === Setup Plugin ===
// --------------------

// -----------------
// Set Plugin Values
// -----------------
global $wordquestplugins, $vbugbotslug, $vbugbotversion;
$vslug = $vbugbotslug = 'wp-bugbot';
$wordquestplugins[$vslug]['version'] = $vbugbotversion = '1.7.4';
$wordquestplugins[$vslug]['title'] = 'WP BugBot';
$wordquestplugins[$vslug]['namespace'] = 'bugbot';
$wordquestplugins[$vslug]['settings'] = 'bugbot';
$wordquestplugins[$vslug]['hasplans'] = false;
$wordquestplugins[$vslug]['wporgslug'] = 'wp-bugbot';
$vbugbotpage = false; // settings page switch

// ------------------------
// Check for Update Checker
// ------------------------
// note: lack of updatechecker.php file indicates WordPress.Org SVN version
// presence of updatechecker.php indicates site download or GitHub version
$vfile = __FILE__; $vupdatechecker = dirname($vfile).'/updatechecker.php';
if (!file_exists($vupdatechecker)) {$wordquestplugins[$vslug]['wporg'] = true;}
else {include($vupdatechecker); $wordquestplugins[$vslug]['wporg'] = false;}

// -----------------------------------
// Load WordQuest Helper/Pro Functions
// -----------------------------------
if (is_admin()) {$wordquest = dirname(__FILE__).'/wordquest.php'; if (file_exists($wordquest)) {include($wordquest);} }
$vprofunctions = dirname(__FILE__).'/pro-functions.php';
if (file_exists($vprofunctions)) {include($vprofunctions); $wordquestplugins[$vslug]['plan'] = 'premium';}
else {$wordquestplugins[$vslug]['plan'] = 'free';}

// -----------------
// Load Freemius SDK
// -----------------
function bugbot_freemius($vslug) {
    global $wordquestplugins, $bugbot_freemius;
    $vwporg = $wordquestplugins[$vslug]['wporg'];
	if ($wordquestplugins[$vslug]['plan'] == 'premium') {$vpremium = true;} else {$vpremium = false;}
	$vhasplans = $wordquestplugins[$vslug]['hasplans'];

	// redirect for support forum
	if ( (is_admin()) && (isset($_REQUEST['page'])) ) {
		if ($_REQUEST['page'] == $vslug.'-wp-support-forum') {
			if(!function_exists('wp_redirect')) {include(ABSPATH.WPINC.'/pluggable.php');}
			wp_redirect('http://wordquest.org/quest/quest-category/plugin-support/'.$vslug.'/'); exit;
		}
	}

    if (!isset($bugbot_freemius)) {
        if (!class_exists('Freemius')) {require_once(dirname(__FILE__).'/freemius/start.php');}

		$bugbot_settings = array(
            'id'                => '159',
            'slug'              => $vslug,
            'public_key'        => 'pk_03b9fce070af83d5e91dcffcb8719',
            'is_premium'        => $vpremium,
            'has_addons'        => false,
            'has_paid_plans'    => $vhasplans,
            'is_org_compliant'  => $vwporg,
            'menu'              => array(
                'slug'       	=> $vslug,
                'first-path' 	=> 'admin.php?page='.$vslug.'&welcome=true',
                'parent' 		=> array('slug'=>'wordquest'),
                'contact'		=> $vpremium,
                // 'support'   	=> false,
                // 'account'    => false,
            )
        );
        $bugbot_freemius = fs_dynamic_init($bugbot_settings);
    }
    return $bugbot_freemius;
}
// Initialize Freemius
$bugbot_freemius = bugbot_freemius($vslug);

// Custom Freemius Connect Message
function bugbot_freemius_connect($message, $user_first_name, $plugin_title, $user_login, $site_link, $freemius_link) {
	return sprintf(
		__fs('hey-x').'<br>'.
		__('If you want to more easily provide feedback for this plugins features and functionality, %s can connect your user, %s at %s, to %s', 'wp-automedic'),
		$user_first_name, '<b>'.$plugin_title.'</b>', '<b>'.$user_login.'</b>', $site_link, $freemius_link
	);
}
if ( (is_object($bugbot_freemius)) && (method_exists($bugbot_freemius,'add_filter')) ) {
	$bugbot_freemius->add_filter('connect_message', 'bugbot_freemius_connect', WP_FS__DEFAULT_PRIORITY, 6);
}

// ---------------
// Add Admin Menus
// ---------------
if (is_admin()) {add_action('admin_menu', 'bugbot_admin_menu',1);}
function bugbot_admin_menu() {

	if (empty($GLOBALS['admin_page_hooks']['wordquest'])) {
		$vicon = plugins_url('images/wordquest-icon.png',__FILE__); $vposition = apply_filters('wordquest_menu_position','3');
		add_menu_page('WordQuest Alliance', 'WordQuest', 'manage_options', 'wordquest', 'wqhelper_admin_page', $vicon, $vposition);
	}
	add_submenu_page('wordquest', 'WP BugBot', 'WP BugBot', 'manage_options', 'wp-bugbot', 'bugbot_options_page');

	// Add icons and styling to the plugin submenu :-)
	add_action('admin_footer','bugbot_admin_javascript');
	function bugbot_admin_javascript() {
		global $vbugbotslug; $vslug = $vbugbotslug; $vcurrent = '0';
		$vicon = plugins_url('images/icon.png',__FILE__);
		if (isset($_REQUEST['page'])) {if ($_REQUEST['page'] == $vslug) {$vcurrent = '1';} }
		echo "<script>jQuery(document).ready(function() {if (typeof wordquestsubmenufix == 'function') {
		wordquestsubmenufix('".$vslug."','".$vicon."','".$vcurrent."');} });</script>";
	}

	// Plugin Page Settings Link
	add_filter('plugin_action_links', 'bugbot_plugin_action_links', 10, 2);
	function bugbot_plugin_action_links($vlinks, $vfile) {
		global $vbugbotslug;
		$vthisplugin = plugin_basename(__FILE__);
		if ($vfile == $vthisplugin) {
			$vsettingslink = "<a href='".admin_url('admin.php')."?page=".$vbugbotslug."'>".__('Settings','wp-bugbot')."</a>";
			array_unshift($vlinks, $vsettingslink);
		}
		return $vlinks;
	}
}

// ---------------------------
// === Actions and Filters ===
// ---------------------------

// Load Plugin Options
// -------------------
global $vbugbot, $vbugbotsearches;
$vbugbot = get_option('wp_bugbot');
$vbugbotsearches = get_option('wp_bugbot_searches');

// Debug Mode Switch
// -----------------
if (isset($_REQUEST['bugbotdebug'])) {
	if ($_REQUEST['bugbotdebug'] == '1') {
		echo "<!-- WP BugBot Debug Mode is ON -->";
		delete_option('bugbot_debug'); add_option('bugbot_debug','1');
	}
	if ($_REQUEST['bugbotdebug'] == '0') {
		echo "<!-- WP BugBot Debug Mode is OFF -->";
		delete_option('butbot_debug');
	}
}
$vbugbotdebug = get_option('bugbot_debug');

// File Search Triggers
// --------------------
if (is_admin()) {
	if ( (isset($_REQUEST['pluginfilesearch'])) && (isset($_REQUEST['searchkeyword']))
		&& ($_REQUEST['pluginfilesearch'] != '') && ($_REQUEST['searchkeyword'] != '') ) {
			add_filter('all_admin_notices', 'bugbot_plugin_file_do_search');
	}
	if ( (isset($_REQUEST['themefilesearch'])) && (isset($_REQUEST['searchkeyword']))
		&& ($_REQUEST['themefilesearch'] != '') && ($_REQUEST['searchkeyword'] != '') ) {
			add_filter('all_admin_notices', 'bugbot_theme_file_do_search');
	}
	if ( (isset($_REQUEST['corefilesearch'])) && (isset($_REQUEST['searchkeyword']))
		&& ($_REQUEST['corefilesearch'] != '') && ($_REQUEST['searchkeyword'] != '') ) {
			add_filter('all_admin_notices', 'bugbot_core_file_do_search');
	}
}

// Main User Interface Hook
// ------------------------
if (!function_exists('bugbot_interface_hook'))  {
	if (is_admin()) {
		function bugbot_interface_hook($content) {
			global $pagenow;
			// 1.5.0: changed this check from is_multisite
			if (is_network_admin()) {
				if (preg_match('|network/plugin-editor.php|i', $_SERVER["REQUEST_URI"])) {echo '<div class="wrap">'; bugbot_maybe_notice_boxer(); bugbot_plugin_file_search_ui(); echo '</div>';}
				if (preg_match('|network/theme-editor.php|i', $_SERVER["REQUEST_URI"])) {echo '<div class="wrap">'; bugbot_maybe_notice_boxer(); bugbot_theme_file_search_ui(); echo '</div>';}
				if (preg_match('|network/update-core.php|i', $_SERVER["REQUEST_URI"])) {
					echo '<div class="wrap">'; bugbot_maybe_notice_boxer(); bugbot_core_file_search_ui();
					// 1.7.1: if no editing, add theme and plugin searches on update page
					if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {
						bugbot_theme_file_search_ui(); bugbot_plugin_file_search_ui();
					}
					echo '</div>';
				}
			} else {
				if ($pagenow == 'plugin-editor.php') {echo '<div class="wrap">'; bugbot_maybe_notice_boxer(); bugbot_plugin_file_search_ui(); echo '</div>';}
				if ($pagenow == 'theme-editor.php') {echo '<div class="wrap">'; bugbot_maybe_notice_boxer(); bugbot_theme_file_search_ui(); echo '</div>';}
				if ($pagenow == 'update-core.php') {
					echo '<div class="wrap">'; bugbot_maybe_notice_boxer();
					bugbot_core_file_search_ui();
					// 1.7.1: if no editing, add theme and plugin searches on update page
					if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {
						bugbot_theme_file_search_ui(); bugbot_plugin_file_search_ui();
					}
					echo '</div>';
				}
			}

			if ( (isset($_REQUEST['showfilecontents'])) && (trim($_REQUEST['showfilecontents']) != '') ) {
				// TODO: different case for when 'file' is set instead?
				bugbot_file_search_show_file_contents();
			}

			return $content;
		}
	}
	add_filter('all_admin_notices', 'bugbot_interface_hook', 999);
}


// ----------------------
// === Plugin Options ===
// ----------------------

// Get Plugin Option
// -----------------
function bugbot_get_option($vkey,$vfilter=true) {
	global $vbugbot;
	if (isset($vbugbot[$vkey])) {
		if ($vfilter) {return apply_filters($vkey,$vbugbot[$vkey]);}
		else {return $vbugbot[$vkey];}
	} else {
		// 1.7.1: fallback to default options
		$vdefaults = bugbot_default_options();
		if (isset($vdefaults[$vkey])) {return $vdefaults[$vkey];}
		else {return '';}
	}
}

// Set Default Settings
// --------------------
register_activation_hook(__FILE__,'bugbot_add_options');
function bugbot_add_options() {

	// 1.7.0: use plugin global option
	global $vbugbot;
	// 1.7.1: use default option function
	$vbugbot = bugbot_default_options();
	add_option('wp_bugbot',$vbugbot);

	if (file_exists(dirname(__FILE__).'/updatechecker.php')) {$vadsboxoff = '';} else {$vadsboxoff = 'checked';}
	$sidebaroptions = array('adsboxoff'=>$vadsboxoff,'donationboxoff'=>'','reportboxoff'=>'','installdate'=>date('Y-m-d'));
	add_option('bugbot_sidebar_options',$sidebaroptions);
}

// get Default Options
// -------------------
// 1.7.1: separate defaults function
function bugbot_default_options() {
	$vbugbot['save_selected_plugin'] = 'yes';
	$vbugbot['save_selected_theme'] = 'yes';
	$vbugbot['save_selected_dir'] = 'yes';
	$vbugbot['save_last_searched'] = 'yes';
	$vbugbot['save_case_sensitive'] = 'yes';
	$vbugbot['add_editable_extensions'] = '';
	// 1.5.0: added svg,psd,swf to default media
	$vbugbot['donotsearch_extensions'] = 'zip,jpg,jpeg,gif,png,tif,tiff,bmp,svg,psd,pdf,mp3,wma,m4a,wmv,ogg,mpg,mkv,avi,mp4,flv,fla,swf';
	$vbugbot['snip_long_lines_at'] = '500';
	// 1.5.0: added search time limit default 5 mins
	$vbugbot['search_time_limit'] = '300';
	// 1.5.0: added donot replace theme editor option
	$vbugbot['donot_replace_theme_editor'] = '';
	return $vbugbot;
}

// maybe Transfer Old Options/Searches
// -----------------------------------
// 1.7.0: compact old options/searches to single option
if ( (get_option('patsee_do_not_seach_extensions')) && (!get_option('wp_bugbot')) ) {

	$voldoptions = array('save_selected_plugin','save_selected_theme','save_selected_dir',
		'save_last_searched','save_case_sensitive','search_time_limit','donot_replace_theme_editor',
		'snip_long_lines_at','add_editable_extensions','donotsearch_extensions',
		'plugin_file_search_plugin','theme_file_search_theme', 'core_file_search_dir',
		'plugin_file_search_keyword', 'theme_file_search_keyword', 'core_file_search_keyword',
		'plugin_file_search_case','theme_file_search_case', 'core_file_search_case'
	);
	foreach ($voldoptions as $voldoption) {
		$vbugbot[$voldoption] = get_option('patsee_'.$voldoption); delete_option('patsee_'.$voldoption);
	}
	update_option('wp_bugbot',$vbugbot);

	$voldsearches = array('plugin_file_search_plugin','theme_file_search_theme','core_file_search_dir',
		'plugin_file_search_keyword','theme_file_search_keyword','core_file_search_keyword',
		'plugin_file_search_case','theme_file_search_case','core_file_search_case'
	);
	foreach ($voldsearches as $voldsearch) {
		$vbugbotsearches[$voldsearch] = get_option('patsee_'.$voldsearch); delete_option('patsee_'.$voldsearch);
	}
	update_option('wp_bugbot_searches',$vbugbotsearches);

	add_option('bugbot_sidebar_options',get_option('patsee_sidebar_options')); delete_option('patsee_sidebar_options');
}

// Save Settings
// -------------
// 1.7.4: changed to use admin_init hook
add_action('admin_init', 'bugbot_save_settings');
function bugbot_save_settings() {

	// 1.7.4: moved post value checks to inside function
	if (!isset($_REQUEST['bugbot_save_settings'])) {return;}
	if ($_REQUEST['bugbot_save_settings'] != 'yes') {return;}
	if (!current_user_can('manage_options')) {return;}
	// 1.7.0: check nonce value
	check_admin_referer('wp-bugbot');

	global $vbugbot;

	// Update Options
	// --------------
	if (isset($_REQUEST['save_selected_plugin'])) {
		$vsaveselectedplugin = $vbugbot['save_selected_plugin'] = $_REQUEST['save_selected_plugin'];
	} else {$vsaveselectedplugin = $vbugbot['save_selected_plugin'] = '';}
	if (isset($_REQUEST['save_selected_theme'])) {
		$vsaveselectedtheme = $vbugbot['save_selected_theme'] = $_REQUEST['save_selected_theme'];
	} else {$vsaveselectedtheme = $vbugbot['save_selected_theme'] = '';}
	if (isset($_REQUEST['save_selected_dir'])) {
		$vsaveselecteddir = $vbugbot['save_selected_dir'] = $_REQUEST['save_selected_dir'];
	} else {$vsaveselecteddir = $vbugbot['save_selected_dir'] = '';}
	if (isset($_REQUEST['save_last_searched'])) {
		$vsavelastsearched = $vbugbot['save_last_searched'] = $_REQUEST['save_last_searched'];
	} else {$vsavelastsearched = $vbugbot['save_last_searched'] = '';}
	if (isset($_REQUEST['save_case_sensitive'])) {
		$vcasesensitiveselection = $vbugbot['save_case_sensitive'] = $_REQUEST['save_case_sensitive'];
	} else {$vcasesensitiveselection = $vbugbot['save_case_sensitive'] = '';}
	if (isset($_REQUEST['donot_replace_theme_editor'])) {
		$vdonotreplacethemeeditor = $vbugbot['donot_replace_theme_editor'] = $_REQUEST['donot_replace_theme_editor'];
	} else {$vdonotreplacethemeeditor = $vbugbot['donot_replace_theme_editor'] = '';}

	$vsearchtimelimit = $vbugbot['search_time_limit'] = $_REQUEST['search_time_limit'];
	if (!is_numeric($vsearchtimelimit)) {$vbugbot['search_time_limit'] = 300;}
	$vsniplonglinesat = $vbugbot['snip_long_lines_at'] = $_REQUEST['snip_long_lines_at'];
	if (!is_numeric($vsniplonglinesat)) {$vbugbot['snip_long_lines_at'] = 500;}

	// 1.7.0: maybe delete saved searches
	global $vbugbotsearches; $vnewsearches = array();
	if ($vsaveselectedplugin != 'yes') {
		$vnewsearches['plugin_file_search_plugin'] = '';
		$vnewsearches['theme_file_search_theme'] = '';
		$vnewsearches['core_file_search_dir'] = '';
	}
	if ($vsavelastsearched != 'yes') {
		$vnewsearches['plugin_file_search_keyword'] = '';
		$vnewsearches['theme_file_search_keyword'] = '';
		$vnewsearches['core_file_search_keyword'] = '';
	}
	if ($vcasesensitiveselection != 'yes') {
		$vnewsearches['plugin_file_search_case'] = '';
		$vnewsearches['theme_file_search_case'] = '';
		$vnewsearches['core_file_search_case'] = '';
	}
	if ($vbugbotsearches != $vnewsearches) {update_option('wp_bugbot_searches', $vnewsearches);}

	// Handle User Defined Editable Extensions
	// ---------------------------------------
	$vaddextensions = $_REQUEST['add_editable_extensions'];
	$vextensionstoadd = explode(",",$vaddextensions);

	if (count($vextensionstoadd) > 0) {
		$vi = 0;
		foreach ($vextensionstoadd as $vanextension) {
			if (strstr($vanextension,'.')) {$vanextension = str_replace('.', '', $vanextension);}
			$vanextension = trim(preg_replace('/[^a-z0-9]/i', '', $vanextension));
			if ($vanextension != '') {$vextensionstoadd[$vi] = $vanextension;}
			else {unset($vextensionstoadd[$vi]);}
			$vi++;
		}
	}
	$vextensionstoadd = implode(',',$vextensionstoadd);
	$vbugbot['add_editable_extensions'] = $vextensionstoadd;

	// Handle Do Not Search Extensions
	// -------------------------------
	$vdnsextensions = $_REQUEST['donotsearch_extensions'];
	$vdonotsearch = explode(",",$vdnsextensions);

	if (count($vdonotsearch) > 0) {
		$vi = 0;
		foreach ($vdonotsearch as $vanextension) {
			if (strstr($vanextension,'.')) {$vanextension = str_replace('.','',$vanextension);}
			$vanextension = trim(preg_replace('/[^a-z0-9]/i', '', $vanextension));
			if ($vanextension != '') {$vdonotsearch[$vi] = $vanextension;}
			else {unset($vdonotsearch[$vi]);}
			$vi++;
		}
	}
	$vdnsextensions = implode(',',$vdonotsearch);
	$vbugbot['donotsearch_extensions'] = $vdnsextensions;

	update_option('wp_bugbot',$vbugbot);

	// for debugging save values
	// ob_start(); echo "POSTED: "; print_r($_POST); echo PHP_EOL."OPTIONS: "; print_r($vbugbot);
	// $posted = ob_get_contents(); ob_end_clean();
	// $fh = fopen(dirname(__FILE__).'/debug-save.txt','w'); fwrite($fh,$posted); fclose($fh);

	$vsidebaroptions = get_option('bugbot_sidebar_options');
	$vnewoptions = $vsidebaroptions;
	if (isset($_REQUEST['bugbot_donation_box_off'])) {$vnewoptions['donationboxoff'] = $_REQUEST['bugbot_donation_box_off'];}
	if (isset($_REQUEST['bugbot_report_box_off'])) {$vnewoptions['reportboxoff'] = $_REQUEST['bugbot_report_box_off'];}
	if (isset($_REQUEST['bugbot_ads_box_off'])) {$vnewoptions['adsboxoff'] = $_REQUEST['bugbot_ads_box_off'];}
	if ($vnewoptions != $vsidebaroptions) {
		$vsidebaroptions = $vnewoptions;
		update_option('bugbot_sidebar_options',$vsidebaroptions);
	}

	// Special: Quick Sidebar Save
	if (isset($_REQUEST['onpagesave'])) {
		echo "<script>parent.quicksavedshow();";
		if ($vsidebaroptions['donationboxoff'] == 'checked') {echo "parent.document.getElementById('donate').style.display = 'none';";}
		else {echo "parent.document.getElementById('donate').style.display = '';";}
		if ($vsidebaroptions['reportboxoff'] == 'checked') {echo "parent.document.getElementById('bonusoffer').style.display = 'none';";}
		else {echo "parent.document.getElementById('bonusoffer').style.display = '';";}
		if ($vsidebaroptions['adsboxoff'] == 'checked') {echo "parent.document.getElementById('pluginads').style.display = 'none';";}
		else {echo "parent.document.getElementById('pluginads').style.display = '';";}
		echo "</script>"; exit;
	}
}

// Admin Notice Boxer for the default screens only, called later on for search screens...
function bugbot_maybe_notice_boxer() {
	if (!isset($_POST['searchkeyword'])) {
		if (function_exists('wqhelper_admin_notice_boxer')) {wqhelper_admin_notice_boxer();}
	}
}

// --------------------
// === Options Page ===
// --------------------

// Options Page Wrapper
// --------------------
function bugbot_options_page() {

	global $vbugbotslug, $vbugbotpage, $vbugbotversion, $vbugbotsidebar;
	if ($_REQUEST['page'] == $vbugbotslug) {$vbugbotpage = 'done';}

	// 1.7.0: buffer here for stickykit positioning
	ob_start();

	// Sidebar Floatbox
	// ----------------
	// minus save for settings page
	// $vargs = array('bugbot','wp-bugbot','free','wp-bugbot','special','WP BugBot',$vbugbotversion);
	$vargs = array($vbugbotslug,'yes'); // (trimmed arguments)
	if (function_exists('wqhelper_sidebar_floatbox')) {

		wqhelper_sidebar_floatbox($vargs);

		// 1.7.0: replace floatbox with stickykit
		echo wqhelper_sidebar_stickykitscript();
		echo "<style>#floatdiv {float:right;}</style>";
		echo '<script>jQuery("#floatdiv").stick_in_parent();
		jQuery(document).ready(function() {
			wrapwidth = jQuery("#pagewrap").width();
			sidebarwidth = jQuery("#floatdiv").width();
			newwidth = wrapwidth - sidebarwidth;
			jQuery("#wrapbox").css("width",newwidth+"px");
			jQuery("#adminnoticebox").css("width",newwidth+"px");
		});</script>';

		// echo wqhelper_sidebar_floatmenuscript();
		// echo '<script language="javascript" type="text/javascript">
		// floatingMenu.add("floatdiv", {targetRight: 10, targetTop: 20, centerX: false, centerY: false});
		// function move_upper_right() {
		// floatingArray[0].targetTop=20;
		//	floatingArray[0].targetBottom=undefined;
		//	floatingArray[0].targetLeft=undefined;
		//	floatingArray[0].targetRight=10;
		//	floatingArray[0].centerX=undefined;
		//	floatingArray[0].centerY=undefined;
		// }
		// move_upper_right();</script>';
	}
	$vbugbotsidebar = ob_get_contents(); ob_end_clean();

	// Load Options Display
	// --------------------
	$vbugbotpage = true;
	bugbot_sidebar_plugin_header();

	// 1.7.0: added error log search
	echo "<div id='errorlogsearch' style='padding-left:20px;padding-bottom:30px;'>";

	// 1.7.3: error log header in any case
	echo "<h3>".__('PHP Error Logs','wp-bugbot')."</h3>";

	$vlognames = array('error.log','php_errors.log');
	$verrorlog = ini_get('error_log');
	if ( ($verrorlog) && (!in_array($verrorlog,$vlognames)) ) {$vlognames[] = ini_get('error_log');}

	// 1.7.3: filter the log names searched for
	$vfilterlognames = apply_filters('bugbot_error_log_search',$vlognames);
	if (is_array($vfilterlognames)) {$vlognames = $vfilterlognames;}

	// 1.7.3: output the log filenames searched for
	if (count($vlognames) > 0) {
		$vi = 0;
		echo __('Searching your current installation for the following filenames','wp-bugbot').':<br>';
		$vdisplaylogs = implode(', ',$vlognames);
		echo $vdisplaylogs."...<br>";
	} else {echo __('Searching for Error Logs has been disabled by filter.','wp-bugbot').'<br>';}

	// 1.7.1: fix to empty variable to array
	$verrorlogs = array();
	// 1.7.3: for subdirectory installs, also check for error logs in parent directory
	if ( (@file_exists(dirname(ABSPATH).'/wp-config.php'))
	  && (!@file_exists(dirname(ABSPATH).'/wp-settings.php')) ) {
	  	$vparentfiles = scandir(dirname(ABSPATH));
	  	foreach ($vparentfiles as $vfile) {
	  		if ( ($vfile != '.') && ($vfile != '..') ) {
				foreach ($vlognames as $vlogname) {
					if (substr($vfile,-(strlen($vlogname)),strlen($vlogname)) == $vlogname) {
						$verrorlogs[$vfile] = dirname(ABSPATH).'/'.$vfile;
					}
				}
			}
	  	}
	}
	// do the main recursive search for log files
	$vfiles = bugbot_file_search_list_files(ABSPATH);
	foreach ($vfiles as $vfile) {
		foreach ($vlognames as $vlogname) {
			if (substr($vfile,-(strlen($vlogname)),strlen($vlogname)) == $vlogname) {
				$verrorlogs[$vfile] = ABSPATH.$vfile;
			}
		}
	}
	// print_r($verrorlogs);

	if (count($verrorlogs) > 0) {
		// 1.7.1: number of log lines to parse
		echo "<center><table><tr>";
		echo "<td><b>".__('Process Last x Lines of Error Log','wp-bugbot').":</b></td>";
		echo "<td width='30'></td>";
		echo "<td><input id='loglines' type='number' value='200' style='width:70px;'></td>";
		echo "<td width='10'></td>";
		echo "<td>(".__('0 or blank for all').".)</td>";
		echo "</tr></table></center>";

		foreach ($verrorlogs as $vdisplay => $vpath) {
			$vdisplayurl = admin_url('admin-ajax.php').'?action=bugbot_view_error_log&path='.urlencode($vpath).'&lines=';
			echo "<a href='".$vdisplayurl."' target='errorlogframe' onclick='this.href+=document.getElementById(\"loglines\").value'>".$vdisplay."</a><br>";
		}
	} else {echo __('No Error Logs were found.','wp-bugbot');}

	echo "<div id='errorlogwrap' style='display:none;'><h4>".__('Error Log Contents')."</h4>";
	echo "<iframe src='javascript:void(0);' name='errorlogframe' id='errorlogframe' width='650px' height='650px'></iframe>";
	echo "</div>";
	echo "</div>"; // close error log search

	echo "</div></div>"; // close wrapbox
	echo "</div>"; // close wrap

}

// AJAX View Error Log
// -------------------
// 1.7.0: added error log viewer
add_action('wp_ajax_bugbot_view_error_log','bugbot_view_error_log');
function bugbot_view_error_log() {
	if (!current_user_can('manage_options')) {exit;}

	if (isset($_REQUEST['path'])) {$path = $_REQUEST['path'];} else {exit;}
	if (!file_exists($path)) {echo __('Oops, that log file no longer exists!','wp-bugbot'); exit;}
	if (isset($_REQUEST['lines'])) {$maxlines = $_REQUEST['lines'];} else {$maxlines = 200;}
	// 1.7.1: validate maximum number of lines
	$maxlines = absint($maxlines);
	if ( ($maxlines == '') || ($maxlines < 1) ) {$maxlines = 0;} // >

	echo '<style>body {font-family: Consolas, "Lucida Console", Monaco, FreeMono, monospace;}
	.error {font-size:14px;} .errordatetime, .smaller {font-size:12px;}
	.datelist, .datelist li {list-style:none; display:inline-block; padding:0; margin:0;} .datelist li {margin-left:20px;}
	.fatal {color:#EE0000;} .warning {color:#EE6600;} .notice {color:#0000AA;} .deprecated {color:#000066
	</style>';

	echo __('Log Path','wp-bugbot').': '.stripslashes($path).'<br><br>';

	// read the error log file... backwards..!
	// ref: http://stackoverflow.com/a/26595154/5240159
	$lines = 0; $errors = array(); $errortimes = array();
	if ( $v = @fopen($path, 'r') ) { // open the file
		fseek($v, 0, SEEK_END); // move cursor to the end of the file

		/* help functions: */
		// moves cursor one step back if can - returns true, if can't - returns false
		function moveOneStepBack( &$f ){
			if( ftell($f) > 0 ) { fseek($f, -1, SEEK_CUR); return true; }
			else {return false;}
		}
		// reads $length chars but moves cursor back where it was before reading
		function readNotSeek( &$f, $length ){
			$r = fread($f, $length);
			fseek($f, -$length, SEEK_CUR);
			return $r;
		}

		/* THE READING+PRINTING ITSELF: */
		while ( ftell($v) > 0 ) { // while there is at least 1 character to read
			$newLine = false; $charCounter = 0;

			// line counting
			while ( !$newLine && moveOneStepBack( $v ) ) { // not start of a line / the file
				if( readNotSeek($v, 1) == "\n" ) {$newLine = true;}
				$charCounter++;
			}

			// line reading / printing
			if ( $charCounter > 1 ) { // if there was anything on the line
				// if ( !$newLine ) {echo "<br>";} // prints missing "\n" before last *printed* line
				$thisline = readNotSeek( $v, $charCounter ); // gets current line

				// modify original function to store for later display
				if (strstr($thisline,'] ')) {
					$pos = strpos($thisline,'] ') + 2;
					$datetime = trim(substr($thisline,0,$pos));
					$datetime = str_replace('[','',$datetime);
					$datetime = str_replace(']','',$datetime);
					$error = substr($thisline,$pos,strlen($thisline));
					if (in_array($error,$errors)) {$errortimes[$error][] = $datetime;}
					else {$errortimes[$error][0] = $datetime; $errors[] = $error;}
				} else {
					if ( !$newLine ) {echo "<br>";}
					echo $thisline;
				}
				$lines++;
			}
			// 1.7.1: handle limited or unlimited lines
			if ( ($maxlines > 0) && ($lines > $maxlines) ) {break;}
		}
		fclose( $v ); // close the file, because we are well-behaved
	}

	if (count($errors) > 0) {
		$errornum = 0;
		echo "<script>var adminurl = '".admin_url('admin-ajax.php')."';
		function showdates(error) {
			if (document.getElementById('errordates-'+error).style.display == 'none') {
				document.getElementById('errordates-'+error).style.display = '';
			} else {document.getElementById('errordates-'+error).style.display = 'none';}
		}
		function loadline(error,file,line) {
			document.getElementById('errorline-'+error).src = adminurl+'?action=bugbot_load_line&file='+file+'&line='+line;
			document.getElementById('errorline-'+error).style.display = '';
		}
		function deleteerror(path,error,errornum) {
			agree = confirm('".__('Delete all occurrences of this error in log file?','bioship')."');
			if (!agree) {return false;}
			document.getElementById('errorline-'+errornum).src = adminurl+'?action=bugbot_delete_error&path='+path+'&error='+error+'&errornum='+errornum;
		}</script>".PHP_EOL;

		echo "<table cellpadding='0' cellspacing='5'>";
		foreach ($errors as $error) {
			echo "<tr id='errornum-".$errornum."'><td style='vertical-align:top;'>";
			if (count($errortimes[$error]) > 1) {echo "[".count($errortimes[$error])."]";}
			echo "</td><td class='errordatetime' style='vertical-align:top;'>";
				$displaydatetimes = '';
				if (count($errortimes[$error]) == 1) {echo $errortimes[$error][0];}
				else {
					echo "<a href='javascript:void(0);' onclick='showdates(\"".$errornum."\");'>";
					echo $errortimes[$error][0]."</a>";
					$displaydatetimes = "<div id='errordates-".$errornum."' class='smaller' style='display:none;'>";
					$displaydatetimes .= __('Earlier Occurrences of this Error','wp-bugbot').":<br>";
					$displaydatetimes .= "<ul class='datelist'>";
					foreach ($errortimes[$error] as $datetime) {$displaydatetimes .= "<li>".$datetime."</li>";}
					$displaydatetimes .= "</ul></div>";
				}
			echo "</td><td class='error' style='vertical-align:top;'>";
				echo $displaydatetimes;
				$linelink = ''; $displayerror = $error;
				if ( (strstr($error,' in ')) && (strstr($error,' on line ')) ) {
					$posa = strpos($error,' in ') + 4;
					$posb = strpos($error,' on line ');
					$filepath = substr($error,$posa,($posb-$posa));
					$temp = substr($error,($posb + 9),strlen($error));
					$pos = strpos($temp,' ');
					if ($pos > 0) {$linenum = substr($temp,0,$pos);} else {$linenum = $temp;}

					// 1.7.2: fix to view URL for core/plugin/theme
					$viewurl = "update-core.php?showfilecontents=".urlencode($filepath);
					if (!defined('DISALLOW_FILE_EDIT') || !DISALLOW_FILE_EDIT) {
						if (strstr($filepath,'wp-content/plugins')) {
							$viewurl = "plugin-editor.php?showfilecontents=".urlencode($filepath)."&pluginfile=yes";
						} elseif (strstr($filepath,'wp-content/themes')) {
							$viewurl = "theme-editor.php?showfilecontents=".urlencode($filepath)."&themefile=yes";
						}
					}

					$filelink = "<a href='".$viewlink."' target=_blank";
					$filelink .= " style='text-decoration:none;'>".str_replace(ABSPATH,'',$filepath)."</a>";
					$linelink = "<a href='javascript:void(0);' ";
					$linelink .= "onclick='loadline(\"".$errornum."\",\"".urlencode($filepath)."\",\"".$linenum."\");'><b>line ".$linenum."</b></a>";
					$search = ' in '.$filepath.' on line '.$linenum;
					$replace = ' in '.$filelink.' on '.$linelink;
					$displayerror = str_replace($search,$replace,$displayerror);
				}

				$displayerror = str_ireplace('PHP Fatal Error','<span class="fatal">Fatal Error</span>',$displayerror);
				$displayerror = str_ireplace('PHP Parse Error','<span class="fatal">Parse Error</span>',$displayerror);
				$displayerror = str_ireplace('PHP Warning','<span class="warning">Warning</span>',$displayerror);
				$displayerror = str_ireplace('PHP Notice','<span class="notice">Notice</span>',$displayerror);
				$displayerror = str_ireplace('PHP Deprecated','<span class="deprecated">Deprecated</span>',$displayerror);
				echo $displayerror;
				echo "<iframe src='javascript:void(0);' style='display:none;' width='100%' height='100px' name='errorline-".$errornum."' id='errorline-".$errornum."' frameborder='no'></iframe>";
			echo "</td><td style='vertical-align:top;'>";
				echo "<a href='javascript:void(0);' onclick='deleteerror(\"".urlencode($path)."\",\"".urlencode($error)."\",\"".$errornum."\");' style='text-decoration:none;color:#ee0000;' title='".__('Delete this Error from Log','wp-bugbot')."'>X</a>";
			echo "</td></tr>";
			$errornum++;
		}
		echo "</table>";
	}

	echo "<script>parent.document.getElementById('errorlogwrap').style.display = '';</script>";
	exit;
}

// AJAX View File Line Number
// --------------------------
add_action('wp_ajax_bugbot_load_line','bugbot_load_line');
function bugbot_load_line() {
	if (!current_user_can('manage_options')) {exit;}

	if (isset($_REQUEST['file'])) {$file = $_REQUEST['file'];} else {exit;}
	if (isset($_REQUEST['line'])) {$line = $_REQUEST['line'];} else {exit;}
	if (!is_numeric($line)) {exit;}

	echo '<style>body {font-family: Consolas, "Lucida Console", Monaco, FreeMono, monospace; font-size:14px; line-height:1.4em;}</style>';
	if (file_exists($file)) {
		$filecontents = file_get_contents($file);
		$lines = explode("\n",$filecontents);

		if ($line !== 0) {echo "<span style='background-color:#DDDDDD;'>".$lines[$line-2]."</span><br>";}
		echo "<span style='background-color:#EEEE00;'>".$lines[$line-1]."</span><br>";
		if (count($lines) > ($line-1)) {echo "<span style='background-color:#DDDDDD;'>".$lines[$line]."</span><br>";}

	} else {echo __('File Not Found','wp-bugbot').": ".$file;}
	exit;
}

// AJAX Delete Error From Log
// --------------------------
// 1.7.0: added error removal function
add_action('wp_ajax_bugbot_delete_error','bugbot_delete_error');
function bugbot_delete_error() {
	if (!current_user_can('manage_options')) {exit;}

	if (isset($_REQUEST['path'])) {$path = $_REQUEST['path'];} else {exit;}
	// echo $path.'<br>';
	if (!file_exists($path)) {exit;}
	if (isset($_REQUEST['error'])) {$error = stripslashes($_REQUEST['error']);} else {exit;}
	if (isset($_REQUEST['errornum'])) {$errornum = $_REQUEST['errornum'];} else {exit;}

	echo $error.'-----<br>';
	// make sure this is a log file
	$verrorlogs = '';
	$vlognames = array('error.log','php_errors.log');
	$verrorlog = ini_get('error_log');
	if ( ($verrorlog) && (!in_array($verrorlog,$vlognames)) ) {$vlognames[] = ini_get('error_log');}
	$vpathinfo = pathinfo($path);
	if (!in_array($vpathinfo['basename'],$vlognames)) {exit;}

	$newlogfile = ''; $fh = fopen($path,'r');
	$line = fgets($fh); if (!$line) {exit;}
	while ($line) {
		if (!strstr($line,$error)) {$newlogfile .= $line;}
		$line = fgets($fh);
	}
	fclose($fh);
	// echo '<br>-----'.$newlogfile;

	if (strlen($newlogfile) > 0) {$fh = fopen($path,'w'); fwrite($fh,$newlogfile); fclose($fh);}
	else {@unlink($path);} // delete log if now empty

	echo "<script>parent.document.getElementById('errornum-".$errornum."').style.display = 'none';</script>";
	exit;
}

// Plugin Header Box
// -----------------
// (now combined with settings page output)
function bugbot_sidebar_plugin_header() {

	global $vbugbotversion, $vbugbotslug, $vbugbotpage;
	if ($vbugbotpage === 'done') {return;} // note: must be ===

	if (!$vbugbotpage) {
		echo "<script language='javascript' type='text/javascript'>
		function showsearchoptions() {
			document.getElementById('searchoptions').style.display = '';
			document.getElementById('showsearchoptions').style.display = 'none';
			document.getElementById('hidesearchoptions').style.display = '';
		}
		function hidesearchoptions() {
			document.getElementById('searchoptions').style.display = 'none';
			document.getElementById('hidesearchoptions').style.display = 'none';
			document.getElementById('showsearchoptions').style.display = '';
		}
		function showsearchsettings(divid) {
			var settingsdiv = divid + 'settings';
			document.getElementById('extensionssettings').style.display = 'none';
			document.getElementById('searchsettings').style.display = 'none';
			document.getElementById('sidebarsettings').style.display = 'none';
			document.getElementById(settingsdiv).style.display = '';
			var spanid = divid + 'bg';
			document.getElementById('extensionsbg').style.backgroundColor = '#ffffff';
			document.getElementById('searchbg').style.backgroundColor = '#ffffff';
			document.getElementById('sidebarbg').style.backgroundColor = '#ffffff';
			document.getElementById(spanid).style.backgroundColor = '#eeeeee';
		}
		</script>";
	}

	if ($vbugbotpage) {

		echo '<div id="pagewrap" class="wrap" style="width:100%;margin-right:0px !important;">';

		// Admin Notices Boxer
		// -------------------
		if (function_exists('wqhelper_admin_notice_boxer')) {wqhelper_admin_notice_boxer();}

		global $vbugbotsidebar;
		echo $vbugbotsidebar;

		// Plugin Page Title
		// -----------------
		$viconurl = plugins_url('images/wp-bugbot.png',__FILE__);
		echo "<table><tr>";
		echo "<td width='48'><img src='".$viconurl."'></td><td width='20'></td><td>";
			echo "<table><tr><td><h2>WP BugBot</h2></td><td width='20'></td>";
			echo "<td><h3>v".$vbugbotversion."</h3></td></tr>";
			echo "<tr><td colspan='3' align='center'>".__('by','wp-bugbot');
			echo " <a href='http://wordquest.org/' style='text-decoration:none;' target=_blank><b>WordQuest Alliance</b></a>";
			echo "</td></tr></table>";
		echo "</td><td width='50'></td>";
		// 1.7.1: added welcome message
		if ( (isset($_REQUEST['welcome'])) && ($_REQUEST['welcome'] == 'true') ) {
			echo "<td><table style='background-color: lightYellow; border-style:solid; border-width:1px; border-color: #E6DB55; text-align:center;'>";
			echo "<tr><td><div class='message' style='margin:0.25em;'><font style='font-weight:bold;'>";
			echo __('Welcome! For usage see','wp-bugbot')." <i>readme.txt</i> FAQ</font></div></td></tr></table></td>";
		}
		if ( (isset($_REQUEST['updated'])) && ($_REQUEST['updated'] == 'yes') ) {
			echo "<td><table style='background-color: lightYellow; border-style:solid; border-width:1px; border-color: #E6DB55; text-align:center;'>";
			echo "<tr><td><div class='message' style='margin:0.25em;'><font style='font-weight:bold;'>";
			echo __('Settings Updated.','wp-bugbot')."</font></div></td></tr></table></td>";
		}
		echo "</tr></table><br>";

		echo "<div id='wrapbox' class='postbox' style='width:680px;line-height:2em;'><div class='inner' style='padding-left:20px;'>";

		// 1.7.1: added no file editing allowed message
		if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {
			$message = __('File editing is disabled. Plugin and Theme Search interfaces can be found on the ');
			// 1.7.4: fix to update URL for dissallowed file editing
			$message .= "<a href='update-core.php'>".__('Core Updates page')."</a>.";
			echo "<center><p><div class='message'>";
			echo "<table width='80%' style='background-color: lightYellow; border-style:solid; border-width:1px; border-color: #E6DB55; padding: 0 0.6em; text-align:center;'>";
			echo "<tr><td><p style='margin:7px;' class='message'>".$message."</p></td></tr></table></div></p></center>";
		}
	}

	// start save form
	echo '<form id="pfssettings" action="" target="pfssaveframe" method="post">';

	if (!$vbugbotpage) {
		echo "<div id='searchoption'><div class='stuffbox' style='width:250px;'><h3>".__('Plugin Options','wp-bugbot')."</h3><div class='inside'>";
		echo "<div id='showsearchoptions'><a href='javascript:void(0);' onclick='showsearchoptions();'>".__('Show Plugin Options','wp-bugbot')."</a></div>";
		echo "<div id='hidesearchoptions' style='display:none;'><a href='javascript:void(0);' onclick='hidesearchoptions();'>".__('Hide Plugin Options','wp-bugbot')."</a></div>";
		echo "<div id='searchoptions' style='display:none;'>";

		// Settings Tabs
		echo "<center><table><tr>";
		echo "<td><a href='javascript:void(0)' onclick='showsearchsettings(\"extensions\");'><span id='extensionsbg' style='background-color:#eeeeee;'>".__('Extensions','wp-bugbot')."</span></a></td><td width='20'></td>";
		echo "<td><a href='javascript:void(0)' onclick='showsearchsettings(\"search\");'><span id='searchbg'>".__('Settings','wp-bugbot')."</span></a></td><td width='20'></td>";
		echo "<td><a href='javascript:void(0)' onclick='showsearchsettings(\"sidebar\");'><span id='sidebarbg'>".__('Sidebar','wp-bugbot')."</span></a></td><td width='20'></td>";
		echo "</td></tr></table></center><br>";
	} else {echo '<div><div><div>';}

	// Editable Extensions
	// -------------------
	if ($vbugbotpage) {echo "<table><tr><td align='center' style='vertical-align:top;'>";}
	echo "<div id='extensionssettings'>";
	echo "<center><h4>".__('Editable Extensions','wp-bugbot')."</h4>";
	echo "<b>".__('Default Editable Extensions','wp-bugbot').":</b><br>";
	// 1.7.0: just specify these directly
	// $vdefaulteditableextensions = bugbot_get_option('default_editable_extensions',false);
	// $vextensionarray = explode(",",$vdefaulteditableextensions);
	$vextensionarray = array('php', 'txt', 'text', 'js', 'css', 'html', 'htm', 'xml', 'inc', 'include');
	if (count($vextensionarray) > 0) {
		$vi = 0;
		foreach ($vextensionarray as $vextension) {
			if ($vi == 0) {echo $vextension;} else {echo ", ".$vextension;}
			$vi++;
		}
	}
	$vaddextensions = bugbot_get_option('add_editable_extensions',false);
	echo "<br><b>".__('Add Editable Extensions','wp-bugbot').":</b><br>";
	echo "<textarea rows='2' cols='25' id='add_editable_extensions' name='add_editable_extensions'>".$vaddextensions."</textarea><br>";
	echo "(".__('comma separated list','wp-bugbot').")<br>";

	$vdonotsearchextensions = bugbot_get_option('donotsearch_extensions',false);
	echo "<p style='line-height:1.4em;margin-bottom:5px;'><b>".__('Do Not Search Files','wp-bugbot')."<br>".__('with these Extensions','wp-bugbot').":</b></p>";
	echo "<textarea rows='3' cols='25' id='donotsearch_extensions' name='donotsearch_extensions'>".$vdonotsearchextensions."</textarea><br>";
	echo "(".__('comma separated list','wp-bugbot').")<br>";
	echo "</center></div>";

	// Search Settings
	// ---------------
	if ($vbugbotpage) {echo "</td><td width='20'></td><td align='center' style='vertical-align:top;'>";}
	echo '<div id="searchsettings"';
	if (!$vbugbotpage) {echo ' style="display:none;"';}
	echo '><center><h4>"'.__('Search Settings','wp-bugbot').'"</h4>';
	echo '<table><tr><td width="220"><b>'.__('Save Last Selected Plugins','wp-bugbot').'?</b></td><td></td>';
	echo '<td align="center"><input type="checkbox" value="yes" name="save_selected_plugin"'; // '
	if (bugbot_get_option('save_selected_plugin',false) == 'yes') {echo " checked>";} else {echo ">";}
	echo '</td></tr>';
	echo '<tr><td width="220"><b>'.__('Save Last Selected Theme','wp-bugbot').'?</b></td><td></td>';
	echo '<td align="center"><input type="checkbox" value="yes" name="save_selected_theme"'; // '
	if (bugbot_get_option('save_selected_theme',false) == 'yes') {echo " checked>";} else {echo ">";}
	echo '</td></tr>';
	echo '<tr><td width="220"><b>'.__('Save Last Selected Core Path','wp-bugbot').'?</b></td><td></td>';
	echo '<td align="center"><input type="checkbox" value="yes" name="save_selected_dir"'; // '
	if (bugbot_get_option('save_selected_dir',false) == 'yes') {echo " checked>";} else {echo ">";}
	echo '</td></tr>';
	echo '<tr><td><b>Save Last Searched Keyword?</b></td><td></td>';
	echo '<td align="center"><input type="checkbox" value="yes" name="save_last_searched"';
	if (bugbot_get_option('save_last_searched',false) == 'yes') {echo " checked>";} else {echo ">";}
	echo '</td></tr>';
	echo '<tr><td><b>'.__('Save Case Sensitive Selection','wp-bugbot').'?</b></td><td></td>';
	echo '<td align="center"><input type="checkbox" value="yes" name="save_case_sensitive"';
	if (bugbot_get_option('save_case_sensitive',false) == 'yes') {echo " checked>";} else {echo ">";}
	echo "</td></tr>";
	echo "<tr height='20'><td> </td></tr>";
	$vsniplinesat = bugbot_get_option('snip_long_lines_at',false);
	echo '<tr><td><b>'.__('Snip Result Lines at x Characters','wp-bugbot').':</b></td><td></td>';
	echo '<td><input type="text" size="2" value="'.$vsniplinesat.'" name="snip_long_lines_at"';
	echo "</td></tr>";
	$vtimelimit = bugbot_get_option('search_time_limit',false);
	if (!$vtimelimit) {$vtimelimit = 300;}
	echo '<tr><td><b>'.__('Search Time Limit','wp-bugbot').':</b></td><td></td>';
	echo '<td><input type="text" size="2" value="'.$vtimelimit.'" name="search_time_limit"';
	echo "</td></tr>";
	echo '<tr><td><b>'.__('Use Default Theme Editor','wp-bugbot').':</b></td><td></td>';
	echo '<td align="center"><input type="checkbox" value="yes" name="donot_replace_theme_editor"';
	if (bugbot_get_option('donot_replace_theme_editor',false) == 'yes') {echo " checked>";} else {echo ">";}
	echo "</td></tr>";
	echo "</table></center>";
	echo "</div>";
	if ($vbugbotpage) {echo "</td><td width='20'></td><td align='center' style='vertical-align:top;'>";}

	// Sidebar Settings
	// ----------------
	$vsidebaroptions = get_option('bugbot_sidebar_options');
	echo '<div id="sidebarsettings" style="display:none;">';
	echo "<center><b>".__('Sidebar Settings','wp-bugbot')."</b><br><br>";
	echo "<table><tr><td align='center'>";
	echo "<b>".__('I rock! I have made a donation.','wp-bugbot')."</b><br>(hides donation box)</td><td width='10'></td>";
	echo "<td align='center'><input type='checkbox' name='bugbot_donation_box_off' value='checked'";
	if ($vsidebaroptions['donationboxoff'] == 'checked') {echo " checked>";} else {echo ">";}
	echo "</td></tr>";
	echo "<tr><td align='center'>";
	echo "<b>".__("I've got your report, you",'wp-bugbot')."<br>".__('can stop bugging me now.','wp-bugbot')." :-)</b><br>(hides report box)</td><td width='10'></td>";
	echo "<td align='center'><input type='checkbox' name='bugbot_report_box_off' value='checked'";
	if ($vsidebaroptions['reportboxoff'] == 'checked') {echo " checked>";} else {echo ">";}
	echo "</td></tr>";
	echo "<tr><td align='center'>";
	echo "<b>".__('My site is so awesome it','wp-bugbot')."<br>".__("doesn't need any more quality",'wp-bugbot');
	echo "<br>".__('plugins recommendations.','wp-bugbot')."</b><br>(".__('hides sidebar ads.','wp-bugbot').")</td><td width='10'></td>";
	echo "<td align='center'><input type='checkbox' name='bugbot_ads_box_off' value='checked'";
	if ($vsidebaroptions['adsboxoff'] == 'checked') {echo " checked>";} else {echo ">";}
	echo "</td></tr></table></center>";
	echo '</div>';
	if ($vbugbotpage) {echo "</td></tr><tr><td colspan='5'>";}

	// Save Settings Button
	// --------------------
	echo "<script language='javascript' type='text/javascript'>
	function changetarget(formtarget) {
		if (formtarget == 'iframe') {document.getElementById('pfssettings').target = 'pfssaveframe';}
		if (formtarget == 'reload') {document.getElementById('pfssettings').target = '_self';}
	}</script>";

	echo "<br><input type='hidden' name='bugbot_save_settings' value='yes'>";
	// 1.7.0: add nonce check field
	wp_nonce_field('wp-bugbot');

	if (isset($_REQUEST['themefilesearch'])) {echo "<input type='hidden' name='themefilesearch' value='".$_REQUEST['themefilesearch']."'>";}
	if (isset($_REQUEST['pluginfilesearch'])) {echo "<input type='hidden' name='pluginfilesearch' value='".$_REQUEST['pluginfilesearch']."'>";}
	if (isset($_REQUEST['corefilesearch'])) {echo "<input type='hidden' name='corefilesearch' value='".$_REQUEST['corefilesearch']."'>";}
	if (isset($_REQUEST['searchkeyword'])) {echo "<input type='hidden' name='searchkeyword' value='".$_REQUEST['searchkeyword']."'>";}
	if (isset($_REQUEST['searchcase'])) {echo "<input type='hidden' name='searchcase' value='".$_REQUEST['searchcase']."'>";}

	echo "<center><table><tr><td>";
	if ($vbugbotpage) {
		echo "<center>";
		echo "<input type='submit' class='button-primary' id='plugin-settings-save' onclick='changetarget(\"reload\");' value='".__('Save Settings','wp-bugbot')."'>";
		echo "</center>";
	} else {
		echo "<input type='submit' class='button-secondary' name='onpagesave' onclick='changetarget(\"iframe\");' value='".__('Save Settings','wp-bugbot')."'>";
		echo "</td><td width='10'></td><td>";
		echo "<input type='submit' class='button-primary' name='newpagesave' onclick='changetarget(\"reload\");' value='".__('Save','wp-bugbot')." + ".__('ReSearch','wp-bugbot')."'>";
	}
	echo "</td></tr></table></center>";
	echo "</form>";
	// end save form

	echo "<div id='settingssaved' style='display:none;'>";
	echo "<center><div id='search_message_box'>";
	echo "<table style='background-color: lightYellow; border-style:solid; border-width:1px; border-color: #E6DB55; text-align:center;'>";
	echo "<tr><td><div class='message' style='margin:0.25em;'><font style='font-weight:bold;'>";
	echo __('Settings Saved.','wp-bugbot')."</font></div></td></tr></table></div></center>";
	echo "</div>";

	echo "<script>function quicksavedshow() {
		quicksaved = document.getElementById('settingssaved'); quicksaved.style.display = 'block';
		setTimeout(function() {jQuery(quicksaved).fadeOut(5000,function(){});}, 5000);
	}</script>";

	if ($vbugbotpage) {echo "</td></tr></table>";}
	echo "</div>"; // close #searchoptions
	echo "</div></div></div>";

	// settings update iframe
	echo "<iframe style='display:none;' src='javascript:void(0);' name='pfssaveframe' id='pfssaveframe'></iframe>";

	// 1.7.0: moved to incorporate log viewer
	// if ($vbugbotpage) {
	// 	echo "</div></div>"; // close wrapbox
	// 	echo "</div>"; // close wrap
	// }
}


// ------------------------
// === Helper Functions ===
// ------------------------

// Search Time Limit
// -----------------
function bugbot_set_time_limit() {
	// 1.5.0: added maxiumum time limit for search
	$timelimit = bugbot_get_option('search_time_limit');
	if ($timelimit == '') {$timelimit = 300;}
	$timelimit = absint($timelimit);
	if (!is_numeric($timelimit)) {$timelimit = 300;}
	if ($timelimit < 60) {$timelimit = 60;}
	if ($timelimit > 3600) {$timelimit = 3600;}
	@set_time_limit($timelimit);
}

// Replace Theme Editor
// --------------------
// 1.5.0: replace theme-editor.php so we can add theme_editor_allowed_files filter
// as default editor only lists css and php to dir depth of 1?! say what?
global $pagenow;
// 1.7.1: fix to typo in function name
if ($pagenow == 'theme-editor.php') {add_action('load-theme-editor.php', 'bugbot_load_theme_editor', 1);}

// 1.7.0: modify theme-editor.php to add allowed files filter
// 1.7.1: check file match just before using it
if (preg_match('|theme-editor.php|i', $_SERVER["REQUEST_URI"])) {
	add_action('load-theme-editor.php', 'bugbot_replace_theme_editor', 0);
}

// note: this is on by default
function bugbot_replace_theme_editor() {
	$donotreplacethemeeditor = bugbot_get_option('donot_replace_theme_editor');
	if ( ($donotreplacethemeeditor != 'yes') || ($donotreplacethemeeditor != '1') ) {
		$themeeditorpath = ABSPATH.'wp-admin/theme-editor.php';
		$defaultthemeeditor = file_get_contents($themeeditorpath);
		if (!file_exists($themeeditorpath)) {return;}
		$themeeditorpath = dirname(__FILE__).'/theme-editor.php';
		$savedthemeeditor = file_get_contents($themeeditorpath);

		$search = 'validate_file_to_edit( $file, $allowed_files );';
		$replace = "\$allowed_files = apply_filters('theme_editor_allowed_files',\$allowed_files);".PHP_EOL.$search;
		$modthemeeditor = str_replace($search,$replace,$defaultthemeeditor);
		$search = "require_once( dirname( __FILE__ ) . '/admin.php' );";
		$replace = "require_once( ABSPATH . 'wp-admin/admin.php' );";
		$modthemeeditor = str_replace($search,$replace,$modthemeeditor);

		if ($savedthemeeditor != $modthemeeditor) {
			// 1.7.1: check filesystem direct method before writing
			// 1.7.2: use WP Filesystem for writing file with permissions
			if (!function_exists('request_filesystem_credentials')) {include(ABSPATH.'wp-admin/includes/file.php');}

			// request filesystem credentials
			ob_start(); // start output buffer for no form output
			$creds = request_filesystem_credentials('', false, false, dirname(__FILE__), null, true);
			ob_end_clean(); // clear the output buffer

			// initialize the filesystem if we have credentials
			if ($creds) {$filesystem = WP_Filesystem($creds, dirname(__FILE__), true);}

			// write if there are credentials and they are valid
			if ($creds && $filesystem) {
				// write the file using the WP Filesystem
				global $wp_filesystem;
				$writeresult = $wp_filesystem->put_contents($themeeditorpath, $modthemeeditor, FS_CHMOD_FILE);
			} else {
				// does not match and we could not replace it, revert to default editor
				remove_action('load-theme-editor.php', 'bugbot_load_theme_editor', 1);
			}
		}
	}
}

// 1.7.1: fix to typo in function name
function bugbot_load_theme_editor() {
	$themeeditorpath = dirname(__FILE__).'/theme-editor.php';
	if (isset($_REQUEST['theme'])) {$theme = $_REQUEST['theme'];} else {$theme = '';}
	if (isset($_REQUEST['file'])) {$file = $_REQUEST['file'];} else {$file = '';}
	if (isset($_REQUEST['error'])) {$error = $_REQUEST['error'];} else {$error = '';}
	if (isset($_REQUEST['action'])) {$action = $_REQUEST['action'];} else {$action = '';}
	include($themeeditorpath); exit;
}

// 1.7.0:  use the wp_theme_editor_filetypes filter added WP 4.4
add_filter('wp_theme_editor_filetypes', 'bugbot_theme_file_types', 10, 2);
function bugbot_theme_file_types($default_types, $theme) {
	// check plugin editable extensions option here
	$editableextensions = bugbot_get_editable_extensions();
	return $editableextensions;
}

// Theme Editor Allowed Files
// --------------------------
// 1.5.0: added this to allow theme editing
// as default editor only adds root css and php to depth of 1 !?!
add_filter('theme_editor_allowed_files','bugbot_theme_editor_allowed_files');
function bugbot_theme_editor_allowed_files($allowed_files) {

	if (isset($_REQUEST['theme'])) {
		$thetheme = $_REQUEST['theme'];
		$theme = wp_get_theme($thetheme);
	} else {$theme = wp_get_theme();}

	// 1.7.1: removed display distinction here
	// else {
	//	// use default display for default theme directory
	//	return $allowed_files;
	// }
	// print_r($theme);

	$extensions = bugbot_get_editable_extensions();

	foreach ($extensions as $extension) {
		$allow_files = $theme->get_files($extension, 10);
		$allowed_files += $allow_files;
	}
	// print_r($allowed_files);

	// if on a file screen, only show files in this directory
	if (isset($_REQUEST['file'])) {
		$file = $_REQUEST['file'];
		$pathinfo = pathinfo($file);
		$directory = $pathinfo['dirname'];

		foreach ($allowed_files as $allowed_file => $fullpath) {
			$pathinfo = pathinfo($allowed_file);
			$thisdir = $pathinfo['dirname'];
			if ($thisdir == $directory) {
				$allowedfiles[$allowed_file] = $fullpath;
			}
		}
		// print_r($allowedfiles);
		return $allowedfiles;
	}

	return $allowed_files;
}

// Plugin Editable Extensions
// --------------------------
add_filter('editable_extensions', 'bugbot_modify_editable_extensions');
function bugbot_modify_editable_extensions($editable_extensions) {

	global $vbugbotdebug, $vbugbot;

	// Note: Default Wordpress Editable Extensions:
	// $veditableextensions = array('php', 'txt', 'text', 'js', 'css', 'html', 'htm', 'xml', 'inc', 'include');
 	// $veditableextensions = implode(',',$editable_extensions);

	// $vbugbot['default_editable_extensions'] = $veditable_extensions;
	// update_option('wp_bugbot',$vbugbot);
	// if ($vbugbotdebug) {echo "<!-- Default Extensions: ".bugbot_get_option('default_editable_extensions')." -->";}

	// Add User Defined Editable Extensions
	// ------------------------------------
	$vaddextensions = bugbot_get_option('add_editable_extensions');
	if ($vaddextensions != '') {
		if (strstr($vaddextensions,',')) {$vextensionstoadd = explode(",",$vaddextensions);}
		else {$vextensionstoadd[0] = $vaddextensions;}

		$vi = count($editable_extensions);
		foreach ($vextensionstoadd as $vanextension) {
			$editable_extensions[$vi] = $vanextension; $vi++;
		}
	}

	// if ($vbugbotdebug == '1') {echo "<!-- Editable Extensions: "; print_r($editable_extensions); echo " -->";}
	return $editable_extensions;

	// Remove Extensions (not implemented)
	// $removeextensions = get_option('remove_editable_extensions');

	// $extensionstoremove = explode(",",$removeextensions);
	// $i = 0;
	// foreach ($extensionstoremove as $anextension) {
	//		if (strstr($anextension,'.')) {anextension = str_replace('.','',$anextension);
	//		$anextension = trim(preg_replace('/[^a-z0-9]/i', '', $anextension));
	//		if ($anextension != '') {$extensionstoremove[$i] = $anextension;}
	//		else {unset($extensionstoremove[$i]);}
	// 	$i++;
	// }

	// if (count($extensionstoremove) > 0) {
	// 	$editable_extensions = array_diff($editable_extensions,$extensionstoremove);
	// }
}

// get Editable Extensions
// -----------------------
function bugbot_get_editable_extensions() {
	global $vbugbotdebug;
	$editable_extensions = array('php', 'txt', 'text', 'js', 'css', 'html', 'htm', 'xml', 'inc', 'include');
	// 1.7.0: just use filter above instead of repeating all this
	// $defaultextensions = bugbot_get_option('default_editable_extensions');
	// if ($vbugbotdebug) {echo "<!-- Default Extensions: ".$defaultextensions." -->";}
	// $extensionsdefault = explode(',',$defaultextensions);
	// $addextensions = bugbot_get_option('add_editable_extensions');
	// if ($addextensions != '') {
	//	if (strstr($addextensions,',')) {$extensionstoadd = explode(",",$addextensions);}
	//	else {$extensionstoadd[0] = $addextensions;}
	//	$editable_extensions = array_merge($extensionsdefault,$extensionstoadd);
	// } else {$editable_extensions = $extensionsdefault;}
	return apply_filters('editable_extensions',$editable_extensions);
}

// get DoNotSearch Extensions
// --------------------------
function bugbot_get_donotsearch_extensions() {
	$donotsearch = bugbot_get_option('donotsearch_extensions');
	$dnsarray = array();
	if ( ($donotsearch) && ($donotsearch != '') ) {
		if (strstr($donotsearch,',')) {$dnsarray = explode(',',$donotsearch);}
		else {$dnsarray[0] = $donotsearch;}
	}
	return $dnsarray;
}

// Perform Actual Keyword Search
// -----------------------------
function bugbot_file_search_for_keyword($code,$keyword,$case)  {
	// 1.6.0: change explode to PHP_EOL from "\n"
	// 1.7.0: no actually "\n" covers different plugin sources
	$codearray = explode("\n",$code);
	$occurences = array(); $i = 0; $j = 0;
	foreach ($codearray as $codeline) {
		if ($case == 'sensitive') {
			if (strstr($codeline,$keyword)) {
				$occurences[$j]['line'] = $i + 1;
				$occurences[$j]['value'] = $codeline;
				$j++;
			}
		}
		if ($case == 'insensitive') {
			if (stristr($codeline,$keyword)) {
				$occurences[$j]['line'] = $i + 1;
				$occurences[$j]['value'] = $codeline;
				$j++;
			}
		}
		$i++;
	}
	return $occurences;
}

// Directory Scanning Function
// ---------------------------
function bugbot_file_search_list_files($dir, $recursive = true, $basedir = '') {
	if ($dir == '') {return array();} else {$results = array(); $subresults = array();}
	if (!is_dir($dir)) {$dir = dirname($dir);} // so a files path can be sent
	if ($basedir == '') {$basedir = realpath($dir).DIRECTORY_SEPARATOR;}

	$files = scandir($dir);
	foreach ($files as $key => $value){
		if ( ($value != '.') && ($value != '..') ) {
			$path = realpath($dir.DIRECTORY_SEPARATOR.$value);
			if (is_dir($path)) { // do not combine with the next line or
				if ($recursive) { // non-recursive file list includes subdirs
					$subdirresults = bugbot_file_search_list_files($path,$recursive,$basedir);
					$results = array_merge($results,$subdirresults);
					unset($subdirresults);
				}
			} else { // strip basedir and add to subarray to separate list
				$subresults[] = str_replace($basedir,'',$path);
			}
		}
	}
	// merge the subarray to give list of files first, then subdirectory files
	if (count($subresults) > 0) {
		$results = array_merge($subresults,$results); unset($subresults);
	}
	return $results;
}


// Quick Textarea File Viewer
// --------------------------
// TODO: improve file viewer interface to allow saving?
// TODO: add scroll to line and search javascript?
function bugbot_file_search_show_file_contents() {
	if (current_user_can('manage_options')) {

		$file = $_REQUEST['showfilecontents'];

		if ( (isset($_REQUEST['corefile'])) && ($_REQUEST['corefile'] == 'yes') ) {
			$searchdir = $_REQUEST['searchdir'];
			// 1.5.0: fixed directory for core search
			$basedir = ABSPATH.$searchdir;
			$filepath = $basedir.$file;
		} elseif ( (isset($_REQUEST['pluginfile'])) && ($_REQUEST['pluginfile'] == 'yes') ) {
			$pluginpath = str_replace(basename($_REQUEST['plugin']),'',$_REQUEST['plugin']);
			$basedir = trailingslashit(WP_PLUGIN_DIR).$pluginpath;
			$filepath = $basedir.$file;
		} elseif ( (isset($_REQUEST['themefile'])) && ($_REQUEST['themefile'] == 'yes') ) {
			$basedir = get_theme_root($_REQUEST['theme']).'/'.$_REQUEST['theme'].'/';
			$filepath = $basedir.$file;
		} else {$filepath = $file;}

		$filepath = str_replace('//','/',$filepath);
		// 1.7.2: fix here to backwards replacement and typo
		if (strstr($filepath,'\\')) {$filepath = str_replace('\\','/',$filepath);}
		if (!file_exists($filepath)) {return;}
		$filedata = file_get_contents($filepath);

		if (strlen($filedata) > 0) {
			echo "<br>".__('Contents of','wp-bugbot')." '".$filepath."' (".__('not editable here','wp-bugbot')."):<br><br>";
			echo "<textarea rows='25' cols='100'>".$filedata."</textarea>";
		} else {echo "<br>".__('Empty File','wp-bugbot').": '".$filepath."'<br><br>";}
	}
}

// HTML Wordwrap Function
// ----------------------
function bugbot_html_wrap(&$str, $maxLength, $char='<br />') {
    $count = 0;
    $newStr = '';
    $openTag = false;
    $lenstr = strlen($str);
    for($i=0; $i<$lenstr; $i++){
        $newStr .= $str{$i};
        if ($str{$i} == '<'){
            $openTag = true;
            continue;
        }
        if (($openTag) && ($str{$i} == '>')) {
            $openTag = false;
            continue;
        }
        if (!$openTag) {
            if ($str{$i} == ' ') {
                if ($count == 0) {
                    $newStr = substr($newStr,0, -1);
                    continue;
                } else {
                    $lastspace = $count + 1;
                }
            }
            $count++;
            if ($count == $maxLength) {
                if ($str{$i+1} != ' ' && $lastspace && ($lastspace < $count)) {
                    $tmp = ($count - $lastspace)* -1;
                    $newStr = substr($newStr,0, $tmp) . $char . substr($newStr,$tmp);
                    $count = $tmp * -1;
                } else {
                    $newStr .= $char;
                    $count = 0;
                }
                $lastspace = 0;
            }
        }
    }
    return $newStr;
}


// Show Plugin/Theme Files Not Listed by Wordpress
// -----------------------------------------------
function bugbot_show_unlisted_files($vtype) {

	if (current_user_can('manage_options')) {

		$editableextensions = bugbot_get_editable_extensions();

		if ($vtype == 'plugin') {
			$plugins = get_plugins();
			$plugin = $_REQUEST['plugin'];

			foreach ($plugins as $plugin_key => $a_plugin) {
				if ($plugin_key == $plugin) {
					$files = get_plugin_files($plugin);
				}
			}
			// echo "<br>"; print_r($files);
			// 1.5.0: Fix to strip the initial plugin directory
			if (count($files) > 0) {
				$vi = 0; $pathinfo = pathinfo($plugin);
				// print_r($pathinfo);
				foreach ($files as $file) {
					$listedfiles[$vi] = str_replace($pathinfo['dirname'].'/','',$file);
					$vi++;
				}
			}
			// echo "<br>"; print_r($listedfiles);
			$pluginpath = dirname(WP_PLUGIN_DIR.'/'.$plugin);
			$pluginfiles = bugbot_file_search_list_files($pluginpath);

			// echo "<br>"; print_r($pluginfiles);
			$unlistedfiles = array_diff($pluginfiles,$listedfiles);
			$files = $pluginfiles; // used again shortly
			// echo "<br>"; print_r($unlistedfiles);
		}

		if ($vtype == 'theme') {

			$themes = wp_get_themes();
			$theme = $_REQUEST['theme'];
			$themeobject = wp_get_theme($theme);

			// 1.7.1: replicate what theme-editor.php does now
			$allowed_files = $style_files = array();
			$default_types = array( 'php', 'css' );
			$file_types = apply_filters( 'wp_theme_editor_filetypes', $default_types, $theme );
			$file_types = array_unique( array_merge( $file_types, $default_types ) );
			$depth = 1;
			if (has_action('load-theme-editor.php', 'bugbot_load_theme_editor')) {$depth = 10;}

			foreach ( $file_types as $type ) {
				switch ( $type ) {
					case 'php':
						$allowed_files += $theme->get_files( 'php', $depth );
						break;
					case 'css':
						$style_files = $theme->get_files( 'css' );
						$allowed_files['style.css'] = $style_files['style.css'];
						$allowed_files += $style_files;
						break;
					default:
						$allowed_files += $theme->get_files( $type );
						break;
				}
			}

			$files = $allowed_files;

			// $php_files = $themeobject->get_files('php', 1);
			// $style_files = $themeobject->get_files('css');
			// $files = array_merge($php_files, $style_files);
			// print_r($files);

			// 1.5.0: get array key values
			if (count($files) > 0) {
				$vi = 0;
				foreach ($files as $file => $fullpath) {
					$listedfiles[$vi] = $file; $vi++;
				}
			}

			foreach ($themes as $atheme) {
				if ($atheme->stylesheet == $theme) {
					$themepath = get_theme_root($theme)."/".$theme."/";
					$themefiles = bugbot_file_search_list_files($themepath);
				}
			}
			$unlistedfiles = array_diff($themefiles,$listedfiles);
			$files = $themefiles; // used again shortly
		}

		$unlistedfound = count($unlistedfiles);

		// 1.6.5: split out files with 'do not search' extensions
		// 1.7.0: use function call to get array
		$dnsarray = bugbot_get_donotsearch_extensions();

		$vi = 0; $checkedfiles = array(); $dnsfiles = array();
		foreach ($unlistedfiles as $file) {
			$strip = false;
			foreach ($dnsarray as $dns) {
			 	$dns = ".".$dns; $len = strlen($dns); $len = -abs($len);
			 	if (substr($file,$len) == $dns) {$dnsfiles[] = $file; $strip = true;}
			}
			if (!$strip) {$checkedfiles[$vi] = $file; $vi++;}
		}

		if ($unlistedfound > 0) {
			echo "<div id='unlistedfilesinterface'>";
			echo "<script language='javascript' type='text/javascript'>";
			echo "function showhideunlistedfiles() {
				if (document.getElementById('unlistedfiles').style.display == 'none') {
					document.getElementById('unlistedfiles').style.display = '';
				} else {document.getElementById('unlistedfiles').style.display = 'none';}
			}</script>";

			echo " ".count($files)." ".$vtype." ".__('files found','wp-bugbot').". ".count($listedfiles)." ";
			echo __('files listed by Wordpress.','wp-bugbot')." ".count($unlistedfiles)." ".__('unlisted files','wp-bugbot').".</b> ";
			echo "<a href='javascript:void(0);' onclick='showhideunlistedfiles();'>".__('Click here to show unlisted files','wp-bugbot')."</a>.<br>";

			echo "<div id='unlistedfiles' style='display:none;'>";
			echo "<table><tr><td style='vertical-align:top;'>";
			if (count($checkedfiles) > 0) {
				echo "<b>".__('Unlisted Files','wp-bugbot').":</b><br>";
				foreach ($checkedfiles as $file) {
					// 1.5.0: fix for local searches
					$file = str_replace('\\','/',$file);
					echo "<a href='".$vtype."-editor.php?showfilecontents=".urlencode($file)."&".$vtype."file=yes";
					if ($vtype == 'plugin') {echo "&plugin=".urlencode($plugin);} // echo "&file=".urlencode($pathinfo['dirname'])."%2F".urlencode($file);
					if ($vtype == 'theme') {echo "&theme=".urlencode($theme);} // echo "&file=".urlencode($theme)."%2F".urlencode($file);
					echo "'>".$file."</a><br>";
				}
			}
			echo "</td><td width='50'></td><td style='vertical-align:top;'>";
			if (count($dnsfiles) > 0) {
				echo "<b>".__('Other Files','wp-bugbot').":</b><br>";
				foreach ($dnsfiles as $file) {
					// 1.5.0: fix for local searches
					$file = str_replace('\\','/',$file);
					echo "<a href='".$vtype."-editor.php?showfilecontents=".urlencode($file)."&".$vtype."file=yes";
					if ($vtype == 'plugin') {echo "&plugin=".urlencode($plugin);} // echo "&file=".urlencode($pathinfo['dirname'])."%2F".urlencode($file);
					if ($vtype == 'theme') {echo "&theme=".urlencode($theme);} // echo "&file=".urlencode($theme)."%2F".urlencode($file);
					echo "'>".$file."</a><br>";
				}
			}
			echo "</td></tr></table></div>";
			echo "</div>";
		}
	}
}


// ---------------------
// === File Searches ===
// ---------------------

// === Plugin File Search ===
// --------------------------

function bugbot_plugin_file_do_search() {

	global $vbugbotslug, $vbugbotversion, $vbugbotdebug, $vissearchpage; $vissearchpage = true;

	// 1.5.0: changed capability from manage_options to edit_plugins
	// 1.7.4: fix for permission check for DISALLOW_FILE_EDIT
	$vdosearch = false;
	if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {
		if (current_user_can('manage_options')) {$vdosearch = true;}
	} elseif (current_user_can('edit_plugins')) {$vdosearch = true;}
	if (!$vdosearch) {return;}

	// set time limit
	bugbot_set_time_limit();

	// get DoNotSearch Extensions
	// 1.7.0: use function call to get array
	$dnsarray = bugbot_get_donotsearch_extensions();

	// get Editable Extensions
	$editableextensions = bugbot_get_editable_extensions();
	if ($vbugbotdebug == '1') {echo "<!-- Editable Extensions: "; print_r($editableextensions); echo " -->";}

	// get Line Snip Length
	$vsniplinesat = bugbot_get_option('snip_long_lines_at');
	if ( (!is_numeric($vsniplinesat)) || ($vsniplinesat < 1) ) {$vsniplinesat = false;}

	// print Wordwrap Styles
	bugbot_wordwrap_styles();

	// print show/hide snipped javascript
	bugbot_snipped_javascript();

	// get Search Request
	$plugins = get_plugins();
	if ( (isset($_REQUEST['searchtype'])) && ($_REQUEST['searchtype'] == 'multiple') ) {$pluginarray = $_REQUEST['multipluginfilesearch'];}
	else {$pluginarray[0] = $_REQUEST['pluginfilesearch'];}
	if (isset($_REQUEST['searchkeyword'])) {$keyword = stripslashes($_REQUEST['searchkeyword']);} else {$keyword = '';}
	if (isset($_REQUEST['searchcase'])) {$searchcase = $_REQUEST['searchcase'];} else {$searchcase = '';}
	if ((count($pluginarray) > 1) && (in_array("ALLPLUGINS",$pluginarray))) {$pluginarray = array(); $pluginarray[0] = "ALLPLUGINS";}
	// if ($vbugbotdebug) {print_r($pluginarray);}

	// save Search Request
	global $vbugbotsearches; $vnewsearches = $vbugbotsearches;
	// 1.7.4: fix to save search logic
	if (bugbot_get_option('save_selected_plugin') == 'yes') {
		$plugincsv = implode(',',$pluginarray); $vnewsearches['plugin_file_search_plugin'] = $plugincsv;
	}
	if (bugbot_get_option('save_last_searched') == 'yes') {$vnewsearches['plugin_file_search_keyword'] = $keyword;}
	if (bugbot_get_option('save_case_sensitive') == 'yes') {$vnewsearches['plugin_file_search_case'] = $searchcase;}
	if ($vnewsearches != $vbugbotsearches) {update_option('wp_bugbot_searches', $vnewsearches); $vbugbotsearches = $vnewsearches;}

	// Get Plugins Editable Files
	// $editable_plugin_files = array();
	// foreach ($plugins as $plugin_key=>$a_plugin) {
	//	if ($plugin == "ALLPLUGINS") {
	// 		$these_plugin_files = get_plugin_files($plugin_key);
	//		$editable_plugin_files = array_merge($these_plugin_files,$editable_plugin_files);
	//	}
	//	elseif ($plugin_key == $plugin) {
	//		$plugin_name = $a_plugin['Name'];
	//		$editable_plugin_files = get_plugin_files($plugin);
	//	}
	// }

	$vi = 0;
	$activeplugins = get_option('active_plugins');
	foreach ($plugins as $plugin_key => $value) {
		if (in_array($plugin_key,$activeplugins)) {$activeplugindata[$plugin_key] = $value;}
		else {$inactiveplugindata[$plugin_key] = $value;}
		$allplugins[$vi] = $plugin_key; $vi++; // 1.7.0: counter fix
	}

	// 1.6.0: fix to pluginarray variable typos
	// 1.7.0: fix to plugin loop counters
	$vi = 0; $searchtype = '';
	if ($pluginarray[0] == "ALLPLUGINS") {
		// $pluginpath = WP_PLUGIN_DIR."/";
		// $plugin_files = bugbot_file_search_list_files($pluginpath);
		$pluginarray = $allplugins;
		$searchtype = "ALLPLUGINS";
	} elseif ($pluginarray[0] == "ACTIVEPLUGINS") {
		foreach ($activeplugindata as $key => $value) {$pluginarray[$vi] = $key; $vi++;}
		$searchtype = "ACTIVEPLUGINS";
	} elseif ($pluginarray[0] == "INACTIVEPLUGINS") {
		foreach ($inactiveplugindata as $key => $value) {$pluginarray[$vi] = $key; $vi++;}
		$searchtype = "INACTIVEPLUGINS";
	} elseif ($pluginarray[0] == "UPDATEPLUGINS") {
		$pluginupdates = get_site_transient('update_plugins'); $vj = 0;
		foreach ($pluginupdates->response as $pluginupdate => $values) {$updatelist[$vj] = $pluginupdate; $vj++;}
		foreach ($updatelist as $update) {$pluginarray[$vi] = $update; $vi++;}
		$searchtype = "UPDATEPLUGINS";
	}

	$plugin_files = array(); $multiple = false;
	if (count($pluginarray) > 0) {
		if (count($pluginarray) > 1) {$multiple = true;}
		foreach ($pluginarray as $plugin) {
			$pluginchunks = explode('/',$plugin);
			$plugindir = $pluginchunks[0];
			$pluginpath = WP_PLUGIN_DIR.'/'.$plugindir.'/';
			$these_plugin_files = bugbot_file_search_list_files($pluginpath);
			$plugin_files = array_merge($these_plugin_files,$plugin_files);
		}
	}

	// get Plugin Names for display
	$plugin_name = '';
	foreach ($plugins as $plugin_key => $a_plugin) {
		foreach ($pluginarray as $plugin) {
			if ($plugin_key == $plugin) {
				if ($plugin_name == '') {$plugin_name = $a_plugin['Name'];}
				else {$plugin_name .= ", ".$a_plugin['Name'];}
			}
		}
	}

	// if ($vbugbotdebug) {print_r($pluginarray); echo "***".$searchtype."***";}

	echo '<div class="wrap" id="pagewrap" style="margin-right:0px !important;">';

	// Admin Notices Boxer
	if (function_exists('wqhelper_admin_notice_boxer')) {wqhelper_admin_notice_boxer();}

	// Search Interface Header
	// -----------------------
	global $vbugbotversion;
	$viconurl = plugins_url('images/wp-bugbot.png',__FILE__);
	echo "<center><table><tr><td><img src='".$viconurl."' width='96' height='96'></td><td width='32'></td>";
	echo "<td align='center'><h2>WP BugBot <i>v".$vbugbotversion."</i><br><br>".__('Plugin File Search','wp-bugbot')." </h2>";
	echo "</td></tr></table></center>";

	// Plugin Search Interface
	// -----------------------
	echo "<br><center>".bugbot_plugin_file_search_ui()."</center><br><br>";

	if ($keyword == '') {echo __('Error: No Keyword Specified!','bioship'); return;}

	// Search Message Header
	// ---------------------
	echo "<center><p><div id='search_message_box' style='display:inline-block;'>";
	echo "<table style='background-color: lightYellow; border-style:solid; border-width:1px; border-color: #E6DB55; text-align:center;'>";
	echo "<tr><td><div class='message' style='margin:0.5em;'><font style='font-size:10.5pt; font-weight:bold;'>";
	if ($searchtype == "ALLPLUGINS") {
		echo __('Searched','wp-bugbot')." ".count($plugin_files)." ".__('files from ALL Plugins for','wp-bugbot')." ";
	} elseif ($searchtype == "ACTIVEPLUGINS") {
		echo __('Searched','wp-bugbot')." ".count($plugin_files)." ".__('files from Active Plugins for','wp-bugbot')." ";
	} elseif ($searchtype == "INACTIVEPLUGINS") {
		echo __('Searched','wp-bugbot')." ".count($plugin_files)." ".__('files from Inactive Plugins for','wp-bugbot')." ";
	} elseif ($searchtype == "UPDATEPLUGINS") {
		echo __('Searched','wp-bugbot')." ".count($plugin_files)." ".__('files from Update Available Plugins for','wp-bugbot')." ";
	} else {
		if ($multiple) {echo __('Searched','wp-bugbot')." ".count($plugin_files)." ".__('files of the plugins','wp-bugbot').": '".$plugin_name."' ".__('for','wp-bugbot')." ";}
		else {echo __('Searched','wp-bugbot')." ".count($plugin_files)." ".__('files of the','wp-bugbot')." '".$plugin_name."' ".__('plugin for','wp-bugbot')." ";}
	}
	echo "<code>".htmlspecialchars($keyword)."</code>";
	if ($searchcase == 'sensitive') {echo " (".__('case sensitive','wp-bugbot').")...";}
	else {$searchcase = 'insensitive'; echo " (".__('case insensitive','wp-bugbot').")...";}
	echo "</font></div></td></tr></table></div></p></center>";

	// print_r($plugin_files);
	// print_r($editable_plugin_files);

	// Load Search Sidebar
	// ----------------
	bugbot_search_sidebar();

	// Search Results
	// --------------
	echo "<div id='filesearchresults' style='width:100%;'>";

	$vsnipcount = 0;
	foreach ($pluginarray as $plugin) {

		$pluginchunks = explode('/',$plugin);
		$plugindir = $pluginchunks[0];
		$pluginpath = WP_PLUGIN_DIR."/".$plugindir."/";
		$plugin_files = bugbot_file_search_list_files($pluginpath);

		if (count($plugin_files) > 0) {
			$vfound = false;
			if (count($pluginarray) > 1) {
				foreach ($plugins as $plugin_key=>$a_plugin) {
					if ($plugin_key == $plugin) {$plugin_name = $a_plugin['Name'];}
				}
				echo "<center><h3>".$plugin_name."</h3></center>";
			}

			$i = 0; // strip do not search extensions
			if (count($dnsarray) > 0) {
				foreach ($plugin_files as $plugin_file) {
					foreach ($dnsarray as $dns) {
						$dns = ".".$dns; $len = strlen($dns); $len = -abs($len);
						if (substr($plugin_file,$len) == $dns) {unset($plugin_files[$i]);}
					}
					$i++;
				}
			}

			foreach ($plugin_files as $plugin_file) {

				if ($plugin == "ALLPLUGINS") {$urlfile = $plugin_file;} else {$urlfile = $plugindir."/".$plugin_file;}
				$real_file = $pluginpath.$plugin_file;

				// read file
				$fh = fopen($real_file,'r'); $filecontents = stream_get_contents($fh); fclose($fh);

				// check extension
				$pathinfo = pathinfo($real_file);
				if (isset($pathinfo['extension'])) {$extension = $pathinfo['extension'];} else {$extension = '';}

				// Loop Occurrences
				$occurences = array();
				$occurences = bugbot_file_search_for_keyword($filecontents,$keyword,$searchcase);
				if (count($occurences) > 0) {

					$vfound = true;

					// Line Header
					echo '<br><div style="display:inline-block;width:40px;text-align:center;float:left;">Line</div>';

					// File Header
					echo '<div style="display:inline-block;text-align:center;min-width:600px;"><b>File: ';

					if ($vbugbotdebug) {echo "<!-- *".$extension."*"; print_r($editableextensions); echo " -->";}

					// 1.7.1: allow for file viewing if editor is disabled
					if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {
						echo '<a href="update-core.php?showfilecontents='.urlencode($urlfile).'&pluginfile=yes" style="text-decoration:none;">';
						echo $plugin_file.'</a></b>';
						echo "<br>(".__('Link to file view only - file editing is disabled.','wp-bugbot').")";
					} elseif (in_array($extension,$editableextensions)) {
						echo '<a href="plugin-editor.php?file='.urlencode($urlfile).'&searchkeyword='.urlencode($keyword).'" style="text-decoration:none;">';
						echo $plugin_file.'</a></b>';
					} else {
						echo '<a href="plugin-editor.php?showfilecontents='.urlencode($urlfile).'&pluginfile=yes" style="text-decoration:none;">';
						echo $plugin_file.'</a></b>';
						echo "<br>(".__('Plugin file not editable - change your editable extensions from the sidebar.','wp-bugbot').")";
					}
					echo '</div>';

					foreach ($occurences as $occurence) {

						// Snip Long Lines
						if ($vsniplinesat) {
							if (strlen($occurence['value']) > $vsniplinesat) {
								$vthisoccurence = $occurence['value'];
								$occurence['value'] = substr($occurence['value'],0,$vsniplinesat);
								$vsnipped = " <i><a href='javascript:void(0);' onclick='showsnipped(\"".$vsnipcount."\");'>[...".__('more','wp-bugbot')."...]</a></i>";
								$vsnipped .= "<div id='snip".$vsnipcount."' style='display:none;'><code>".substr($vthisoccurence,$vsniplinesat)."</code>";
								$vsnipped .= "<a href='javascript:void(0);' onclick='hidesnipped(\"".$vsnipcount."\");'>[...".__('less','wp-bugbot')."...]</a></div>";
								$vsnipcount++;
							} else {$vsnipped = "";}
						}

						echo "<div style='text-align:left;vertical-align:top;'>";

						echo "<table><tr><td align='center' style='vertical-align:top;width:40px;min-width:40px;'>";
						// 1.7.1: allow for file viewing if editor is disabled
						if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {
							echo '<a href="update-core.php?showfilecontents='.urlencode($urlfile).'&pluginfile=yes&scrolltoline='.$occurence['line'].'">'.$occurence['line'].'</a>';
						} elseif (in_array($extension,$editableextensions)) {
							if ($plugin == "ALLPLUGINS") {echo '<a href="plugin-editor.php?file='.urlencode($urlfile).'&plugin='.urlencode($plugin).'&searchkeyword='.urlencode($keyword).'&scrolltoline='.$occurence['line'].'">'.$occurence['line'].'</a>: ';}
							else {echo '<a href="plugin-editor.php?file='.urlencode($urlfile).'&searchkeyword='.urlencode($keyword).'&scrolltoline='.$occurence['line'].'">'.$occurence['line'].'</a>: ';}
						} else {echo '<a href="plugin-editor.php?showfilecontents='.urlencode($urlfile).'&pluginfile=yes&scrolltoline='.$occurence['line'].'">'.$occurence['line'].'</a>';}
						echo "</td>";

						// Output Code Block
						echo "<td class='wordwrapcell'><span style='background-color:#eeeeee;'><code>";

						$block = $occurence['value'];
						$firstblock = '';

						if (stristr($block,$keyword)) {
							$position = stripos($block,$keyword);
							if ($position > 0) {
								$chunks = str_split($block,$position);
								$firstblock = $chunks[0];
								unset($chunks[0]);
								$remainder = implode('',$chunks);
							}
							else {$remainder = $occurence['value'];}

							$chunks = str_split($remainder,strlen($keyword));
							$kdisplay = $chunks[0];
							unset($chunks[0]);
							$lastblock = implode('',$chunks);

							$block = htmlspecialchars($firstblock);
							$block .= "<span style='background-color:#F0F077'>";
							$block .= htmlspecialchars($kdisplay);
							$block .= "</span>";
							$block .= htmlspecialchars($lastblock);
						}
						else {$block = htmlspecialchars($occurence['value']);}

						$displayblock = bugbot_html_wrap($block,80,'<br>');
						echo $displayblock;

						echo "</code>".$vsnipped."</span></td></tr></table>";

						echo "</div>";
					}
				}
			}
			if (!$vfound) {echo __('No results found for this plugin.','wp-bugbot');}
		}
	}
	echo "</div>";

	echo "</div>";

	exit;
}


// === Plugin Editor Search Form ===
// ---------------------------------

function bugbot_plugin_file_search_ui() {

	global $vbugbotdebug, $vbugbotslug, $vissearchpage, $vbugbotsearches;

	// Get Plugins
	// -----------
	$plugins = get_plugins();
	if (isset($_REQUEST['file'])) {$plugin = stripslashes($_REQUEST['file']);}
	if (empty($plugin)) {$plugin = array_keys($plugins); $plugin = $plugin[0];}
	$plugin_files = get_plugin_files($plugin);

	$activeplugins = get_option('active_plugins');
	foreach ($plugins as $plugin_key => $value) {
		if (in_array($plugin_key,$activeplugins)) {$activeplugindata[$plugin_key] = $value;}
		else {$inactiveplugindata[$plugin_key] = $value;}
	}

	$pluginupdates = get_site_transient('update_plugins'); $vi = 0;
	if (is_array($pluginupdates->response)) {
		foreach ($pluginupdates->response as $pluginupdate => $values) {
			$updatelist[$vi] = $pluginupdate; $vi++;
		}
	} else {$updatelist = array();}

	// $real_file = WP_PLUGIN_DIR.'/'.$file;
	// $scrollto = isset($_REQUEST['scrollto']) ? (int) $_REQUEST['scrollto'] : 0;

	// Get Last Search Values
	$lastsearchedkeyword = ''; $lastsearchedplugins[0] = ''; $lastsearchedcase = '';
	if (isset($vbugbotsearches['plugin_file_search_keyword'])) {$lastsearchedkeyword = $vbugbotsearches['plugin_file_search_keyword'];}
	if (isset($vbugbotsearches['plugin_file_search_keyword'])) {$lastsearchedplugins = explode(',',$vbugbotsearches['plugin_file_search_plugin']);}
	if (isset($vbugbotsearches['plugin_file_search_case'])) {$lastsearchedcase = $vbugbotsearches['plugin_file_search_case'];}

	// Multisearch Javascript
	echo "<script language='javascript' type='text/javascript'>
	function showmultisearch() {
		document.getElementById('searchtype').value = 'multiple';
		document.getElementById('singlesearch').style.display = 'none';
		document.getElementById('showsinglesearch').style.display = '';
		document.getElementById('multisearch').style.display = '';
		document.getElementById('showmultisearch').style.display = 'none';
		document.getElementById('multisup').style.display = '';
	}
	function showsinglesearch() {
		document.getElementById('searchtype').value = 'single';
		document.getElementById('multisearch').style.display = 'none';
		document.getElementById('showmultisearch').style.display = '';
		document.getElementById('singlesearch').style.display = '';
		document.getElementById('showsinglesearch').style.display = 'none';
		document.getElementById('multisup').style.display = 'none';
	}
	</script>";

	// Plugin Search Bar
	// -----------------
	echo '<div id="pluginfilesearchbar" style="display:inline;width:100%;margin-bottom:10px;" class="alignright">';
	// 1.7.1: different action if editing is not allowed
	$formaction = 'plugin-editor.php';
	if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {$formaction = 'update-core.php';}
	echo '<form action="'.$formaction.'" method="post" onSubmit="return checkpluginkeyword();">';

	// 1.5.0: fixed for repeat multiple search submissions
	if (count($lastsearchedplugins) > 1) {$searchtype = 'multiple';} else {$searchtype = 'single';}
	echo '<input type="hidden" name="searchtype" id="searchtype" value="'.$searchtype.'">';
	echo '<table><tr>';
	$viconurl = plugins_url('images/wp-bugbot.png',__FILE__);
	if (!$vissearchpage) {
		echo '<td width="48" style="vertical-align:top;"><img src="'.$viconurl.'" width="48" height="48"></td>';
	}
	echo '<td width="220" style="vertical-align:top;">';
	echo '<strong><label for="keyword" style="font-size:10.5pt;"><a href="http://wordquest.org/plugins/wp-bugbot/" style="text-decoration:none;" target=_blank>WP BugBot ';
	// 1.7.4: change label prefix if using multiple search page
	if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {echo __('Plugin','wp-bugbot');}
	else {echo __('Keyword','wp-bugbot');}
	echo ' '.__('Search','wp-bugbot').'</a>:</label></strong> ';

	echo '<div style="float:right;font-size:8pt;"><input type="checkbox" name="searchcase" value="sensitive"';
	if ($lastsearchedcase == 'sensitive') {echo ' checked';}
	echo '> '.__('Match Case','wp-bugbot').'?</div><br>';

	// 1.7.4: use separate keyword element ID for javascript check (pluginkeyword)
	if ((strstr($lastsearchedkeyword,"'")) && (strstr($lastsearchedkeyword,'"'))) {
		// $lastsearchedkeyword = str_replace('"','\"',$lastsearchedkeyword);
		$lastsearchedkeyword = htmlspecialchars($lastsearchedkeyword,ENT_QUOTES);
		echo '<input type="text" id="pluginkeyword" name="searchkeyword" value="'.$lastsearchedkeyword.'" size="35"></td>';
	}
	elseif (strstr($lastsearchedkeyword,"'")) {
		echo '<input type="text" id="pluginkeyword" name="searchkeyword" value="'.$lastsearchedkeyword.'" size="35"></td>';
	}
	else {
		echo "<input type='text' id='pluginkeyword' name='searchkeyword' value='".$lastsearchedkeyword."' size='35'></td>";
	}
	echo '</td><td width="20"></td>';
	echo '<td width="120" style="vertical-align:top;"><strong><label for="pluginfilesearch" style="float:right;">'.__('Plugin','wp-bugbot').'<span id="multisup" style="display:none;">s</span> '.__('to Search','wp-bugbot').':</label></strong><br>';
	echo '<div id="showsinglesearch" style="text-align:right;';
	if (count($lastsearchedplugins) < 2) {echo 'display:none;';}
	echo '"><a href="javascript:void(0);" onclick="showsinglesearch();" style="text-decoration:none;">'.__('Single','wp-bugbot').' '.__('Search','wp-bugbot').'</a><br><br><font style="font-size:8pt;">('.__('Hold Ctrl and click to','wp-bugbot').'<br>'.__('select multiple plugins','wp-bugbot').'.)</font></div>';
	echo '<div id="showmultisearch" style="text-align:right;';
	if (count($lastsearchedplugins) > 1) {echo 'display:none;';}
	echo '"><a href="javascript:void(0);" onclick="showmultisearch();" style="text-decoration:none;">'.__('Multi','wp-bugbot').' '.__('Search','wp-bugbot').'</a></div>';
	echo '</td><td width="10"></td>';
	echo '<td style="vertical-align:top;"><div id="singlesearch"';
	if (count($lastsearchedplugins) > 1) {echo ' style="display:none;"';}
	echo '><select style="font-size:11pt;" name="pluginfilesearch" id="pluginfilesearch">';
	echo '<optgroup label="Select Plugins">';
	echo '<option value="ALLPLUGINS" style="font-size:12pt;"';
	if ($lastsearchedplugins[0] == "ALLPLUGINS") {echo " selected='selected'";}
	echo '>'.__('All','wp-bugbot').' '.__('Plugins','wp-bugbot').'</option>';
	if (count($activeplugindata) > 0) {
		echo '<option value="ACTIVEPLUGINS" style="font-size:12pt;"';
		if ($lastsearchedplugins[0] == "ACTIVEPLUGINS") {echo " selected='selected'";}
		echo '>'.__('Active','wp-bugbot').' '.__('Plugins','wp-bugbot').'</option>';
	}
	if (count($inactiveplugindata) > 0) {
		echo '<option value="INACTIVEPLUGINS"';
		if ($lastsearchedplugins[0] == "INACTIVEPLUGINS") {echo " selected='selected'";}
		echo '>'.__('Inactive','wp-bugbot').' '.__('Plugins','wp-bugbot').'</option>';
	}
	if (count($inactiveplugindata) > 0) {
		echo '<option value="UPDATEPLUGINS"';
		if ($lastsearchedplugins[0] == "INACTIVEPLUGINS") {echo " selected='selected'";}
		echo '>* '.__('Update Available','wp-bugbot').'</option>';
	}
	echo '</optgroup>';

	// active plugins
	if (count($activeplugindata) > 0) {
		echo "<optgroup label='".__('Active','wp-bugbot')." ".__('Plugins','wp-bugbot')."'>";
		foreach ($activeplugindata as $plugin_key=>$a_plugin) {
			$update = ''; if (in_array($plugin_key,$updatelist)) {$update = '* ';}
			$plugin_name = $a_plugin['Name'];
			if (strlen($plugin_name) > 40) {$plugin_name = substr($plugin_name,0,40)."...";}
			if ($plugin_key == $lastsearchedplugins[0]) {$selected = " selected='selected'";}
			else {$selected = '';}
			$plugin_name = esc_attr($plugin_name);
			$plugin_key = esc_attr($plugin_key);
			echo "\n\t<option value=\"".$plugin_key."\" ".$selected." class=\"activeplugin\" style=\"text-shadow: 0px 0px 0px black;font-weight:bolder\">";
			echo $update.$plugin_name."</option>";
		}
		echo "</optgroup>";
	}
	if (count($inactiveplugindata) > 0) {
		echo "<optgroup label='".__('Inactive','wp-bugbot')." ".__('Plugins','wp-bugbot')."'>";
		foreach ($inactiveplugindata as $plugin_key=>$a_plugin) {
			$update = ''; if (in_array($plugin_key,$updatelist)) {$update = '* ';}
			$plugin_name = $a_plugin['Name'];
			if (strlen($plugin_name) > 40) {$plugin_name = substr($plugin_name,0,40)."...";}
			if ($plugin_key == $lastsearchedplugins[0]) {$selected = " selected='selected'";}
			else {$selected = '';}
			$plugin_name = esc_attr($plugin_name);
			$plugin_key = esc_attr($plugin_key);
			echo "\n\t<option value=\"$plugin_key\" ".$selected." class=\"inactiveplugin\" style=\"font-size:10pt;\">";
			echo $update.$plugin_name."</option>";
		}
		echo "</optgroup>";
	}

	echo '</select></div>';
	echo '<div id="multisearch"';
	if (count($lastsearchedplugins) < 2) {echo ' style="display:none;"';}
	echo '><select multiple size="10" style="font-size:11pt;" name="multipluginfilesearch[]" id="multipluginfilesearch">';
	// if (count($activeplugindata) > 0) {
	//	echo '<option value="ACTIVEPLUGINS"';
	//	if (in_array('ACTIVEPLUGINS',$lastsearchedplugins)) {echo " selected='selected'";}
	//	echo '>ACTIVE PLUGINS</option>';
	// }
	// if (count($inactiveplugindata) > 0) {
	// 	echo '<option value="INACTIVEPLUGINS"';
	//	if (in_array('INACTIVEPLUGINS',$lastsearchedplugins)) {echo " selected='selected'";}
	//	echo '>INACTIVE PLUGINS</option>';
	// }
	if (count($activeplugindata) > 0) {
		echo "<optgroup label='".__('Active','wp-bugbot')." ".__('Plugins','wp-bugbot')."'>";
		foreach ($activeplugindata as $plugin_key => $a_plugin) {
			$update = ''; if (in_array($plugin_key,$updatelist)) {$update = '* ';}
			$plugin_name = $a_plugin['Name'];
			if (strlen($plugin_name) > 40) {$plugin_name = substr($plugin_name,0,40)."...";}
			if (in_array($plugin_key,$lastsearchedplugins)) {$selected = " selected='selected'";}
			else {$selected = '';}
			$plugin_name = esc_attr($plugin_name);
			$plugin_key = esc_attr($plugin_key);
			echo "\n\t<option value=\"".$plugin_key."\" ".$selected." style=\"text-shadow: 0px 0px 0px black;\">";
			echo $update.$plugin_name."</option>";
		}
		echo "</optgroup>";
	}
	if (count($inactiveplugindata) > 0) {
		echo "<optgroup label='".__('Inactive','wp-bugbot')." ".__('Plugins','wp-bugbot')."'>";
		foreach ($inactiveplugindata as $plugin_key => $a_plugin) {
			$update = ''; if (in_array($plugin_key,$updatelist)) {$update = '* ';}
			$plugin_name = $a_plugin['Name'];
			if (strlen($plugin_name) > 40) {$plugin_name = substr($plugin_name,0,40)."...";}
			if (in_array($plugin_key,$lastsearchedplugins)) {$selected = " selected='selected'";}
			else {$selected = '';}
			$plugin_name = esc_attr($plugin_name);
			$plugin_key = esc_attr($plugin_key);
			echo "\n\t<option value=\"".$plugin_key."\" ".$selected." style=\"font-size:10pt;\">".$update.$plugin_name."</option>";
		}
		echo "</optgroup>";
	}
	echo '</select></td>';
	echo '<td width="10"></td><td style="vertical-align:top;">';
	echo '<input type="submit" class="button-primary" value="'.__('Search','wp-bugbot').'">';
	echo '</td></tr></table></form></div>';

	// 1.7.4: use separate keyword element ID for javascript check
	echo "<script language='javascript' type='text/javascript'>
	function checkpluginkeyword() {
		if (document.getElementById('pluginkeyword').value == '') {
			alert('".__('Please enter a keyword to search plugin(s) for.','wp-bugbot')."'); return false;
		}
	}
	</script>";

	// 1.5.0: moved unlisted files call here
	if ( (isset($_REQUEST['plugin'])) && ($_REQUEST['plugin'] != '') ) {bugbot_show_unlisted_files('plugin');}

}


// === Theme File Search ===
// -------------------------

function bugbot_theme_file_do_search() {

	global $vbugbotslug, $vbugbotdebug, $vbugbotversion, $vissearchpage; $vissearchpage = true;

	// 1.5.0: changed capability from manage_options to edit_themes
	// 1.7.4: fix for permission check for DISALLOW_FILE_EDIT
	$vdosearch = false;
	if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {
		if (current_user_can('manage_options')) {$vdosearch = true;}
	} elseif (current_user_can('edit_themes')) {$vdosearch = true;}
	if (!$vdosearch) {return;}

	// set time limit
	bugbot_set_time_limit();

	// get DoNotSearch Extensions
	// 1.7.0: use function call to get array
	$dnsarray = bugbot_get_donotsearch_extensions();

	// get Editable Extensions
	$editableextensions = bugbot_get_editable_extensions();
	if ($vbugbotdebug == '1') {echo "<!-- Editable Extensions: "; print_r($editableextensions); echo " -->";}

	// get Line Snip Length
	$vsniplinesat = bugbot_get_option('snip_long_lines_at');
	if ( (!is_numeric($vsniplinesat)) || ($vsniplinesat < 1) ) {$vsniplinesat = false;}

	// print Wordwrap Styles
	bugbot_wordwrap_styles();

	// print show/hide snipped javascript
	bugbot_snipped_javascript();

	// get Search Request
	$themes = wp_get_themes();
	$themesearch = $_REQUEST['themefilesearch'];
	$theme = wp_get_theme($themesearch);

	if (isset($_REQUEST['searchkeyword'])) {$keyword = stripslashes($_REQUEST['searchkeyword']);} else {$keyword = '';}
	if (isset($_REQUEST['searchcase'])) {$searchcase = $_REQUEST['searchcase'];} else {$searchcase = '';}

	if ($vbugbotdebug == '1') {
		echo "<!-- Theme Search: ".$themesearch." - Keyword: ".$keyword." - Case: ";
		if ($searchcase == 'sensitive') {echo $searchcase;} else {echo "insensitive";}
		echo " --><!-- Theme Info Dump: "; print_r($theme);	echo " -->";
	}

	// save Search Request
	global $vbugbotsearches; $vnewsearches = $vbugbotsearches;
	// 1.7.4: fix to search save logic
	if (bugbot_get_option('save_selected_theme') == 'yes') {$vnewsearches['theme_file_search_theme'] = $themesearch;}
	if (bugbot_get_option('save_last_searched') == 'yes') {$vnewsearches['theme_file_search_keyword'] = $keyword;}
	if (bugbot_get_option('save_case_sensitive') == 'yes') {$vnewsearches['theme_file_search_case'] = $searchcase;}
	if ($vnewsearches != $vbugbotsearches) {update_option('wp_bugbot_searches', $vnewsearches); $vbugbotsearches = $vnewsearches;}

	// Get Themes Editable Files
	// ---------------------
	// $theme_files = array();
	// foreach ($themes as $a_theme) {
	//	if ($theme == "ALLTHEMES") {
	// 		$these_theme_files = get_theme_files($theme->stylesheet);
	//		$editable_theme_files = array_merge($these_theme_files,$editable_theme_files);
	//	}
	//	elseif ($theme->Name == $a_theme->Name) {
	//		$theme_name = $a_theme['Name'];
	//		$editable_theme_files = get_theme_files($theme->stylesheet);
	//	}
	// }

	// Get All the Themes Files
	if ($themesearch == "ALLTHEMES") {
		$themepath = get_theme_root()."/";
		$theme_files = bugbot_file_search_list_files($themepath);
	}
	else {
		$themedir = $theme->stylesheet;
		$themepath = get_theme_root($themedir)."/".$themedir."/";
		$theme_files = bugbot_file_search_list_files($themepath);
	}

	// print_r($theme_files);
	// echo $themepath;
	// echo "<br>";
	// echo $themedir;

	$i = 0; // strip do not search extensions
	foreach ($theme_files as $theme_file) {
		foreach ($dnsarray as $dns) {
			$dns = ".".$dns; $len = strlen($dns); $len = -abs($len);
			if (substr($theme_file,$len) == $dns) {unset($theme_files[$i]);}
		}
		$i++;
	}

	echo '<div class="wrap" id="pagewrap" style="margin-right:0px !important;">';

	// Admin Notices Boxer
	if (function_exists('wqhelper_admin_notice_boxer')) {wqhelper_admin_notice_boxer();}

	// Search Interface Header
	// -----------------------
	$viconurl = plugins_url('images/wp-bugbot.png',__FILE__);
	echo "<center><table><tr><td><img src='".$viconurl."' width='96' height='96'></td><td width='32'></td>";
	echo '<td align="center"><h2>WP BugBot <i>v'.$vbugbotversion.'</i><br><br>'.__('Theme File Search','wp-bugbot').' </h2></td>';
	echo '</tr></table></center>';

	// Theme Search Interface
	// ----------------------
	echo "<br><center>".bugbot_theme_file_search_ui()."</center><br><br>";

	if ($keyword == '') {echo __('Error: No Keyword Specified!','wp-bugbot'); return;}

	// Search Result Header
	// --------------------
	echo "<center><p><div id='search_message_box' style='display:inline-block;'>";
	echo "<table style='background-color: lightYellow; border-style:solid; border-width:1px; border-color: #E6DB55; text-align:center;'>";
	echo "<tr><td><div class='message' style='margin:0.5em;'><font style='font-size:10.5pt; font-weight:bold;'>";
	if ($themesearch == "ALLTHEMES") {
		echo __('Searched','wp-bugbot')." ".count($theme_files)." ".__('files from ALL Themes for','wp-bugbot')." ";
	} else {
		echo __('Searched','wp-bugbot')." ".count($theme_files)." ".__('files of the','wp-bugbot')." '".$theme->Name."' ".__('theme for','wp-bugbot')." ";
	}
	echo "<code>".htmlspecialchars($keyword)."</code>";
	if ($searchcase == 'sensitive') {echo " (".__('case sensitive','wp-bugbot').")...";}
	else {$searchcase = 'insensitive'; echo " (".__('case insensitive','wp-bugbot').")...";}
	echo "</font></div></td></tr></table></div></p></center>";

	// Load Search Sidebar
	// -------------------
	bugbot_search_sidebar();

	// print_r($theme_files);
	$themeref = "";

	$vsnipcount = 0;
	echo "<div id='filesearchresults' style='width:100%;'>";
	foreach ($theme_files as $theme_file) {

		$vfound = false;

		if ($themesearch == "ALLTHEMES") {
			$oldthemeref = $themeref;
			$pos = stripos($theme_file,"/");
			if ($pos > 0) {
				$chunks = str_split($theme_file,$pos);
				$themeref = $chunks[0];
				unset($chunks[0]);
				$theme_file = implode("",$chunks);
			}
			$slashcheck = substr($theme_file,0,1);
			if ($slashcheck == "/") {$theme_file = substr($theme_file,1,(strlen($theme_file)-1));}
			// $pos = stripos($theme_file,"/");
			// if ($pos == '0') {$theme_file = substr($theme_file,1,(strlen($theme_file)-1));}
			$real_file = $themepath.$themeref."/".$theme_file;
			if ($oldthemeref != $themeref) {echo "<br><h3>".$themeref."</h3>";}
		} else {$real_file = $themepath.$theme_file;}

		if ($vbugbotdebug) {echo "<!-- ".$themeref." - ".$real_file." - ".$themepath." - ".$theme_file." -->";}

		// check extension
		$pathinfo = pathinfo($real_file);
		if (isset($pathinfo['extension'])) {$extension = $pathinfo['extension'];} else {$extension = '';}

		// Read File
		$fh = fopen($real_file,'r'); $filecontents = stream_get_contents($fh); fclose($fh);

		// Loop Occurrences
		$occurences = array();
		$occurences = bugbot_file_search_for_keyword($filecontents,$keyword,$searchcase);
		if (count($occurences) > 0) {

			$vfound = true;

			// Line Header
			echo '<br><div style="display:inline-block;width:40px;text-align:center;float:left;">Line</div>';

			// File Header
			echo '<div style="display:inline-block;text-align:center;min-width:600px;">';

			echo '<b>File: ';

			// 1.7.1: allow for file viewing if editor is disabled
			if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {
				echo '<a href="update-core.php?showfilecontents='.urlencode($theme_file).'&themefile=yes" style="text-decoration:none;">';
				echo $theme_file.'</a></b>';
				echo "<br>(".__('Link to file view only - file editing is disabled.','wp-bugbot').")";
			} elseif (in_array($extension,$editableextensions)) {
				if ($themesearch != "ALLTHEMES") {echo '<a href="theme-editor.php?file='.urlencode($theme_file).'&theme='.$theme->stylesheet.'&searchkeyword='.urlencode($keyword).'"  style="text-decoration:none;">';}
				else {echo '<a href="theme-editor.php?file='.urlencode($theme_file).'&theme='.$themeref.'&searchkeyword='.urlencode($keyword).'"  style="text-decoration:none;">';}
				echo $theme_file.'</a></b>';
			} else {
				echo '<a href="theme-editor.php?showfilecontents='.urlencode($theme_file).'&themefile=yes" style="text-decoration:none;">';
				echo $theme_file.'</a></b>';
				echo "<br>(".__('').")";
			}


			if (!in_array($extension,$editableextensions)) {echo "<br>(".__('Theme file not editable - try changing your editable extensions from the sidebar.','wp-bugbot').")";}
			echo '</div>';

			foreach ($occurences as $occurence) {

				// Snip Long Lines
				// ---------------
				if ($vsniplinesat) {
					if (strlen($occurence['value']) > $vsniplinesat) {
						$vthisoccurence = $occurence['value'];
						$occurence['value'] = substr($occurence['value'],0,$vsniplinesat);
						$vsnipped = " <i><a href='javascript:void(0);' onclick='showsnipped(\"".$vsnipcount."\");'>[...".__('more','wp-bugbot')."...]</a></i>";
						$vsnipped .= "<div id='snip".$vsnipcount."' style='display:none;'><code>".substr($vthisoccurence,$vsniplinesat)."</code>";
						$vsnipped .= "<a href='javascript:void(0);' onclick='hidesnipped(\"".$vsnipcount."\");'>[...".__('less','wp-bugbot')."...]</a></div>";
						$vsnipcount++;
					} else {$vsnipped = "";}
				}

				echo "<div style='text-align:left;vertical-align:top;'>";

				// Occurence Line Link
				echo "<table><tr><td style='text-align:center;vertical-align:top;width:40px;min-width:40px;'>";
				// 1.7.1: allow for file viewing if editor is disabled
				if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {
					echo '<a href="update-core.php?showfilecontents='.urlencode($theme_file).'&themefile=yes&scrolltoline='.$occurence['line'].'">'.$occurence['line'].'</a>';
				} elseif (in_array($extension,$editableextensions)) {
					if ($themesearch != "ALLTHEMES") {echo '<a href="theme-editor.php?file='.urlencode($theme_file).'&theme='.$theme->stylesheet.'&searchkeyword='.urlencode($keyword).'&scrolltoline='.$occurence['line'].'">'.$occurence['line'].'</a>: ';}
					else {echo '<a href="theme-editor.php?file='.urlencode($theme_file).'&theme='.$themeref.'&searchkeyword='.urlencode($keyword).'&scrolltoline='.$occurence['line'].'">'.$occurence['line'].'</a>: ';}
				} else {echo '<a href="theme-editor.php?showfilecontents='.urlencode($theme_file).'&themefile=yes&scrolltoline='.$occurence['line'].'">';}
				echo "</td>";

				// Output Code Block
				echo "<td class='wordwrapcell'><span style='background-color:#eeeeee;'><code>";
				if (stristr($occurence['value'],$keyword)) {
					$position = stripos($occurence['value'],$keyword);
					if ($position > 0) {
						$chunks = str_split($occurence['value'],$position);
						$firstblock = $chunks[0];
						unset($chunks[0]);
						$remainder = implode('',$chunks);
					}
					else {$remainder = $occurence['value'];}
					$chunks = str_split($remainder,strlen($keyword));
					$kdisplay = $chunks[0];
					unset($chunks[0]);
					$lastblock = implode('',$chunks);

					$block = htmlspecialchars($firstblock);
					$block .= "<span style='background-color:#F0F077'>";
					$block .= htmlspecialchars($kdisplay);
					$block .= "</span>";
					$block .= htmlspecialchars($lastblock);
				}
				else {$block= htmlspecialchars($occurence['value']);}
				if ((!strstr($block,' ')) && (strlen($block) > 80)) {
					$chunks = str_split($block,80);
					$block = implode('<br>',$chunks);
				}
				echo $block;
				echo "</code>".$vsnipped."</span></td></tr></table>";

				echo "</div>";
			}
		}
		if (!$vfound) {echo __('No results found for this theme.','wp-bugbot');}
	}
	echo "</div>";

	echo "</div>";
	exit;
}


// === Theme Editor Search Form ===
// --------------------------------

function bugbot_theme_file_search_ui() {

	global $vbugbotdebug, $vbugbotslug, $wp_version, $vissearchpage, $vbugbotsearches;

	// get themes
	if (isset($_REQUEST['themefilesearch'])) {$themesearch = $_REQUEST['themefilesearch'];} else {$themesearch = '';}
	$themes = wp_get_themes();

	// get current theme
	if (version_compare($wp_version,'3.4','<')) { //'
		$vtheme = get_theme_data(get_stylesheet_directory().'/style.css');
	} else {$vtheme = wp_get_theme();}

	if ($vbugbotdebug == '1') {
		// echo "<!-- Theme Dump: "; print_r($themes); " -->";
		// foreach ($themes as $theme) {
			// $theme_dir = $theme->theme_root."/".$theme->stylesheet;
			// echo $theme_dir."<br>";
		// }
	}

	// Get Last Searched Values
	// ------------------------
	$lastsearchedkeyword = ''; $lastsearchedtheme = ''; $lastsearchedcase = '';
	if (isset($vbugbotsearches['theme_file_search_keyword'])) {$lastsearchedkeyword = $vbugbotsearches['theme_file_search_keyword'];}
	if (isset($vbugbotsearches['theme_file_search_theme'])) {$lastsearchedtheme = $vbugbotsearches['theme_file_search_theme'];}
	if ($lastsearchedtheme == '') {
		// fallback to currently active theme
		$currenttheme = wp_get_theme(); $lastsearchedtheme = $currenttheme->stylesheet;
	}
	if (isset($vbugbotsearches['theme_file_search_case'])) {$lastsearchedcase = $vbugbotsearches['theme_file_search_case'];}

	// Theme Search Bar
	// ----------------
	echo '<div id="themefilesearchbar" style="display:inline;width:100%;margin-bottom:10px;" class="alignright">';
	// 1.7.1: different action if editing is not allowed
	$formaction = 'theme-editor.php';
	if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {$formaction = 'update-core.php';}
	echo '<form action="'.$formaction.'" method="post" onSubmit="return checkthemekeyword();">';

	echo '<table><tr>';
	$viconurl = plugins_url('images/wp-bugbot.png',__FILE__);
	if (!$vissearchpage) {echo '<td width="48"><img src="'.$viconurl.'" width="48" height="48"></td>';}
	echo '<td><strong><label for="keyword"><a href="http://wordquest.org/plugins/wp-bugbot/" style="text-decoration:none;" target=_blank>WP BugBot ';
	// 1.7.4: change label prefix if using multiple search page
	if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {echo __('Theme','wp-bugbot');}
	else {echo __('Keyword','wp-bugbot');}
	echo ' '.__('Search','wp-bugbot').'</a>:</label></strong></td>';

	// 1.7.4: use separate keyword element ID for javascript check (themekeyword)
	if ((strstr($lastsearchedkeyword,"'")) && (strstr($lastsearchedkeyword,'"'))) {
		// $lastsearchedkeyword = str_replace('"','\"',$lastsearchedkeyword);
		$lastsearchedkeyword = htmlspecialchars($lastsearchedkeyword,ENT_QUOTES);
		echo '<td><input type="text" id="themekeyword" name="searchkeyword" value="'.$lastsearchedkeyword.'" style="width:200px;" size="35"></td>';
	} elseif (strstr($lastsearchedkeyword,"'")) {
		echo '<td><input type="text" id="themekeyword" name="searchkeyword" value="'.$lastsearchedkeyword.'" style="width:200px;" size="35"></td>';
	} else {
		echo "<td><input type='text' id='themekeyword' name='searchkeyword' value='".$lastsearchedkeyword."' style='width:200px;' size='35'></td>";
	}
	echo '<td width=10></td>';
	echo '<td><input type="checkbox" name="searchcase" value="sensitive"';
	if ($lastsearchedcase == 'sensitive') {echo ' checked';}
	echo '> <font style="font-size:7pt;">'.__('Match Case','wp-bugbot').'?</font></td>';
	echo '<td width=10></td>';
	echo '<td><strong><label for="themefilesearch">'.__('Theme','wp-bugbot').' '.__('to Search','wp-bugbot').':</label></strong></td>';
	echo '<td><select style="font-size:11pt;" name="themefilesearch" id="themefilesearch">';
	if (count($themes) > 0) {
		echo '<option value="ALLTHEMES"';
		if ($lastsearchedtheme == 'ALLTHEMES') {echo " selected='selected'";}
		echo '>'.__('ALL THEMES','wp-bugbot').'</option>';
	}

	// Active Theme(s)
	$theme = $vtheme;
	$theme_name = $theme['Name'];
	if (strlen($theme_name) > 40) {$theme_name = substr($theme_name,0,40)."...";}
	$theme_template = $stylesheet = $theme['Stylesheet'];
	if ($theme_template == $lastsearchedtheme) {$selected = " selected='selected'";}
	else {$selected = '';}
	$theme_name = esc_attr($theme_name);
	echo '<optgroup label="'.__('Active Theme','wp-bugbot').'">';
	echo "\n\t<option value=\"$theme_template\" ".$selected." style=\"font-size:12pt;text-shadow: 0px 0px 0px black; font-weight:bolder\">".$theme_name."</option>";
	// 1.6.0: mark parent theme for child
	if (is_child_theme()) {
		$parenttheme = $theme['Template'];
		foreach ($themes as $theme) {if ($theme->Stylesheet == $parenttheme) {$parentthemename = $theme->Name;} }
		if ($parenttheme == $lastsearchedtheme) {$selected = " selected='selected'";}
		echo "\n\t<option value=\"$parenttheme\" ".$selected." style=\"font-size:12pt;text-shadow: 0px 0px 0px black; font-weight:bolder\">".$parentthemename." (".__('Parent','wp-bugbot').")</option>";
	}
	echo '</optgroup>';

	// Inactive Themes
	if (count($themes) > 0) {
		echo '<optgroup label="'.__('Inactive Themes','wp-bugbot').'">';
		foreach ($themes as $theme) {
			$theme_name = $theme->Name;
			if (strlen($theme_name) > 40) {$theme_name = substr($theme_name,0,40)."...";}
			$theme_template = $theme->stylesheet;
			if ( ($theme_template != $stylesheet) && ($theme_template != $parenttheme) ) {
				if ($theme_template == $lastsearchedtheme) {$selected = " selected='selected'";}
				else {$selected = '';}
				$theme_name = esc_attr($theme_name);
				echo "\n\t<option value=\"$theme_template\" ".$selected." style=\"font-size:10pt;\">".$theme_name."</option>";
			}
		}
		echo '</optgroup>';
	}
	echo '</select></td>';
	echo '<td width=10></td><td>';
	echo '<input type="submit" class="button-primary" value="'.__('Search','wp-bugbot').'">';
	echo '</td></tr></table></form></div>';

	// 1.7.4: use separate keyword element ID for javascript check
	echo "<script language='javascript' type='text/javascript'>
	function checkthemekeyword() {
		if (document.getElementById('themekeyword').value == '') {
			alert('".__('Please enter a keyword to search theme(s) for.','wp-bugbot')."'); return false;
		}
	}
	</script>";

	// 1.5.0: moved unlisted files call here
	if ( (isset($_REQUEST['theme'])) && ($_REQUEST['theme'] != '') ) {
		bugbot_show_unlisted_files('theme');
	}
}


// === Core File Search ===
// ------------------------

function bugbot_core_file_do_search() {

	global $vbugbotdebug, $vbugbotslug, $vbugbotversion, $vissearchpage; $vissearchpage = true;

	if (!current_user_can('manage_options')) {return;}

	// set time limit
	bugbot_set_time_limit();

	// get DoNotSearch Extensions
	// 1.7.0: use function call to get array
	$dnsarray = bugbot_get_donotsearch_extensions();

	// get Editable Extensions
	$editableextensions = bugbot_get_editable_extensions();
	if ($vbugbotdebug == '1') {echo "<!-- Editable Extensions: "; print_r($editableextensions); echo " -->";}

	// get Line Snip Length
	$vsniplinesat = bugbot_get_option('snip_long_lines_at');
	if ( (!is_numeric($vsniplinesat)) || ($vsniplinesat < 1) ) {$vsniplinesat = false;}

	// print Wordwrap Styles
	bugbot_wordwrap_styles();

	// print show/hide snipped javascript
	bugbot_snipped_javascript();

	// get Search Request
	if (isset($_REQUEST['corefilesearch'])) {$coresearch = $_REQUEST['corefilesearch'];} else {$coresearch = 'ALLCORE';}
	if (isset($_REQUEST['searchkeyword'])) {$keyword = stripslashes($_REQUEST['searchkeyword']);} else {$keyword = '';}
	if (isset($_REQUEST['searchcase'])) {$searchcase = $_REQUEST['searchcase'];} else {$searchcase = '';}

	// Debug Output
	if ($vbugbotdebug == '1') {
		echo "<!-- Core Search: ".$coresearch." - Keyword: ".$keyword." - Case: ";
		if ($searchcase == 'sensitive') {echo $searchcase;} else {echo "insensitive";}
		echo " -->";
	}

	// save Search Request
	global $vbugbotsearches; $vnewsearches = $vbugbotsearches;
	// 1.7.4: fix to search save logic
	if (bugbot_get_option('save_selected_dir') == 'yes') {$vnewsearches['core_file_search_dir'] = $coresearch;}
	if (bugbot_get_option('save_last_searched') == 'yes') {$vnewsearches['core_file_search_keyword'] = $keyword;}
	if (bugbot_get_option('save_case_sensitive') == 'yes') {$vnewsearches['core_file_search_case'] = $searchcase;}
	if ($vnewsearches != $vbugbotsearches) {update_option('wp_bugbot_searches', $vnewsearches); $vbugbotsearches = $vnewsearches;}

	// Get the Core Files
	// ------------------
	if ($coresearch == "ALLCORE") {
		$coredir[0] = ABSPATH;
		$coredir[1] = ABSPATH.'/wp-admin/';
		$coredir[2] = ABSPATH.'/wp-includes/';
		$corefiles[0] = bugbot_file_search_list_files($coredir[0],false);
		$corefiles[1] = bugbot_file_search_list_files($coredir[1]);
		$corefiles[2] = bugbot_file_search_list_files($coredir[2]);
		$core_files = array_merge($corefiles[0],$corefiles[1]);
		$core_files = array_merge($core_files,$corefiles[2]);
	} elseif ($coresearch == 'root') {
		$coredir = ABSPATH; // non-recursive case
		$core_files = bugbot_file_search_list_files($coredir,false);
	} elseif ($coresearch == 'wp-admin') {
		$coredir = ABSPATH.'/wp-admin/'; // recursive case
		$core_files = bugbot_file_search_list_files($coredir,false);
	} elseif ($coresearch == 'wp-includes') {
		$coredir = ABSPATH.'/wp-includes/'; // recursive case
		$core_files = bugbot_file_search_list_files($coredir,false);
	} elseif ($coresearch == '/wp-admin/') {
		$coredir = ABSPATH.'/wp-admin/'; // non-recursive case
		$core_files = bugbot_file_search_list_files($coredir,false);
	} elseif ($coresearch == '/wp-includes/') {
		$coredir = ABSPATH.'/wp-includes/'; // non-recursive case
		$core_files = bugbot_file_search_list_files($coredir,false);
	} else {
		$coredir = ABSPATH.$coredir; // recursive search
		$core_files = bugbot_file_search_list_files($coredir);
	}

	if ($vbugbotdebug == '1') {echo "<!-- Core File List Dump: "; print_r($core_files); echo " -->";}

	$i = 0; // strip do not search extensions
	foreach ($core_files as $core_file) {
		foreach ($dnsarray as $dns) {
			$dns = ".".$dns; $len = strlen($dns); $len = -abs($len);
			if (substr($core_file,$len) == $dns) {unset($core_files[$i]);}
		}
		$i++;
	}

	echo '<div class="wrap" id="pagewrap" style="margin-right:0px !important;">';

	// Admin Notices Boxer
	if (function_exists('wqhelper_admin_notice_boxer')) {wqhelper_admin_notice_boxer();}

	// Search Interface Header
	// -----------------------
	$viconurl = plugins_url('images/wp-bugbot.png',__FILE__);
	echo "<center><table><tr><td><img src='".$viconurl."' width='96' height='96'></td><td width='32'></td>";
	echo '<td align="center"><h2>WP BugBot <i>v'.$vbugbotversion.'</i><br><br>'.__('Wordpress Core File Search','wp-bugbot').'</h2></td>';
	echo '</tr></table></center>';

	// Core Search Interface
	// ---------------------
	echo "<br><center>".bugbot_core_file_search_ui()."<br><br>";

	if ($keyword == '') {echo __('Error: No Keyword Specified!','wp-bugbot'); return;}

	// Search Result Header
	// --------------------
	echo "<center><p><div id='search_message_box'>";
	echo "<table style='background-color: lightYellow; border-style:solid; border-width:1px; border-color: #E6DB55; text-align:center;'>";
	echo "<tr><td><div class='message' style='margin:0.5em;'><font style='font-size:10.5pt; font-weight:bold;'>";
	if ($coresearch == "ALLCORE") {
		echo __('Searched','wp-bugbot')." ".count($core_files)." ".__('files from ALL Wordpress Core files for','wp-bugbot')." ";
		echo "<code>".htmlspecialchars($keyword)."</code>";
	} elseif ( ($coresearch == '/wp-admin/') || ($coresearch == '/wp-includes/') || ($coresearch == '/') ) {
		echo __('Searched','wp-bugbot')." ".count($core_files)." ".__('files from','wp-bugbot')." '".$coresearch." ".__('path for','wp-bugbot')." ";
		echo "<code>".htmlspecialchars($keyword)."</code>";
	} else {
		echo __('Searched','wp-bugbot')." ".count($core_files)." ".__('files of the Wordpress path','wp-bugbot')." '".$coresearch."' ".__('path and subdirectories for','wp-bugbot')." ";
		echo "<code>".htmlspecialchars($keyword)."</code>";
	}
	if ($searchcase == 'sensitive') {echo " (".__('case sensitive','wp-bugbot').")...";}
	else {$searchcase = 'insensitive'; echo " (".__('case insensitive','wp-bugbot').")...";}
	echo "</font></div></td></tr></table></div></p></center>";

	// Load Search Sidebar
	// -------------------
	bugbot_search_sidebar();

	// Search Results
	// --------------
	echo "<div id='filesearchresults' style='width:100%;'>";
	$vsnipcount = 0; $coreref = "";
	foreach ($core_files as $core_file) {

		$vfound = false;

		$real_file = $coredir.$core_file;

		// 1.5.0: fix for file path for all core search
		if ($coresearch == "ALLCORE") {
			if (in_array($core_file,$corefiles[0])) {
				$real_file = $coredir[0].$core_file;
				$searchdir = urlencode('/');
			}
			if (in_array($core_file,$corefiles[1])) {
				$real_file = $coredir[1].$core_file;
				$searchdir = urlencode('/wp-admin/');
			}
			if (in_array($core_file,$corefiles[2])) {
				$real_file = $coredir[2].$core_file;
				$searchdir = urlencode('/wp-includes/');
			}
		} else {
			$searchdir = urlencode($coresearch);
			$real_file = $coredir.$core_file;
		}

		$real_file = str_replace('//','/',$real_file);
		if (strstr($real_file,'\\')) {$real_file = str_replace('/','\\',$real_file);}

		if ($vbugbotdebug == '1') {echo "<!-- ".$coredir." --- ".$real_file." --- ".$core_file." -->";}

		// Read File
		$fh = fopen($real_file,'r'); $filecontents = stream_get_contents($fh); fclose($fh);

		// Loop Occurrences
		$occurences = array();
		$occurences = bugbot_file_search_for_keyword($filecontents,$keyword,$searchcase);
		if (count($occurences) > 0) {

			$vfound = true;

			// Line Header
			echo '<br><div style="display:inline-block;width:40px;text-align:center;float:left;">Line</div>';

			// File Header
			echo '<div style="display:inline-block;text-align:center;min-width:600px;"><b>File: ';
			echo '<a href="update-core.php?showfilecontents='.urlencode($core_file).'&corefile=yes&searchdir='.$searchdir.'&searchkeyword='.urlencode($keyword).'"  style="text-decoration:none;">';
			echo $core_file.'</a></b></div>';

			foreach ($occurences as $occurence) {

				// Snip Long Lines
				if ($vsniplinesat) {
					if (strlen($occurence['value']) > $vsniplinesat) {
						$vthisoccurence = $occurence['value'];
						$occurence['value'] = substr($occurence['value'],0,$vsniplinesat);
						$vsnipped = " <i><a href='javascript:void(0);' onclick='showsnipped(\"".$vsnipcount."\");'>[...".__('more','wp-bugbot')."...]</a></i>";
						$vsnipped .= "<div id='snip".$vsnipcount."' style='display:none;'><code>".substr($vthisoccurence,$vsniplinesat)."</code>";
						$vsnipped .= "<a href='javascript:void(0);' onclick='hidesnipped(\"".$vsnipcount."\");'>[...".__('less','wp-bugbot')."...]</a></div>";
						$vsnipcount++;
					} else {$vsnipped = "";}
				}

				echo "<div style='text-align:left;vertical-align:top;'>";

				echo "<table><tr><td style='text-align:center;vertical-align:top;width:40px;min-width:40px;'>";
				if ($coresearch != "ALLCORE") {echo '<a href="update-core.php?showfilecontents='.urlencode($core_file).'&corefile=yes&searchkeyword='.urlencode($keyword).'&searchdir='.$searchdir.'&scrolltoline='.$occurence['line'].'">'.$occurence['line'].'</a>: ';}
				else {echo '<a href="update-core.php?showfilecontents='.urlencode($core_file).'&corefile=yes&searchkeyword='.urlencode($keyword).'&searchdir='.$searchdir.'&scrolltoline='.$occurence['line'].'">'.$occurence['line'].'</a>: ';}
				echo "</td>";

				// Output Code Block
				echo "<td class='wordwrapcell'><span style='background-color:#eeeeee;'><code>";
				if (stristr($occurence['value'],$keyword)) {
					$position = stripos($occurence['value'],$keyword);
					if ($position > 0) {
						$chunks = str_split($occurence['value'],$position);
						$firstblock = $chunks[0];
						unset($chunks[0]);
						$remainder = implode('',$chunks);
					}
					else {$remainder = $occurence['value'];}
					$chunks = str_split($remainder,strlen($keyword));
					$kdisplay = $chunks[0];
					unset($chunks[0]);
					$lastblock = implode('',$chunks);

					$block = htmlspecialchars($firstblock);
					$block .= "<span style='background-color:#F0F077'>";
					$block .= htmlspecialchars($kdisplay);
					$block .= "</span>";
					$block .= htmlspecialchars($lastblock);
				}
				else {$block= htmlspecialchars($occurence['value']);}
				if ((!strstr($block,' ')) && (strlen($block) > 80)) {
					$chunks = str_split($block,80);
					$block = implode('<br>',$chunks);
				}
				echo $block;
				echo "</code>".$vsnipped."</span></td></tr></table>";

				echo "</div>";
			}
		}
		if (!$vfound) {echo __('No results found for this directory.','wp-bugbot');}
	}
	echo "</div>";

	echo "</div>";
	exit;
}


// === Wordpress Core Search Form ===
// ----------------------------------

function bugbot_core_file_search_ui() {

	global $vissearchpage, $vbugbotsearches;
	clearstatcache();

	// 1.7.0: fix to undefine variable warning
	$coresearch = '';
	if (isset($_REQUEST['corefilesearch'])) {$coresearch = $_REQUEST['corefilesearch'];}
	$coredirs = array('root', 'wp-admin', 'wp-includes');

	// 1.7.0: replace static dir list with scan for actual directories
	$admindirs[0] = '/wp-admin/';
	$vi = 1; $admindirfiles = scandir(ABSPATH.'/wp-admin');
	foreach ($admindirfiles as $dirfile) {
		if ( ($dirfile != '.') && ($dirfile != '..') ) {
			if (is_dir(ABSPATH.'/wp-admin/'.$dirfile)) {$admindirs[$vi] = '/wp-admin/'.$dirfile; $vi++;}
		}
	}
	// echo "<!-- Admin Dirs: "; print_r($admindirs); echo " -->";

	$includesdirs[0] = '/wp-includes/';
	$vi = 1; $includesdirfiles = scandir(ABSPATH.'/wp-includes');
	foreach ($includesdirfiles as $dirfile) {
		if ( ($dirfile != '.') && ($dirfile != '..') ) {
			if (is_dir(ABSPATH.'/wp-includes/'.$dirfile)) {$includesdirs[$vi] = '/wp-includes/'.$dirfile; $vi++;}
		}
	}
	// echo "<!-- Includes Dirs: "; print_r($includesdirs); echo " -->";

	$lastsearchedkeyword = ''; $lastsearcheddir = ''; $lastsearchedcase = '';
	if (isset($vbugbotsearches['core_file_search_keyword'])) {$lastsearchedkeyword = $vbugbotsearches['core_file_search_keyword'];}
	if (isset($vbugbotsearches['core_file_search_dir'])) {$lastsearcheddir = $vbugbotsearches['core_file_search_dir'];}
	if (isset($vbugbotsearches['core_file_search_case'])) {$lastsearchedcase = $vbugbotsearches['core_file_search_case'];}

	echo '<div id="corefilesearchbar" style="display:inline;width:100%;margin-bottom:10px;" class="alignright"><form action="update-core.php" method="post" onSubmit="return checkcorekeyword();">';

	echo '<table><tr>';
	$viconurl = plugins_url('images/wp-bugbot.png',__FILE__);
	if (!$vissearchpage) {echo '<td width="48"><img src="'.$viconurl.'" width="48" height="48"></td>';}
	echo '<td><strong><label for="keyword">';
	echo '<a href="http://wordquest.org/plugins/wp-bugbot/" style="text-decoration:none;" target=_blank>WP BugBot ';
	// 1.7.4: change label prefix if using multiple search page
	if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {echo __('Core','wp-bugbot');}
	else {echo __('Keyword','wp-bugbot');}
	echo ' '.__('Search','wp-bugbot').'</a>:</label></strong></td>';

	// 1.7.4: use separate keyword element ID for javascript check (corekeyword)
	if ((strstr($lastsearchedkeyword,"'")) && (strstr($lastsearchedkeyword,'"'))) {
		// $lastsearchedkeyword = str_replace('"','\"',$lastsearchedkeyword);
		$lastsearchedkeyword = htmlspecialchars($lastsearchedkeyword,ENT_QUOTES);
		echo '<td><input type="text" id="corekeyword" name="searchkeyword" value="'.$lastsearchedkeyword.'" size="35" style="width:150px;"></td>';
	} elseif (strstr($lastsearchedkeyword,"'")) {
		echo '<td><input type="text" id="corekeyword" name="searchkeyword" value="'.$lastsearchedkeyword.'" size="35" style="width:150px;"></td>';
	} else {
		echo "<td><input type='text' id='corekeyword' name='searchkeyword' value='".$lastsearchedkeyword."' size='35' style='width:150px;'></td>";
	}
	echo '<td width="10"></td>';
	echo '<td><input type="checkbox" name="searchcase" value="sensitive"';
	if ($lastsearchedcase == 'sensitive') {echo ' checked';}
	echo '> <font style="font-size:7pt;">'.__('Match Case','wp-bugbot').'?</font></td>';
	echo '<td width="10"></td>';
	echo '<td><strong><label for="corefilesearch">'.__('Paths','wp-bugbot').' '.__('to Search','wp-bugbot').':</label></strong></td>';
	echo '<td><select name="corefilesearch" id="corefilesearch">';
	echo '<optgroup label="'.__('Wordpress Core','wp-bugbot').'">';
	echo '<option value="ALLCORE"';
	if ($lastsearcheddir == "ALLCORE") {echo " selected='selected'";}
	echo '>'.__('All Core Directories','wp-bugbot').'</option>';
	// core directories
	foreach ($coredirs as $coredir) {
		if ($lastsearcheddir == $coredir) {$selected = " selected='selected'";} else {$selected = '';}
		echo "\n\t<option value=\"".$coredir."\" ".$selected.">";
		if ($coredir == 'root') {echo '/ ('.__('root only','wp-bugbot').')';}
		if ($coredir == 'wp-admin') {echo '/wp-admin/ ('.__('and subdirs','wp-bugbot').')';}
		if ($coredir == 'wp-includes') {echo '/wp-includes/ ('.__('and subdirs','wp-bugbot').')';}
		echo "</option>";
	}
	echo '</optgroup>';
	// admin subdirectories
	echo '<optgroup label="'.__('Admin Subdirectories','wp-bugbot').'">';
	foreach ($admindirs as $admindir) {
		if ($lastsearcheddir == $admindir) {$selected = " selected='selected'";} else {$selected = '';}
		echo "\n\t<option value=\"".$admindir."\" ".$selected.">".$admindir;
		// 1.7.0: fix to variable typo
		if ($admindir == '/wp-admin/') {echo ' ('.__('and subdirs','wp-bugbot').')';}
		echo "</option>";
	}
	echo '</optgroup>';
	// includes subdirectories
	echo '<optgroup label="'.__('Includes Subdirectories','wp-bugbot').'">';
	foreach ($includesdirs as $includesdir) {
		if ($lastsearcheddir == $includesdir) {$selected = " selected='selected'";} else {$selected = '';}
		echo "\n\t<option value=\"".$includesdir."\" ".$selected.">".$includesdir;
		// 1.7.0: fix to variable typo
		if ($includesdir == '/wp-includes/') {echo ' ('.__('and subdirs','wp-bugbot').')';}
		echo "</option>";
	}
	echo '</optgroup>';
	echo '</select></td>';
	echo '<td width=10></td><td>';
	echo '<input type="submit" class="button-primary" value="'.__('Search','wp-bugbot').'">';
	echo '</td></tr></table></form></div>';

	// 1.7.4: use separate keyword element ID for javascript check
	echo "<script language='javascript' type='text/javascript'>
	function checkcorekeyword() {
		if (document.getElementById('corekeyword').value == '') {
			alert('".__('Please enter a keyword to search core for.','wp-bugbot')."'); return false;
		}
	}
	</script>";
}


// ---------------
// === Scripts ===
// ---------------

// Output Search Sidebar
// ---------------------
function bugbot_search_sidebar() {
	global $vbugbotslug;
	// $vargs = array('bugbot','wp-bugbot','free','wp-bugbot','replace','WP BugBot',$vbugbotversion);
	$vargs = array($vbugbotslug,'replace'); // (trimmed arguments)
	if (function_exists('wqhelper_sidebar_floatbox')) {
		wqhelper_sidebar_floatbox($vargs);
		echo wqhelper_sidebar_stickykitscript();
		echo "<style>#floatdiv {float:right;}</style>";
		// initialize stickykit and fix widths
		echo '<script>jQuery("#floatdiv").stick_in_parent();
		jQuery(document).ready(function() {
			wrapwidth = jQuery("#pagewrap").width();
			sidebarwidth = jQuery("#floatdiv").width();
			newwidth = wrapwidth - sidebarwidth;
			jQuery("#filesearchresults").css("width",newwidth+"px");
		});</script>';
	}
}

// Show / Hide Snipped Javascript
// ------------------------------
function bugbot_snipped_javascript() {
	echo "<script language='javascript' type='text/javascript'>
	function showsnipped(snipid) {var snipdiv = 'snip'+snipid; document.getElementById(snipdiv).style.display = '';}
	function hidesnipped(snipid) {var snipdiv = 'snip'+snipid; document.getElementById(snipdiv).style.display = 'none';}
	</script>";
}

// ------------------------
// Editor Search Javascript
// ------------------------

function bugbot_editor_output_javascript() {

	// Insert Directory Selection Div
	// ------------------------------
	// 1.5.0: added this to insert directory selector

	echo '
		if (document.getElementById("dirselectdiv")) {
			var dirselect_insBeforeMe = document.getElementById("templateside");
			var dirselect_insParent = dirselect_insBeforeMe.parentNode;
			var dirselect_insMe = document.getElementById("dirselectdiv");
			dirselect_insParent.insertBefore(dirselect_insMe, dirselect_insBeforeMe);
		}
	';

	// Credits: The following javascript is from...
	// WordPress Editor Search Plugin ?2005 :: Javascript Code
	// Thanks Andrew Buckman - Awesome Javascript coding!
	// http://www.theoneandtheonly.com/wordpress-editor-search/

	// Firefox: tests for setSelectionRange
	// IE: tests for createTextRange
	// ? other browsers ?

?>
	var ajbES_insBeforeMe = document.getElementById("templateside");
	var ajbES_insParent = ajbES_insBeforeMe.parentNode;
	var ajbES_insMe = document.getElementById("ajbESdiv");
	ajbES_insParent.insertBefore(ajbES_insMe, ajbES_insBeforeMe);

	function ajbES_Search() {
	  var a = document.getElementById("ajbES_SearchInput");
	  var b = document.getElementById("newcontent");
	  if (b.setSelectionRange) {
		ajbES_Search_Firefox(a, b);
	  } else if (b.createTextRange) {
		ajbES_Search_IE(a, b);
	  }
	}

	function ajbES_Search_IE(a, b) {
	  b.focus();
	  // Internet Explorer
	  var rc = document.selection.createRange().duplicate();
	  var r = document.selection.createRange(); // b.createTextRange()
	  r.setEndPoint("StartToEnd", rc);
	  foundPos = r.findText(a.value);
	  if (!foundPos) {
		// Not found, wrap-around
		r = b.createTextRange();
		foundPos = r.findText(a.value);
	  }
	  if (foundPos) {
		r.expand("word");
		r.select();
	  } else {
		alert('No match found!');
	  }
	}

	function ajbES_Search_Firefox(a, b) {
	  // grab cursor position for starting point of search
	  var cursorPos = 0;
	  if (b.selectionStart!=b.value.length) cursorPos = b.selectionStart + 1;

	  // find text

	  var foundPos;
	  foundPos = b.value.indexOf(a.value, cursorPos);
	  if ((foundPos < 0) && (cursorPos > 0)) {
		// text not found, try looping to the start and looking again
		foundPos = b.value.indexOf(a.value);
	  }

	  if (foundPos < 0) {
		alert('No match found!');
	  } else {
		// select textarea (b) and move cursor to foundPos
		b.focus();
		b.selectionStart = foundPos;
		b.selectionEnd = foundPos + a.value.length;
		ajbES_scrollToCursor();
	  }
	}

	function ajbES_FindLineNumber() {
	  var a = document.getElementById("ajbES_LineNumInput");
	  if (a.value) {
		a = a.value - 1;
		var b = document.getElementById("newcontent");
		var lines = b.value.split('\n');
		if (a<lines.length && a>=0) {
		  var i;
		  var cursorPos=0;
		  for (i=0; i<a; i++) {
			cursorPos += lines[i].length;
		  }
		  b.focus();
		  if (b.setSelectionRange) {
			// Firefox
			cursorPos += a; // Adjust to account for newline chars
			b.selectionStart = cursorPos;
			b.selectionEnd = cursorPos;
			ajbES_scrollToCursor();
		  } else if (b.createTextRange) {
			// Internet Explorer
			var r = b.createTextRange();
			r.move("character", cursorPos);
			r.select();
			// IE does not appear to need scrolling function
		  }
		} else if (a>=lines.length) {
		  alert('Sorry there are only ' + lines.length + ' lines in the textarea.');
		} else {
		  alert('Please enter a valid line number to go to.');
		}
		if (lines[a].length==0) alert('Warning, your selected line is blank, the cursor will show up at end of previous line.');
	  }
	}

	function ajbES_scrollToCursor() {
	  // scrolls the textarea so the cursor / highlight are onscreen
	  var b = document.getElementById("newcontent");

	  var cursorPos  = b.selectionStart;
	  var linesTotal = b.value.split('\n').length;
	  var linesAbove = b.value.slice(0, cursorPos).split('\n').length - 1;
	  if (linesAbove<(b.rows/2)) linesAbove = 0;
	  var scrollTo = (linesAbove / (linesTotal-b.rows)) * (b.scrollHeight - b.clientHeight);
	  b.scrollTop = scrollTo;
	}

   function ajbES_clearbox(a) {
	  // When user clicks in an input field, clear the other one
	  if (a=='search') {
		document.getElementById('ajbES_LineNumInput').value='';
	  } else if (a=='linenum') {
		document.getElementById('ajbES_SearchInput').value='';
	  }
   }

<?php
	// Delayed Jump to Line Number
	if ( (isset($_REQUEST['scrolltoline'])) && ($_REQUEST['scrolltoline'] != "") ) {
		echo "window.onload = setTimeout(ajbES_FindLineNumber(),1000);";
		echo "window.onload = setTimeout(ajbES_scrollToCursor(),3000);";
	}

	// ----------------------------------
	// Scroll to Line Integration Support
	// ----------------------------------

	// Advanced Code Editor
	// --------------------
	if (class_exists('advanced_code_editor')) {
		echo "
			function ace_scrolltoline() {
				var line = '".$scrolltoline."';
				line--;
				editor.setCursor(Number(line),0);
				editor.setSelection({line:Number(line),ch:0},{line:Number(line)+1,ch:0});
				editor.focus();
			}
			window.onload = setTimeout(ace_scrolltoline(),2000);
		";
	}

}

function bugbot_editor_insert_code() {

	// Editor Search Buttons
	// ---------------------
	// 1.7.0: fix to undefined index warnings
	$vscrolltoline = ''; if (isset($_REQUEST['scrolltoline'])) {$vscrolltoline = $_REQUEST['scrolltoline'];}
	$vsearchkeyword = ''; if (isset($_REQUEST['searchkeyword'])) {$vsearchkeyword = $_REQUEST['searchkeyword'];}
	echo '
	<div id="ajbESdiv" style="display: inline;">
		<div style="display:inline">
			<form style="display:inline" action="#" onsubmit="ajbES_FindLineNumber(); return false;">
				<input id="ajbES_LineNumInput" type="text" value="'.$vscrolltoline.'" style="width: 40px; margin-right:20px; text-align: right;" />
				<input type="button"  class="button-secondary" value="'.__('Jump To Line','wp-bugbot').'" onclick="ajbES_FindLineNumber(); return false;" />
			</form>
		</div>
		<div style="display:inline; margin-left:150px;">
			<form style="display:inline" action="#" onsubmit="ajbES_Search(); return false;">
				<input id="ajbES_SearchInput" type="text" value="'.$vsearchkeyword.'" style="width: 200px; margin-right:20px;" />
				<input type="button" class="button-secondary" value="'.__('Find In Code','wp-bugbot').'" onclick="ajbES_Search(); return false;" />
			</form>
		</div>
	  </div>
	 ';

	echo "      <script type=\"text/javascript\"><!--//--><![CDATA[//><!--\n";
	bugbot_editor_output_javascript();
	echo "      //--><!]]></script>\n";
}

// Directory Selector
// ------------------
function bugbot_directory_selector() {

	// 1.5.0: added this
	if (isset($_REQUEST['file'])) {$thisfile = $_REQUEST['file'];}
	elseif (isset($_REQUEST['showfilecontents'])) {$thisfile = $_REQUEST['showfilecontents'];}
	if (isset($thisfile)) {$pathinfo = pathinfo($thisfile); $thisdirectory = $pathinfo['dirname'];}
	else {$thisdirectory = '';}

	if (preg_match('|plugin-editor.php|i', $_SERVER["REQUEST_URI"])) {
		$editordisplaytype = __('Plugin','wp-bugbot');
		if (isset($_REQUEST['plugin'])) {$plugin = $_REQUEST['plugin'];}
		else {
			// 1.6.0: fix for when only file is specified
			$plugins = get_plugins();
			// echo "<!-- ".$thisfile." -->";
			$firstplugin = ''; $plugin = '';
			foreach ($plugins as $pluginkey => $thisplugin) {
				if ($firstplugin == '') {$firstplugin = $pluginkey;}
				$pluginpathinfo = pathinfo($pluginkey);
				$pluginbasepath = $pluginpathinfo['dirname'];
				// echo "<!-- ".$pluginbasepath."-->";
				if (strstr($thisfile,$pluginbasepath)) {$plugin = $pluginkey;}
			}
			// 1.6.5: added this fix for default load page
			if ($plugin == '') {$plugin = $firstplugin;}
		}
		$hiddeninput = '<input type="hidden" name="plugin" value="'.$plugin.'">';
		$pluginpath = dirname(WP_PLUGIN_DIR.'/'.$plugin);
		$pathinfo = pathinfo($plugin);
		$pluginbasedir = $pathinfo['dirname'];
		$pluginbasefile = $pathinfo['basename'];
		$pluginfiles = bugbot_file_search_list_files($pluginpath);
		$directories = array(); $dirarray = array();
		$editableextensions = bugbot_get_editable_extensions();
		foreach ($pluginfiles as $pluginfile) {
			$pathinfo = pathinfo($pluginfile);
			$plugindir = $pathinfo['dirname'];
			// limit to editable extensions to prevent non-editable screen
			if (in_array($pathinfo['extension'],$editableextensions)) {
				if ($plugindir == '.') {
					$pluginfile = $pluginbasefile;
					$plugindir = '(plugin root)';
				}
				if (!in_array($plugindir,$dirarray)) {
					// echo $plugindir.'<br>';
					$plugindir = str_replace('\\','/',$plugindir);
					$dirarray[] = $plugindir;
					$pluginfile = str_replace('\\','/',$pluginfile);
					$pluginfile = $pluginbasedir.'/'.$pluginfile;
					$directories[$plugindir] = $pluginfile;
				}
			}
		}
		if ($thisdirectory == $pluginbasedir) {$thisdirectory = '(plugin root)';}
	}

	if (preg_match('|theme-editor.php|i', $_SERVER["REQUEST_URI"])) {
		$editordisplaytype = __('Theme','wp-bugbot');
		if (isset($_REQUEST['theme'])) {$theme = $_REQUEST['theme'];}
		else {$thetheme = wp_get_theme(); $theme = $thetheme['Stylesheet'];}
		$hiddeninput = '<input type="hidden" name="theme" value="'.$theme.'">';
		$themepath = get_theme_root($theme).'/'.$theme.'/';
		$themefiles = bugbot_file_search_list_files($themepath);
		$extensions = array('php','css');
		// 1.7.0: use new wp_theme_editor_filetypes for theme file extensions
		$extensions = apply_filters('wp_theme_editor_filetypes',$extensions,$theme);
		$directories = array(); $dirarray = array();
		foreach ($themefiles as $themefile) {
			$pathinfo = pathinfo($themefile);
			// 1.5.0: limited to css or php to avoid 'that file is not editable' upon selection
			// 1.7.0: now we can change this thanks to new wp_theme_editor_filetypes filter
			if ( (isset($pathinfo['extension'])) && (in_array($pathinfo['extension'],$extensions)) ) {
				$themedir = $pathinfo['dirname'];
				if ($themedir == '.') {
					$themefile = 'style.css';
					$themedir = '(theme root)';
				}
				if (!in_array($themedir,$dirarray)) {
					// echo $themedir.'<br>';
					$themedir = str_replace('\\','/',$themedir);
					$dirarray[] = $themedir;
					$themefile = str_replace('\\','/',$themefile);
					$directories[$themedir] = $themefile;
				}
			}
		}
		if ($thisdirectory == $theme) {$thisdirectory = '(theme root)';}
	}

	// echo "<!-- * ".$thisdirectory." * -->";
	// echo "<!-- Directories: "; print_r($directories); echo " -->";

	if (count($directories) > 1) {
		echo '<div id="dirselectdiv" style="display:block; text-align:right; margin-bottom:10px;">';
		echo '	<div style="display:inline;">';
		echo '		<b>'.$editordisplaytype.' Directory:</b> ';
		echo '		<form action="" method="get" style="display:inline;">';
		echo '		'.$hiddeninput;
		echo '		<select name="file" id="directoryfile">';
		foreach ($directories as $directory => $value) {
			echo '			<option value="'.$value.'"';
			if ($directory == $thisdirectory) {echo ' selected="selected"';}
			echo '>'.$directory.'</option>';
		}
		echo '		</select>';
		echo '		<input type="submit" class="button-secondary" value="Select">';
		echo '		</form>';
		echo '	</div>';
		echo '</div>';

		// 1.7.0: use jquery to insert at position
		echo "<script>jQuery(document).ready(function() {
			\$fileeditsub = jQuery('.fileedit-sub'); jQuery('#dirselectdiv').insertAfter(\$fileeditsub);
		});</script>";
	}
}

// Add the Javascript into the Admin Footer
// ----------------------------------------
function bugbot_editor_admin_footer() {
	if ( (preg_match('|theme-editor.php|i', $_SERVER["REQUEST_URI"]))
	  || (preg_match('|plugin-editor.php|i', $_SERVER["REQUEST_URI"])) ) {
			bugbot_editor_insert_code();
			bugbot_directory_selector();
	}
}
add_action('admin_footer', 'bugbot_editor_admin_footer');


// --------------
// === Styles ===
// --------------

// Wordwrap Style Fix
// ------------------
function bugbot_wordwrap_styles() {
	echo "<style>.wordwrapcell {";
	echo "min-width:40em; max-width:60em; overflow-wrap:break-word; word-wrap:break-word; "; /* IE 5+ */
	echo "white-space:pre; "; /* CSS 2.0 */
	echo "white-space:pre-wrap; "; /* CSS 2.1 */
	echo "white-space:pre-line; "; /* CSS 3.0 */
	echo "white-space:-pre-wrap; "; /* Opera 4-6 */
	echo "white-space:-o-pre-wrap; "; /* Opera 7 */
	echo "white-space:-moz-pre-wrap; "; /* Mozilla */
	echo "white-space:-hp-pre-wrap; "; /* HP Printers */
	echo "}</style>";
}

?>