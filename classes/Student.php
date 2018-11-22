<?php

/**
 * Class Teacher
 * @package DOZENT
 *
 * @since v.1.0.0
 */

namespace DOZENT;


class Student {

	protected $error_msgs = '';
	public function __construct() {
		add_action('template_redirect', array($this, 'register_student'));
	}

	/**
	 * Register new user and mark him as student
	 *
	 * @since v.1.0.0
	 */
	public function register_student(){
		if ( ! isset($_POST['dozent_action'])  ||  $_POST['dozent_action'] !== 'dozent_register_student' ){
			return;
		}
		//Checking nonce
		dozent_utils()->checking_nonce();

		$required_fields = apply_filters('dozent_student_registration_required_fields', array(
			'first_name'                => __('First name field is required', 'dozent'),
			'last_name'                 =>  __('Last name field is required', 'dozent'),
			'email'                     => __('E-Mail field is required', 'dozent'),
			'user_login'                => __('User Name field is required', 'dozent'),
			'phone_number'              => __('Phone Number field is required', 'dozent'),
			'password'                  => __('Password field is required', 'dozent'),
			'password_confirmation'     => __('Password Confirmation field is required', 'dozent'),
		));

		$validation_errors = array();
		foreach ($required_fields as $required_key => $required_value){
			if (empty($_POST[$required_key])){
				$validation_errors[$required_key] = $required_value;
			}
		}

		if (!filter_var(dozent_utils()->input_old('email'), FILTER_VALIDATE_EMAIL)) {
			$validation_errors['email'] = __('Valid E-Mail is required', 'dozent');
		}
		if (dozent_utils()->input_old('password') !== dozent_utils()->input_old('password_confirmation')){
			$validation_errors['password_confirmation'] = __('Confirm password does not matched with Password field', 'dozent');
		}

		if (count($validation_errors)){
			$this->error_msgs = $validation_errors;
			add_filter('dozent_student_register_validation_errors', array($this, 'dozent_student_form_validation_errors'));
			return;
		}

		$first_name     = sanitize_text_field(dozent_utils()->input_old('first_name'));
		$last_name      = sanitize_text_field(dozent_utils()->input_old('last_name'));
		$email          = sanitize_text_field(dozent_utils()->input_old('email'));
		$user_login     = sanitize_text_field(dozent_utils()->input_old('user_login'));
		$phone_number   = sanitize_text_field(dozent_utils()->input_old('phone_number'));
		$password       = sanitize_text_field(dozent_utils()->input_old('password'));
		$dozent_profile_bio = wp_kses_post(dozent_utils()->input_old('dozent_profile_bio'));

		$userdata = array(
			'user_login'    =>  $user_login,
			'user_email'    =>  $email,
			'first_name'    =>  $first_name,
			'last_name'     =>  $last_name,
			//'role'          =>  dozent()->student_role,
			'user_pass'     =>  $password,
		);

		$user_id = wp_insert_user( $userdata ) ;
		if ( ! is_wp_error($user_id)){
			update_user_meta($user_id, 'phone_number', $phone_number);
			update_user_meta($user_id, 'description', $dozent_profile_bio);
			update_user_meta($user_id, '_dozent_profile_bio', $dozent_profile_bio);

			$user = get_user_by( 'id', $user_id );
			if( $user ) {
				wp_set_current_user( $user_id, $user->user_login );
				wp_set_auth_cookie( $user_id );
			}
		}else{
			$this->error_msgs = $user_id->get_error_messages();
			add_filter('dozent_student_register_validation_errors', array($this, 'dozent_student_form_validation_errors'));
			return;
		}

		wp_redirect(dozent_utils()->input_old('_wp_http_referer'));
		die();
	}

	public function dozent_student_form_validation_errors(){
		return $this->error_msgs;
	}
	

}