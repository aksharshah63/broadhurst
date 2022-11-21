<?php
/**
 * class-groups-file-access-admin.php
 *
 * Copyright (c) "kento" Karim Rahimpur www.itthinx.com
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This header and all notices must be kept intact.
 *
 * @author Karim Rahimpur
 * @package groups-file-access
 * @since groups-file-access 1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles admin settings.
 */
class Groups_File_Access_Admin extends Groups_File_Access {

	/**
	 * Plugin settings - additional Groups admin section.
	 */
	public static function groups_admin_file_access() {

		$output = '';

		if ( !current_user_can( GROUPS_ADMINISTER_OPTIONS ) ) {
			wp_die( esc_html__( 'Access denied.', 'groups-file-access' ) );
		}

		$is_sitewide_plugin = false;
		if ( is_multisite() ) {
			$active_sitewide_plugins = get_site_option( 'active_sitewide_plugins', array() );
			$active_sitewide_plugins = array_keys( $active_sitewide_plugins );
			$is_sitewide_plugin = in_array( 'groups-file-access/groups-file-access.php', $active_sitewide_plugins );
		}

		if ( isset( $_GET[self::DISMISS_HELP] ) ) {
			if ( $_GET[self::DISMISS_HELP] ) {
				update_user_meta( get_current_user_id(), self::PLUGIN_OPTIONS . '-' . self::DISMISS_HELP, true );
			} else {
				delete_user_meta( get_current_user_id(), self::PLUGIN_OPTIONS . '-' . self::DISMISS_HELP );
			}
		}

		$options = get_option( self::PLUGIN_OPTIONS , array() );
		if ( !isset( $options[self::SCHEMA_UPDATED] ) || !$options[self::SCHEMA_UPDATED] ) {
			self::schema_update();
		}
		if ( !file_exists( GFA_UPLOADS_DIR ) ) {
			self::folders_update();
		}

		if ( isset( $_POST['submit'] ) ) {
			if ( wp_verify_nonce( $_POST[self::NONCE], self::SET_ADMIN_OPTIONS ) ) {
				// Amazon S3
				$options[self::AMAZON_S3] = !empty( $_POST[self::AMAZON_S3] );
				$options[self::AMAZON_S3_ACCESS_KEY] = trim( sanitize_text_field( $_POST[self::AMAZON_S3_ACCESS_KEY] ) );
				$options[self::AMAZON_S3_SECRET_KEY] = trim( sanitize_text_field( $_POST[self::AMAZON_S3_SECRET_KEY] ) );
				$options[self::AMAZON_S3_EXPIRES] = trim( sanitize_text_field( $_POST[self::AMAZON_S3_EXPIRES] ) );
				if ( intval( $options[self::AMAZON_S3_EXPIRES] ) < self::AMAZON_S3_EXPIRES_MIN ) {
					$options[self::AMAZON_S3_EXPIRES] = self::AMAZON_S3_EXPIRES_DEFAULT;
				}
				$options[self::AMAZON_S3_DIRECT] = !empty( $_POST[self::AMAZON_S3_DIRECT] );
				if ( $options[self::AMAZON_S3_DIRECT] ) {
					$options[self::AMAZON_S3_REDIRECT] = false;
				} else {
					$options[self::AMAZON_S3_REDIRECT] = !empty( $_POST[self::AMAZON_S3_REDIRECT] );
				}
				// Serving Files
				$options[self::APPLY_MIME_TYPES] = !empty( $_POST[self::APPLY_MIME_TYPES] );
				$content_disposition = $_POST[self::CONTENT_DISPOSITION];
				switch( $content_disposition ) {
					case self::CONTENT_DISPOSITION_ATTACHMENT :
					case self::CONTENT_DISPOSITION_INLINE :
						$options[self::CONTENT_DISPOSITION] = $content_disposition;
						break;
					default :
						$options[self::CONTENT_DISPOSITION] = self::CONTENT_DISPOSITION_DEFAULT;
				}
				$options[self::SESSION_ACCESS] = !empty( $_POST[self::SESSION_ACCESS] );
				$t = !empty( $_POST[self::SESSION_ACCESS_TIMEOUT] ) ? intval( $_POST[self::SESSION_ACCESS_TIMEOUT] ) : self::SESSION_ACCESS_TIMEOUT_DEFAULT;
				if ( $t <= 0 ) {
					$t = self::SESSION_ACCESS_TIMEOUT_DEFAULT;
				}
				$options[self::SESSION_ACCESS_TIMEOUT] = $t;
				// Notifications
				$options[self::NOTIFY_ADMIN]  = !empty( $_POST[self::NOTIFY_ADMIN] );
				$email = !empty( $_POST[self::ADMIN_EMAIL] ) ? trim( $_POST[self::ADMIN_EMAIL] ) : '';
				$email = wp_strip_all_tags( $email );
				if ( is_email( $email ) ) {
					$options[self::ADMIN_EMAIL] = $email;
				} else {
					$options[self::ADMIN_EMAIL] = '';
				}
				$options[self::ADMIN_SUBJECT] = sanitize_text_field( $_POST[self::ADMIN_SUBJECT] );
				$options[self::ADMIN_MESSAGE] = wp_kses_post( trim( stripslashes( $_POST[self::ADMIN_MESSAGE] ) ) );
				// User Profile, admins, show service key, show files
				$options[self::USER_PROFILE_SHOW_FOR_ADMINS]= !empty( $_POST[self::USER_PROFILE_SHOW_FOR_ADMINS] );
				$options[self::USER_PROFILE_SHOW_SERVICE_KEY]= !empty( $_POST[self::USER_PROFILE_SHOW_SERVICE_KEY] );
				$options[self::USER_PROFILE_SHOW_FILES]= !empty( $_POST[self::USER_PROFILE_SHOW_FILES] );
				// Redirect
				$options[self::LOGIN_REDIRECT]= !empty( $_POST[self::LOGIN_REDIRECT] );
				// Delete data
				if ( !$is_sitewide_plugin ) {
					$options[self::DELETE_DATA]               = !empty( $_POST[self::DELETE_DATA] );
					$options[self::DELETE_DATA_ON_DEACTIVATE] = !empty( $_POST[self::DELETE_DATA_ON_DEACTIVATE] );
				}
			}
			update_option( self::PLUGIN_OPTIONS, $options );
		}

		$amazon_s3            = isset( $options[self::AMAZON_S3] ) ? $options[self::AMAZON_S3] : self::AMAZON_S3_DEFAULT;
		$amazon_s3_access_key = isset( $options[self::AMAZON_S3_ACCESS_KEY] ) ? $options[self::AMAZON_S3_ACCESS_KEY] : '';
		$amazon_s3_secret_key = isset( $options[self::AMAZON_S3_SECRET_KEY] ) ? $options[self::AMAZON_S3_SECRET_KEY] : '';
		$amazon_s3_expires    = isset( $options[self::AMAZON_S3_EXPIRES] ) ? $options[self::AMAZON_S3_EXPIRES] : self::AMAZON_S3_EXPIRES_DEFAULT;
		$amazon_s3_direct     = isset( $options[self::AMAZON_S3_DIRECT] ) ? $options[self::AMAZON_S3_DIRECT] : self::AMAZON_S3_DIRECT_DEFAULT;
		$amazon_s3_redirect   = isset( $options[self::AMAZON_S3_REDIRECT] ) ? $options[self::AMAZON_S3_REDIRECT] : self::AMAZON_S3_REDIRECT_DEFAULT;

		$apply_mime_types    = isset( $options[self::APPLY_MIME_TYPES] ) ? $options[self::APPLY_MIME_TYPES] : self::APPLY_MIME_TYPES_DEFAULT;
		$content_disposition = isset( $options[self::CONTENT_DISPOSITION] ) ? $options[self::CONTENT_DISPOSITION] : self::CONTENT_DISPOSITION_DEFAULT;
		$session_access      = isset( $options[self::SESSION_ACCESS] ) ? $options[self::SESSION_ACCESS] : self::SESSION_ACCESS_DEFAULT;
		$session_access_timeout = isset( $options[self::SESSION_ACCESS_TIMEOUT] ) ? $options[self::SESSION_ACCESS_TIMEOUT] : self::SESSION_ACCESS_TIMEOUT_DEFAULT;

		$notify_admin      = isset( $options[self::NOTIFY_ADMIN] ) ? $options[self::NOTIFY_ADMIN] : self::NOTIFY_ADMIN_DEFAULT;
		$admin_email       = !empty( $options[self::ADMIN_EMAIL] ) ? $options[self::ADMIN_EMAIL] : '';
		$admin_subject     = isset( $options[self::ADMIN_SUBJECT] ) ? esc_attr( wp_filter_nohtml_kses( $options[self::ADMIN_SUBJECT] ) ) : self::ADMIN_DEFAULT_SUBJECT;
		$admin_message     = isset( $options[self::ADMIN_MESSAGE] ) ? $options[self::ADMIN_MESSAGE] : self::ADMIN_DEFAULT_MESSAGE;

		$user_profile_show_for_admins = isset( $options[self::USER_PROFILE_SHOW_FOR_ADMINS] ) ? $options[self::USER_PROFILE_SHOW_FOR_ADMINS] : false;
		$user_profile_show_service_key = isset( $options[self::USER_PROFILE_SHOW_SERVICE_KEY] ) ? $options[self::USER_PROFILE_SHOW_SERVICE_KEY] : false;
		$user_profile_show_files = isset( $options[self::USER_PROFILE_SHOW_FILES] ) ? $options[self::USER_PROFILE_SHOW_FILES] : false;

		$login_redirect    = isset( $options[self::LOGIN_REDIRECT] ) ? $options[self::LOGIN_REDIRECT] : false;

		$delete_data = isset( $options[self::DELETE_DATA] ) ? $options[self::DELETE_DATA] : false;
		$delete_data_on_deactivate = isset( $options[self::DELETE_DATA_ON_DEACTIVATE] ) ? $options[self::DELETE_DATA_ON_DEACTIVATE] : false;

		if ( $delete_data ) {
			register_uninstall_hook( GFA_FILE, array( 'Groups_File_Access', 'uninstall' ) );
		} else {
			self::remove_uninstall_hook( GFA_FILE, array( 'Groups_File_Access', 'uninstall' ) );
		}

		$output .= '<h1>' . esc_html__( 'Groups File Access', 'groups-file-access' ) . '</h1>';

		$output .= '<form action="" name="options" method="post" autocomplete="off">';
		$output .= '<div style="margin-right:1em">';

		if ( !get_user_meta( get_current_user_id(), self::PLUGIN_OPTIONS . '-' . self::DISMISS_HELP, true ) ) {
			$output .= esc_html__( 'The following information is also available on the Help tab.', 'groups-file-access' );
			$output .= ' ';
			$dismiss_url = admin_url( 'admin.php?page=groups-admin-file-access' ) . '&' . self::DISMISS_HELP . '=1';
			$output .= '<a href="' . esc_url( $dismiss_url ) . '">' . esc_html__( 'Ok I got it, remove it from here.', 'groups-file-access'  ) . '</a>';
			require_once GFA_VIEWS_LIB . '/class-gfa-help.php';
			$output .= GFA_Help::get_help();
		}

		//
		// Storage Section
		//
		$output .= '<div class="manage">';
		$output .= '<h2>' . esc_html__( 'Storage', 'groups-file-access' ) . '</h2>';

		//
		// Uploads subsection
		//
		$output .= '<h3>' . esc_html__( 'Uploads', 'groups-file-access' ) . '</h3>';

		if ( file_exists( GFA_UPLOADS_DIR ) ) {
			$output .= '<p>';
			$output .= wp_kses_post( sprintf( __( 'Files are uploaded to the <code>%s</code> directory.', 'groups-file-access' ), GFA_UPLOADS_DIR ) );
			$output .= '<p>';
		} else {
			$output .= "<div class='error'>";
			$output .= wp_kses_post( sprintf( __( 'I could not create the <code>%s</code> directory. Your web server must have write permissions on its parent directory.', 'groups-file-access' ), GFA_UPLOADS_DIR ) );
			$output .= "</div>";
		}
		if ( file_exists( GFA_UPLOADS_DIR . '/.htaccess' ) ) {
			$output .= '<p>';
			$output .= wp_kses_post( sprintf( __( 'Access to files in <code>%s</code> is protected by an <code>.htaccess</code> file in that directory.', 'groups-file-access' ), GFA_UPLOADS_DIR ) );
			$output .= '</p>';
		} else {
			$output .= "<div class='error'>";
			$output .= wp_kses_post( sprintf( __( 'I could not create the <code>.htaccess</code> file in the <code>%s</code> directory. This file is required to assure that unauthorized access to files is avoided.', 'groups-file-access' ), GFA_UPLOADS_DIR ) );
			$output .= "</div>";
		}
		if ( file_exists( GFA_UPLOADS_DIR . '/index.html' ) ) {
			$output .= '<p>';
			$output .= wp_kses_post( sprintf( __( 'The directory listing is hidden through an <code>index.html</code> in <code>%s</code>.', 'groups-file-access' ), GFA_UPLOADS_DIR ) );
			$output .= '</p>';
		} else {
			$output .= "<div class='error'>";
			$output .= wp_kses_post( sprintf( __( 'I could not create the <code>index.html</code> file in the <code>%s</code> directory. This file is used to hide the directory contents from prying eyes.', 'groups-file-access' ), GFA_UPLOADS_DIR ) );
			$output .= "</div>";
		}

		//
		// Amazon S3 subsection
		//
		$output .= '<h3>' . esc_html__( 'Amazon S3', 'groups-file-access' ) . '</h3>';
		// enable
		$output .= '<p>';
		$output .= '<label>';
		$output .= '<input id="' . esc_attr( self::AMAZON_S3 ) . '" name="' . esc_attr( self::AMAZON_S3 ) . '" type="checkbox" ' . ( $amazon_s3 ? ' checked="checked" ' : '' ) . ' />';
		$output .= ' ';
		$output .= esc_html__( 'Enable Amazon S3 file access', 'groups-file-access' );
		$output .=  '</label>';
		$output .= '</p>';
		// access key
		$output .= '<p>';
		$output .= '<label style="cursor: help" id="amazon-s3-access-key-label" style="display:block" for="' . self::AMAZON_S3_ACCESS_KEY . '">' . esc_html__( 'Access key ID', 'groups-file-access' ) . '</label>';
		$output .= '<input id="amazon-s3-access-key" class="widefat" name="' . esc_attr( self::AMAZON_S3_ACCESS_KEY ) . '" type="password" autocomplete="one-time-code" value="' . esc_attr( htmlentities( stripslashes( $amazon_s3_access_key ) ) ) . '" />';
		$output .= '</p>';
		// secret key
		$output .= '<p>';
		$output .= '<label style="cursor: help" id="amazon-s3-secret-key-label" style="display:block" for="' . self::AMAZON_S3_SECRET_KEY . '">' . esc_html__( 'Secret access key', 'groups-file-access' ) . '</label>';
		$output .= '<input id="amazon-s3-secret-key" class="widefat" name="' . esc_attr( self::AMAZON_S3_SECRET_KEY ) . '" type="password" autocomplete="one-time-code" value="' . esc_attr( htmlentities( stripslashes( $amazon_s3_secret_key ) ) ) . '" />';
		$output .= '</p>';
		// password reveal/hide
		$output .= '<script type="text/javascript">';
		$output .= 'const access_key = document.getElementById( "amazon-s3-access-key" );';
		$output .= 'const secret_key = document.getElementById( "amazon-s3-secret-key" );';
		$output .= 'const access_key_label = document.getElementById("amazon-s3-access-key-label");';
		$output .= 'const secret_key_label = document.getElementById("amazon-s3-secret-key-label");';
		$output .= 'access_key_label.addEventListener(' .
						'"click",' .
						'function() {' .
							'if ( access_key.type === "password" ) {' .
								'access_key.type = "text";' .
							'} else {' .
								'access_key.type = "password";' .
							'}' .
						'}' .
					');';
		$output .= 'secret_key_label.addEventListener(' .
						'"click",' .
						'function() {' .
							'if ( secret_key.type === "password" ) {' .
								'secret_key.type = "text";' .
							'} else {' .
								'secret_key.type = "password";' .
							'}' .
						'}' .
					');';
		$output .= '</script>';
		// expires
		$output .= '<p>';
		$output .= sprintf(
			'<label id="amazon-s3-expires-label" for="%s" title="%s">%s</label>',
			esc_attr( self::AMAZON_S3_EXPIRES ),
			esc_html__( 'Time interval until generated file access URLs expire.', 'groups-file-access' ),
			esc_html__( 'Expiration', 'groups-file-access' )
		);
		$output .= ' ';
		$output .= sprintf(
			'<input id="amazon-s3-expires" class="amazon-s3-expires" name="%s" type="number" value="%d" min="%d"/>',
			esc_attr( self::AMAZON_S3_EXPIRES ),
			esc_attr( $amazon_s3_expires ),
			esc_attr( self::AMAZON_S3_EXPIRES_MIN )
		);
		$output .= ' ';
		$output .= esc_html__( 'Number of minutes', 'groups-file-access' );
		$output .= '</p>';
		// direct
		$output .= '<p>';
		$output .= sprintf(
			'<label title="%s">',
			esc_attr__( 'When enabled, file access is provided via temporary Object URLs.', 'groups-file-access' ) .
			' ' .
			esc_attr__( 'If this option is disabled, file access is provided via site-based URLs.', 'groups-file-access' )
		);
		$output .= '<input id="' . esc_attr( self::AMAZON_S3_DIRECT ) . '" name="' . esc_attr( self::AMAZON_S3_DIRECT ) . '" type="checkbox" ' . ( $amazon_s3_direct ? ' checked="checked" ' : '' ) . ' />';
		$output .= ' ';
		// Create direct file access URLs to the Amazon S3 Object URL ...
		$output .= esc_html__( 'Direct Object URLs', 'groups-file-access' );
		$output .=  '</label>';
		$output .= '</p>';
		$output .= '<p class="description">';
		$output .= esc_html__( 'Provide file access based on the Amazon S3 Object URL.', 'groups-file-access' );
		$output .= ' ';
		$output .= esc_html__( 'When enabled, file access is provided via temporary Object URLs.', 'groups-file-access' );
		$output .= ' ';
		$output .= esc_html__( 'If this option is disabled, file access is provided via site-based URLs.', 'groups-file-access' );
		$output .= '</p>';
		// redirect
		$output .= '<p>';
		$output .= sprintf(
			'<label title="%s">',
			esc_attr__( 'When enabled, the requests for site-based URLs are redirected to temporary Object URLs of the objects on Amazon S3.', 'groups-file-access' )
		);
		$output .= '<input id="' . esc_attr( self::AMAZON_S3_REDIRECT ) . '" name="' . esc_attr( self::AMAZON_S3_REDIRECT ) . '" type="checkbox" ' . ( $amazon_s3_redirect ? ' checked="checked" ' : '' ) . ' />';
		$output .= ' ';
		// Redirect file access requests to the access URL of the Amazon S3 object ...
		$output .= esc_html__( 'Redirect to Object URLs', 'groups-file-access' );
		$output .=  '</label>';
		$output .= '</p>';
		$output .= '<p class="description">';
		$output .= esc_html__( 'Redirect file access requests to the Amazon S3 Object URL.', 'groups-file-access' );
		$output .= ' ';
		$output .= esc_html__( 'When enabled, the requests for site-based URLs are redirected to temporary Object URLs of the objects on Amazon S3.', 'groups-file-access' );
		$output .= ' ';
		$output .= esc_html__( 'If this is disabled, we act as a proxy and serve files as if they were hosted directly on the server.', 'groups-file-access' );
		$output .= '</p>';
		// direct enabled => cannot enable redirect
		$output .= '<script type="text/javascript">';
		$output .= sprintf( "const amazon_s3_direct = document.getElementById( '%s' );", esc_js( self::AMAZON_S3_DIRECT ) );
		$output .= sprintf( "const amazon_s3_redirect = document.getElementById( '%s' );", esc_js( self::AMAZON_S3_REDIRECT ) );
		$output .= 'amazon_s3_direct.addEventListener( "change", function() {';
		$output .= 'if ( event.currentTarget.checked ) {';
		$output .= 'amazon_s3_redirect.checked = false;';
		$output .= 'amazon_s3_redirect.setAttribute( "readonly", "readonly" );';
		$output .= 'amazon_s3_redirect.setAttribute( "disabled", "disabled" );';
		$output .= '} else {';
		$output .= 'amazon_s3_redirect.removeAttribute( "readonly" );';
		$output .= 'amazon_s3_redirect.removeAttribute( "disabled" );';
		$output .= '}';
		$output .= '} );';
		$output .= 'amazon_s3_direct.dispatchEvent( new Event( "change" ) );';
		$output .= '</script>';

		$output .= '</div>'; // .manage

		$output .= '<div class="manage">';

		$output .= '<h2>' . esc_html__( 'Serving Files', 'groups-file-access' ) . '</h2>';

		$output .= '<h3>' . esc_html__( 'MIME Types', 'groups-file-access' ) . '</h3>';

		$output .= '<p>';
		$output .= '<label>';
		$output .= '<input name="' . esc_attr( self::APPLY_MIME_TYPES ) . '" type="checkbox" ' . ( $apply_mime_types ? ' checked="checked" ' : '' ) . ' />';
		$output .= ' ';
		$output .= esc_html__( 'Use specific MIME types', 'groups-file-access' );
		$output .=  '</label>';
		$output .= '</p>';

		$output .= '<p class="description">';
		$output .= wp_kses_post( __( 'If enabled, the determined MIME type is used as the content type for files served. Otherwise, <code>application/octet-stream</code> is used.', 'groups-file-access' ) );
		$output .= '</p>';

		$output .= '<h3>' . esc_html__( 'Content Disposition', 'groups-file-access' ) . '</h3>';

		$output .= '<p>';
		$output .= '<label>';
		$output .= '<select name="' . self::CONTENT_DISPOSITION . '">';
		$output .= esc_html__( 'Content Disposition Value', 'groups-file-access' );
		$output .= ' ';
		$output .= sprintf(
			'<option value="%s" %s>%s</option>',
			esc_attr( self::CONTENT_DISPOSITION_ATTACHMENT ),
			$content_disposition == self::CONTENT_DISPOSITION_ATTACHMENT ? ' selected="selected" ' : '',
			esc_html( self::CONTENT_DISPOSITION_ATTACHMENT . ' - ' . __( 'user controlled display', 'groups-file-access' ) )
		); // WPCS: XSS ok.
		$output .= sprintf(
			'<option value="%s" %s>%s</option>',
			esc_attr( self::CONTENT_DISPOSITION_INLINE ),
			$content_disposition == self::CONTENT_DISPOSITION_INLINE ? ' selected="selected" ' : '',
			esc_html( self::CONTENT_DISPOSITION_INLINE . ' - ' . __( 'displayed automatically', 'groups-file-access' ) )
		);
		$output .=  '</select>';
		$output .=  '</label>';
		$output .= '<p class="description">';
		$output .= wp_kses_post( __( 'An <code>inline</code> content-disposition means that the file should be automatically displayed.', 'groups-file-access' ) );
		$output .= ' ' ;
		$output .= wp_kses_post( __( 'An <code>attachment</code> content-disposition, is not displayed automatically and requires some form of action from the user to open it.', 'groups-file-access' ) );
		$output .= '</p>';
		$output .= '</p>';

		$output .= '<h3>' . esc_html__( 'Session Access', 'groups-file-access' ) . '</h3>';

		$output .= '<p>';
		$output .= '<label>';
		$output .= '<input name="' . esc_attr( self::SESSION_ACCESS ) . '" type="checkbox" ' . ( $session_access ? ' checked="checked" ' : '' ) . ' />';
		$output .= ' ';
		$output .= esc_html__( 'Enable temporary access URLs', 'groups-file-access' );
		$output .=  '</label>';
		$output .= '</p>';

		$output .= '<p class="description">';
		$output .= wp_kses_post( __( 'If enabled, all file URLs and links that are rendered using the <code>[groups_file_url]</code> or the <code>[groups_file_link]</code> shortcode will have a session access identifier appended automatically for authorized users.', 'groups-file-access' ) );
		$output .= ' ';
		$output .= esc_html__( 'These URLs grant access to files without the need to be logged in.', 'groups-file-access' );
		$output .= ' ';
		$output .= wp_kses_post( __( 'Session access can be granted for specific files without the need for this option to be enabled, by specifying the <code>session_access="yes"</code> shortcode attribute.', 'groups-file-access' ) );
		$output .= '</p>';

		$output .= '<p>';
		$output .= '<label>';
		$output .= esc_html__( 'Temporary access timeout', 'groups-file-access' );
		$output .= ' ';
		$output .= sprintf( '<input name="' . esc_attr( self::SESSION_ACCESS_TIMEOUT ) . '" type="text" value="%d" style="width:5em;text-align:right;"/>', esc_attr( $session_access_timeout ) );
		$output .= ' ';
		$output .= wp_kses_post( __( '<em>seconds</em>', 'groups-file-access' ) );
		$output .=  '</label>';
		$output .= '</p>';

		$output .= '<p class="description">';
		$output .= esc_html__( 'Temporary access is valid during the period of time established through the timeout.', 'groups-file-access' );
		$output .= ' ';
		$output .= esc_html__( 'If the temporary URL has not been accessed during that period of time, the link is invalid and access is refused.', 'groups-file-access' );
		$output .= ' ';
		$output .= esc_html__( 'The time period is extended for the duration of the timeout while the URL is accessed.', 'groups-file-access' );
		$output .= '</p>';

		$output .= '</div>'; // .manage

		$output .= '<div class="manage">';

		//
		// Notifications
		//
		$output .= '<h2>' . esc_html__( 'Notifications', 'groups-file-access' ) . '</h2>';

		$output .= '<h3>' . esc_html__( 'Notify the admin', 'groups-file-access' ) . '</h3>';
		$output .= '<p>';
		$output .= '<input name="' . esc_attr( self::NOTIFY_ADMIN ) . '" type="checkbox" ' . ( $notify_admin ? ' checked="checked" ' : '' ) . ' />';
		$output .= '&nbsp;';
		$output .= '<label for="' . esc_attr( self::NOTIFY_ADMIN ) . '">' . esc_html__( 'Notify the site administrator', 'groups-file-access' ) . '</label>';
		$output .= '</p>';
		$output .= '<p class="description">' . esc_html__( 'Sends a notification email to the site administrator when a file has been accessed.', 'groups-file-access' ) . '</p>';

		$output .= '<p>';
		$output .= '<label style="display:block" for="' . self::ADMIN_EMAIL . '">' . esc_html__( 'Notification email', 'groups-file-access' ) . '</label>';
		$output .= sprintf(
			'<input class="widefat" name="%s" type="text" value="%s" placeholder="%s"/>',
			esc_attr( self::ADMIN_EMAIL ),
			esc_attr( $admin_email ),
			esc_attr( get_bloginfo( 'admin_email' ) )
		);
		$output .= '</p>';

		$output .= '<h3>' . esc_html__( 'Admin notification', 'groups-file-access' ) . '</h3>';
		$output .= '<p>';
		$output .= '<label style="display:block" for="' . self::ADMIN_SUBJECT . '">' . esc_html__( 'Notification email subject', 'groups-file-access' ) . '</label>';
		$output .= '<input class="widefat" name="' . esc_attr( self::ADMIN_SUBJECT ) . '" type="text" value="' . esc_attr( htmlentities( stripslashes( $admin_subject ) ) ) . '" />';
		$output .= '</p>';
		$output .= '<p>';
		$output .= esc_html__( 'The default subject is:', 'groups-file-access' );
		$output .= '</p>';
		$output .= '<p>';
		$output .= sprintf(
			'<input class="widefat" readonly="readonly" value="%s">',
			esc_attr( htmlentities( stripslashes( self::ADMIN_DEFAULT_SUBJECT ) ) )
		);
		$output .= '</p>';
		$output .= '<p>';
		$output .= '<label style="display:block" for="' . esc_attr( self::ADMIN_MESSAGE ) . '">' . esc_html__( 'Notification email message', 'groups-file-access' ) . '</label>';
		$output .= '<textarea class="widefat" style="font-family:monospace;height:10em;" name="' . esc_attr( self::ADMIN_MESSAGE ) . '">' . htmlentities( stripslashes( $admin_message ) ) . '</textarea>';
		$output .= '</p>';
		$output .= '<p>';
		$output .= esc_html__( 'The default message is:', 'groups-file-access' );
		$output .= '</p>';
		$output .= '<textarea class="widefat" readonly="readonly" style="font-family:monospace;height:5em;">';
		$output .= htmlentities( stripslashes( self::ADMIN_DEFAULT_MESSAGE ) );
		$output .= '</textarea>';
		$output .= '<p class="description">' .wp_kses_post(  __( 'The message format is HTML. Use <code>&lt;br/&gt;</code> for line breaks.', 'groups-file-access' ) ) . '</p>';
		$output .= '<p class="description">';
		$output .= esc_html__( 'These tokens can be used in the subject and message:', 'groups-file-access' );
		$output .= '<code>';
		$output .= '[file_id] [file_path] [file_url] [ip] [server_ip] [referrer] [request_uri] [request] [datetime] [site_title] [site_url] [user_id] [username]';
		$output .= '</code>';
		$output .= '</p>';

		$output .= '<h4>' . esc_html__( 'Amazon S3', 'groups-file-access' ) . '</h4>';
		$output .= '<p>';
		$output .= esc_html__( 'Notifications are sent for files served via Amazon S3 when Direct Object URLs are disabled.', 'groups-file-access' );
		$output .= '</p>';
		$output .= '<p>';
		$output .= esc_html__( 'No access notifications are sent for files served via Amazon S3 when Direct Object URLs are enabled.', 'groups-file-access' );
		$output .= ' ';
		$output .= sprintf(
			esc_html__( 'Review %s instead.', 'groups-file-access' ),
			'<a href="https://docs.aws.amazon.com/AmazonS3/latest/userguide/monitoring-overview.html" target="_blank">Monitoring Amazon S3</a>'
		);
		$output .= '</p>';

		$output .= '</div>'; // .manage

		$output .= '<div class="manage">';
		$output .= '<h2>' . esc_html__( 'User Profiles', 'groups-file-access' ) . '</h2>';
		$output .= '<p>';
		$output .= '<input name="' . self::USER_PROFILE_SHOW_FOR_ADMINS . '" type="checkbox" ' . ( $user_profile_show_for_admins ? ' checked="checked" ' : '' ) . ' />';
		$output .= '&nbsp;';
		$output .= '<label for="' . self::USER_PROFILE_SHOW_FOR_ADMINS . '">' . esc_html__( 'Always show service keys and files to users who can administer Groups', 'groups-file-access' ) . '</label>';
		$output .= '</p>';
		$output .= '<p>';
		$output .= '<input name="' . self::USER_PROFILE_SHOW_SERVICE_KEY . '" type="checkbox" ' . ( $user_profile_show_service_key ? ' checked="checked" ' : '' ) . ' />';
		$output .= '&nbsp;';
		$output .= '<label for="' . self::USER_PROFILE_SHOW_SERVICE_KEY . '">' . esc_html__( 'Show service keys in user profiles', 'groups-file-access' ) . '</label>';
		$output .= '</p>';
		$output .= '<p>';
		$output .= '<input name="' . self::USER_PROFILE_SHOW_FILES . '" type="checkbox" ' . ( $user_profile_show_files ? ' checked="checked" ' : '' ) . ' />';
		$output .= '&nbsp;';
		$output .= '<label for="' . self::USER_PROFILE_SHOW_FILES . '">' . esc_html__( 'Show files in user profiles', 'groups-file-access' ) . '</label>';
		$output .= '</p>';
		$output .= '</div>'; // .manage

		$output .= '<div class="manage">';
		$output .= '<h2>' . esc_html__( 'Redirect', 'groups-file-access' ) . '</h2>';
		$output .= '<p>';
		$output .= '<input name="' . self::LOGIN_REDIRECT . '" type="checkbox" ' . ( $login_redirect ? ' checked="checked" ' : '' ) . ' />';
		$output .= '&nbsp;';
		$output .= '<label for="' . self::LOGIN_REDIRECT . '">' . esc_html__( 'Redirect to the WordPress login when a user who is not logged in tries to access a file?', 'groups-file-access' ) . '</label>';
		$output .= '</p>';
		$output .= '</div>'; // .manage

		if ( !$is_sitewide_plugin ) {
			$output .= '<div class="manage">';
			$output .= '<h2>' . esc_html__( 'Delete data', 'groups-file-access' ) . '</h2>';
			$output .= '<p>';
			$output .= '<input name="' . self::DELETE_DATA . '" type="checkbox" ' . ( $delete_data ? ' checked="checked" ' : '' ) . ' />';
			$output .= '&nbsp;';
			$output .= '<label for="' . self::DELETE_DATA . '">' . wp_kses_post( __( 'Delete plugin data when the plugin is <strong>deleted</strong>?', 'groups-file-access' ) ) . '</label>';
			$output .= '</p>';
			$output .= '<p>';
			$output .= '<input name="' . self::DELETE_DATA_ON_DEACTIVATE . '" type="checkbox" ' . ( $delete_data_on_deactivate ? ' checked="checked" ' : '' ) . ' />';
			$output .= '&nbsp;';
			$output .= '<label for="' . self::DELETE_DATA_ON_DEACTIVATE . '">' . wp_kses_post( __( 'Delete all <em>Groups File Access</em> plugin data when the plugin is <strong>deactivated</strong>? This option is useful to clean up after testing.', 'groups-file-access' ) ) . '</label>';
			$output .= '</p>';
			$output .= '<p class="description warning">' . wp_kses_post( __( 'CAUTION: These options will delete all plugin data and settings when the plugin is <strong>deactivated</strong> or <strong>deleted</strong>. By enabling any of these options, you agree to be solely responsible for any loss of data or any other consequences thereof.', 'groups-file-access' ) ) . '</p>';
			$output .= '<p class="description">' . wp_kses_post( sprintf( __( 'They will not delete any files in the <code>%s</code> directory.', 'groups-file-access' ), GFA_UPLOADS_DIR ) ) . '</p>';
			$output .= '<p class="description">' . wp_kses_post( __( 'Deletion will only take effect if the <strong>Groups</strong> plugin is activated.', 'groups-file-access' ) ) . '</p>';
			$output .= '</div>'; // .manage
		}

		$output .= '<div>';
		$output .= '<p>';
		$output .= wp_nonce_field( self::SET_ADMIN_OPTIONS, self::NONCE, true, false );
		$output .= '<input class="button button-primary" type="submit" name="submit" value="' . esc_attr__( 'Save', 'groups-file-access' ) . '"/>';
		$output .= '</p>';
		$output .= '</div>';

		$output .= '</div>';
		$output .= '</form>';

		require_once GFA_VIEWS_LIB . '/class-gfa-help.php';
		$output .= GFA_Help::footer( true );

		echo $output; // WPCS: XSS ok.
	}

	public static function groups_network_admin_file_access() {

		$output = '';

		if ( !current_user_can( GROUPS_ADMINISTER_OPTIONS ) ) {
			wp_die( esc_html__( 'Access denied.', 'groups-file-access' ) );
		}

		$options = get_option( self::PLUGIN_OPTIONS , array() );
		if ( isset( $_POST['submit'] ) ) {
			if ( wp_verify_nonce( $_POST[self::NONCE], self::SET_ADMIN_OPTIONS ) ) {
				$options[self::NETWORK_DELETE_DATA_ON_DEACTIVATE] = !empty( $_POST[self::NETWORK_DELETE_DATA_ON_DEACTIVATE] );
				update_option( self::PLUGIN_OPTIONS, $options );
			}
		}

		$delete_data_on_deactivate = isset( $options[self::NETWORK_DELETE_DATA_ON_DEACTIVATE] ) ? $options[self::NETWORK_DELETE_DATA_ON_DEACTIVATE] : false;

		$output .= '<div>';
		$output .= '<h1>';
		$output .= esc_html__( 'Groups File Access', 'groups-file-access' );
		$output .= '</h1>';
		$output .= '</div>';

		$output .= '<form action="" name="options" method="post">';
		$output .= '<div style="margin-right:1em">';

		$output .= '<div class="manage">';
		$output .= '<h2>' . esc_html__( 'Network delete data', 'groups-file-access' ) . '</h2>';
		$output .= '<p>';
		$output .= '<input name="' . esc_attr( self::NETWORK_DELETE_DATA_ON_DEACTIVATE ) . '" type="checkbox" ' . ( $delete_data_on_deactivate ? ' checked="checked" ' : '' ) . ' />';
		$output .= '&nbsp;';
		$output .= '<label for="' . esc_attr( self::NETWORK_DELETE_DATA_ON_DEACTIVATE ) . '">' . wp_kses_post( __( 'Delete all <em>Groups File Access</em> plugin data on <strong>all sites</strong> when the plugin is <strong>network deactivated</strong>.', 'groups-file-access' ) ) . '</label>';
		$output .= '</p>';
		$output .= '<p>';
		$output .= '<ul>';
		$output .= '<li class="description warning">';
		$output .= wp_kses_post( __( 'CAUTION: This option will delete all plugin data and settings <strong>on all sites</strong> when the plugin is <strong>network deactivated</strong>. By enabling the option, you agree to be solely responsible for any loss of data or any other consequences thereof.', 'groups-file-access' ) );
		$output .= '</li>';
		$output .= '<li class="description">';
		$output .= esc_html__( 'This option will not delete any files that have been uploaded to the sites.', 'groups-file-access' );
		$output .= '</li>';
		$output .= '<li class="description">';
		$output .= wp_kses_post( __( 'This option will only take effect if the <strong>Groups</strong> plugin is activated.', 'groups-file-access' ) );
		$output .= '</li>';
		$output .= '</ul>';
		$output .= '</p>';

		$output .= '<p>';
		$output .= wp_nonce_field( self::SET_ADMIN_OPTIONS, self::NONCE, true, false );
		$output .= '<input class="button" type="submit" name="submit" value="' . esc_attr__( 'Save', 'groups-file-access' ) . '"/>';
		$output .= '</p>';
		$output .= '</div>';

		$output .= '</div>';
		$output .= '</form>';

		require_once GFA_VIEWS_LIB . '/class-gfa-help.php';
		$output .= GFA_Help::footer( true );

		echo $output; // WPCS: XSS ok.
	}
}
