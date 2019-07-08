<?php

/*
Plugin Name: WP BugBot
Plugin URI: http://wordquest.org/plugins/wp-bugbot/
Description: WP BugBot, Plugin And Theme Search Editor Engine! Search installed plugin and theme files (and core) for code from the editor screens. A bugfixers dream..!
Version: 1.8.0
Author: WP Medic
Author URI: http://wpmedic.tech
GitHub Plugin URI: majick777/wp-bugbot
@fs_premium_only wp-bugbot-pro.php
*/

if (!function_exists('add_action')) {exit;}

// Development TODOs
// -----------------
// * retest editable extensions filter
// ? fix menu permissions to match search permissions
// ? fix unlisted files editor section
// - update line jump/code search for Code Mirror
// + plugin / theme specific editable extensions settings
// ? merge plugin/theme/core search/UIs


// ====================
// --- Setup Plugin ---
// ====================

// 1.5.0: added svg,psd,swf to default media
// 1.5.0: added search time limit default 5 mins
// 1.5.0: added donot replace theme editor option
// 1.7.9: changed default to not replace theme editor
// 1.8.0: merge defaults and options array
$options = array(
	'save_selected_plugin'		=> array('type'	=> 'checkbox', 'default' => 'yes'),
	'save_selected_theme'		=> array('type'	=> 'checkbox', 'default' => 'yes'),
	'save_selected_dir'			=> array('type'	=> 'checkbox', 'default' => 'yes'),
	'save_last_searched'		=> array('type'	=> 'checkbox', 'default' => 'yes'),
	'save_case_sensitive'		=> array('type' => 'checkbox', 'default' => 'yes'),
	'add_editable_extensions'	=> array('type'	=> 'text', 'default' => ''),
	'donotsearch_extensions'	=> array(
									'type'		=> 'text',
									'default'	=> 'zip,jpg,jpeg,gif,png,tif,tiff,bmp,svg,psd,pdf,mp3,wma,m4a,wmv,ogg,mpg,mkv,avi,mp4,flv,fla,swf',
								),
	'snip_long_lines_at'		=> array('type'	=> 'numeric', 'default' => 500),
	'search_time_limit'			=> array('type'	=> 'numeric', 'default' => 300),
	'donot_replace_theme_editor' => array('type' => 'checkbox', 'default'	=> 'yes'),
);

// ---------------
// Loader Settings
// ---------------
// 1.8.0: updated settings to use plugin loader
$slug = 'wp-bugbot';
$args = array(
	// --- Plugin Info ---
	'slug'			=> $slug,
	'file'			=> __FILE__,
	'title'			=> __('WP BugBot','wp-bugbot'),
	'version'		=> '0.0.1',

	// --- Menus and Links ---
	'parentmenu'	=> 'wordquest',
	'home'			=> 'http://wpmedic.tech/wp-bugbot/',
	'support'		=> 'http://wordquest.org/quest-category/'.$slug.'/',
	'share'			=> 'http://wpmedic.tech/wp-bugbot/#share',
	'donate'		=> 'https://patreon.com/wpmedic',
	'donatetext'	=> __('Support WP Medic'),
	// 'welcome'	=> '', // TODO

	// --- Options ---
	'namespace'		=> 'bugbot',
	'settings'		=> 'bb',
	'option'		=> 'wp_bugbot',
	'options'		=> $options,

	// --- WordPress.Org ---
	'wporgslug'		=> 'wp-bugbot',
	'wporg'			=> false,
	'textdomain'	=> 'wp-bugbot',

	// --- Freemius ---
	'freemius_id'	=> '159',
	'freemius_key'	=> 'pk_03b9fce070af83d5e91dcffcb8719',
	'hasplans'		=> false,
	'hasaddons'		=> false,
	'plan'			=> 'free',
);

// ----------------------------
// Start Plugin Loader Instance
// ----------------------------
require(dirname(__FILE__).DIRECTORY_SEPARATOR.'loader.php');
$instance = new bugbot_loader($args);

// -------------------------------------
// (temp) Remove Bonus Offer Sidebar Box
// -------------------------------------
function bb_sidebar_bonus_offer() {return;}


// =======================
// --- Action Triggers ---
// =======================

// --------------------
// File Search Triggers
// --------------------
add_filter('all_admin_notices', 'bugbot_check_for_file_search');
function bugbot_check_for_file_search() {
	if (isset($_REQUEST['searchkeyword'])) {
		if (isset($_REQUEST['pluginfilesearch']) && ($_REQUEST['pluginfilesearch'] != '')) {
			return bugbot_plugin_file_do_search();
		}
		if (isset($_REQUEST['themefilesearch']) && ($_REQUEST['themefilesearch'] != '')) {
			return bugbot_theme_file_do_search();
		}
		if (isset($_REQUEST['corefilesearch']) && ($_REQUEST['corefilesearch'] != '')) {
			return bugbot_core_file_do_search();
		}
	}
}

// ------------------------
// Main User Interface Hook
// ------------------------
if (!function_exists('bugbot_interface_hook'))  {
	function bugbot_interface_hook($content) {

		// 1.5.0: changed this check from is_multisite
		if (is_network_admin()) {
			// TODO: maybe use $pagenow global for matching here also ?
			if (preg_match('|network/plugin-editor.php|i', $_SERVER["REQUEST_URI"])) {
				echo '<div class="wrap">'; bugbot_maybe_notice_boxer(); bugbot_plugin_file_search_ui(); echo '</div>';
			} elseif (preg_match('|network/theme-editor.php|i', $_SERVER["REQUEST_URI"])) {
				echo '<div class="wrap">'; bugbot_maybe_notice_boxer(); bugbot_theme_file_search_ui(); echo '</div>';
			} elseif (preg_match('|network/update-core.php|i', $_SERVER["REQUEST_URI"])) {
				echo '<div class="wrap">'; bugbot_maybe_notice_boxer(); bugbot_core_file_search_ui();
				// 1.7.1: if no editing allowed, add theme and plugin searches on update page
				if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {
					bugbot_theme_file_search_ui(); bugbot_plugin_file_search_ui();
				}
				echo '</div>';
			}
		} else {
			global $pagenow;
			if ($pagenow == 'plugin-editor.php') {echo '<div class="warp">'; bugbot_maybe_notice_boxer(); bugbot_plugin_file_search_ui(); echo '</div>';}
			if ($pagenow == 'theme-editor.php') {echo '<div class="warp">'; bugbot_maybe_notice_boxer(); bugbot_theme_file_search_ui(); echo '</div>';}
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
			// TODO: different case for when 'file' is set in querystring ?
			bugbot_file_search_show_file_contents();
		}

		return $content;
	}
	add_filter('all_admin_notices', 'bugbot_interface_hook', 999);
}


// ======================
// --- Plugin Options ---
// ======================

$bugbot['searches'] = get_option('bugbot_searches');

// -----------------
// Debug Mode Switch
// -----------------
$bugbot['debug'] = get_option('bugbot_debug', false);
if (isset($_REQUEST['bugbotdebug'])) {
	if ($_REQUEST['bugbotdebug'] == '2') {
		echo "<!-- WP BugBot Debug Mode is ON -->";
		update_option('bugbot_debug', '1');
		$bugbot['debug'] = true;
	}
	// 1.7.9: added temporary debug mode
	if ($_REQUEST['bugbotdebug'] == '1') {
		echo "<!-- WP BugBot Debug Mode is ON for this Pageload -->";
		$bugbot['debug'] = true;
	}
	if ($_REQUEST['bugbotdebug'] == '0') {
		echo "<!-- WP BugBot Debug Mode is OFF -->";
		delete_option('butbot_debug');
		$bugbot['debug'] = false;
	}
}

// -----------------------------------
// maybe Transfer Old Options/Searches
// -----------------------------------
// 1.7.0: compact old options/searches to single option
function bugbot_transfer_settings() {
	if (!get_option('wp_bugbot') && get_option('patsee_do_not_seach_extensions')) {

		$oldoptions = array('save_selected_plugin','save_selected_theme','save_selected_dir',
			'save_last_searched','save_case_sensitive','search_time_limit','donot_replace_theme_editor',
			'snip_long_lines_at','add_editable_extensions','donotsearch_extensions',
			'plugin_file_search_plugin','theme_file_search_theme', 'core_file_search_dir',
			'plugin_file_search_keyword', 'theme_file_search_keyword', 'core_file_search_keyword',
			'plugin_file_search_case','theme_file_search_case', 'core_file_search_case'
		);
		foreach ($oldoptions as $oldoption) {
			$bugbot[$oldoption] = get_option('patsee_'.$oldoption); delete_option('patsee_'.$oldoption);
		}
		update_option('wp_bugbot', $bugbot);

		$oldsearches = array('plugin_file_search_plugin','theme_file_search_theme','core_file_search_dir',
			'plugin_file_search_keyword','theme_file_search_keyword','core_file_search_keyword',
			'plugin_file_search_case','theme_file_search_case','core_file_search_case'
		);
		foreach ($oldsearches as $oldsearch) {
			$bugbotsearches[$oldsearch] = get_option('patsee_'.$oldsearch); delete_option('patsee_'.$oldsearch);
		}
		update_option('wp_bugbot_searches', $bugbotsearches);

		$sidebar_options = get_option('patsee_sidebar_options');
		if ($sidebar_options) {add_option('bugbot_sidebar_options', $sidebar_options); delete_option('patsee_sidebar_options');}
	}
}

// --------------------
// Save Plugin Settings
// --------------------
// 1.7.4: changed to use admin_init hook
function bugbot_process_settings() {

	global $bugbot, $bugbotsearches; $settings = $bugbot;

	$defaults = bugbot_default_settings();

	// set flag to update searches values or not
	$updatesearches = false;

	// Update Options
	// --------------
	// 1.7.9: fixed and streamlined search saving logic
	if (isset($_REQUEST['save_selected_plugin']) && ($_REQUEST['save_selected_plugin'] == 'yes')) {
		$saveselectedplugin = $bugbot['save_selected_plugin'] = $_REQUEST['save_selected_plugin'];
	} else {
		$saveselectedplugin = $bugbot['save_selected_plugin'] = '';
		$bugbotsearches['plugin_file_search_plugin'] = ''; $updatesearches = true;
	}
	if (isset($_REQUEST['save_selected_theme']) && ($_REQUEST['save_selected_theme'] == 'yes')) {
		$saveselectedtheme = $bugbot['save_selected_theme'] = $_REQUEST['save_selected_theme'];
	} else {
		$saveselectedtheme = $bugbot['save_selected_theme'] = '';
		$bugbotsearches['theme_file_search_theme'] = ''; $updateearches = true;
	}
	if (isset($_REQUEST['save_selected_dir']) && ($_REQUEST['save_selected_plugin'] == 'yes')) {
		$saveselecteddir = $bugbot['save_selected_dir'] = $_REQUEST['save_selected_dir'];
	} else {
		$saveselecteddir = $bugbot['save_selected_dir'] = '';
		$bugbotsearches['core_file_search_dir'] = ''; $updatesearches = true;
	}
	if (isset($_REQUEST['save_last_searched']) && ($_REQUEST['save_last_searched'] == 'yes')) {
		$savelastsearched = $bugbot['save_last_searched'] = $_REQUEST['save_last_searched'];
	} else {
		$savelastsearched = $bugbot['save_last_searched'] = '';
		$bugbotsearches['plugin_file_search_keyword'] = '';
		$bugbotsearches['theme_file_search_keyword'] = '';
		$bugbotsearches['core_file_search_keyword'] = '';
		$updatesearches = true;
	}
	if (isset($_REQUEST['save_case_sensitive']) && ($_REQUEST['save_case_sensitive'])) {
		$savecasesensitive = $bugbot['save_case_sensitive'] = $_REQUEST['save_case_sensitive'];
	} else {
		$savecasesensitive = $bugbot['save_case_sensitive'] = '';
		$bugbotsearches['plugin_file_search_case'] = '';
		$bugbotsearches['theme_file_search_case'] = '';
		$bugbotsearches['core_file_search_case'] = '';
		$updatesearches = true;
	}

	if (isset($_REQUEST['donot_replace_theme_editor']) && ($_REQUEST['donot_replace_theme_editor'])) {
		$donotreplacethemeeditor = $bugbot['donot_replace_theme_editor'] = $_REQUEST['donot_replace_theme_editor'];
	} else {$donotreplacethemeeditor = $bugbot['donot_replace_theme_editor'] = '';}

	// 1.7.9: added better numerical sanitization
	if (isset($_REQUEST['search_time_limit'])) {
		$searchtimelimit = absint($_REQUEST['search_time_limit']);
		if ($searchtimelimit < 0) {$searchtimelimit = 300;}
		$bugbot['search_time_limit'] = $searchtimelimit;
	}
	if (isset($_REQUEST['snip_long_lines_at'])) {
		$sniplonglinesat = absint($_REQUEST['snip_long_lines_at']);
		if (!$sniplonglinesat < 0) {$sniplonglinesat = 500;}
		$bugbot['snip_long_lines_at'] = $sniplonglinesat;
	}

	// 1.7.0: maybe update (delete) saved searches
	if ($updateearches) {update_option('wp_bugbot_searches', $bugbotsearches);}

	// Handle User Defined Editable Extensions
	// ---------------------------------------
	if (isset($_REQUEST['add_editable_extensions'])) {
		$addextensions = $_REQUEST['add_editable_extensions'];
		$extensionstoadd = explode(",", $addextensions);
		if (count($extensionstoadd) > 0) {
			foreach ($extensionstoadd as $i => $extension) {
				$position = strpos($ext, '.');
				if ($position !== false) {$extension = substr($extension, $pos+1, strlen($extension));}
				$extension = trim(preg_replace('/[^a-z0-9]/i', '', $extension));
				if ($extension != '') {$extensionstoadd[$i] = $extension;}
				else {unset($extensionstoadd[$i]);}
			}
		}
		$editable_extensions = implode(',', $extensionstoadd);
		$bugbot['add_editable_extensions'] = $editable_extensions;
	}

	// Handle Do Not Search Extensions
	// -------------------------------
	if (isset($_REQUEST['donotsearch_extensions'])) {
		$dnsextensions = $_REQUEST['donotsearch_extensions'];
		$donotsearch = explode(",", $dnsextensions);
		if (count($donotsearch) > 0) {
			foreach ($donotsearch as $i => $extension) {
				$position = strpos($ext, '.');
				if ($position !== false) {$extension = substr($extension, $pos+1, strlen($extension));}
				$extension = trim(preg_replace('/[^a-z0-9]/i', '', $extension));
				if ($extension != '') {$donotsearch[$i] = $extension;}
				else {unset($donotsearch[$i]);}
			}
		}
		$donotsearch_extensions = implode(',', $donotsearch);
		$bugbot['donotsearch_extensions'] = $donotsearch_extensions;
	}

	// 1.7.9: remove any keys not from the settings
	$settings = $bugbot; // TEMP
	$settings_keys = array_keys($defaults);
	foreach ($settings as $key => $value) {
		if (!in_array($key, $settings_keys)) {unset($settings[$key]);}
	}

	// for debugging save values
	// $debug = "Posted: ".PHP_EOL.print_r($_POST,true).PHP_EOL."Settings: ".print_r($settings,true).PHP_EOL;
	// error_log($debug, 3, dirname(__FILE__).'/debug-save.txt');

	// for updating sidebar options
	$sidebaroptions = get_option($bugbot['settings'].'_sidebar_options');
	$newsidebaroptions = $sidebaroptions;
	if (isset($_REQUEST['bugbot_donation_box_off'])) {$newsidebaroptions['donationboxoff'] = $_REQUEST['bugbot_donation_box_off'];}
	if (isset($_REQUEST['bugbot_report_box_off'])) {$newsidebaroptions['reportboxoff'] = $_REQUEST['bugbot_report_box_off'];}
	if (isset($_REQUEST['bugbot_ads_box_off'])) {$newsidebaroptions['adsboxoff'] = $_REQUEST['bugbot_ads_box_off'];}
	if ($newsidebaroptions != $sidebaroptions) {
		update_option($bugbot['settings'].'_sidebar_options', $newsidebaroptions);
	}

	// Special: for onpage sidebar save reponse
	if (isset($_REQUEST['onpagesave'])) {
		echo "<script>parent.quicksavedshow();";
		if ($sidebaroptions['donationboxoff'] == 'checked') {echo "parent.document.getElementById('donate').style.display = 'none';";}
		else {echo "parent.document.getElementById('donate').style.display = '';";}
		if ($sidebaroptions['reportboxoff'] == 'checked') {echo "parent.document.getElementById('bonusoffer').style.display = 'none';";}
		else {echo "parent.document.getElementById('bonusoffer').style.display = '';";}
		if ($sidebaroptions['adsboxoff'] == 'checked') {echo "parent.document.getElementById('pluginads').style.display = 'none';";}
		else {echo "parent.document.getElementById('pluginads').style.display = '';";}
		echo "</script>"; exit;
	}

	return $settings;

}

// ----------------------------
// Maybe use Admin Notice Boxer
// ----------------------------
// Admin Notice Boxer for the default screens only, called later on inside search screens...
function bugbot_maybe_notice_boxer() {
	if (!isset($_POST['searchkeyword'])) {
		if (function_exists('wqhelper_admin_notice_boxer')) {wqhelper_admin_notice_boxer();}
	}
}

// --------------------
// Plugin Settings Page
// --------------------
function bugbot_settings_page() {

	global $bugbot;
	if ($_REQUEST['page'] == $bugbot['slug']) {$bugbot['settings_page'] = 'done';}

	// 1.7.0: buffer here for stickykit positioning
	ob_start();

	// Sidebar Floatbox
	// ----------------
	// minus save for settings page
	// $args = array('bugbot','wp-bugbot','free','wp-bugbot','special','WP BugBot',$bugbot['version']);
	$args = array($bugbot['slug'], 'yes'); // (trimmed arguments)
	if (function_exists('wqhelper_sidebar_floatbox')) {

		wqhelper_sidebar_floatbox($args);

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

	}
	$bugbot['sidebar'] = ob_get_contents(); ob_end_clean();

	// Load Options Display
	// --------------------
	$bugbot['settings_page'] = true;
	bugbot_sidebar_plugin_header();

	// 1.7.9: moved error log panel to separate function
	bugbot_error_log_panel();

	echo "</div></div>"; // close wrapbox
	echo "</div>"; // close wrap

}

// -----------------
// Plugin Header Box
// -----------------
// (now combined with settings page output)
function bugbot_sidebar_plugin_header() {

	global $bugbot; $settings = $bugbot;
	// note: must be === here
	if (isset($bugbot['settings_page']) && ($bugbot['settings_page'] === 'done')) {return;}

	if (!$bugbot['settings_page']) {
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

	if ($bugbot['settings_page']) {

		echo '<div id="pagewrap" class="wrap" style="width:100%;margin-right:0px !important;">';

		// Admin Notices Boxer
		// -------------------
		if (function_exists('wqhelper_admin_notice_boxer')) {wqhelper_admin_notice_boxer();}

		echo $bugbot['sidebar'];

		// Plugin Page Title
		// -----------------
		bugbot_settings_header();

		echo "<div id='wrapbox' class='postbox' style='width:680px;line-height:2em;'><div class='inner' style='padding-left:20px;'>";

		// --- check of file editing disallowed ---
		if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {

			// --- no file editing allowed message ---
			// 1.7.1: added no file editing allowed message
			$message = __('File editing is disabled. Plugin, Theme and Core Search interfaces can all be found on the ');
			// 1.7.4: fix to update URL for dissallowed file editing
			$updates_url = admin_url('update-core.php');
			$message .= "<a href='".$updates_url."'>".__('Core Updates page')."</a>.";
			// 1.8.0: use plugin loader message_box function here
			bugbot_message_box($message, true);

		} else {

			// --- search interface link table ---
			// 1.8.0: add search interface links
			echo "<center><table><tr height='10'><td></td></tr><tr><td>";
				echo "<b>".__('Search Interfaces','wp-bugbot')."</b>:";
			echo "</td><td width='20'></td><td>";
				echo "<a class='button button-primary' href='".admin_url('plugin-editor.php')."'>".__('Plugin Search','wp-bugbot')."</a>";
			echo "</td><td width='20'></td><td>";
				echo "<a class='button button-primary' href='".admin_url('theme-editor.php')."'>".__('Theme Search','wp-bugbot')."</a>";
			echo "</td><td width='20'></td><td>";
				echo "<a class='button button-primary' href='".admin_url('update-core.php')."'>".__('Core Search','wp-bugbot')."</a>";
			echo "</td></tr></table></center>";

		}
	}

	// --- start save form ---
	echo '<form id="pfssettings" target="pfssaveframe" method="post">';

	if (!$bugbot['settings_page']) {

		// --- sidebar options box ---
		echo "<div id='searchoption'><div class='stuffbox' style='width:250px;'><h3>".__('Plugin Options','wp-bugbot')."</h3><div class='inside'>";
		echo "<div id='showsearchoptions'><a href='javascript:void(0);' onclick='showsearchoptions();'>".__('Show Plugin Options','wp-bugbot')."</a></div>";
		echo "<div id='hidesearchoptions' style='display:none;'><a href='javascript:void(0);' onclick='hidesearchoptions();'>".__('Hide Plugin Options','wp-bugbot')."</a></div>";
		echo "<div id='searchoptions' style='display:none;'>";

		// --- sidebar settings tabs ---
		echo "<center><table><tr>";
		echo "<td><a href='javascript:void(0)' onclick='showsearchsettings(\"extensions\");'><span id='extensionsbg' style='background-color:#eeeeee;'>".__('Extensions','wp-bugbot')."</span></a></td><td width='20'></td>";
		echo "<td><a href='javascript:void(0)' onclick='showsearchsettings(\"search\");'><span id='searchbg'>".__('Settings','wp-bugbot')."</span></a></td><td width='20'></td>";
		echo "<td><a href='javascript:void(0)' onclick='showsearchsettings(\"sidebar\");'><span id='sidebarbg'>".__('Sidebar','wp-bugbot')."</span></a></td><td width='20'></td>";
		echo "</td></tr></table></center><br>";

	} else {echo '<div><div><div>';}

	// Editable Extensions
	// -------------------
	if ($bugbot['settings_page']) {echo "<table><tr><td align='center' style='vertical-align:top;'>";}
	echo "<div id='extensionssettings' style='max-width:370px;'>";
	echo "<center><h4>".__('Editable Extensions','wp-bugbot')."</h4>";
	echo "<b>".__('Default Editable Extensions','wp-bugbot').":</b><br>";
	// 1.7.0: specify default editable extensions directly
	// $extensionsarray = array('php', 'txt', 'text', 'js', 'css', 'html', 'htm', 'xml', 'inc', 'include');
	// 1.7.9: simplify printing of default editable extensions
	$editable_extensions = bugbot_get_editable_extensions(null);
	echo implode(", ", $editable_extensions);
	$addextensions = bugbot_get_setting('add_editable_extensions',false);
	echo "<br><b>".__('Add Editable Extensions','wp-bugbot').":</b><br>";
	echo "<textarea rows='1' cols='40' id='add_editable_extensions' name='add_editable_extensions'>".$addextensions."</textarea><br>";
	echo "(".__('comma separated list','wp-bugbot').")<br>";

	$donotsearchextensions = bugbot_get_setting('donotsearch_extensions',false);
	echo "<p style='line-height:1.4em;margin-bottom:5px;'><b>".__('Do Not Search Files','wp-bugbot')."<br>".__('with these Extensions','wp-bugbot').":</b></p>";
	echo "<textarea rows='3' cols='40' id='donotsearch_extensions' name='donotsearch_extensions'>".$donotsearchextensions."</textarea><br>";
	echo "(".__('comma separated list','wp-bugbot').")<br>";
	echo "</center></div>";

	// Search Settings
	// ---------------
	if ($bugbot['settings_page']) {echo "</td><td width='20'></td><td align='center' style='vertical-align:top;'>";}
	echo '<div id="searchsettings"';
		if (!$bugbot['settings_page']) {echo ' style="display:none;"';}
	echo '><center><h4>"'.__('Search Settings','wp-bugbot').'"</h4>';

	// save last search plugins
	echo '<table><tr><td width="220"><b>'.__('Save Last Searched Plugins','wp-bugbot').'?</b></td><td></td>';
	echo '<td align="center"><input type="checkbox" value="yes" name="save_selected_plugin"'; // '>
		if (bugbot_get_setting('save_selected_plugin',false) == 'yes') {echo " checked";}
	echo '></td></tr>';

	// save last searched theme
	echo '<tr><td width="220"><b>'.__('Save Last Searched Theme','wp-bugbot').'?</b></td><td></td>';
	echo '<td align="center"><input type="checkbox" value="yes" name="save_selected_theme"'; // '>
		if (bugbot_get_setting('save_selected_theme',false) == 'yes') {echo " checked";}
	echo '></td></tr>';

	// save last selected core directory
	echo '<tr><td width="220"><b>'.__('Save Last Searched Core Path','wp-bugbot').'?</b></td><td></td>';
	echo '<td align="center"><input type="checkbox" value="yes" name="save_selected_dir"'; // '>
		if (bugbot_get_setting('save_selected_dir', false) == 'yes') {echo " checked";}
	echo '></td></tr>';

	// save last search keyword
	echo '<tr><td><b>Save Last Searched Keyword?</b></td><td></td>';
	echo '<td align="center"><input type="checkbox" value="yes" name="save_last_searched"'; // '>
		if (bugbot_get_setting('save_last_searched', false) == 'yes') {echo " checked";}
	echo '></td></tr>';

	// save case sensitive selection
	echo '<tr><td><b>'.__('Save Case Sensitive Selection','wp-bugbot').'?</b></td><td></td>';
	echo '<td align="center"><input type="checkbox" value="yes" name="save_case_sensitive"'; // '>
		if (bugbot_get_setting('save_case_sensitive', false) == 'yes') {echo " checked";}
	echo "></td></tr>";

	echo "<tr height='20'><td> </td></tr>";

	// snip long search result lines
	$sniplinesat = bugbot_get_setting('snip_long_lines_at',false);
	echo '<tr><td><b>'.__('Snip Result Lines at x Characters','wp-bugbot').':</b></td><td></td>';
	echo '<td><input type="text" size="2" value="'.$sniplinesat.'" name="snip_long_lines_at">';
	echo "</td></tr>";

	$timelimit = bugbot_get_setting('search_time_limit', false);
	if (!$timelimit) {$timelimit = 300;}
	echo '<tr><td><b>'.__('Search Time Limit','wp-bugbot').':</b></td><td></td>';
	echo '<td><input type="text" size="2" value="'.$timelimit.'" name="search_time_limit">';
	echo "</td></tr>";

	echo '<tr><td><b>'.__('Use Default Theme Editor','wp-bugbot').':</b></td><td></td>';
	echo '<td align="center"><input type="checkbox" value="yes" name="donot_replace_theme_editor"'; // '>
		if (bugbot_get_setting('donot_replace_theme_editor',false) == 'yes') {echo " checked";}
	echo "></td></tr>";
	echo "</table></center>";
	echo "</div>";

	if ($bugbot['settings_page']) {echo "</td><td width='20'></td><td align='center' style='vertical-align:top;'>";}

	// Sidebar Settings
	// ----------------
	$sidebaroptions = get_option($bugbot['settings'].'_sidebar_options');
	echo '<div id="sidebarsettings" style="display:none;">';
	echo "<center><b>".__('Sidebar Settings','wp-bugbot')."</b><br><br>";
	echo "<table><tr><td align='center'>";
	echo "<b>".__('I rock! I have made a donation.','wp-bugbot')."</b><br>(hides donation box)</td><td width='10'></td>";
	echo "<td align='center'><input type='checkbox' name='bugbot_donation_box_off' value='checked'";
		if ($sidebaroptions['donationboxoff'] == 'checked') {echo " checked";}
	echo "></td></tr>";
	echo "<tr><td align='center'>";
	echo "<b>".__("I've got your report, you",'wp-bugbot')."<br>".__('can stop bugging me now.','wp-bugbot')." :-)</b><br>(hides report box)</td><td width='10'></td>";
	echo "<td align='center'><input type='checkbox' name='bugbot_report_box_off' value='checked'";
		if ($sidebaroptions['reportboxoff'] == 'checked') {echo " checked";}
	echo "></td></tr>";
	echo "<tr><td align='center'>";
	echo "<b>".__('My site is so awesome it','wp-bugbot')."<br>".__("doesn't need any more quality",'wp-bugbot');
	echo "<br>".__('plugins recommendations.','wp-bugbot')."</b><br>(".__('hides sidebar ads.','wp-bugbot').")</td><td width='10'></td>";
	echo "<td align='center'><input type='checkbox' name='bugbot_ads_box_off' value='checked'";
		if ($sidebaroptions['adsboxoff'] == 'checked') {echo " checked";}
	echo "></td></tr></table></center>";
	echo '</div>';
	if ($bugbot['settings_page']) {echo "</td></tr><tr><td colspan='5'>";}

	// Save Settings Button
	// --------------------
	// 1.7.9: added reset to defaults confirmation
	$reset_confirm = __('Are you sure you want to reset this plugin to default settings?','wp-automedic');
	echo "<script language='javascript' type='text/javascript'>
	function resettodefaults() {
		message = '".$reset_confirm."';
		agree = confirm(message); if (!agree) {return false;}
		document.getElementById('bugbot-update-action').value = 'reset';
		document.getElementById('bugbot-update-form').submit();
	}
	function changetarget(formtarget) {
		if (formtarget == 'iframe') {document.getElementById('pfssettings').target = 'pfssaveframe';}
		if (formtarget == 'reload') {document.getElementById('pfssettings').target = '_self';}
	}</script>";

	echo "<br><input type='hidden' id='bugbot-update-action' name='".$settings['namespace']."_update_settings' value='yes'>";
	// 1.7.0: add nonce check field
	wp_nonce_field($settings['slug']);

	if (isset($_REQUEST['themefilesearch'])) {echo "<input type='hidden' name='themefilesearch' value='".$_REQUEST['themefilesearch']."'>";}
	if (isset($_REQUEST['pluginfilesearch'])) {echo "<input type='hidden' name='pluginfilesearch' value='".$_REQUEST['pluginfilesearch']."'>";}
	if (isset($_REQUEST['corefilesearch'])) {echo "<input type='hidden' name='corefilesearch' value='".$_REQUEST['corefilesearch']."'>";}
	if (isset($_REQUEST['searchkeyword'])) {echo "<input type='hidden' name='searchkeyword' value='".$_REQUEST['searchkeyword']."'>";}
	if (isset($_REQUEST['searchcase'])) {echo "<input type='hidden' name='searchcase' value='".$_REQUEST['searchcase']."'>";}

	echo "<center><table><tr><td align='center'>";
	if ($bugbot['settings_page']) {
		// 1.7.9: add reset settings button to plugin page
		echo "<input type='button' class='button-secondary' id='plugin-settings-reset' onclick='changetarget(\"reload\"); return resettodefaults();' value='".__('Reset Settings','wp-bugbot')."'>";
		echo "</td><td width='50'></td><td align='center'>";
		echo "<input type='submit' class='button-primary' id='plugin-settings-save' onclick='changetarget(\"reload\");' value='".__('Save Settings','wp-bugbot')."'>";
	} else {
		echo "<input type='submit' class='button-secondary' name='onpagesave' onclick='changetarget(\"iframe\");' value='".__('Save Settings','wp-bugbot')."'>";
		echo "</td><td width='10'></td><td align='center'>";
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

	if ($bugbot['settings_page']) {echo "</td></tr></table>";}
	echo "</div>"; // close #searchoptions
	echo "</div></div></div>";

	// settings update iframe
	echo "<iframe style='display:none;' src='javascript:void(0);' name='pfssaveframe' id='pfssaveframe'></iframe>";

	// 1.7.0: moved this to incorporate log viewer
	// if ($bugbot['settings_page']) {
	// 	echo "</div></div>"; // close wrapbox
	// 	echo "</div>"; // close wrap
	// }
}


// ========================
// --- Helper Functions ---
// ========================

// ---------------------
// Set Search Time Limit
// ---------------------
function bugbot_set_time_limit() {
	// 1.5.0: added maxiumum time limit for search
	$timelimit = bugbot_get_setting('search_time_limit');
	if ($timelimit == '') {$timelimit = 300;}
	$timelimit = absint($timelimit);
	if (!is_numeric($timelimit) || ($timelimit < 0)) {$timelimit = 300;}
	if ($timelimit < 60) {$timelimit = 60;} // minimum
	if ($timelimit > 3600) {$timelimit = 3600;} // maximum
	@set_time_limit($timelimit);
}

// --------------------
// Replace Theme Editor
// --------------------
// 1.5.0: replace theme-editor.php so we can add theme_editor_allowed_files filter
// as default editor only lists css and php to dir depth of 1?! say what?
// 1.7.0: modify theme-editor.php to add allowed files filter
// 1.7.1: check file match just before using it, fix typo in function name
// 1.7.9: note: this is probably no longer needed as depth is now -1 (infinite)
global $pagenow;
if ($pagenow == 'theme-editor.php') {
	add_action('load-theme-editor.php', 'bugbot_replace_theme_editor', 0);
}

// -----------------------------------------------
// Replace Theme Editor to Allow Further Filtering
// -----------------------------------------------
// 1.7.0: replace theme editor is on by default
// 1.7.9: now turned off by default (not really needed)
function bugbot_replace_theme_editor() {
	$donotreplacethemeeditor = bugbot_get_setting('donot_replace_theme_editor');
	// 1.7.9: fix to theme setting check
	if ( ($donotreplacethemeeditor != 'yes') && ($donotreplacethemeeditor != '1') ) {
		$replace = true;
		$themeeditorpath = ABSPATH.'wp-admin/theme-editor.php';
		$defaultthemeeditor = file_get_contents($themeeditorpath);
		if (!file_exists($themeeditorpath)) {return;}
		$themeeditorpath = dirname(__FILE__).'/theme-editor.php';
		$savedthemeeditor = file_get_contents($themeeditorpath);

		$search = 'validate_file_to_edit( $file, $allowed_files );';
		$replace = "\$allowed_files = apply_filters('theme_editor_allowed_files',\$allowed_files);".PHP_EOL.$search;
		$modthemeeditor = str_replace($search, $replace, $defaultthemeeditor);
		$search = "require_once( dirname( __FILE__ ) . '/admin.php' );";
		$replace = "require_once( ABSPATH . 'wp-admin/admin.php' );";
		$modthemeeditor = str_replace($search, $replace, $modthemeeditor);

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
				if (!$writeresult) {$replace = false;}
			} else {$replace = false;}
		}

		// maybe fallback to default editor
		if ($replace) {add_action('load-theme-editor.php', 'bugbot_load_theme_editor', 1);}
	}
}

// --------------------------
// Load Modified Theme Editor
// --------------------------
// 1.7.1: fix to typo in function name
function bugbot_load_theme_editor() {
	echo "<!-- Loading Modified Theme Editor to Filter Editable Files -->";
	// 1.7.9: using this breaks the 4.9+ file selector pane :-(
	$themeeditorpath = dirname(__FILE__).'/theme-editor.php';
	if (isset($_REQUEST['theme'])) {$theme = $_REQUEST['theme'];} else {$theme = '';}
	if (isset($_REQUEST['file'])) {$file = $_REQUEST['file'];} else {$file = '';}
	if (isset($_REQUEST['error'])) {$error = $_REQUEST['error'];} else {$error = '';}
	if (isset($_REQUEST['action'])) {$action = $_REQUEST['action'];} else {$action = '';}
	include($themeeditorpath); exit;
}

// ----------------------------------------
// Editable Extensions Filter (Theme Files)
// ----------------------------------------
// 1.7.0: use the wp_theme_editor_filetypes filter added WP 4.4
// 1.7.9: fixed to simply add plugin settings for editable extensions
// 1.8.0: added missing global declartion for bugbot debug
add_filter('wp_theme_editor_filetypes', 'bugbot_theme_file_types', 10, 2);
function bugbot_theme_file_types($default_types, $theme) {

	global $bugbot;

	$addextensions = bugbot_get_setting('add_editable_extensions');
	if ($addextensions && ($addextensions != '')) {
		if (strstr($addextensions, ',')) {$extensionstoadd = explode(",", $addextensions);}
		else {$extensionstoadd[0] = $addextensions;}
		foreach ($extensionstoadd as $extension) {
			if (!in_array($extension)) {$default_types[] = $extension;}
		}
	}
	if ($bugbot['debug'] == '1') {
		echo "<!-- Editable Theme File Extensions: ".print_r($default_types,true)." -->";
	}

	// TODO: merge settings for plugin-only editable extensions ?

	return $default_types;
}

// --------------------------
// Theme Editor Allowed Files
// --------------------------
// 1.5.0: added this to allow theme editing
// as default editor only adds root css and php to depth of 1 !?!
// 1.7.9: note this is only used if replace theme editor is on
add_filter('theme_editor_allowed_files', 'bugbot_theme_editor_allowed_files');
function bugbot_theme_editor_allowed_files($allowed_files) {

	if (isset($_REQUEST['theme'])) {
		$thetheme = $_REQUEST['theme'];
		$theme = wp_get_theme($thetheme);
	} else {$theme = wp_get_theme();}

	$extensions = bugbot_get_editable_extensions('theme');

	foreach ($extensions as $extension) {
		$allow_files = $theme->get_files($extension, 10);
		$allowed_files += $allow_files;
	}

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
		return $allowedfiles;
	}

	return $allowed_files;
}

// -----------------------------------------
// Editable Extensions Filter (Plugin Files)
// -----------------------------------------
// 1.8.0: added missing global declaration (for debug)
add_filter('editable_extensions', 'bugbot_modify_editable_extensions', 10, 2);
function bugbot_modify_editable_extensions($editable_extensions, $plugin) {

	global $bugbot;

	// add user defined editable extensions
	$addextensions = bugbot_get_setting('add_editable_extensions');
	if ($addextensions && ($addextensions != '')) {
		if (strstr($addextensions, ',')) {$extensionstoadd = explode(",", $addextensions);}
		else {$extensionstoadd[0] = $addextensions;}
		foreach ($extensionstoadd as $extension) {
			if (!in_array($extension)) {$editable_extensions[] = $extension;}
		}
	}
	if ($bugbot['debug'] == '1') {
		echo "<!-- Editable Plugin File Extensions: ".print_r($editable_extensions,true)." -->";
	}

	// TODO: merge settings for theme-only editable extensions ?

	return $editable_extensions;

	// Remove Extensions (not implemented)
	// $removeextensions = get_option('remove_editable_extensions');
}

// -----------------------
// Get Editable Extensions
// -----------------------
// 1.7.9: update to get editable extensions for type
function bugbot_get_editable_extensions($type) {

	global $bugbot, $wp_version;

	if ( ($type == 'plugin') && function_exists('wp_get_plugin_file_editable_extensions') ) {
		$editable_extensions = wp_get_plugin_file_editable_extensions(null);
	} elseif ( ($type == 'theme') && function_exists('wp_get_theme_file_editable_extensions') ) {
		$editable_extensions = wp_get_theme_file_editable_extensions(null);
	} else {
		// $editable_extensions = array('php', 'txt', 'text', 'js', 'css', 'html', 'htm', 'xml', 'inc', 'include');
		$editable_extensions = array(
			'bash', 'conf', 'css', 'diff', 'htm', 'html', 'http', 'inc', 'include', 'js', 'json', 'jsx', 'less', 'md',
			'patch', 'php', 'php3', 'php4', 'php5', 'php7', 'phps', 'phtml', 'sass', 'scss', 'sh', 'sql', 'svg',
			'text', 'txt', 'xml', 'yaml', 'yml',
		);
	}
	if ($bugbot['debug']) {echo "<!-- Editable Extensions for ".$type.": ".print_r($editable_extensions,true)." -->";}

	return $editable_extensions;
}

// ----------------------------
// Get Do Not Search Extensions
// ----------------------------
function bugbot_get_donotsearch_extensions() {
	$donotsearch = bugbot_get_setting('donotsearch_extensions');
	$dnsarray = array();
	if ($donotsearch && ($donotsearch != '')) {
		if (strstr($donotsearch,',')) {$dnsarray = explode(',', $donotsearch);}
		else {$dnsarray[0] = $donotsearch;}
	}
	return $dnsarray;
}

// -----------------------------
// Perform Actual Keyword Search
// -----------------------------
function bugbot_file_search_for_keyword($code, $keyword, $case)  {
	// 1.6.0: change explode to PHP_EOL from "\n"
	// 1.7.0: change back as "\n" works to cover different plugin sources
	// 1.7.9: streamlined and compressed code logic
	$codearray = explode("\n", $code);
	$occurences = array(); $i = 1; $j = 0;
	foreach ($codearray as $codeline) {
		if ( ($case == 'sensitive') && strstr($codeline,$keyword) ) {
			$occurences[$j]['line'] = $i; $occurences[$j]['value'] = $codeline; $j++;
		} elseif ( ($case == 'insensitive') && stristr($codeline,$keyword) ) {
			$occurences[$j]['line'] = $i; $occurences[$j]['value'] = $codeline; $j++;
		}
		$i++;
	}
	return $occurences;
}

// ---------------------------
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
					$subdirresults = bugbot_file_search_list_files($path, $recursive, $basedir);
					$results = array_merge($results, $subdirresults);
					unset($subdirresults);
				}
			} else { // strip basedir and add to subarray to separate list
				$subresults[] = str_replace($basedir, '', $path);
			}
		}
	}
	// merge the subarray to give list of files first, then subdirectory files
	if (count($subresults) > 0) {
		$results = array_merge($subresults, $results); unset($subresults);
	}
	return $results;
}

// --------------------------
// Quick Textarea File Viewer
// --------------------------
// TODO: maybe improve file viewer interface to allow saving ?
// TODO: add legacy editor toolbar and javascript ?
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

		$filepath = str_replace('//', '/', $filepath);
		// 1.7.2: fix here to backslash replacement and typo
		if (strstr($filepath,' \\')) {$filepath = str_replace('\\', '/', $filepath);}
		if (!file_exists($filepath)) {
			// 1.7.9: added file does not exist message
			echo __('File Not Found','wp-bugbot').": '".$filepath."'<br><br>";
		} else {
			$filedata = file_get_contents($filepath);
			if (strlen($filedata) > 0) {
				echo "<br>".__('Contents of','wp-bugbot')." '".$filepath."' (".__('not editable here','wp-bugbot')."):<br><br>";
				echo "<textarea id='newcontent' rows='25' cols='100'>".$filedata."</textarea>";
			} else {echo "<br>".__('Empty File','wp-bugbot').": '".$filepath."'<br><br>";}
		}
	}
}

// ----------------------
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

// -----------------------------------------------
// Show Plugin/Theme Files Not Listed by Wordpress
// -----------------------------------------------
// TODO: fix this before restoring, seems to be broken now
function bugbot_show_unlisted_files($type) {

	if (current_user_can('manage_options')) {

		$editableextensions = bugbot_get_editable_extensions($type);

		if ($type == 'plugin') {
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
				$pathinfo = pathinfo($plugin);
				// 1.7.9: use index in array loop
				foreach ($files as $i => $file) {
					$listedfiles[$i] = str_replace($pathinfo['dirname'].'/', '' ,$file);
				}
			}
			$pluginpath = dirname(WP_PLUGIN_DIR.'/'.$plugin);
			$pluginfiles = bugbot_file_search_list_files($pluginpath);

			$unlistedfiles = array_diff($pluginfiles, $listedfiles);
			$files = $pluginfiles; // used again shortly
		}

		if ($type == 'theme') {

			$themes = wp_get_themes();
			$theme = $_REQUEST['theme'];
			$themeobject = wp_get_theme($theme);

			// 1.7.1: replicate what theme-editor.php does now
			$allowed_files = $style_files = array();
			$default_types = array( 'php', 'css' );
			$file_types = apply_filters( 'wp_theme_editor_filetypes', $default_types, $theme );
			$file_types = array_unique( array_merge( $file_types, $default_types ) );
			$depth = 1;
			if (has_action('load-theme-editor.php', 'bugbot_load_theme_editor', 0)) {$depth = 10;}

			foreach ( $file_types as $type ) {
				switch ( $type ) {
					// 1.7.5: fix to use theme object instead of string
					case 'php':
						$allowed_files += $themeobject->get_files( 'php', $depth );
						break;
					case 'css':
						$style_files = $themeobject->get_files( 'css' );
						$allowed_files['style.css'] = $style_files['style.css'];
						$allowed_files += $style_files;
						break;
					default:
						$allowed_files += $themeobject->get_files( $type );
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
				$i = 0;
				foreach ($files as $file => $fullpath) {
					$listedfiles[$i] = $file; $i++;
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

		$i = 0; $checkedfiles = array(); $dnsfiles = array();
		foreach ($unlistedfiles as $file) {
			$strip = false;
			foreach ($dnsarray as $dns) {
			 	$dns = ".".$dns; $len = strlen($dns); $len = -abs($len);
			 	if (substr($file,$len) == $dns) {$dnsfiles[] = $file; $strip = true;}
			}
			if (!$strip) {$checkedfiles[$i] = $file; $i++;}
		}

		if ($unlistedfound > 0) {
			echo "<div id='unlistedfilesinterface'>";
			echo "<script language='javascript' type='text/javascript'>";
			echo "function showhideunlistedfiles() {
				if (document.getElementById('unlistedfiles').style.display == 'none') {
					document.getElementById('unlistedfiles').style.display = '';
				} else {document.getElementById('unlistedfiles').style.display = 'none';}
			}</script>";

			echo " ".count($files)." ".$type." ".__('files found','wp-bugbot').". ".count($listedfiles)." ";
			echo __('files listed by Wordpress.','wp-bugbot')." ".count($unlistedfiles)." ".__('unlisted files','wp-bugbot').".</b> ";
			echo "<a href='javascript:void(0);' onclick='showhideunlistedfiles();'>".__('Click here to show unlisted files','wp-bugbot')."</a>.<br>";

			echo "<div id='unlistedfiles' style='display:none;'>";
			echo "<table><tr><td style='vertical-align:top;'>";
			if (count($checkedfiles) > 0) {
				echo "<b>".__('Unlisted Files','wp-bugbot').":</b><br>";
				foreach ($checkedfiles as $file) {
					// 1.5.0: fix for local searches
					$file = str_replace('\\','/',$file);
					echo "<a href='".$type."-editor.php?showfilecontents=".urlencode($file)."&".$type."file=yes";
					if ($type == 'plugin') {echo "&plugin=".urlencode($plugin);} // echo "&file=".urlencode($pathinfo['dirname'])."%2F".urlencode($file);
					if ($type == 'theme') {echo "&theme=".urlencode($theme);} // echo "&file=".urlencode($theme)."%2F".urlencode($file);
					echo "'>".$file."</a><br>";
				}
			}
			echo "</td><td width='50'></td><td style='vertical-align:top;'>";
			if (count($dnsfiles) > 0) {
				echo "<b>".__('Other Files','wp-bugbot').":</b><br>";
				foreach ($dnsfiles as $file) {
					// 1.5.0: fix for local searches
					$file = str_replace('\\','/',$file);
					echo "<a href='".$type."-editor.php?showfilecontents=".urlencode($file)."&".$type."file=yes";
					if ($type == 'plugin') {echo "&plugin=".urlencode($plugin);} // echo "&file=".urlencode($pathinfo['dirname'])."%2F".urlencode($file);
					if ($type == 'theme') {echo "&theme=".urlencode($theme);} // echo "&file=".urlencode($theme)."%2F".urlencode($file);
					echo "'>".$file."</a><br>";
				}
			}
			echo "</td></tr></table></div>";
			echo "</div>";
		}
	}
}

// ------------------
// Wordwrap Style Fix
// ------------------
function bugbot_wordwrap_styles() {
	echo "<style>.wordwrapcell {";
	echo "min-width:40em; max-width:60em; overflow-wrap:break-word; word-wrap:break-word; "; /* IE 5+ */
	echo "white-space:pre; "; 			/* CSS 2.0 */
	echo "white-space:pre-wrap; "; 		/* CSS 2.1 */
	echo "white-space:pre-line; "; 		/* CSS 3.0 */
	echo "white-space:-pre-wrap; "; 	/* Opera 4-6 */
	echo "white-space:-o-pre-wrap; "; 	/* Opera 7 */
	echo "white-space:-moz-pre-wrap; "; /* Mozilla */
	echo "white-space:-hp-pre-wrap; "; 	/* HP Printers */
	echo "}</style>".PHP_EOL;
}


// =====================
// --- File Searches ---
// =====================

// --------------------------
// === Plugin File Search ===
// --------------------------

function bugbot_plugin_file_do_search() {

	global $bugbot, $bugbotsearches;
	$bugbot['search_page'] = true;

	// 1.5.0: changed capability from manage_options to edit_plugins
	// 1.7.4: update permission check for DISALLOW_FILE_EDIT
	$allowed = false;
	if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {
		if (current_user_can('manage_options')) {$allowed = true;}
	} elseif (current_user_can('edit_plugins')) {$allowed = true;}
	if (!$allowed) {return;}

	// set time limit
	bugbot_set_time_limit();

	// get DoNotSearch Extensions
	// 1.7.0: use function call to get array
	$dnsarray = bugbot_get_donotsearch_extensions();

	// get Editable Extensions
	$editableextensions = bugbot_get_editable_extensions('plugin');
	if ($bugbot['debug'] == '1') {echo "<!-- Editable Extensions: "; print_r($editableextensions); echo " -->";}

	// get Line Snip Length
	$sniplinesat = bugbot_get_setting('snip_long_lines_at');
	if ( (!is_numeric($sniplinesat)) || ($sniplinesat < 1) ) {$sniplinesat = false;}

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
	// if ($bugbot['debug']) {print_r($pluginarray);}

	// save Search Request
	// 1.7.4: fix to save search logic
	// 1.7.9: streamlined search saving
	$updatesearches = false;
	if (!isset($bugbotsearches) || !is_array($bugbotsearches)) {$bugbotsearches = array();}
	if (bugbot_get_setting('save_selected_plugin') == 'yes') {
		$bugbotsearches['plugin_file_search_plugin'] = implode(',', $pluginarray); $updatesearches = true;
	}
	if (bugbot_get_setting('save_last_searched') == 'yes') {
		$bugbotsearches['plugin_file_search_keyword'] = $keyword; $updatesearches = true;
	}
	if (bugbot_get_setting('save_case_sensitive') == 'yes') {
		$bugbotsearches['plugin_file_search_case'] = $searchcase; $updatesearches = true;
	}
	if ($updatesearches) {update_option('wp_bugbot_searches', $bugbotsearches);}

	$i = 0; $activeplugins = get_option('active_plugins');
	foreach ($plugins as $plugin_key => $value) {
		if (in_array($plugin_key, $activeplugins)) {$activeplugindata[$plugin_key] = $value;}
		else {$inactiveplugindata[$plugin_key] = $value;}
		$allplugins[$i] = $plugin_key; $i++; // 1.7.0: counter fix
	}

	// 1.6.0: fix to pluginarray variable typos
	// 1.7.0: fix to plugin loop counters
	$i = 0; $searchtype = '';
	if ($pluginarray[0] == "ALLPLUGINS") {
		// $pluginpath = WP_PLUGIN_DIR."/";
		// $plugin_files = bugbot_file_search_list_files($pluginpath);
		$pluginarray = $allplugins;
		$searchtype = "ALLPLUGINS";
	} elseif ($pluginarray[0] == "ACTIVEPLUGINS") {
		foreach ($activeplugindata as $key => $value) {$pluginarray[$i] = $key; $i++;}
		$searchtype = "ACTIVEPLUGINS";
	} elseif ($pluginarray[0] == "INACTIVEPLUGINS") {
		foreach ($inactiveplugindata as $key => $value) {$pluginarray[$i] = $key; $i++;}
		$searchtype = "INACTIVEPLUGINS";
	} elseif ($pluginarray[0] == "UPDATEPLUGINS") {
		$pluginupdates = get_site_transient('update_plugins'); $j = 0;
		// 1.8.0: added check if response property exists
		if (property_exists($pluginupdates, 'response')) {
			foreach ($pluginupdates->response as $pluginupdate => $values) {$updatelist[$j] = $pluginupdate; $j++;}
			foreach ($updatelist as $update) {$pluginarray[$i] = $update; $i++;}
		}
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
	// if ($bugbot['debug']) {print_r($pluginarray); echo "***".$searchtype."***";}

	echo '<div class="wrap" id="pagewrap" style="margin-right:0px !important;">';

	// Admin Notices Boxer
	if (function_exists('wqhelper_admin_notice_boxer')) {wqhelper_admin_notice_boxer();}

	// Search Interface Header
	// -----------------------
	$icon_url = plugins_url('images/wp-bugbot.png',__FILE__);
	echo "<center><table><tr><td><img src='".$icon_url."' width='96' height='96'></td><td width='32'></td>";
	echo "<td align='center'><h2>WP BugBot <i>v".$bugbot['version']."</i><br><br>".__('Plugin File Search','wp-bugbot')." </h2>";
	echo "</td></tr></table></center>";

	// Plugin Search Interface
	// -----------------------
	echo "<br><center>".bugbot_plugin_file_search_ui()."</center><br><br>";

	if ($keyword == '') {echo __('Error: No Keyword Specified!','wp-bugbot'); return;}

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

	$snipcount = 0;
	foreach ($pluginarray as $plugin) {

		$pluginchunks = explode('/',$plugin);
		$plugindir = $pluginchunks[0];
		$pluginpath = WP_PLUGIN_DIR."/".$plugindir."/";
		$plugin_files = bugbot_file_search_list_files($pluginpath);

		if (count($plugin_files) > 0) {
			$found = false;
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

					$found = true;

					// --- File Header ---
					echo '<br><div style="display:inline-block;text-align:center;min-width:600px;"><b>'.__('File','wp-bugbot').': ';

					if ($bugbot['debug']) {echo "<!-- *".$extension."*"; print_r($editableextensions); echo " -->";}

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

					// --- Line Header ---
					echo '<div style="display:inline-block;width:40px;text-align:center;float:left;">'.__('Line','wp-bugbot').'</div>';

					foreach ($occurences as $occurence) {

						// Snip Long Lines
						if ($sniplinesat) {
							if (strlen($occurence['value']) > $sniplinesat) {
								$thisoccurence = $occurence['value'];
								$occurence['value'] = substr($occurence['value'], 0, $sniplinesat);
								$snipped = " <i><a href='javascript:void(0);' onclick='showsnipped(\"".$snipcount."\");'>[...".__('more','wp-bugbot')."...]</a></i>";
								$snipped .= "<div id='snip".$snipcount."' style='display:none;'><code>".substr($thisoccurence,$sniplinesat)."</code>";
								$snipped .= "<a href='javascript:void(0);' onclick='hidesnipped(\"".$snipcount."\");'>[...".__('less','wp-bugbot')."...]</a></div>";
								$snipcount++;
							} else {$snipped = "";}
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

						if (stristr($block,$keyword)) {
							$position = stripos($block,$keyword);
							if ($position > 0) {
								$chunks = str_split($block,$position);
								$firstblock = $chunks[0];
								unset($chunks[0]);
								$remainder = implode('',$chunks);
							} else {
								// 1.8.0: fix for firstblock value
								$firstblock = '';
								$remainder = $occurence['value'];
							}

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

						echo "</code>".$snipped."</span></td></tr></table>";

						echo "</div>";
					}
				}
			}
		}
		// 1.7.5: move this line to correct position (not for each file)
		if (!$found) {echo __('No results found for this plugin.','wp-bugbot');}
	}
	echo "</div>";

	echo "</div>";

	exit;
}

// ---------------------------------
// === Plugin Editor Search Form ===
// ---------------------------------

function bugbot_plugin_file_search_ui() {

	global $bugbot, $bugbotsearches;

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

	// 1.7.9: fix to handle no plugin updates found
	// 1.8.0: added check that response property exists
	$i = 0; $pluginupdates = get_site_transient('update_plugins');
	$updatelist = array();
	if (property_exists($pluginupdates, 'response')) {
		if (is_array($pluginupdates->response) && (count($pluginupdates->response) > 0)) {
			foreach ($pluginupdates->response as $pluginupdate => $values) {
				$updatelist[$i] = $pluginupdate; $i++;
			}
		}
	}

	// Get Last Search Values
	$lastsearchedplugins[0] = $lastsearchedkeyword = $lastsearchedcase = '';
	if (isset($bugbotsearches['plugin_file_search_keyword'])) {$lastsearchedkeyword = $bugbotsearches['plugin_file_search_keyword'];}
	// 1.7.9: fix to incorrect check of searches key
	if (isset($bugbotsearches['plugin_file_search_plugin'])) {$lastsearchedplugins = explode(',', $bugbotsearches['plugin_file_search_plugin']);}
	if (isset($bugbotsearches['plugin_file_search_case'])) {$lastsearchedcase = $bugbotsearches['plugin_file_search_case'];}

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
	}</script>";

	// Plugin Search Bar
	// -----------------
	echo '<div id="pluginfilesearchbar" style="display:inline;width:100%;margin-bottom:10px;" class="alignright">';
	// 1.7.1: different destination action if editing is not allowed
	$formaction = 'plugin-editor.php';
	if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {$formaction = 'update-core.php';}
	echo '<form action="'.$formaction.'" method="post" onSubmit="return checkpluginkeyword();">';

	// 1.5.0: fixed for repeat multiple search submissions
	if (count($lastsearchedplugins) > 1) {$searchtype = 'multiple';} else {$searchtype = 'single';}
	echo '<input type="hidden" name="searchtype" id="searchtype" value="'.$searchtype.'">';
	echo '<table><tr>';
	$icon_url = plugins_url('images/wp-bugbot.png',__FILE__);
	if (!isset($bugbot['search_page'])) {
		echo '<td width="48" style="vertical-align:top;"><img src="'.$icon_url.'" width="48" height="48"></td>';
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
		foreach ($activeplugindata as $plugin_key => $a_plugin) {
			$update = ''; if (in_array($plugin_key, $updatelist)) {$update = '* ';}
			$plugin_name = $a_plugin['Name'];
			if (strlen($plugin_name) > 40) {$plugin_name = substr($plugin_name, 0, 40)."...";}
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
		foreach ($inactiveplugindata as $plugin_key => $a_plugin) {
			$update = ''; if (in_array($plugin_key, $updatelist)) {$update = '* ';}
			$plugin_name = $a_plugin['Name'];
			if (strlen($plugin_name) > 40) {$plugin_name = substr($plugin_name, 0, 40)."...";}
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
			$update = ''; if (in_array($plugin_key, $updatelist)) {$update = '* ';}
			$plugin_name = $a_plugin['Name'];
			if (strlen($plugin_name) > 40) {$plugin_name = substr($plugin_name, 0, 40)."...";}
			if (in_array($plugin_key, $lastsearchedplugins)) {$selected = " selected='selected'";}
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
			$update = ''; if (in_array($plugin_key, $updatelist)) {$update = '* ';}
			$plugin_name = $a_plugin['Name'];
			if (strlen($plugin_name) > 40) {$plugin_name = substr($plugin_name, 0, 40)."...";}
			if (in_array($plugin_key, $lastsearchedplugins)) {$selected = " selected='selected'";}
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
	// 1.7.9: TEMP removed unlisted files section
	// if ( (isset($_REQUEST['plugin'])) && ($_REQUEST['plugin'] != '') ) {bugbot_show_unlisted_files('plugin');}

}

// -------------------------
// === Theme File Search ===
// -------------------------

function bugbot_theme_file_do_search() {

	global $bugbot, $bugbotsearches;
	$bugbot['search_page'] = true;

	// 1.5.0: changed capability from manage_options to edit_themes
	// 1.7.4: update permission check for DISALLOW_FILE_EDIT
	$allowed = false;
	if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {
		if (current_user_can('manage_options')) {$allowed = true;}
	} elseif (current_user_can('edit_themes')) {$allowed = true;}
	if (!$allowed) {return;}

	// set time limit
	bugbot_set_time_limit();

	// get DoNotSearch Extensions
	// 1.7.0: use function call to get array
	$dnsarray = bugbot_get_donotsearch_extensions();

	// get Editable Extensions
	$editableextensions = bugbot_get_editable_extensions('theme');

	// get Line Snip Length
	$sniplinesat = bugbot_get_setting('snip_long_lines_at');
	if ( (!is_numeric($sniplinesat)) || ($sniplinesat < 1) ) {$sniplinesat = false;}

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

	if ($bugbot['debug'] == '1') {
		echo "<!-- Theme Search: ".$themesearch." - Keyword: ".$keyword." - Case: ";
			if ($searchcase == 'sensitive') {echo $searchcase;} else {echo "insensitive";}
		echo " -->".PHP_EOL."<!-- Theme Info Dump: ".print_r($theme,true)." -->";
	}

	// save Search Request
	// 1.7.9: streamlined search saving
	$updatesearches = true;
	// 1.7.4: fix to search save logic
	if (!isset($bugbotsearches) || !is_array($bugbotsearches)) {$bugbotsearches = array();}
	if (bugbot_get_setting('save_selected_theme') == 'yes') {
		$bugbotsearches['theme_file_search_theme'] = $themesearch; $updatesearches = true;
	}
	if (bugbot_get_setting('save_last_searched') == 'yes') {
		$bugbotsearches['theme_file_search_keyword'] = $keyword; $updatesearches = true;
	}
	if (bugbot_get_setting('save_case_sensitive') == 'yes') {
		$bugbotsearches['theme_file_search_case'] = $searchcase; $updatesearches = true;
	}
	if ($updatesearches) {update_option('wp_bugbot_searches', $bugbotsearches);}

	// Get All the Themes Files
	if ($themesearch == "ALLTHEMES") {
		$themepath = get_theme_root()."/";
		$theme_files = bugbot_file_search_list_files($themepath);
	} else {
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
			if (substr($theme_file, $len) == $dns) {unset($theme_files[$i]);}
		}
		$i++;
	}

	echo '<div class="wrap" id="pagewrap" style="margin-right:0px !important;">';

	// Admin Notices Boxer
	if (function_exists('wqhelper_admin_notice_boxer')) {wqhelper_admin_notice_boxer();}

	// Search Interface Header
	// -----------------------
	$icon_url = plugins_url('images/wp-bugbot.png',__FILE__);
	echo "<center><table><tr><td><img src='".$icon_url."' width='96' height='96'></td><td width='32'></td>";
	echo '<td align="center"><h2>WP BugBot <i>v'.$bugbot['version'].'</i><br><br>'.__('Theme File Search','wp-bugbot').' </h2></td>';
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

	$themeref = "";

	$snipcount = 0; $found = false;
	echo "<div id='filesearchresults' style='width:100%;'>";
	foreach ($theme_files as $i => $theme_file) {

		if ($themesearch == "ALLTHEMES") {
			if ($themeref == '') {$firsttheme = true;} else {$firsttheme = false;}
			$oldthemeref = $themeref;
			$pos = stripos($theme_file, "/");
			if ($pos > 0) {
				$chunks = str_split($theme_file, $pos);
				$themeref = $chunks[0]; unset($chunks[0]);
				$theme_file = implode("", $chunks);
			}
			$slashcheck = substr($theme_file, 0, 1);
			if ($slashcheck == "/") {$theme_file = substr($theme_file, 1, (strlen($theme_file)-1));}
			// $pos = stripos($theme_file,"/");
			// if ($pos == '0') {$theme_file = substr($theme_file, 1, (strlen($theme_file)-1));}
			$real_file = $themepath.$themeref."/".$theme_file;
			if ($oldthemeref != $themeref) {
				// 1.7.9: correct position for not found message on all themes search
				if (!$found && !$firsttheme) {echo __('No results found for this theme.','wp-bugbot');}
				echo "<br><h3>".$themeref."</h3>";
				$found = false;
			}
		} else {$real_file = $themepath.$theme_file;}

		if ($bugbot['debug'] == '1') {echo "<!-- ".$themeref." - ".$real_file." - ".$themepath." - ".$theme_file." -->";}

		// check extension
		$extension = ''; $pathinfo = pathinfo($real_file);
		if (isset($pathinfo['extension'])) {$extension = $pathinfo['extension'];}

		// Read File
		$fh = fopen($real_file, 'r'); $filecontents = stream_get_contents($fh); fclose($fh);

		// Loop Occurrences
		$occurences = array();
		$occurences = bugbot_file_search_for_keyword($filecontents, $keyword, $searchcase);
		if (count($occurences) > 0) {

			$found = true;

			// --- File Header ---
			echo '<br><div style="display:inline-block;text-align:center;min-width:600px;"><b>'.__('File','wp-bugbot').': ';

			// 1.7.1: allow for file viewing if editor is disabled
			if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {
				echo '<a href="update-core.php?showfilecontents='.urlencode($theme_file).'&themefile=yes" style="text-decoration:none;">';
				echo $theme_file.'</a></b>';
				echo "<br>(".__('Link to file view only - file editing is disabled.','wp-bugbot').")";
			} elseif (in_array($extension, $editableextensions)) {
				if ($themesearch != "ALLTHEMES") {echo '<a href="theme-editor.php?file='.urlencode($theme_file).'&theme='.$theme->stylesheet.'&searchkeyword='.urlencode($keyword).'" style="text-decoration:none;">';}
				else {echo '<a href="theme-editor.php?file='.urlencode($theme_file).'&theme='.$themeref.'&searchkeyword='.urlencode($keyword).'"  style="text-decoration:none;">';}
				echo $theme_file.'</a></b>';
			} else {
				echo '<a href="theme-editor.php?showfilecontents='.urlencode($theme_file).'&themefile=yes" style="text-decoration:none;">';
				echo $theme_file.'</a></b>';
				echo "<br>(".__('').")";
			}

			if (!in_array($extension, $editableextensions)) {echo "<br>(".__('Theme file not editable - try changing your editable extensions from the sidebar.','wp-bugbot').")";}
			echo '</div>';

			// --- Line Header ---
			echo '<div style="display:inline-block;width:40px;text-align:center;float:left;">'.__('Line','wp-bugbot').'</div>';

			foreach ($occurences as $occurence) {

				// Snip Long Lines
				// ---------------
				if ($sniplinesat) {
					if (strlen($occurence['value']) > $sniplinesat) {
						$thisoccurence = $occurence['value'];
						$occurence['value'] = substr($occurence['value'], 0, $sniplinesat);
						$snipped = " <i><a href='javascript:void(0);' onclick='showsnipped(\"".$snipcount."\");'>[...".__('more','wp-bugbot')."...]</a></i>";
						$snipped .= "<div id='snip".$snipcount."' style='display:none;'><code>".substr($thisoccurence, $sniplinesat)."</code>";
						$snipped .= "<a href='javascript:void(0);' onclick='hidesnipped(\"".$snipcount."\");'>[...".__('less','wp-bugbot')."...]</a></div>";
						$snipcount++;
					} else {$snipped = "";}
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
					} else {
						// 1.8.0: fix for firstblock value
						$firstblock = '';
						$remainder = $occurence['value'];
					}
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
					$chunks = str_split($block, 80);
					$block = implode('<br>',$chunks);
				}
				echo $block;
				echo "</code>".$snipped."</span></td></tr></table>";

				echo "</div>";
			}
		}
	}

	// 1.7.5: move this line to correct position (not for each file)
	// 1.7.9: message here single theme search or last theme in all themes search
	if (!$found) {
		if ( ($themesearch != "ALLTHEMES") || (!$firsttheme) ) {
			echo __('No results found for this theme.','wp-bugbot');
		}
	}
	echo "</div>";

	echo "</div>";
	exit;
}

// --------------------------------
// === Theme Editor Search Form ===
// --------------------------------

function bugbot_theme_file_search_ui() {

	global $bugbot, $bugbotsearches, $wp_version;

	// get themes
	if (isset($_REQUEST['themefilesearch'])) {$themesearch = $_REQUEST['themefilesearch'];} else {$themesearch = '';}
	$themes = wp_get_themes();

	// get current theme
	if (version_compare($wp_version,'3.4', '<')) { //'
		$theme = get_theme_data(get_stylesheet_directory().'/style.css');
	} else {$theme = wp_get_theme();}

	if ($bugbot['debug'] == '1') {echo "<!-- Theme Dump: ".print_r($themes,true)." -->";}

	// Get Last Searched Values
	// ------------------------
	$lastsearchedkeyword = $lastsearchedtheme = $lastsearchedcase = '';
	if (isset($bugbotsearches['theme_file_search_keyword'])) {$lastsearchedkeyword = $bugbotsearches['theme_file_search_keyword'];}
	if (isset($bugbotsearches['theme_file_search_theme'])) {$lastsearchedtheme = $bugbotsearches['theme_file_search_theme'];}
	if ($lastsearchedtheme == '') {
		// fallback to currently active theme as default selection
		$currenttheme = wp_get_theme(); $lastsearchedtheme = $currenttheme->stylesheet;
	}
	if (isset($bugbotsearches['theme_file_search_case'])) {$lastsearchedcase = $bugbotsearches['theme_file_search_case'];}

	// Theme Search Bar
	// ----------------
	echo '<div id="themefilesearchbar" style="display:inline;width:100%;margin-bottom:10px;" class="alignright">';
	// 1.7.1: different destination action if editing is not allowed
	$formaction = 'theme-editor.php';
	if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {$formaction = 'update-core.php';}
	echo '<form action="'.$formaction.'" method="post" onSubmit="return checkthemekeyword();">';

	echo '<table><tr>';
	$icon_url = plugins_url('images/wp-bugbot.png',__FILE__);
	if (!isset($bugbot['search_page'])) {
		echo '<td width="48"><img src="'.$icon_url.'" width="48" height="48"></td>';
	}
	echo '<td><strong><label for="keyword"><a href="http://wordquest.org/plugins/wp-bugbot/" style="text-decoration:none;" target=_blank>WP BugBot ';
	// 1.7.4: change label prefix if using multiple search page
	if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {echo __('Theme','wp-bugbot');}
	else {echo __('Keyword','wp-bugbot');}
	echo ' '.__('Search','wp-bugbot').'</a>:</label></strong></td>';

	// 1.7.4: use separate keyword element ID for javascript check (themekeyword)
	if ((strstr($lastsearchedkeyword, "'")) && (strstr($lastsearchedkeyword, '"'))) {
		// $lastsearchedkeyword = str_replace('"','\"',$lastsearchedkeyword);
		$lastsearchedkeyword = htmlspecialchars($lastsearchedkeyword,ENT_QUOTES);
		echo '<td><input type="text" id="themekeyword" name="searchkeyword" value="'.$lastsearchedkeyword.'" style="width:200px;" size="35"></td>';
	} elseif (strstr($lastsearchedkeyword, "'")) {
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
	$theme_name = $theme['Name'];
	if (strlen($theme_name) > 40) {$theme_name = substr($theme_name, 0, 40)."...";}
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

// ------------------------
// === Core File Search ===
// ------------------------

function bugbot_core_file_do_search() {

	global $bugbot, $bugbotsearches;
	$bugbot['search_page'] = true;

	// TODO: better check/filter of search permissions here ?
	if (!current_user_can('manage_options')) {return;}

	// set time limit
	bugbot_set_time_limit();

	// get DoNotSearch Extensions
	// 1.7.0: use function call to get array
	$dnsarray = bugbot_get_donotsearch_extensions();

	// get Editable Extensions
	$editableextensions = bugbot_get_editable_extensions(null);

	// get Line Snip Length
	$sniplinesat = bugbot_get_setting('snip_long_lines_at');
	if ( (!is_numeric($sniplinesat)) || ($sniplinesat < 1) ) {$sniplinesat = false;}

	// print Wordwrap Styles
	bugbot_wordwrap_styles();

	// print show/hide snipped javascript
	bugbot_snipped_javascript();

	// get Search Request
	if (isset($_REQUEST['corefilesearch'])) {$coresearch = $_REQUEST['corefilesearch'];} else {$coresearch = 'ALLCORE';}
	if (isset($_REQUEST['searchkeyword'])) {$keyword = stripslashes($_REQUEST['searchkeyword']);} else {$keyword = '';}
	if (isset($_REQUEST['searchcase'])) {$searchcase = $_REQUEST['searchcase'];} else {$searchcase = '';}

	// Debug Output
	if ($bugbot['debug'] == '1') {
		echo "<!-- Core Search: ".$coresearch." - Keyword: ".$keyword." - Case: ";
		if ($searchcase == 'sensitive') {echo $searchcase;} else {echo "insensitive";}
		echo " -->";
	}

	// save Search Request
	// 1.7.9: streamlined search saving
	$updatesearches = false;
	// 1.7.4: fix to search save logic
	if (!isset($bugbotsearches) || !is_array($bugbotsearches)) {$bugbotsearches = array();}
	if (bugbot_get_setting('save_selected_dir') == 'yes') {$bugbotsearches['core_file_search_dir'] = $coresearch;}
	if (bugbot_get_setting('save_last_searched') == 'yes') {$bugbotsearches['core_file_search_keyword'] = $keyword;}
	if (bugbot_get_setting('save_case_sensitive') == 'yes') {$bugbotsearches['core_file_search_case'] = $searchcase;}
	if ($updatesearches) {update_option('wp_bugbot_searches', $bugbotsearches);}

	// Get the Core Files
	// ------------------
	if ($coresearch == "ALLCORE") {
		$coredir[0] = ABSPATH;
		$coredir[1] = ABSPATH.'/wp-admin/';
		$coredir[2] = ABSPATH.'/wp-includes/';
		$corefiles[0] = bugbot_file_search_list_files($coredir[0],false);
		$corefiles[1] = bugbot_file_search_list_files($coredir[1]);
		$corefiles[2] = bugbot_file_search_list_files($coredir[2]);
		$core_files = array_merge($corefiles[0], $corefiles[1]);
		$core_files = array_merge($core_files, $corefiles[2]);
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

	if ($bugbot['debug'] == '1') {echo "<!-- Core File List Dump: "; print_r($core_files); echo " -->";}

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
	$icon_url = plugins_url('images/wp-bugbot.png',__FILE__);
	echo "<center><table><tr><td><img src='".$icon_url."' width='96' height='96'></td><td width='32'></td>";
	echo '<td align="center"><h2>WP BugBot <i>v'.$bugbot['version'].'</i><br><br>'.__('Core File Search','wp-bugbot').'</h2></td>';
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
		echo __('Searched','wp-bugbot')." ".count($core_files)." ".__('files from ALL Core files for','wp-bugbot')." ";
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
	// TODO: check/fix div display width here ?
	echo "<div id='filesearchresults' style='width:100%;'>";
	$snipcount = 0; $coreref = "";
	foreach ($core_files as $core_file) {

		$found = false;

		$real_file = $coredir.$core_file;

		// 1.5.0: fix for file path for all core search
		if ($coresearch == "ALLCORE") {
			if (in_array($core_file, $corefiles[0])) {
				$real_file = $coredir[0].$core_file;
				$searchdir = urlencode('/');
			}
			if (in_array($core_file,$corefiles[1])) {
				$real_file = $coredir[1]. $core_file;
				$searchdir = urlencode('/wp-admin/');
			}
			if (in_array($core_file, $corefiles[2])) {
				$real_file = $coredir[2].$core_file;
				$searchdir = urlencode('/wp-includes/');
			}
		} else {
			$searchdir = urlencode($coresearch);
			$real_file = $coredir.$core_file;
		}

		$real_file = str_replace('//', '/', $real_file);
		if (strstr($real_file, '\\')) {$real_file = str_replace('/', '\\', $real_file);}

		if ($bugbot['debug'] == '1') {echo "<!-- ".$coredir." --- ".$real_file." --- ".$core_file." -->";}

		// Read File
		$fh = fopen($real_file,'r'); $filecontents = stream_get_contents($fh); fclose($fh);

		// Loop Occurrences
		$occurences = array();
		$occurences = bugbot_file_search_for_keyword($filecontents, $keyword, $searchcase);

		if (count($occurences) > 0) {

			$found = true;

			// --- File Header ---
			echo '<br><div style="display:inline-block;text-align:center;min-width:600px;"><b>'.__('File','wp-bugbot').': ';
			echo '<a href="update-core.php?showfilecontents='.urlencode($core_file).'&corefile=yes&searchdir='.$searchdir.'&searchkeyword='.urlencode($keyword).'"  style="text-decoration:none;">';
			echo $core_file.'</a></b></div>';

			// --- Line Header ---
			echo '<div style="display:inline-block;width:40px;text-align:center;float:left;">Line</div>';

			foreach ($occurences as $occurence) {

				// Snip Long Lines
				if ($sniplinesat) {
					if (strlen($occurence['value']) > $sniplinesat) {
						$thisoccurence = $occurence['value'];
						$occurence['value'] = substr($occurence['value'], 0, $sniplinesat);
						$snipped = " <i><a href='javascript:void(0);' onclick='showsnipped(\"".$snipcount."\");'>[...".__('more','wp-bugbot')."...]</a></i>";
						$snipped .= "<div id='snip".$snipcount."' style='display:none;'><code>".substr($thisoccurence, $sniplinesat)."</code>";
						$snipped .= "<a href='javascript:void(0);' onclick='hidesnipped(\"".$snipcount."\");'>[...".__('less','wp-bugbot')."...]</a></div>";
						$snipcount++;
					} else {$snipped = "";}
				}

				echo "<div style='text-align:left;vertical-align:top;'>";

				echo "<table><tr><td style='text-align:center;vertical-align:top;width:40px;min-width:40px;'>";
				if ($coresearch != "ALLCORE") {echo '<a href="update-core.php?showfilecontents='.urlencode($core_file).'&corefile=yes&searchkeyword='.urlencode($keyword).'&searchdir='.$searchdir.'&scrolltoline='.$occurence['line'].'">'.$occurence['line'].'</a>: ';}
				else {echo '<a href="update-core.php?showfilecontents='.urlencode($core_file).'&corefile=yes&searchkeyword='.urlencode($keyword).'&searchdir='.$searchdir.'&scrolltoline='.$occurence['line'].'">'.$occurence['line'].'</a>: ';}
				echo "</td>";

				// Output Code Block
				echo "<td class='wordwrapcell'><span style='background-color:#eeeeee;'><code>";
				if (stristr($occurence['value'],$keyword)) {
					$position = stripos($occurence['value'], $keyword);
					if ($position > 0) {
						$chunks = str_split($occurence['value'], $position);
						$firstblock = $chunks[0]; unset($chunks[0]);
						$remainder = implode('', $chunks);
					} else {
						// 1.8.0: fix for firstblock value
						$firstblock = '';
						$remainder = $occurence['value'];
					}
					$chunks = str_split($remainder, strlen($keyword));
					$kdisplay = $chunks[0]; unset($chunks[0]);
					$lastblock = implode('', $chunks);

					$block = htmlspecialchars($firstblock);
					$block .= "<span style='background-color:#F0F077'>";
					$block .= htmlspecialchars($kdisplay);
					$block .= "</span>";
					$block .= htmlspecialchars($lastblock);
				}
				else {$block= htmlspecialchars($occurence['value']);}
				if ((!strstr($block,' ')) && (strlen($block) > 80)) {
					$chunks = str_split($block, 80);
					$block = implode('<br>', $chunks);
				}
				echo $block;
				echo "</code>".$snipped."</span></td></tr></table>";

				echo "</div>";
			}
		}
	}
	// 1.7.5: move this line to correct position (not for each file)
	if (!$found) {echo __('No results found for this directory.','wp-bugbot');}
	echo "</div>";

	echo "</div>";
	exit;
}


// ----------------------------------
// === Wordpress Core Search Form ===
// ----------------------------------

function bugbot_core_file_search_ui() {

	global $bugbot, $bugbotsearches;
	clearstatcache();

	// 1.7.0: fix to undefine variable warning
	$coresearch = '';
	if (isset($_REQUEST['corefilesearch'])) {$coresearch = $_REQUEST['corefilesearch'];}
	$coredirs = array('root', 'wp-admin', 'wp-includes');

	// 1.7.0: replace static dir list with scan for actual directories
	$admindirs[0] = '/wp-admin/';
	$i = 1; $admindirfiles = scandir(ABSPATH.'/wp-admin');
	foreach ($admindirfiles as $dirfile) {
		if ( ($dirfile != '.') && ($dirfile != '..') ) {
			if (is_dir(ABSPATH.'/wp-admin/'.$dirfile)) {$admindirs[$i] = '/wp-admin/'.$dirfile; $i++;}
		}
	}
	if ($bugbot['debug'] == '1') {echo "<!-- Admin Dirs: "; print_r($admindirs); echo " -->";}

	$includesdirs[0] = '/wp-includes/';
	$i = 1; $includesdirfiles = scandir(ABSPATH.'/wp-includes');
	foreach ($includesdirfiles as $dirfile) {
		if ( ($dirfile != '.') && ($dirfile != '..') ) {
			if (is_dir(ABSPATH.'/wp-includes/'.$dirfile)) {$includesdirs[$i] = '/wp-includes/'.$dirfile; $i++;}
		}
	}
	if ($bugbot['debug'] == '1') {echo "<!-- Includes Dirs: "; print_r($includesdirs); echo " -->";}

	$lastsearchedkeyword = $lastsearcheddir = $lastsearchedcase = '';
	if (isset($bugbotsearches['core_file_search_keyword'])) {$lastsearchedkeyword = $bugbotsearches['core_file_search_keyword'];}
	if (isset($bugbotsearches['core_file_search_dir'])) {$lastsearcheddir = $bugbotsearches['core_file_search_dir'];}
	if (isset($bugbotsearches['core_file_search_case'])) {$lastsearchedcase = $bugbotsearches['core_file_search_case'];}

	echo '<div id="corefilesearchbar" style="display:inline;width:100%;margin-bottom:10px;" class="alignright"><form action="update-core.php" method="post" onSubmit="return checkcorekeyword();">';

	echo '<table><tr>';
	$icon_url = plugins_url('images/wp-bugbot.png',__FILE__);
	if (!isset($bugbot['search_page'])) {
		echo '<td width="48"><img src="'.$icon_url.'" width="48" height="48"></td>';
	}
	echo '<td><strong><label for="keyword">';
	echo '<a href="http://wordquest.org/plugins/wp-bugbot/" style="text-decoration:none;" target=_blank>WP BugBot ';
	// 1.7.4: change label prefix if using multiple search page
	if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {echo __('Core','wp-bugbot');}
	else {echo __('Keyword','wp-bugbot');}
	echo ' '.__('Search','wp-bugbot').'</a>:</label></strong></td>';

	// 1.7.4: use separate keyword element ID for javascript check (corekeyword)
	if ((strstr($lastsearchedkeyword, "'")) && (strstr($lastsearchedkeyword, '"'))) {
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
	echo "<script language='javascript'>
	function checkcorekeyword() {
		if (document.getElementById('corekeyword').value == '') {
			alert('".__('Please enter a keyword to search core for.','wp-bugbot')."'); return false;
		}
	}</script>";
}


// ==================
// --- Error Logs ---
// ==================

// ---------------
// Error Log Panel
// ---------------
// 1.7.9: moved error log panel to separate function
function bugbot_error_log_panel() {

	// 1.7.0: added error log search
	echo "<div id='errorlogsearch' style='padding-left:20px;padding-bottom:30px;'>";

	// 1.7.3: error log header in any case
	echo "<h3>".__('PHP Error Logs','wp-bugbot')."</h3>";

	// 1.7.9: use get error log filenames function
	$lognames = bugbot_get_error_log_filenames();

	// 1.7.3: filter the log names searched for
	$filterlognames = apply_filters('bugbot_error_log_search', $lognames);
	if (is_array($filterlognames)) {$lognames = $filterlognames;} else {$lognames = array();}

	// 1.7.3: output the log filenames searched for
	if (count($lognames) > 0) {
		echo __('Searching your current installation for the following filenames','wp-bugbot').':<br>';
		$displaylogs = implode(', ', $lognames);
		echo $displaylogs."...<br>";
	} else {echo __('Searching for Error Logs has been disabled by filter.','wp-bugbot').'<br>';}

	// 1.7.1: fix to empty variable to array
	$errorlogs = array();
	// 1.7.3: for subdirectory installs, also check for error logs in parent directory
	if ( (@file_exists(dirname(ABSPATH).'/wp-config.php'))
	  && (!@file_exists(dirname(ABSPATH).'/wp-settings.php')) ) {
	  	$parentfiles = scandir(dirname(ABSPATH));
	  	foreach ($parentfiles as $file) {
	  		if ( ($file != '.') && ($file != '..') ) {
				foreach ($lognames as $logname) {
					if (substr($file, -(strlen($logname)), strlen($logname)) == $logname) {
						$errorlogs[$file] = dirname(ABSPATH).'/'.$file;
					}
				}
			}
	  	}
	}
	// do the main recursive search for log files
	$files = bugbot_file_search_list_files(ABSPATH);
	foreach ($files as $file) {
		foreach ($lognames as $logname) {
			if (substr($file, -(strlen($logname)), strlen($logname)) == $logname) {
				$errorlogs[$file] = ABSPATH.$file;
			}
		}
	}
	// print_r($errorlogs);

	if (count($errorlogs) > 0) {
		// 1.7.1: number of log lines to parse
		echo "<center><table><tr>";
		echo "<td><b>".__('Process Last x Lines of Error Log','wp-bugbot').":</b></td>";
		echo "<td width='30'></td>";
		echo "<td><input id='loglines' type='number' value='200' style='width:70px;'></td>";
		echo "<td width='10'></td>";
		echo "<td>(".__('0 or blank for all').".)</td>";
		echo "</tr></table></center>";

		foreach ($errorlogs as $display => $path) {
			$displayurl = admin_url('admin-ajax.php').'?action=bugbot_view_error_log&path='.urlencode($path).'&lines=';
			echo "<a href='".$displayurl."' target='errorlogframe' onclick='this.href+=document.getElementById(\"loglines\").value'>".$display."</a><br>";
		}
	} else {echo __('No Error Logs were found.','wp-bugbot');}

	// error log contents frame
	echo "<div id='errorlogwrap' style='display:none;'><h4>".__('Error Log Contents')."</h4>";
	echo "<iframe src='javascript:void(0);' name='errorlogframe' id='errorlogframe' width='650px' height='650px'></iframe>";
	echo "</div>";

	echo "</div>"; // close error log search
}

// -------------------
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
			if ($charCounter > 1) { // if there was anything on the line
				// if (!$newLine) {echo "<br>";} // prints missing "\n" before last *printed* line
				$thisline = readNotSeek( $v, $charCounter ); // gets current line

				// modify original function to store for later display
				if (strstr($thisline,'] ')) {
					$pos = strpos($thisline,'] ') + 2;
					$datetime = trim(substr($thisline,0,$pos));
					$datetime = str_replace('[', '', $datetime);
					$datetime = str_replace(']', '', $datetime);
					$error = substr($thisline, $pos, strlen($thisline));
					if (in_array($error,$errors)) {$errortimes[$error][] = $datetime;}
					else {$errortimes[$error][0] = $datetime; $errors[] = $error;}
				} else {
					if (!$newLine) {echo "<br>";}
					echo $thisline;
				}
				$lines++;
			}
			// 1.7.1: handle limited or unlimited lines
			if ( ($maxlines > 0) && ($lines > $maxlines) ) {break;}
		}
		fclose($v);
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
			agree = confirm('".__('Delete all occurrences of this error in log file?','wp-bugbot')."');
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
					$filepath = substr($error, $posa, ($posb-$posa));
					$temp = substr($error, ($posb + 9), strlen($error));
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
					$displayerror = str_replace($search, $replace, $displayerror);
				}

				$displayerror = str_ireplace('PHP Fatal Error','<span class="fatal">Fatal Error</span>', $displayerror);
				$displayerror = str_ireplace('PHP Parse Error','<span class="fatal">Parse Error</span>', $displayerror);
				$displayerror = str_ireplace('PHP Warning','<span class="warning">Warning</span>', $displayerror);
				$displayerror = str_ireplace('PHP Notice','<span class="notice">Notice</span>', $displayerror);
				$displayerror = str_ireplace('PHP Deprecated','<span class="deprecated">Deprecated</span>', $displayerror);
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

// --------------------------
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
		$lines = explode("\n", $filecontents);
		if ($line !== 0) {echo "<span style='background-color:#DDDDDD;'>".$lines[$line-2]."</span><br>";}
		echo "<span style='background-color:#EEEE00;'>".$lines[$line-1]."</span><br>";
		if (count($lines) > ($line - 1)) {echo "<span style='background-color:#DDDDDD;'>".$lines[$line]."</span><br>";}
	} else {echo __('File Not Found','wp-bugbot').": ".$file;}
	exit;
}

// --------------------------
// AJAX Delete Error From Log
// --------------------------
// 1.7.0: added error removal function
add_action('wp_ajax_bugbot_delete_error','bugbot_delete_error');
function bugbot_delete_error() {
	if (!current_user_can('manage_options')) {exit;}

	if (isset($_REQUEST['path'])) {$path = $_REQUEST['path'];} else {exit;}
	if (!file_exists($path)) {exit;}
	if (isset($_REQUEST['error'])) {$error = stripslashes($_REQUEST['error']);} else {exit;}
	if (isset($_REQUEST['errornum'])) {$errornum = $_REQUEST['errornum'];} else {exit;}

	echo $error.'-----<br>';
	// make sure this is a log file
	$lognames = bugbot_get_error_log_filenames();
	$pathinfo = pathinfo($path);
	if (!in_array($pathinfo['basename'], $lognames)) {exit;}

	$newlogfile = ''; $fh = fopen($path, 'r');
	$line = fgets($fh); if (!$line) {exit;}
	while ($line) {
		if (!strstr($line,$error)) {$newlogfile .= $line;}
		$line = fgets($fh);
	}
	fclose($fh);
	// echo '<br>-----'.$newlogfile;

	if (strlen($newlogfile) > 0) {$fh = fopen($path, 'w'); fwrite($fh, $newlogfile); fclose($fh);}
	else {@unlink($path);} // delete log if now empty

	echo "<script>parent.document.getElementById('errornum-".$errornum."').style.display = 'none';</script>";
	exit;
}

// -----------------------
// Get Error Log Filenames
// -----------------------
// 1.7.9: create separate helper function
function bugbot_get_error_log_filenames() {
	// 1.7.5: add php_errorlog to list of possible log names
	$lognames = array('error.log', 'php_errors.log', 'php_errorlog');
	$errorlog = basename(ini_get('error_log'));
	if ($errorlog && !in_array($errorlog, $lognames)) {$lognames[] = $errorlog;}
	return $lognames;
}


// ===============
// --- Scripts ---
// ===============

// ---------------------
// Output Search Sidebar
// ---------------------
function bugbot_search_sidebar() {
	global $bugbot;
	// $args = array('bugbot','wp-bugbot','free','wp-bugbot','replace','WP BugBot',$bugbot['version']);
	$args = array($bugbot['slug'], 'replace'); // (trimmed arguments)
	if (function_exists('wqhelper_sidebar_floatbox')) {
		wqhelper_sidebar_floatbox($args);
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

// ------------------------------
// Show / Hide Snipped Javascript
// ------------------------------
function bugbot_snipped_javascript() {
	echo "<script language='javascript' type='text/javascript'>
	function showsnipped(snipid) {var snipdiv = 'snip'+snipid; document.getElementById(snipdiv).style.display = '';}
	function hidesnipped(snipid) {var snipdiv = 'snip'+snipid; document.getElementById(snipdiv).style.display = 'none';}
	</script>";
}


// ===========================
// --- Code Editor Toolbar ---
// ===========================

// ------------------------------
// Add Javascript to Admin Footer
// ------------------------------
// add_action('admin_footer', 'bugbot_editor_admin_footer');
function bugbot_editor_admin_footer() {
	// 1.7.9: use pagenow global for admin URL matching
	// if ( (preg_match('|theme-editor.php|i', $_SERVER["REQUEST_URI"]))
	//   || (preg_match('|plugin-editor.php|i', $_SERVER["REQUEST_URI"])) ) {}
	global $pagenow, $wp_version;
	echo "<!-- PAGENOW: ".$pagenow." -->";
	if ( ($pagenow == 'theme-editor.php') || ($pagenow == 'plugin-editor.php') ) {
		if (version_compare($wp_version, '4.9.0', '>=')) {
			// 1.7.9: remove directory selector for 4.9+ (now built-in)
			bugbot_code_editor_toolbar();
		} else {
			// 1.7.9: load legacy editor toolbar for 4.9-
			bugbot_directory_selector();
			bugbot_code_editor_toolbar(true);
		}
	}
}

// ------------------------
// Editor Search Javascript
// ------------------------
// 1.7.9: use generic page element IDs for editor toolbar
function bugbot_editor_legacy_javascript() {

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
	var ajbES_insMe = document.getElementById("code-editor-toolbar");
	ajbES_insParent.insertBefore(ajbES_insMe, ajbES_insBeforeMe);

	function ajbES_Search() {
	  var a = document.getElementById("code-editor-toolbar-search-input");
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
	  var a = document.getElementById("code-editor-toolbar-line-number");
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
		document.getElementById('code-editor-toolbar-line-number').value='';
	  } else if (a=='linenum') {
		document.getElementById('code-editor-toolbar-search-input').value='';
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

// -------------------
// Code Editor Toolbar
// -------------------
// 1.7.9: use generic page element IDs for editor toolbar
function bugbot_code_editor_toolbar($legacy=false) {

	// Editor Search Buttons
	// ---------------------
	// 1.7.0: fix for undefined index warnings
	$scrolltoline = ''; if (isset($_REQUEST['scrolltoline'])) {$scrolltoline = $_REQUEST['scrolltoline'];}
	$searchkeyword = ''; if (isset($_REQUEST['searchkeyword'])) {$searchkeyword = $_REQUEST['searchkeyword'];}

	if ($legacy) {$jump_function = 'ajbES_FindLineNumber();'; $search_function = 'ajbES_Search();';}
	else {
		// TODO: Code Mirror functions
		$jump_function = '';
		$search_function = '';
	}

	echo '
	<div id="code-editor-toolbar" style="display: inline;">
		<div style="display:inline">
			<form style="display:inline" action="#" onsubmit="'.$jump_function.' return false;">
				<input id="code-editor-toolbar-line-number" type="text" value="'.$scrolltoline.'" style="width: 40px; margin-right:20px; text-align: right;" />
				<input type="button"  class="button-secondary" value="'.__('Jump To Line','wp-bugbot').'" onclick="'.$jump_function.' return false;" />
			</form>
		</div>
		<div style="display:inline; margin-left:150px;">
			<form style="display:inline" action="#" onsubmit="'.$search_function.' return false;">
				<input id="code-editor-toolbar-search-input" type="text" value="'.$searchkeyword.'" style="width: 200px; margin-right:20px;" />
				<input type="button" class="button-secondary" value="'.__('Find In Code','wp-bugbot').'" onclick="'.$search_function.' return false;" />
			</form>
		</div>
	</div>';

	if ($legacy) {
		echo "<script type=\"text/javascript\"><!--//--><![CDATA[//><!--\n";
			bugbot_editor_legacy_javascript();
		echo " //--><!]]></script>\n";
	} else {

		// 1.7.9: [not working!] added code mirror addons
		// ref: https://make.wordpress.org/core/tag/codemirror/

		// use Code Mirror as global object
		echo "<script>window.CodeMirror = window.cm =  wp.CodeMirror;</script>";

		// add Code Mirror addons
		$dialog_script_path = dirname(__FILE__).'/scripts/code-editor-dialog.js';
		$dialog_script_version = filemtime($dialog_script_path);
		$dialog_script_url = plugins_url('scripts/code-editor-dialog.js', __FILE__);
		$dialog_script_url = add_query_arg('ver', $dialog_script_version, $dialog_script_url);
		echo "<script type='text/javascript' src='".$search_dialog_url."'>";

		$search_script_path = dirname(__FILE__).'/scripts/code-editor-search.js';
		$search_script_version = filemtime($search_script_path);
		$search_script_url = plugins_url('scripts/code-editor-search.js', __FILE__);
		$search_script_url = add_query_arg('ver', $search_script_version, $search_script_url);
		echo "<script type='text/javascript' src='".$search_script_url."'>";

		$jump_script_path = dirname(__FILE__).'/scripts/code-editor-jump-to-line.js';
		$jump_script_version = filemtime($jump_script_path);
		$jump_script_url = plugins_url('scripts/code-editor-jump-to-line.js', __FILE__);
		$jump_script_url = add_query_arg('ver', $jump_script_version, $jump_script_url);
		echo "<script type='text/javascript' src='".$jump_script_url."'>";
	}
}

// ------------------
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
				if (strstr($thisfile, $pluginbasepath)) {$plugin = $pluginkey;}
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
		$editableextensions = bugbot_get_editable_extensions('plugin');
		foreach ($pluginfiles as $pluginfile) {
			$pathinfo = pathinfo($pluginfile);
			$plugindir = $pathinfo['dirname'];
			// limit to editable extensions to prevent non-editable screen
			if (in_array($pathinfo['extension'], $editableextensions)) {
				if ($plugindir == '.') {
					$pluginfile = $pluginbasefile;
					$plugindir = '(plugin root)';
				}
				if (!in_array($plugindir,$dirarray)) {
					// echo $plugindir.'<br>';
					$plugindir = str_replace('\\', '/', $plugindir);
					$dirarray[] = $plugindir;
					$pluginfile = str_replace('\\', '/', $pluginfile);
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
		// $extensions = apply_filters('wp_theme_editor_filetypes',$extensions,$theme);
		// 1.7.9: use modified function for this purpose
		$extensions = bugbot_get_editable_extensions('theme');
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
					$themedir = str_replace('\\', '/', $themedir);
					$dirarray[] = $themedir;
					$themefile = str_replace('\\', '/', $themefile);
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


		// Insert Directory Selection Div
		// ------------------------------
		// 1.5.0: added this to insert directory selector
		// 1.7.9: moved here from editor search javascript
		echo 'if (document.getElementById("dirselectdiv")) {
			var dirselect_insBeforeMe = document.getElementById("templateside");
			var dirselect_insParent = dirselect_insBeforeMe.parentNode;
			var dirselect_insMe = document.getElementById("dirselectdiv");
			dirselect_insParent.insertBefore(dirselect_insMe, dirselect_insBeforeMe);
		}';

	}
}

