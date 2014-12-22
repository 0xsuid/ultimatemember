<?php

	/***
	***	@Error processing hook : login
	***/
	add_action('um_submit_form_errors_hook_login', 'um_submit_form_errors_hook_login', 10);
	function um_submit_form_errors_hook_login( $args ){
		global $ultimatemember;
		
		$is_email = false;
		
		$form_id = $args['form_id'];
		$mode = $args['mode'];

		if ( isset( $args['username'] ) && $args['username'] == '' ) {
			$ultimatemember->form->add_error( 'username',  __('Please enter your username or email') );
		}
		
		if ( isset( $args['user_login'] ) && $args['user_login'] == '' ) {
			$ultimatemember->form->add_error( 'user_login',  __('Please enter your username') );
		}
		
		if ( isset( $args['user_email'] ) && $args['user_email'] == '' ) {
			$ultimatemember->form->add_error( 'user_email',  __('Please enter your email') );
		}
		
		if ( isset( $args['username'] ) ) {
			$field = 'username';
			if ( is_email( $args['username'] ) ) {
				$is_email = true;
				$data = get_user_by('email', $args['username'] );
				$user_name = (isset ( $data->user_login ) ) ? $data->user_login : null;
			} else {
				$user_name  = $args['username'];
			}
		} else if ( isset( $args['user_email'] ) ) {
				$field = 'user_email';
				$is_email = true;
				$data = get_user_by('email', $args['user_email'] );
				$user_name = (isset ( $data->user_login ) ) ? $data->user_login : null;
		} else {
				$field = 'user_login';
				$user_name = $args['user_login'];
		}
		
		if ( !username_exists( $user_name ) ) {
			if ( $is_email ) {
				$ultimatemember->form->add_error( $field,  __(' Sorry, we can\'t find an account with that email address') );
			} else {
				$ultimatemember->form->add_error( $field,  __(' Sorry, we can\'t find an account with that username') );
			}
		} else {
			if ( $args['user_password'] == '' ) {
				$ultimatemember->form->add_error( 'user_password',  __('Please enter your password') );
			}
		}
		
		$check = wp_authenticate_username_password( null, $user_name, $args['user_password'] );
		
		if ( is_wp_error( $check ) ) {
			$err = $check->get_error_code();
			switch( $err ) {
				
				default:
					
					break;

				case 'incorrect_password':
					if ( username_exists( $user_name ) ) {
						$ultimatemember->form->add_error( 'user_password',  __('That password is incorrect. Please try again.') );
					}
					break;
					
			}
		} else {
			$ultimatemember->login->auth_id = username_exists( $user_name );
		}
		
	}
	
	/***
	***	@login user
	***/
	add_action('um_user_login', 'um_user_login', 10);
	function um_user_login($args){
		global $ultimatemember;
		extract( $args );

		$user_id = $ultimatemember->login->auth_id;
		
		um_fetch_user( $user_id );
		
		$ultimatemember->user->auto_login( $user_id );
		
		$after = um_user('after_login');
		switch( $after ) {
			
			case 'redirect_profile':
				exit( wp_redirect( um_user_profile_url() ) );
				break;
			
			case 'redirect_url':
				exit( wp_redirect( um_user('login_redirect_url') ) );
				break;
				
			case 'refresh':
				exit( wp_redirect( $ultimatemember->permalinks->get_current_url() ) );
				break;
				
		}
	
	}
	
	/***
	***	@form processing
	***/
	add_action('um_submit_form_login', 'um_submit_form_login', 10);
	function um_submit_form_login($args){
		global $ultimatemember;
		
		if ( !isset($ultimatemember->form->errors) ) do_action( 'um_user_login', $args );
		
		do_action('um_user_login_extra_hook', $args );
		
	}

	/***
	***	@Show the submit button
	***/
	add_action('um_after_login_fields', 'um_add_submit_button_to_login', 1000);
	function um_add_submit_button_to_login($args){
		global $ultimatemember;
		
		// DO NOT add when reviewing user's details
		if ( $ultimatemember->user->preview == true && is_admin() ) return;
		
		?>
		
		<div class="um-col-alt">

			<?php if ( isset($args['secondary_btn']) && $args['secondary_btn'] != 0 ) { ?>
			
			<div class="um-left um-half"><input type="submit" value="<?php echo $args['primary_btn_word']; ?>" class="um-button" /></div>
			<div class="um-right um-half"><a href="<?php echo um_get_core_page('register'); ?>" class="um-button um-alt"><?php echo $args['secondary_btn_word']; ?></a></div>
			
			<?php } else { ?>
			
			<div class="um-center"><input type="submit" value="<?php echo $args['primary_btn_word']; ?>" class="um-button" /></div>
			
			<?php } ?>
			
			<div class="um-clear"></div>
			
		</div>
	
		<?php
	}

	/***
	***	@Display a forgot password link
	***/
	add_action('um_after_login_fields', 'um_after_login_submit', 1001);
	function um_after_login_submit(){ ?>
		
		<div class="um-col-alt-b">
			<a href="<?php echo um_get_core_page('password-reset'); ?>" class="um-link-alt"><?php _e('Forgot your password?','ultimatemember'); ?></a>
		</div>
		
		<?php
	}
	
	/***
	***	@Show Fields
	***/
	add_action('um_main_login_fields', 'um_add_login_fields', 100);
	function um_add_login_fields($args){
		global $ultimatemember;
		
		echo $ultimatemember->fields->display( 'login', $args );
		
	}