<?php 
    /*
    Plugin Name: Auto Login
    Plugin URI: http://example.com
    Description: Plugin for login user with url
    Author: Example
    Version: 1.0.0
    Author URI: http://example.com
	Text Domain: plugin-slug
	License: GPLv2 or later
	License URI: http://www.gnu.org/licenses/gpl-2.0.txt
    */
	
	/*
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

	Copyright 2005-2015 Automattic, Inc.
	*/	
?>
<?php	
register_activation_hook( __FILE__, 'autoLogin_activate' );
function autoLogin_activate() {
	//create a variable to specify the details of page
    $randomString = base64_encode(openssl_random_pseudo_bytes(30));
	update_option( 'autoLogin_Secret_Key', $randomString);
	
	//Initialize Virtual url option to default value
	$virtual_url = get_option('autoLogin_Virtual_Url');
	if(empty($virtual_url)){
		update_option( 'autoLogin_Virtual_Url', 'autoLogin');
	}
}

// Code For Virtual Page
$virtual_url = get_option('autoLogin_Virtual_Url');


//Generate Rewrite Rule
add_filter( 'generate_rewrite_rules', function ( $wp_rewrite ) {
    $wp_rewrite->rules = array_merge(
        [''.$virtual_url.'/?$' => 'index.php?user=user&secret=$matches[1]'],
        $wp_rewrite->rules
    );
} ); 

//Add Query Variables
add_filter( 'query_vars', function( $query_vars ) {
	$user_param = get_option( 'autoLogin_User_Parm' );
	$secret_param = get_option( 'autoLogin_Secret_Parm' );
    $query_vars[] = $user_param;
    $query_vars[] = $secret_param;
    return $query_vars;
} );

// Check query variables and redirect to template if true
add_action( 'template_redirect', function() {
	$user_param = get_option( 'autoLogin_User_Parm' );
	$secret_param = get_option( 'autoLogin_Secret_Parm' );
    $hash = get_query_var( $user_param );
    $hash2 = get_query_var( $secret_param );
    if (($hash) && ($hash2)) {
        include plugin_dir_path( __FILE__ ) . 'templates/virtualPage.php';
        die;
    }
} ); 

//Admin Setting Page
add_action('admin_menu', 'autoLogin_create_theme_options_page');
add_action('admin_init', 'autoLogin_register_and_build_fields');
 
function autoLogin_create_theme_options_page() {
	add_menu_page('Auto login settings', 'Auto login settings', 'administrator', __FILE__, 'autoLogin_options_page_fn');
}

function autoLogin_section_cb(){
	
}

function autoLogin_register_and_build_fields() {
	register_setting('autoLogin_Plugin_Options', 'autoLogin_Plugin_Options', 'validate_setting');
	register_setting('autoLogin_Plugin_Options', 'autoLogin_Virtual_Url', 'validate_setting');
	register_setting('autoLogin_Plugin_Options', 'autoLogin_Secret_Key', 'validate_setting');
	register_setting('autoLogin_Plugin_Options', 'autoLogin_User_Parm', 'validate_setting');
	register_setting('autoLogin_Plugin_Options', 'autoLogin_Secret_Parm', 'validate_setting');
 
	add_settings_section('main_section', 'Main Settings', 'autoLogin_section_cb', __FILE__);
 
	add_settings_field('autoLogin_Virtual_Url', 'Virtual Page Slug:', 'autoLogin_virtual_urlsetting', __FILE__, 'main_section');
	add_settings_field('autoLogin_Secret_Key', 'Secret Key:', 'autoLogin_secret_keysetting', __FILE__, 'main_section');
	add_settings_field('autoLogin_User_Parm', 'User Parameter:', 'autoLogin_user_parm_setting', __FILE__, 'main_section');
	add_settings_field('autoLogin_Secret_Parm', 'Secret Parameter:', 'autoLogin_secret_parm_setting', __FILE__, 'main_section');
   
    add_settings_field('autoLogin_user_roles', 'User Roles', 'autoLogin_user_roles', __FILE__, 'main_section');
 
}

// Admin view page
function autoLogin_options_page_fn() {
	ob_start();
?>
    <div id="theme-options-wrap" class="widefat">
		<div class="icon32" id="icon-tools"></div>
	 
		<h2>Auto Login Settings</h2>
		<?php
		$options = get_option('autoLogin_Plugin_Options');
		$autoLogin_Virtual_Url = get_option('autoLogin_Virtual_Url');
		$site_url = site_url();
		$virtual_slug = $autoLogin_Virtual_Url;
		if(empty($virtual_slug)){
			$virtual_slug = 'autoLogin';
		}
		
		$final_url = $site_url.'/'.$virtual_slug.'?user=username&secret=[SHA256(username.SecretKeySetting)]';
		?>
        <h4>Auto Login Url Is :  <b><?php echo $final_url;?></b> </h4>

        <form method="post" action="options.php" enctype="multipart/form-data">
			<?php 
				settings_fields('autoLogin_Plugin_Options');
				do_settings_sections(__FILE__);
			?>
			<p class="submit">
				<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes');?>" />
			</p>
        </form>
    </div>
<?php
ob_end_flush(); 
}

// Virtual Url Slug field
function autoLogin_virtual_urlsetting() {
   $autoLogin_Virtual_Url = get_option('autoLogin_Virtual_Url');
   echo "<input name='autoLogin_Virtual_Url' style='width:400px;' placeholder='autoLogin' type='text' value='{$autoLogin_Virtual_Url}' />";
}

// Secret Key field
function autoLogin_secret_keysetting() {
   $autoLogin_Secret_Key = get_option('autoLogin_Secret_Key');
   echo "<input name='autoLogin_Secret_Key' style='width:400px;' type='text' value='{$autoLogin_Secret_Key}'>";
}

// User Parameter field
function autoLogin_user_parm_setting() {
   $autoLogin_User_Parm = get_option('autoLogin_User_Parm');
   echo "<input name='autoLogin_User_Parm' style='width:400px;' placeholder='user' type='text' value='{$autoLogin_User_Parm}'>";
}

// Secret Parameter field
function autoLogin_secret_parm_setting() {
   $autoLogin_Secret_Parm = get_option('autoLogin_Secret_Parm');
   echo "<input name='autoLogin_Secret_Parm' style='width:400px;' placeholder='secret' type='text' value='{$autoLogin_Secret_Parm}'>";
}



// Role Settings
function autoLogin_user_roles() {
	
	global $wp_roles;

	if ( ! isset( $wp_roles ) )
		$wp_roles = new WP_Roles();

		$all_roles = $wp_roles->get_names();
		
		$options = get_option('autoLogin_Plugin_Options');
	   
		$option = $options['role'];
	// IF AutoLogin Role Options are empty then initialize empty array
	if(empty($option)){
		$option = array();
	}
	
	$roles = array();
	foreach ( $all_roles as $type => $obj ) {
		$roles[] = $type;
	}
	
	foreach ( $roles as $key => $val ) {
		if ( in_array($val ,$option ) ) {
			echo '<input type="checkbox" name="autoLogin_Plugin_Options[role][]" value="'.$val.'" checked="checked">'.$all_roles[$val].'<br>';
		} else {
			echo '<input type="checkbox" name="autoLogin_Plugin_Options[role][]" value="'.$val.'">'.$all_roles[$val].'<br>';
		}
	}
   
}