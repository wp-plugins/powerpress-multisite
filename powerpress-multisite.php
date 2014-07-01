<?php
/*
 * Plugin Name: Blubrry PowerPress MultiSite add-on
 * Plugin URI: http://create.blubrry.com/resources/powerpress/powerpress-multisite-addon/
 * Description: Add MultiSite functionality for managing PowerPress, requires MultiSite and PowerPress to be installed.
 * Version: 0.1
 * Author: Angelo Mandato, Blubrry
 * Author URI: http://www.blubrry.com
 * License: GPL2
 
Requires at least: 3.8
Tested up to: 3.9.1
Text Domain: powerpress-multisite
Change Log: See readme.txt for complete change log
Contributors: Angelo Mandato, CIO RawVoice and host of the PluginsPodcast.com
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt

Copyright 2009-2014 Angelo Mandato, CIO RawVoice and host of the Plugins Podcast (http://www.pluginspodcast.com)
 */

if( !function_exists('add_action') )
	die("access denied.");
	
// WP_PLUGIN_DIR (REMEMBER TO USE THIS DEFINE IF NEEDED)
define('POWERPRESS_MULTISITE_VERSION', '0.1' );

// Translation support:
if ( !defined('POWERPRESS_MULTISITE_ABSPATH') )
	define('POWERPRESS_MULTISITE_ABSPATH', dirname(__FILE__) );

// Translation support loaded:
load_plugin_textdomain('powerpress-multisite', // domain / keyword name of plugin
		POWERPRESS_MULTISITE_ABSPATH .'/languages', // Absolute path
		basename(POWERPRESS_MULTISITE_ABSPATH).'/languages' ); // relative path in plugins folder
	
class PowerPressMultiSitePlugin {

		var $m_settings = array();
		
    public function __construct()  
    {  
			// Options , for future use when we create admin settings we can tweak this
			$this->m_settings['nofollow_before'] = false;
			
			// Init funtions here
			//add_filter('', );
			add_filter('manage_sites_action_links', array($this, 'manage_sites_action_links'),  10, 3);
			//add_action( 'admin_menu', array($this, 'admin_menu') );
			//
			add_action( 'network_admin_menu', array( $this, 'admin_menu' ) );
			add_action('admin_init', array( $this, 'admin_init'), 9 );
    }  
		
		// 'manage_sites_action_links', array_filter( $actions ), $blog['blog_id'], $blogname
		public function manage_sites_action_links($actions, $blog_id, $blogname)
		{
			$actions['powerpress']	= "<span class='view'><a href='" . esc_url( network_admin_url( 'sites.php?page=powerpress-multisite&amp;blog_id=' ). $blog_id ) . "' rel='permalink'>" . __( 'PowerPress' ) . '</a></span>';
			// edit_link = admin_url('admin.php?page=powerpress/powerpressadmin_categoryfeeds.php&amp;from_categories=1&amp;action=powerpress-editcategoryfeed&amp;cat=') . $cat_id;
			return $actions;
		}
		
		public function admin_menu()
		{
			add_submenu_page( 'sites.php', __( 'PowerPress', 'powerpress-multisite' ), __( 'PowerPress', 'powerpress-multisite' ), 'manage_network_options', 'powerpress-multisite', array( $this, 'powerpress_multisite_page' ) );
		}
		
		function powerpress_multisite_page() {
			global $wpdb, $wp_roles, $current_user, $current_site, $is_subdomain;
			
			if( isset($_REQUEST['blog_id']) )
			{
				require_once( POWERPRESS_ABSPATH .'/powerpressadmin-basic.php');
				$Settings = get_blog_option( $_REQUEST['blog_id'], 'powerpress_general' );
				
				$this->_admin_page_header('powerpress-multisite', 'powerpress-multisite-save-site-settings');
?>
<input type="hidden" name="action" value="powerpress-multisite-save-site-settings" />
<input type="hidden" name="blog_id" value="<?php echo $_REQUEST['blog_id']; ?>" />
<div id="powerpress_admin_header">
<h2><?php echo __('Blubrry PowerPress Site Settings', 'powerpress'); ?></h2> 
</div>
<div>
<?php
				powerpressadmin_edit_blubrry_services($Settings, network_admin_url('sites.php?action=powerpress-multisite&amp;ajax=1&amp;blog_id='.$_REQUEST['blog_id']), 'powerpress-multisite-ajax-account');
				powerpressadmin_edit_media_statistics($Settings);
?>
</div>
<div class="clear"></div>
<?php

				powerpress_admin_page_footer(true);
			}
			else
			{
				
				$Settings = get_site_option( 'powerpress_multisite', array('services_multisite_only'=>false) );
				
				$this->_admin_page_header('powerpress-multisite', 'powerpress-multisite-save-network-settings');
?>
<input type="hidden" name="action" value="powerpress-multisite-save-network-settings" />
<div id="powerpress_admin_header">
<h2><?php echo __('Blubrry PowerPress Network Settings', 'powerpress-multisite'); ?></h2>
</div>
<div>
<?php

?>

<table class="form-table">
<tr valign="top">
<th scope="row"><label for="blogname"><?php echo htmlspecialchars(__('Services & Stats', 'powerpress-multisite')); ?></label></th>
<td><fieldset><legend class="screen-reader-text"><span><?php echo htmlspecialchars(__('Services & Stats', 'powerpress-multisite')); ?></span></legend><label for="users_can_register">
<input name="PowerPress[services_multisite_only]" type="checkbox" id="services_multisite_only" value="1" <?php if( !empty($Settings['services_multisite_only']) ) {echo 'checked';} ?>  />
<?php echo htmlspecialchars(__('Managed by Network', 'powerpress-multisite')); ?></label>
</fieldset>
<p><?php echo __('When checked, only the Network Admin interface can configure services and statistics.', 'powerpress-multisite'); ?>
</p>
</td>
</tr>

</table>


</div>
<?php
				powerpress_admin_page_footer(true);
			}
		}
		
		public static function edit_blubrry_services($General)
		{
			//$url = get_site_url();
			//if( function_exists('domain_mapping_siteurl') )
			//	$url = domain_mapping_siteurl( false );
			//echo $url;
			
		$DisableStatsInDashboard = false;
		if( !empty($General['disable_dashboard_stats']) )
			$DisableStatsInDashboard = true;
?>
<h3><?php echo htmlspecialchars(__('Services & Statistics', 'powerpress')); ?> </h3>
<div style="margin-left: 40px;">
	<?php
	if( !empty($General['blubrry_program_keyword']) )
	{
		// Check that the redirect is in the settings...
		$RedirectURL = 'http://media.blubrry.com/'.$General['blubrry_program_keyword'].'/';
		$Error = true;
		if( stristr($General['redirect1'], $RedirectURL ) )
			$Error = false;
		else if( stristr($General['redirect2'], $RedirectURL ) )
			$Error = false;
		else if( stristr($General['redirect3'], $RedirectURL ) )
			$Error = false;
		
		
		
		if( $Error )
		{
	?>
	<p style="font-weight: bold; color: #CC0000;">
	<?php 
		echo __('Statistics are not implemented on this site.', 'powerpress');
		?>
	</p>
	<?php
		}
		else
		{
	?>
	<p style="font-weight: bold;">
	<img src="<?php echo powerpress_get_root_url(); ?>images/Check.png" style="width: 25px; height: 20px;"  alt="<?php echo __('Enabled!', 'powerpress'); ?>" />
	<?php 
		if( empty($General['blubrry_hosting']) || $General['blubrry_hosting'] === 'false' )
			echo __('Statistics Enabled!', 'powerpress');
		else
			echo __('Statistics and Media Hosting Enabled!', 'powerpress');
		?>
	</p>
	<?php
		}
	}
	else
	{
	?>
	<p>
	<?php echo __('No services have been configured. Please contact your Network Administrator.', 'powerpress'); ?> 
	</p>
<?php
	}
	?>
</div>

<div style="margin-left: 40px;">
	<p style="margin-top: 10px;">
	<input name="DisableStatsInDashboard" type="checkbox" value="1"<?php if( $DisableStatsInDashboard == true ) echo ' checked'; ?> />
	<?php echo __('Remove Statistics from WordPress Dashboard', 'powerpress'); ?></p>
</div>
<?php
		}
		
		// Admin page, header
		public function _admin_page_header($page=false, $nonce_field = 'powerpress-multisite-save-network-settings')
		{
			if( !$page )
				$page = 'powerpress-multisite';
?>
<div class="wrap" id="powerpress_settings">
<?php
			if( $nonce_field )
			{
?>
<form enctype="multipart/form-data" method="post" action="<?php echo network_admin_url( 'sites.php?page='.$page) ?>">
<?php
				wp_nonce_field($nonce_field);
			}
	
			//powerpress_page_message_print();
		}
		
		public function admin_init()
		{
			$action = (isset($_GET['action'])?$_GET['action']: (isset($_POST['action'])?$_POST['action']:false) );
			if( !empty($action) )
			{
				switch( $action )
				{
					case 'powerpress-multisite-save-network-settings': {
						check_admin_referer('powerpress-multisite-save-network-settings');
						$Settings = array();
						if( !empty($_POST['PowerPress']) )
							$Settings = $_POST['PowerPress'];
							
						if( empty($Settings['services_multisite_only']) )
							$Settings['services_multisite_only'] = false;
						
						update_site_option('powerpress_multisite', $Settings);
					}; break;
					case 'powerpress-multisite-save-site-settings': {
						
						check_admin_referer('powerpress-multisite-save-site-settings');
						
						$blog_id = $_POST['blog_id'];
						
						$Settings = get_blog_option($blog_id, 'powerpress_general');
						if( empty($_POST['DisableStatsInDashboard']) )
							$Settings['disable_dashboard_stats'] = false;
						else
							$Settings['disable_dashboard_stats'] = true;
							
						$NewSettings = $_POST['General'];
						while( list($index,$value) = each($NewSettings) )
						{
							$Settings[$index] = $value;
						}
							
						update_blog_option($blog_id, 'powerpress_general', $Settings);
						
						// Redirect to the sites.php page...
						//wp_redirect('categories.php?message=3');
						wp_redirect( network_admin_url( 'sites.php' ) );
						exit;
					}; break;
					case 'powerpress-multisite': {
						if( !empty($_POST['ajax']) || !empty($_GET['ajax'])  )
						{
							check_admin_referer('powerpress-multisite-ajax-account');
							$blog_id = (isset($_GET['blog_id'])?$_GET['blog_id']: (isset($_POST['blog_id'])?$_POST['blog_id']:false) );
							$Step = 1;
							$Save = false;
							$Close = false;
							$Programs = false;
							$Password = '';
							$Settings = array('blubrry_username'=>'', 'blubrry_auth'=>'');
							
							// save settings here....
							if( isset($_POST['Password']) && isset($_POST['Settings']) )
							{
								$Password = $_POST['Password'];
								$SaveSettings = $_POST['Settings'];
								$Password = powerpress_stripslashes($Password);
								$SaveSettings = powerpress_stripslashes($SaveSettings);
								$Error = '';
			
								if( !empty($_POST['Remove']) )
								{
									$SaveSettings['blubrry_username'] = '';
									$SaveSettings['blubrry_auth'] = '';
									$SaveSettings['blubrry_program_keyword'] = '';
									$SaveSettings['blubrry_hosting'] = false;
									$Step = 3;
									$Save = true;
								}
								else
								{
									$Programs = array();
									$ProgramHosting = array();
										
										// Anytime we change the password we need to test it...
									$auth = base64_encode( $SaveSettings['blubrry_username'] . ':' . $Password );
									$json_data = false;
									$api_url_array = powerpress_get_api_array();
									while( list($index,$api_url) = each($api_url_array) )
									{
										$req_url = sprintf('%s/service/index.json', rtrim($api_url, '/') );
										$req_url .= (defined('POWERPRESS_BLUBRRY_API_QSA')?'?'. POWERPRESS_BLUBRRY_API_QSA:'');
										$json_data = powerpress_remote_fopen($req_url, $auth);
										if( $json_data != false )
											break;
									}
									
									if( $json_data )
									{
										$results =  powerpress_json_decode($json_data);
										
										if( isset($results['error']) )
										{
											$Error = $results['error'];
											if( strstr($Error, __('currently not available', 'powerpress') ) )
											{
												$Error = __('Unable to find podcasts for this account.', 'powerpress');
												$Error .= '<br /><span style="font-weight: normal; font-size: 12px;">';
												$Error .= 'Verify that the email address you enter here matches the email address you used when you listed your podcast on blubrry.com.</span>';
											}
											else if( preg_match('/No programs found.*media hosting/i', $results['error']) )
											{
												$Error .= '<br/><span style="font-weight: normal; font-size: 12px;">';
												$Error .= 'Service may take a few minutes to activate.</span>';
											}
										}
										else if( !is_array($results) )
										{
											$Error = $json_data;
										}
										else
										{
											// Get all the programs for this user...
											while( list($null,$row) = each($results) )
											{
												$Programs[ $row['program_keyword'] ] = $row['program_title'];
												if( $row['hosting'] === true || $row['hosting'] == 'true' )
													$ProgramHosting[ $row['program_keyword'] ] = true;
												else
													$ProgramHosting[ $row['program_keyword'] ] = false;
											}
											
											if( count($Programs) > 0 )
											{
												$SaveSettings['blubrry_auth'] = $auth;
												
												if( !empty($SaveSettings['blubrry_program_keyword']) )
												{
													$this->add_blubrry_redirect($blog_id, $SaveSettings['blubrry_program_keyword']);
													$SaveSettings['blubrry_hosting'] = $ProgramHosting[ $SaveSettings['blubrry_program_keyword'] ];
													if( !is_bool($SaveSettings['blubrry_hosting']) )
													{
														if( $SaveSettings['blubrry_hosting'] === 'false' || empty($SaveSettings['blubrry_hosting']) )
															$SaveSettings['blubrry_hosting'] = false;
													}
														
													$Save = true;
													$Step = 3;
												}
												else if( isset($SaveSettings['blubrry_program_keyword']) ) // Present but empty
												{
													$Error = __('You must select a program to continue.', 'powerpress');
												}
												else if( count($Programs) == 1 )
												{
													list($keyword, $title) = each($Programs);
													$SaveSettings['blubrry_program_keyword'] = $keyword;
													$SaveSettings['blubrry_hosting'] = $ProgramHosting[ $keyword ];
													if( !is_bool($SaveSettings['blubrry_hosting']) )
													{
														if( $SaveSettings['blubrry_hosting'] === 'false' || empty($SaveSettings['blubrry_hosting']) )
															$SaveSettings['blubrry_hosting'] = false;
													}
													$this->add_blubrry_redirect($blog_id, $keyword);
													$Step = 3;
													$Save = true;
												}
												else
												{
													$Error = __('Please select your podcast program to continue.', 'powerpress');
													$Step = 2;
													$Settings['blubrry_username'] = $SaveSettings['blubrry_username'];
												}
											}
											else
											{
												$Error = __('No podcasts for this account are listed on blubrry.com.', 'powerpress');
											}
										}
									}
									else
									{
										global $g_powerpress_remote_error, $g_powerpress_remote_errorno;
										if( !empty($g_powerpress_remote_errorno) && $g_powerpress_remote_errorno == 401 )
											$Error = 'Incorrect user email address or password, or no program was found signed-up for services.  <br /><span style="font-weight: normal; font-size: 12px;">Verify your account settings and try again.</span>';
										else if( !empty($g_powerpress_remote_error) )
											$Error = __('Error:', 'powerpress') .' '.$g_powerpress_remote_error;
										else
											$Error = __('Authentication failed.', 'powerpress');
									}
									
									if( $Error )
									{
										$Error .= '<p style="text-align: center;"><a href="http://create.blubrry.com/resources/powerpress/powerpress-settings/services-stats/" target="_blank">'. __('Click Here For Help','powerpress') .'</a></p>';
									}
								
								}
								
								if( $Save )
								{
									$this->save_settings($blog_id, $SaveSettings);
									delete_blog_option($blog_id, 'powerpress_stats'); // clear the site dashboard statistics
								}
								
								if( $Error )
									powerpress_page_message_add_notice( $Error );
							}
							
							
							
							
							
							// Load the page here...
							
							if( empty($Settings) )
								$Settings = get_blog_option($blog_id, 'powerpress_general');
							
							$this->_admin_ajax_header( __('Blubrry Media Services', 'powerpress-multisite') );
							
							$this->_admin_ajax_configure_services($blog_id, $Step, $Settings, $Programs, $Password);
								
							$this->_admin_ajax_footer();
							exit;
						}
					}; break;
				}
			}
			/*
			if( !empty($_REQUEST['action']) && $_REQUEST['action'] == 'powerpress-multisite' && !empty($_REQUEST['ajax']) )
			{
				check_admin_referer('powerpress-multisite-ajax-account');
				$blog_id = $_REQUEST['blog_id'];
				$step = 1;
				// save settings here....
				
				
				
				
				i
				
				// Load the page here...
							
							
							
							
				$this->_admin_ajax_header( __('Blubrry Media Services', 'powerpress-multisite') );
				
				//$this->_admin_ajax_footer();
				$this->_admin_ajax_configure_services($blog_id);
				
				$this->_admin_ajax_footer();
				exit;
			}
			*/
		}
		
		
		public function _admin_ajax_header($title)
		{
			$other = false;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php do_action('admin_xml_ns'); ?> <?php language_attributes(); ?>>
<head>
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
<title><?php bloginfo('name') ?> &rsaquo; <?php echo $title; ?> &#8212; <?php echo __('WordPress', 'powerpress'); ?></title>
<?php

// In case these functions haven't been included yet...
require_once(ABSPATH . 'wp-admin/includes/admin.php');

wp_admin_css( 'css/global' );
wp_admin_css();

do_action('admin_print_styles');
do_action('admin_print_scripts');
do_action('admin_head');

echo '<!-- done adding extra stuff -->';

?>
<link rel="stylesheet" href="<?php echo powerpress_get_root_url(); ?>css/jquery.css" type="text/css" media="screen" />
</head>
<body>
<div id="container">
<p style="text-align: right; position: absolute; top: 5px; right: 5px; margin: 0; padding: 0;"><a href="#" onclick="self.parent.tb_remove();" title="<?php echo __('Cancel', 'powerpress'); ?>"><img src="<?php echo admin_url(); ?>/images/no.png" /></a></p>
<?php
		}
		
		public function _admin_ajax_configure_services($blog_id, $Step=1, $Settings = false, $Programs = false, $Password = '' )
		{
			if( !current_user_can(POWERPRESS_CAPABILITY_MANAGE_OPTIONS) )
			{
				powerpress_page_message_add_notice( __('You do not have sufficient permission to manage options.', 'powerpress-multisite') );
				powerpress_page_message_print();
				return;
			}
			
			if( !ini_get( 'allow_url_fopen' ) && !function_exists( 'curl_init' ) )
			{
				powerpress_page_message_add_notice( __('Your server must either have the php.ini setting \'allow_url_fopen\' enabled or have the PHP cURL library installed in order to continue.', 'powerpress-multisite') );
				powerpress_page_message_print();
				return;
			}
			
			if( empty($blog_id) )
			{
				powerpress_page_message_add_notice( __('No site specified.', 'powerpress-multisite') );
				powerpress_page_message_print();
				return;
			}
			
			if( !$Settings )
				$Settings = get_blog_option($blog_id, 'powerpress_general');
			
			if( empty($Settings['blubrry_username']) )
				$Settings['blubrry_username'] = '';
			if( empty($Settings['blubrry_hosting']) || $Settings['blubrry_hosting'] === 'false' )
				$Settings['blubrry_hosting'] = false;
			if( empty($Settings['blubrry_program_keyword']) )
				$Settings['blubrry_program_keyword'] = '';
				
			if( empty($Programs) )
				$Programs = array();
			
			powerpress_page_message_print();
?>
<form action="<?php echo network_admin_url('sites.php?action=powerpress-multisite'); ?>" enctype="multipart/form-data" method="post">
<?php wp_nonce_field('powerpress-multisite-ajax-account'); ?>
<input type="hidden" name="action" value="powerpress-jquery-account-save" />
<input type="hidden" name="ajax" value="1" />
<input type="hidden" name="blog_id" value="<?php echo $blog_id; ?>" />
<div id="accountinfo">
	<h2><?php echo __('Blubrry Services Integration', 'powerpress'); ?></h2>
<?php if( $Step == 1 ) { ?>
	<p>
		<label for="blubrry_username"><?php echo __('Blubrry User Name (Email)', 'powerpress'); ?></label>
		<input type="text" id="blubrry_username" name="Settings[blubrry_username]" value="<?php echo $Settings['blubrry_username']; ?>" />
	</p>
	<p id="password_row">
		<label for="password_password"><?php echo __('Blubrry Password', 'powerpress'); ?></label>
		<input type="password" id="password_password" name="Password" value="" />
	</p>
<?php } else if( $Step == 3 ) { ?>
<p style="text-align: center;"><strong><?php echo __('Settings Saved Successfully!', 'powerpress'); ?></strong></p>
<p style="text-align: center;">
	<a href="<?php echo network_admin_url("sites.php?page=powerpress-multisite&amp;=$blog_idW"); ?>" onclick="self.parent.tb_remove(); return false;" target="_top"><?php echo __('Close', 'powerpress'); ?></a>
</p>
<script type="text/javascript"><!--

jQuery(document).ready(function($) {
	// Upload loading, check the parent window for #blubrry_stats_settings div
	if( jQuery('#blubrry_stats_settings',parent.document).length )
	{
		jQuery('#blubrry_stats_settings',parent.document).html('');
	}
});

// --></script>
<?php } else { ?>
	<input type="hidden" name="Settings[blubrry_username]" value="<?php echo htmlspecialchars($Settings['blubrry_username']); ?>" />
	<input type="hidden" name="Password" value="<?php echo htmlspecialchars($Password); ?>" />
	<!-- <input type="hidden" name="Settings[blubrry_hosting]" value="<?php echo $Settings['blubrry_hosting']; ?>" /> -->
	<p>
		<label for="blubrry_program_keyword"><?php echo __('Select Blubrry Program', 'powerpress'); ?></label>
<select id="blubrry_program_keyword" name="Settings[blubrry_program_keyword]">
<option value=""><?php echo __('Select Program', 'powerpress'); ?></option>
<?php
while( list($value,$desc) = each($Programs) )
	echo "\t<option value=\"$value\"". ($Settings['blubrry_program_keyword']==$value?' selected':''). ">$desc</option>\n";
?>
</select>
	</p>
<?php } ?>
<?php if( $Step != 3 ) { ?>
	<p>
		<input type="submit" name="Save" value="<?php echo __('Save', 'powerpress'); ?>" />
		<input type="button" name="Cancel" value="<?php echo __('Cancel', 'powerpress'); ?>" onclick="self.parent.tb_remove();" />
		<input type="submit" name="Remove" value="Remove" style="float: right;" onclick="return confirm('<?php echo __('Remove Blubrry Services Integration, are you sure?', 'powerpress'); ?>');" />
	</p>
<?php } ?>
</div>
</form>
<?php
		}
		
		public function _admin_ajax_footer()
		{
?>
</div><!-- end container -->
</body>
</html>
<?php
		}
		
		public function save_settings($blog_id, $SettingsNew=false, $field = 'powerpress_general')
		{
			if( empty($SettingsNew) )
				return;
			
			$Settings = get_blog_option($blog_id, $field);
			if( !is_array($Settings) )
				$Settings = array();
			while( list($key,$value) = each($SettingsNew) )
				$Settings[$key] = $value;
			
			update_blog_option($blog_id, $field,  $Settings);
		}
		
		public function add_blubrry_redirect($blog_id, $program_keyword)
		{
			$Settings = get_blog_option($blog_id, 'powerpress_general');
			$RedirectURL = 'http://media.blubrry.com/'.$program_keyword;
			$NewSettings = array();
			
			// redirect1
			// redirect2
			// redirect3
			for( $x = 1; $x <= 3; $x++ )
			{
				$field = sprintf('redirect%d', $x);
				if( !empty($Settings[$field]) && !stristr($Settings[$field], 'podtrac.com') )
					$NewSettings[$field] = '';
			}
			$NewSettings['redirect1'] = $RedirectURL.'/';
			
			if( count($NewSettings) > 0 )
				$this->save_settings($blog_id, $NewSettings);
		}
};


function powerpressadmin_multisite_edit_blubrry_services($General)
{
	PowerPressMultiSitePlugin::edit_blubrry_services($General);
}

if ( is_multisite() ) {
	$wp_powerpress_multisite_plugin = new PowerPressMultiSitePlugin();
}

?>