<?php 
    /*
    Plugin Name: Login By Signed URL
    Plugin URI: http://tbd.com
    Description: Allows users with selected roles to be logged in to WordPress simply by browsing a link.
    Author: Michael Fielding
    Version: 1.1.0
	Text Domain: autoLogin
	License: GPLv2
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

	Copyright 2019 Microneer Limited
	*/	

register_activation_hook( __FILE__, 'autoLogin_activate' );
function autoLogin_activate() {
	// if some plugin options are not set, add the default values
	if ( empty(get_option('autoLogin_Key')) ) {
		update_option( 'autoLogin_Key', base64_encode(openssl_random_pseudo_bytes(30)) );
	}
	if ( empty(get_option('autoLogin_Roles')) ) {
		update_option( 'autoLogin_Roles', array() );
	}
	if ( empty(get_option('autoLogin_User_Param')) ) {
		update_option( 'autoLogin_User_Param', 'user' );
	}
	if ( empty(get_option('autoLogin_Signature_Param')) ) {
		update_option( 'autoLogin_Signature_Param', 'sig' );
	}
}

/**
	Add a message to the outgoing HTTP headers.
	@param $message The message to add.
*/
function autoLogin_debug( $message ) {
	header( "X-Login-By-Signed-URL-Message: $message");
}

//Add Query Variables - i.e. allow our configured query parameters to be accepted in the page URL
add_filter( 'query_vars', function( $query_vars ) {
	$query_vars[] = get_option( 'autoLogin_User_Param' );
	$query_vars[] = get_option( 'autoLogin_Signature_Param' );
    return $query_vars;
} );

// Check query variables during rendering of the template and login a user if there's a user specified
add_action( 'template_redirect', function() {
	// can only work if the plugin options are set
	if ( !empty(get_option('autoLogin_User_Param') ) ) {
		$username = get_query_var( get_option('autoLogin_User_Param') );
		$provided_signature = get_query_var( get_option('autoLogin_Signature_Param') );
		
		// check if both our expected query parameters are present...
		if ( !empty($username) && !empty($provided_signature) ) {
		
			// try to get the user by their username...
			$user = get_user_by('login', $username );
			
			if ( $user === false ) {
				// the username provided didn't even exist
				autoLogin_debug( "User $username not found" );	
			} else {
				// user was found, let's check the signature is correct...
				$expected_signature = hash('sha256',$username.get_option('autoLogin_Key'));
				
				if ( strtolower($provided_signature) !== $expected_signature ) {
					autoLogin_debug( "Signature is wrong" );
				} else {
					// Signature correct,  check each role for user to find one which is allowed to be auto logged in...
					$ok = false; // assume not logged in
					$allowed_roles = array_map( 'strtolower', get_option('autoLogin_Roles') );
					foreach( $user->roles as $user_role ) {
						// look for the user's role in the allowed roles array, doing it case insensitive
						if ( in_array(strtolower($user_role), $allowed_roles) ) {
							// found a valid role, log the user in
							wp_set_current_user($user->ID, $username);
							wp_set_auth_cookie($user->ID, true);
							do_action('wp_login', $username, $user, false);
							$ok = true;
							break; // skip out of the foreach loop, we only need to login once!
						}
					}
					if ( $ok ) {
						autoLogin_debug( "Logged in $username" );
					} else {
						autoLogin_debug( "User's role ".get_option('autoLogin_Roles')[0]." isn't allowed" );
					}

				}
			}
		}
	}
} ); 

//Admin Setting Page
add_action('admin_menu', 'autoLogin_create_theme_options_page');
add_action('admin_init', 'autoLogin_register_and_build_fields');
 
function autoLogin_create_theme_options_page() {
	add_submenu_page('options-general.php', 'Login By Signed URL settings', 'Login By Signed URL', 'administrator', __FILE__, 'autoLogin_options_page');
}

function autoLogin_register_and_build_fields() {
	// register all the options that need to be managed for this plugin
	register_setting('autoLogin_Plugin_Options', 'autoLogin_Key');
	register_setting('autoLogin_Plugin_Options', 'autoLogin_Roles');
	register_setting('autoLogin_Plugin_Options', 'autoLogin_User_Param');
	register_setting('autoLogin_Plugin_Options', 'autoLogin_Signature_Param');
 
	add_settings_section(
		'autoLogin_Main_Section', 
		'Main', 
		function(){
			?>
			<p>The Secret Key may be shared with other systems to enable them to log users into WordPress.</p>
			<p>The Allowed Roles checkboxes enable functionality for individual WordPress user roles - if the checkbox is not checked, users
			with that role cannot be logged in via a link.</p>
			<?php
		} , 
		__FILE__
	);
	add_settings_section(
		'autoLogin_Query_Parameters_Section', 
		'Autologin URL parameters', 
		function(){
			?>
			<p>These settings allow the query string parameter names in the login link to be customised, for example to avoid conflict with another plugin
			that uses query string parameters.</p>
			<?php
		},
		__FILE__
	);
 
	add_settings_field(
		'autoLogin_Key', 
		'Secret key', 
		function () {
			$autoLogin_Key = get_option('autoLogin_Key');
			echo "<input name='autoLogin_Key' style='width:400px;' type='text' value='{$autoLogin_Key}'>";
		}, 
		__FILE__, 
		'autoLogin_Main_Section'
	);
    add_settings_field(
		'autoLogin_user_roles', 
		'Allowed roles', 
		function () {
			
			global $wp_roles;

			if ( ! isset( $wp_roles ) )
				$wp_roles = new WP_Roles();

			$all_roles = $wp_roles->get_names();
			
			$allowed_roles = get_option('autoLogin_Roles');
		   
			foreach ( $all_roles as $role ) {
				// check html checkbox if the role is one of the allowed roles
				$checked = in_array($role, $allowed_roles) ? ' checked="checked"':'';
				echo '<input type="checkbox" name="autoLogin_Roles[]" value="'.$role.'"'.$checked.'>'.$role.'<br>';
			}
		   
		},
		__FILE__, 
		'autoLogin_Main_Section'
	);
	add_settings_field(
		'autoLogin_User_Param', 
		'Username', 
		function () {
		   $autoLogin_User_Param = get_option('autoLogin_User_Param');
		   echo "<input name='autoLogin_User_Param' style='width:400px;' type='text' value='{$autoLogin_User_Param}'>";
		},
		__FILE__, 
		'autoLogin_Query_Parameters_Section'
	);
	add_settings_field(
		'autoLogin_Signature_Param', 
		'Signature', 
		function () {
		   $autoLogin_Signature_Param = get_option('autoLogin_Signature_Param');
		   echo "<input name='autoLogin_Signature_Param' style='width:400px;' type='text' value='{$autoLogin_Signature_Param}'>";
		},
		__FILE__, 
		'autoLogin_Query_Parameters_Section'
	);
}

// Admin view page
function autoLogin_options_page() {
	ob_start();
?>
    <div class="wrap">
		<div class="icon32" id="icon-tools"></div>
	 
		<h1>Login By Signed URL Settings</h1>
		<p>
			The Login By Signed URL plugin allows WordPress users with selected roles to be logged in from a magic link which includes
			two query parameters - one holding the username to log in, and one with a unique signature for that user. This can be useful
			to allow users to log in directly from transactional or marketing emails, for example.
		</p>
		<p>
			The unique signature is the SHA256 hash of the username concatenated with a secret key. The secret key is common to all users - 
			it's configured below.
		</p>
		<?php
		
		$user = get_option('autoLogin_User_Param');
		$sig = get_option('autoLogin_Signature_Param');
		$url = site_url()."/page-to-visit?$user=<i>username</i>&$sig=<i>signature</i>";
		?>
        <h4>Login URL example:  <b><?php echo $url;?></b> </h4>

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