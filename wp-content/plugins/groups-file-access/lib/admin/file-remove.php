<?php
/**
 * file-remove.php
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
 * Shows form to confirm removal of a file.
 *
 * @param int $file_id file id
 */
function gfa_admin_files_remove( $file_id ) {

	global $wpdb;

	if ( !current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups-file-access' ) );
	}

	$file_table = _groups_get_tablename( 'file' );
	$file_id = intval( $file_id );
	$file = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $file_table WHERE file_id=%d", $file_id ) );
	if ( empty( $file ) ) {
		wp_die( esc_html__( 'No such file.', 'groups-file-access' ) );
	}

	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$current_url = remove_query_arg( 'action', $current_url );
	$current_url = remove_query_arg( 'file_id', $current_url );

	$output =
		'<div class="manage-files">' .
		'<div>' .
			'<h1>' .
				__( 'Remove a file', 'groups-file-access' ) .
			'</h1>' .
		'</div>' .
		'<form id="remove-file" action="' . $current_url . '" method="post">' .
		'<div class="file remove">' .
		'<input id="file-id-field" name="file-id-field" type="hidden" value="' . esc_attr( intval( $file->file_id ) ) . '"/>' .
		'<ul>' .
		'<li>' . sprintf( esc_html__( 'Name : %s', 'groups-file-access' ), esc_html( $file->name ) ) . '</li>' .
		'<li>' . sprintf( esc_html__( 'Description : %s', 'groups-file-access' ), esc_html( $file->description ) ) . '</li>' .
		'<li>' . sprintf( esc_html__( 'Path : %s', 'groups-file-access' ), esc_html( $file->path ) ) . '</li>' .
		'</ul> ' .
		wp_nonce_field( 'files-remove', GROUPS_ADMIN_GROUPS_NONCE, true, false ) .
		'<input class="button button-primary" type="submit" value="' . esc_attr__( 'Remove', 'groups-file-access' ) . '"/>' .
		'<input type="hidden" value="remove" name="action"/>' .
		' ' .
		'<a class="cancel button" href="' . $current_url . '">' . esc_attr__( 'Cancel', 'groups-file-access' ) . '</a>' .
		'</div>' .
		'</div>' . // .file.remove
		'</form>' .
		'</div>'; // .manage-files

	require_once GFA_VIEWS_LIB . '/class-gfa-help.php';
	$output .= GFA_Help::footer();

	echo $output; // WPCS: XSS ok.
} // function

/**
 * Handle remove form submission.
 */
function gfa_admin_files_remove_submit() {

	global $wpdb;

	$result = false;

	if ( !current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups-file-access' ) );
	}

	if ( !wp_verify_nonce( $_POST[GROUPS_ADMIN_GROUPS_NONCE], 'files-remove' ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups-file-access' ) );
	}

	$file_id = isset( $_POST['file-id-field'] ) ? $_POST['file-id-field'] : null;
	if ( $file_id ) {
		$file_id = intval( $file_id );
		$file_table = _groups_get_tablename( 'file' );
		$file_group_table = _groups_get_tablename( 'file_group' );
		$file_meta_table = _groups_get_tablename( 'file_meta' );
		if ( $file = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $file_table WHERE file_id=%d", $file_id ) ) ) {
			if ( !file_exists( $file->path ) || @unlink( $file->path ) ) {
				$wpdb->query( $wpdb->prepare( "DELETE FROM $file_table WHERE file_id = %d", $file_id ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM $file_group_table WHERE file_id = %d", $file_id ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM $file_meta_table WHERE file_id = %d", $file_id ) );
				$result = $file_id;
				do_action( "groups_deleted_file", $file );
			}
		}
	}
	return $result;
} // function
