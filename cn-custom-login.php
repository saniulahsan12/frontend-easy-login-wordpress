<?php
/*
  Plugin Name: Easy Custom Login Panel
  Plugin URI: 
  Description: User/Admin login form.
  Version: 1.0
  Author: 
  Author URI: 
 */
// login form fields
function cn_custom_login_form_fields() {

	ob_start(); ?>
		<style type="text/css">
			body {
				background: #eee !important;
			}

			.wrapper {
				margin-top: 80px;
			 	margin-bottom: 80px;
			}

			.form-signin {
			  max-width: 380px;
			  padding: 15px 35px 45px;
			  margin: 0 auto;
			  background-color: #fff;
			  border: 1px solid rgba(0,0,0,0.1);

			  .form-signin-heading,
				.checkbox {
				  margin-bottom: 30px;
				}

				.checkbox {
				  font-weight: normal;
				}

				.form-control {
				  position: relative;
				  font-size: 16px;
				  height: 43px !important;
				  padding: 10px;
					@include box-sizing(border-box);

					&:focus {
					  z-index: 2;
					}
				}

				input[type="text"],input[type="password"] {
				  /*margin-bottom: -1px;*/
				  border-bottom-left-radius: 0;
				  border-bottom-right-radius: 0;
				}

				input[type="password"] {
				  /*margin-bottom: 20px;*/
				  border-top-left-radius: 0;
				  border-top-right-radius: 0;
				}
			}

		</style>
		<div class="wrapper">
		    <form class="form-signin cn_custom_form" id="cn_custom_login_form" action="" method="post">
		      	  <img class="center-block img-responsive" src="http://rfq.parkafm.us/int/wp-content/themes/taxprofessionals/inc/quotation/images/logo.gif">
		    <?php if( !is_user_logged_in() ) : ?>
			      <input type="text" class="form-control" name="cn_custom_user_login" placeholder="Username" autofocus="" style="height:43px; border-radius: 0; margin-top: 15px;"/>
			      <input type="password" class="form-control" name="cn_custom_user_pass" placeholder="Password" style="height:43px; border-radius: 0; margin-top: 15px;"/>
			      <label class="checkbox" style="margin-left: 20px;">
			        <input type="checkbox" checked value="remember-me" id="rememberMe" name="rememberMe"> Remember me
			      </label>
			      <label>Take me to...</label><br>
			      <input type="radio" checked name="user_login_redirect" value="price_quotation"> Price Quotation<br>
			      <input type="radio" name="user_login_redirect" value="price_comparison"> Product Comparison<br>
			      <input type="hidden" name="cn_custom_login_nonce" value="<?php echo wp_create_nonce('cn_custom-login-nonce'); ?>"/>
			      <button id="cn_custom_login_submit" class="btn btn-lg btn-primary btn-block" type="submit" style="border-radius: 0; margin-top: 20px;">Login</button>
                  <a style="border-radius: 0; margin-top: 20px;" class="btn btn-lg btn-danger btn-block" href="<?php echo home_url('/wp-login.php?gaautologin=true&redirect_to='.admin_url()); ?>">Login with Google</a>
			  	<?php else: ?>
			  	  <input type="hidden" name="cn_custom_logout_nonce" value="<?php echo wp_create_nonce('cn_custom-logout-nonce'); ?>"/>
			      <button name="cn_custom_logout_submit" class="btn btn-lg btn-primary btn-block" style="border-radius: 0;">Logout</button>
			      <a href="<?php echo home_url('/compare-checklist/'); ?>" class="btn btn-lg btn-primary btn-block" style="border-radius: 0;">Go to Compare Page</a>
			<?php endif; ?>
		    </form>
		    <?php cn_custom_show_error_messages(); ?>
		</div>

	<?php
	return ob_get_clean();
}


// logs a member in after submitting a form
function cn_custom_login_member() {

	if(isset($_POST['cn_custom_logout_submit']) && wp_verify_nonce($_POST['cn_custom_logout_nonce'], 'cn_custom-logout-nonce')) {

		wp_logout();
		wp_redirect( home_url() );
		exit;
	}

	if(isset($_POST['cn_custom_user_login']) && wp_verify_nonce($_POST['cn_custom_login_nonce'], 'cn_custom-login-nonce')) {

		// this returns the user ID and other info from the user name
		$user = get_userdatabylogin($_POST['cn_custom_user_login']);

		if(!$user) {
			// if the user name doesn't exist
			cn_custom_errors()->add('empty_username', __('Invalid username'));
		}

		if(!isset($_POST['cn_custom_user_pass']) || $_POST['cn_custom_user_pass'] == '') {
			// if no password was entered
			cn_custom_errors()->add('empty_password', __('Please enter a password'));
		}

		// check the user's login with their password
		if(!wp_check_password($_POST['cn_custom_user_pass'], $user->user_pass, $user->ID)) {
			// if the password is incorrect for the specified user
			cn_custom_errors()->add('empty_password', __('Incorrect password'));
		}

		// check users redirect url choice (if empty or not)
		if(empty( $_POST['user_login_redirect'] )) {
			cn_custom_errors()->add('empty_redirect', __('Choose a Redirect URL From Checkbox'));
		}

		// retrieve all error messages
		$errors = cn_custom_errors()->get_error_messages();

		// only log the user in if there are no errors
		if(empty($errors)) {
			$creds = array();

 			if( $_POST['rememberMe'] ) :
 				$creds['remember'] = true;
				wp_setcookie($_POST['cn_custom_user_login'], $_POST['cn_custom_user_pass'], true);
			endif;

			$creds['user_login'] 	= 	$_POST['cn_custom_user_login'];
			$creds['user_password'] = 	$_POST['cn_custom_user_pass'];

			$user = wp_signon( $creds, false );

			if ( is_wp_error($user) ) :
				cn_custom_errors()->add( 'login_error', $user->get_error_message() );
			endif;

			if( $_POST['user_login_redirect'] == 'price_quotation' ):
				wp_redirect( admin_url() ); exit;

			elseif( $_POST['user_login_redirect'] == 'price_comparison' ):
				wp_redirect( home_url('/compare-checklist/') ); exit;

			else:
			endif;
		}
	}
}
add_action('init', 'cn_custom_login_member');


// used for tracking error messages
function cn_custom_errors(){
    static $wp_error; // Will hold global variable safely
    return isset($wp_error) ? $wp_error : ($wp_error = new WP_Error(null, null, null));
}


// displays error messages from form submissions
function cn_custom_show_error_messages() {
	if($codes = cn_custom_errors()->get_error_codes()) {
		echo '<div class="alert alert-danger" style="margin-top:20px;">';
		    // Loop error codes and display errors
		   foreach($codes as $code){
		        $message = cn_custom_errors()->get_error_message($code);
		        echo '<span class="error"><strong>' . __('Error') . '</strong>: ' . $message . '</span><br/>';
		    }
		echo '</div>';
	}
}

add_shortcode( 'easy-login-form','cn_custom_login_form_fields' );
