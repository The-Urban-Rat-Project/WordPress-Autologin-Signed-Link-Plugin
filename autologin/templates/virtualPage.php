<?php
/*--------------------------This is Virtual Page------------------------------------------*/

	// Automatic login //
    $options = get_option('autoLogin_Plugin_Options');
	
	$user_param = get_option( 'autoLogin_User_Parm' );
	$secret_param = get_option( 'autoLogin_Secret_Parm' );
	
	$username = $_GET[$user_param];
	$secret = $_GET[$secret_param];
	
	$user = get_user_by('login', $username );
	
	
	$autoLogin_Secret_Key = get_option( 'autoLogin_Secret_Key' );
	
	//User Original Secret key Hash256( UserName + AutoLogin Secret Key )
	$user_Secret_Key = hash('sha256',$username.$autoLogin_Secret_Key);
	
	//GET Current Page Url
	$pageURL = (@$_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';

	if ($_SERVER['SERVER_PORT'] != '80'){
	  $pageURL .= $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['PHP_SELF'];
	}
	else {
	  $pageURL .= $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];  
	}
	
	if ( $user_Secret_Key != $secret)
    {
		header('Location: '.$pageURL);
		die(0);
	} 
	// Get all the user roles as an array.
	$user_roles = $user->roles;
	$role = $options['role'];
	
	// Check if the role you're interested in, is present in the array.
	if ( in_array($user_roles[0], $role) ) {
		
		
	}else{
		//IF User Role is Not in Auto Login Selected Roles
		
		header('Location: '.$pageURL);
		die(0);
		
	}
	
	// Login Functionality Code If not error//
	if ( !is_wp_error( $user ) )
	{
		$user_id = $user->ID;
		$user_login = $username;  
		wp_set_current_user($user_id, $username);
		wp_set_auth_cookie($user_id, true);
		do_action('wp_login', $user_login, $user, false);
		
		//Check redirect url
		header('Location: '.$pageURL);
		die(0);   

	}else{
		//Redirect To Error Page
		header('Location: '.$pageURL);
		die(0);
	}
