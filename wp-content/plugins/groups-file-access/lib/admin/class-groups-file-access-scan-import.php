<?php
/**
 * class-groups-file-access-scan-import.php
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
 * @since groups-file-access 1.2.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

/**
 * Scan and import data facility.
 */
class Groups_File_Access_Scan_Import {

	const MAX_LINE_LENGTH   = 10384; // increased but not used since 1.3.1

	const FILENAME_INDEX    = 0;
	const FILE_ID_INDEX     = 1;
	const NAME_INDEX        = 2;
	const DESCRIPTION_INDEX = 3;
	const MAX_COUNT_INDEX   = 4;
	const GROUP_NAMES_INDEX = 5;
	const TYPE_INDEX        = 6;
	const REGION_INDEX      = 7;
	const BUCKET_INDEX      = 8;
	const KEY_INDEX         = 9;

	const MAX_INVALID_LINES_SHOWN = 10;

	private static $admin_messages = array();

	/**
	 * Init hook to catch import file generation request.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'wp_init' ) );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
	}

	/**
	 * Prints admin notices.
	 */
	public static function admin_notices() {
		if ( !empty( self::$admin_messages ) ) {
			$output = '<div style="padding:1em;margin:1em;border:1px solid #aa0;border-radius:4px;background-color:#ffe;color:#333;">';
			foreach ( self::$admin_messages as $msg ) {
				$output .= '<p>';
				$output .= $msg;
				$output .= '</p>';
			}
			$output .= '</div>';
			echo wp_kses_post( $output );
		}
	}

	/**
	 * Catch and act on valid file action requests.
	 */
	public static function wp_init() {
		if ( isset( $_REQUEST['action'] ) ) {
			switch( $_REQUEST['action'] ) {
				case 'import_files' :
					if ( isset( $_REQUEST['gfa-import'] ) && wp_verify_nonce( $_REQUEST['gfa-import'], 'import' ) ) {
						self::import_files();
					}
					break;
				case 'export_files' :
					if ( isset( $_REQUEST['gfa-export'] ) && wp_verify_nonce( $_REQUEST['gfa-export'], 'export' ) ) {
						self::export_files();
					}
					break;
				case 'export_file_access' :
					if ( isset( $_REQUEST['gfa-export-file-access'] ) && wp_verify_nonce( $_REQUEST['gfa-export-file-access'], 'export-file-access' ) ) {
						self::export_file_access();
					}
					break;
				case 'scan_files' :
					if ( isset( $_REQUEST['gfa-scan'] ) && wp_verify_nonce( $_REQUEST['gfa-scan'], 'scan' ) ) {
						self::scan_files();
					}
					break;
			}
		}
	}

	/**
	 * Renders the import section.
	 */
	public static function admin_import_files() {

		$output = '<div class="manage-files">';

		//
		// Import files
		//

		$output .= '<div class="manage import">';
		$output .= '<form enctype="multipart/form-data" name="import-subscribers" method="post" action="">';
		$output .= '<div>';
		$output .= '<h1>' . esc_html__( 'Import Files', 'groups-file-access' ) . '</h1>';

		$output .= '<p>';
		$output .= wp_kses_post( sprintf( __( 'Here you can import file data in bulk from a text file, after uploading your files to <code>%s</code> via FTP.', 'groups-file-access' ), GFA_UPLOADS_DIR ) );
		$output .= '</p>';

		$output .= '<ul class="info">';
		$output .= '<li>';
		$output .= wp_kses_post( sprintf( __( '<strong>Adding new files in bulk</strong> : After uploading new files via FTP to the <code>%s</code> folder, use the <em>Scan</em> function below to automatically create an import file. Use the file obtained to import your uploaded files here.', 'groups-file-access' ), GFA_UPLOADS_DIR ) );
		$output .= '</li>';
		$output .= '<li>';
		$output .= wp_kses_post( __( '<strong>Modifying existing file entries in bulk</strong> : Use the <em>Export</em> function below to create an import file based on existing entries.', 'groups-file-access' ) );
		$output .= '</li>';
		$output .= '</ul>';

		$output .= '<p>';
		$output .= esc_html__( 'The accepted file-format is a plain text file with values separated by tabs provided on one line per file and in this order:', 'groups-file-access' );
		$output .= '</p>';
		$output .= '<p>';
		$output .= "<code>filename\tfile_id\tname\tdescription\tmax_count\tgroup_names</code>";
		$output .= '</p>';
		$output .= '<p>';
		$output .= esc_html__( 'Description of fields:', 'groups-file-access' );
		$output .= '</p>';
		$output .= '<ul>';
		$output .= '<li>';
		$output .= wp_kses_post( sprintf( __( '<code>filename</code> - <strong>required</strong> - The full filename of the file uploaded to the <code>%s</code> directory. Do not include the full path.', 'groups-file-access' ), GFA_UPLOADS_DIR ) );
		$output .= ' ';
		$output .= wp_kses_post( __( 'A line that refers to the <code>filename</code> of an existing entry will update the information related to the entry.', 'groups-file-access' ) );
		$output .= '</li>';
		$output .= '<li>';
		$output .= wp_kses_post( __( '<code>file_id</code> - <em>optional</em> - The <em>Id</em> of an existing file entry. If provided, the existing file will be <strong>deleted</strong> and the new file will be related to the entry.', 'groups-file-access' ) );
		$output .= '</li>';
		$output .= '<li>';
		$output .= wp_kses_post( __( '<code>name</code> - <em>optional</em> - A descriptive name for the file. If left empty, the filename will be used.', 'groups-file-access' ) );
		$output .= '</li>';
		$output .= '<li>';
		$output .= wp_kses_post( __( '<code>description</code> - <em>optional</em> - A detailed description of the file.', 'groups-file-access' ) );
		$output .= '</li>';
		$output .= '<li>';
		$output .= wp_kses_post( __( '<code>max_count</code> - <em>optional</em> - The maximum number of allowed accesses to the file per user. Leave empty or use 0 for unlimited accesses.', 'groups-file-access' ) );
		$output .= '</li>';
		$output .= '<li>';
		$output .= wp_kses_post( __( '<code>group_names</code> - <em>optional</em> - The names of the groups that are allowed to access the file, separated by comma. If empty, the file can not be accessed until a group is assigned.', 'groups-file-access' ) );
		$output .= '</li>';
		$output .= '</ul>';
		$output .= '<p>';
		$output .= wp_kses_post( sprintf( __( 'The files must have been uploaded to the <code>%s</code> directory before starting to import.', 'groups-file-access' ), GFA_UPLOADS_DIR ) );
		$output .= '</p>';

		$output .= '<p>';
		$output .= '<label>';
		$output .= sprintf( '<input type="checkbox" name="test_import" %s />', !empty( $_POST['test_import'] ) ? ' checked="checked" ' : '' );
		$output .= ' ';
		$output .= esc_html__( 'Test only', 'groups-file-access' );
		$output .= ' ';
		$output .= '<span class="description">';
		$output .= esc_html__( 'If checked, no changes will be made.', 'groups-file-access' );
		$output .= '</span>';
		$output .= '</label>';
		$output .= '</p>';

		$output .= '<p>';
		$output .= '<label>';
		$output .= sprintf( '<input type="checkbox" name="delete_files" %s />', !empty( $_POST['delete_files'] ) ? ' checked="checked" ' : '' );
		$output .= ' ';
		$output .= esc_html__( 'Delete replaced files', 'groups-file-access' );
		$output .= ' ';
		$output .= '<span class="description">';
		$output .= esc_html__( 'If checked, existing files that are replaced by new ones are deleted.', 'groups-file-access' );
		$output .= '</span>';
		$output .= '</label>';
		$output .= '</p>';

		$output .= wp_nonce_field( 'import', 'gfa-import', true, false );
		$output .= '<div class="buttons">';
		$output .= sprintf( '<input type="file" name="file" /> <input class="import button button-primary" type="submit" name="submit" value="%s" />', esc_attr__( 'Import', 'groups-file-access' ) );
		$output .= '<input type="hidden" name="action" value="import_files" />';

		$output .= '</div>';
		$output .= '</div>';
		$output .= '</form>';
		$output .= '</div>';

		//
		// Export files
		//

		$current_url  = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$output .= '<div class="manage">';
		$output .= '<form name="export-files" method="post" action="">';
		$output .= '<div>';
		$output .= '<h2>' . esc_html__( 'Export Files', 'groups-file-access' ) . '</h2>';
		$output .= '<p>';
		$output .= wp_kses_post( __( 'This will create a text file (in the supported import file-format) with current data for all files managed in the <strong>Groups > Files</strong> section.', 'groups-file-access' ) );
		$output .= '</p>';
		$output .= wp_nonce_field( 'export', 'gfa-export', true, false );
		$output .= '<div class="buttons">';
		$output .= sprintf( '<input class="export button button-primary" type="submit" name="submit" value="%s" />', esc_attr__( 'Export', 'groups-file-access' ) );
		$output .= '<input type="hidden" name="action" value="export_files" />';
		$output .= '</div>';
		$output .= '<div class="export-file-access">';
		$output .= sprintf(
			'<a href="%s" target="_blank">%s</a>',
			esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'export_file_access' ), $current_url ), 'export-file-access', 'gfa-export-file-access' ) ),
			esc_html__( 'Export File Access Table', 'groups-file-access' )
		);
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</form>';
		$output .= '</div>';

		//
		// Scan for files
		//

		$output .= '<div class="manage">';
		$output .= '<form name="scan-files" method="post" action="">';
		$output .= '<div>';
		$output .= '<h2>' . esc_html__( 'Scan for Files', 'groups-file-access' ) . '</h2>';
		$output .= '<p>';
		$output .= wp_kses_post( sprintf( __( 'This will create a text file (in the supported import file-format) with current data for all files in the <code>%s</code> folder.', 'groups-file-access' ), GFA_UPLOADS_DIR ) );
		$output .= '</p>';

		$output .= '<p>';
		$output .= '<label>';
		$output .= sprintf( '<input type="checkbox" name="exclude_existing" %s />', !empty( $_POST['exclude_existing'] ) || empty( $_POST['action'] ) || ( $_POST['action'] != 'scan_files' ) ? ' checked="checked" ' : '' );
		$output .= ' ';
		$output .= esc_html__( 'Exclude existing file entries', 'groups-file-access' );
		$output .= ' ';
		$output .= '<span class="description">';
		$output .= esc_html__( 'If checked, only new files that do not already have an existing entry are included.', 'groups-file-access' );
		$output .= '</span>';
		$output .= '</label>';
		$output .= '</p>';

		$output .= '<p>';
		$output .= esc_html__( 'Predefined fields: These can be left empty, otherwise the value will be used in common for all scanned files.', 'groups-file-access' );
		$output .= '</p>';

		$output .= '<p>';
		$output .= esc_html__( 'The names for file entries are automatically derived from their filename.', 'groups-file-access' );
		$output .= '</p>';

		$output .= '<p>';
		$output .= '<label>';
		$output .= esc_html__( 'Description', 'groups-file-access' );
		$output .= ' ';
		$output .= sprintf( '<input type="text" name="description" value="%s" />', !empty( $_POST['description'] ) ? esc_attr( trim( $_POST['description'] ) ) : '' );
		$output .= ' ';
		$output .= '<span class="description">';
		$output .= esc_html__( 'If indicated, the same description is used for all new files that are found.', 'groups-file-access' );
		$output .= '</span>';
		$output .= '</label>';
		$output .= '</p>';

		$output .= '<p>';
		$output .= '<label>';
		$output .= esc_html__( 'Max #', 'groups-file-access' );
		$output .= ' ';
		$output .= sprintf( '<input type="text" name="max_count" value="%s" />', !empty( $_POST['max_count'] ) ? max( array( 0, intval( trim( $_POST['max_count'] ) ) ) ) : '' );
		$output .= ' ';
		$output .= '<span class="description">';
		$output .= esc_html__( 'Maximum number of accesses per user. Unlimited when empty or 0.', 'groups-file-access' );
		$output .= '</span>';
		$output .= '</label>';
		$output .= '</p>';

		$output .= '<p>';
		$output .= '<label>';
		$output .= esc_html__( 'Groups', 'groups-file-access' );
		$output .= ' ';
		$output .= sprintf( '<input type="text" name="group_names" value="%s" />', !empty( $_POST['group_names'] ) ? esc_attr( trim( $_POST['group_names'] ) ) : '' );
		$output .= ' ';
		$output .= '<span class="description">';
		$output .= esc_html__( 'Indicate one or more names of groups that should have access to the files. Separate multiple names by comma. The groups do not need to exist now, but they must exist when the files are imported.', 'groups-file-access' );
		$output .= '</span>';
		$output .= '</label>';
		$output .= '</p>';

		$output .= wp_nonce_field( 'scan', 'gfa-scan', true, false );

		$output .= '<div class="buttons">';
		$output .= sprintf( '<input class="scan button button-primary" type="submit" name="submit" value="%s" />', esc_attr__( 'Scan', 'groups-file-access' ) );
		$output .= '<input type="hidden" name="action" value="scan_files" />';
		$output .= '</div>';

		$output .= '</div>';
		$output .= '</form>';
		$output .= '</div>';

		$output .= '</div>';

		echo $output; // WPCS: XSS ok.
	}

	/**
	 * Import data from uploaded file.
	 *
	 * @return int number of records created
	 */
	public static function import_files() {

		global $wpdb;

		$charset = get_bloginfo( 'charset' );
		$now     = date( 'Y-m-d H:i:s', time() );

		$test_import = !empty( $_POST['test_import'] );
		$delete_files = !empty( $_POST['delete_files'] );

		$options = get_option( Groups_File_Access::PLUGIN_OPTIONS , array() );
		$amazon_s3  = isset( $options[Groups_File_Access::AMAZON_S3] ) ? $options[Groups_File_Access::AMAZON_S3] : Groups_File_Access::AMAZON_S3_DEFAULT;
		$amazon_s3_access_key = isset( $options[Groups_File_Access::AMAZON_S3_ACCESS_KEY] ) ? $options[Groups_File_Access::AMAZON_S3_ACCESS_KEY] : '';
		$amazon_s3_secret_key = isset( $options[Groups_File_Access::AMAZON_S3_SECRET_KEY] ) ? $options[Groups_File_Access::AMAZON_S3_SECRET_KEY] : '';

		if ( isset( $_FILES['file'] ) ) {
			if ( $_FILES['file']['error'] == UPLOAD_ERR_OK ) {
				$tmp_name = $_FILES['file']['tmp_name'];
				if ( file_exists( $tmp_name ) ) {
					if ( $h = @fopen( $tmp_name, 'r' ) ) {

						$imported           = 0;
						$entries_added      = 0;
						$entries_updated    = 0;
						$empty              = 0; // also comment lines (starting with ; or # )
						$invalid            = 0;
						$invalid_lines      = array();
						$line_number        = 0;
						$skipped_file       = 0;

						$group_table      = _groups_get_tablename( 'group' );
						$file_table       = _groups_get_tablename( 'file' );
						$file_group_table = _groups_get_tablename( 'file_group' );
						$file_meta_table  = _groups_get_tablename( 'file_meta' );

						while ( $line = fgets( $h ) ) {

							$line_number++;
							$line = preg_replace( '/\r|\n/', '', $line );
							$data = explode( "\t", $line );

							$filename     = !empty( $data[self::FILENAME_INDEX] ) ? $data[self::FILENAME_INDEX] : null;
							$file_id      = !empty( $data[self::FILE_ID_INDEX] ) ? intval( $data[self::FILE_ID_INDEX] ) : null;
							$name         = !empty( $data[self::NAME_INDEX] ) ? wp_filter_nohtml_kses( $data[self::NAME_INDEX] ) : $filename;
							$description  = !empty( $data[self::DESCRIPTION_INDEX] ) ? $data[self::DESCRIPTION_INDEX] : '';
							$max_count    = !empty( $data[self::MAX_COUNT_INDEX] ) ? max( array( 0, intval( $data[self::MAX_COUNT_INDEX] ) ) ) : 0;
							$_group_names = !empty( $data[self::GROUP_NAMES_INDEX] ) ? explode( ',', $data[self::GROUP_NAMES_INDEX] ) : array();
							// metas
							$type   = !empty( $data[self::TYPE_INDEX] ) ? sanitize_text_field( $data[self::TYPE_INDEX] ) : '';
							$region = !empty( $data[self::REGION_INDEX] ) ? sanitize_text_field( $data[self::REGION_INDEX] ) : '';
							$bucket = !empty( $data[self::BUCKET_INDEX] ) ? sanitize_text_field( $data[self::BUCKET_INDEX] ) : '';
							$key    = !empty( $data[self::KEY_INDEX] ) ? sanitize_text_field( $data[self::KEY_INDEX] ) : '';

							if ( ( strlen( $line ) > 0 ) && $line[0] !== ';' && $line[0] !== '#' ) {
								if (
									$type === '' && !empty( $filename ) && file_exists( GFA_UPLOADS_DIR . '/' . $filename ) && is_file( GFA_UPLOADS_DIR . '/' . $filename ) ||
									$type === 'amazon-s3' && !empty( $filename )
								) {

									if ( $type === '' ) {
										$path = GFA_File_Upload::path_filter( GFA_UPLOADS_DIR . '/' . $filename );
									}
									// path for S3 is done below

									// file id
									if ( $file_id !== null ) {
										if ( $file_id !== intval( $wpdb->get_var( $wpdb->prepare( "SELECT file_id FROM $file_table WHERE file_id = %d", $file_id ) ) ) ) {
											$invalid++;
											$invalid_lines[] = array(
												'line' => $line_number,
												'message' => sprintf( __( 'Invalid file_id %d', 'groups-file-access' ), $file_id )
											);
											continue;
										}
									}

									// group names
									$group_names = array();
									$invalid_group = false;
									foreach( $_group_names as $group_name ) {
										$group_name = wp_strip_all_tags( trim ( $group_name ) );
										if ( Groups_Group::read_by_name( $group_name ) ) {
											$group_names[] = $group_name;
										} else {
											$invalid++;
											$invalid_lines[] = array(
												'line' => $line_number,
												'message' => sprintf( __( 'Invalid group name %s', 'groups-file-access' ), $group_name )
											);
											$invalid_group = true;
											break;
										}
									}
									if ( $invalid_group ) {
										continue;
									}

									// metas
									$invalid_meta = false;
									switch ( $type ) {
										case '';
											break;
										case 'amazon-s3':
											// We could check regions and raise an error here but are flexible to allow import of regions that have been added
											// $regions = GFA_Amazon_S3::get_regions();
											// if ( !key_exists( $region, $regions ) ) {
											//
											// }
											$invalid_now = $invalid;
											if ( strlen( $region ) === 0 ) {
												$invalid++;
												$invalid_lines[] = array(
													'line' => $line_number,
													'message' => __( 'Missing region', 'groups-file-access' )
												);
											}
											if ( strlen( $bucket ) === 0 ) {
												$invalid++;
												$invalid_lines[] = array(
													'line' => $line_number,
													'message' => __( 'Missing bucket', 'groups-file-access' )
												);
											}
											if ( strlen( $key ) === 0 ) {
												$invalid++;
												$invalid_lines[] = array(
													'line' => $line_number,
													'message' => __( 'Missing key', 'groups-file-access' )
												);
											}
											if ( $invalid > $invalid_now ) {
												$invalid_meta = true;
											}
											break;
										default:
											$invalid++;
											$invalid_lines[] = array(
												'line' => $line_number,
												'message' => sprintf( __( 'Invalid type %s', 'groups-file-access' ), $type )
											);
											$invalid_meta = true;
									}
									if ( $invalid_meta ) {
										continue;
									}

									if ( $type === 'amazon-s3' ) {
										require_once GFA_AWS_LIB . '/aws-autoloader.php';

										try {
											$s3client = new S3Client( array(
												'version' => 'latest',
												'region'  => $region,
												'credentials' => array(
													'key' => $amazon_s3_access_key,
													'secret' => $amazon_s3_secret_key,
												)
											) );
											$path = $s3client->getObjectUrl( $bucket, $key );
										} catch ( Exception $ex ) {
											$invalid++;
											$invalid_lines[] = array(
												'line' => $line_number,
												'message' => sprintf( __( 'Invalid Amazon S3 file credentials: %s', 'groups-file-access' ), wp_kses_post( $ex->getMessage() ) )
											);
											continue;
										}
									}

									$inserted = false;
									$updated  = false;

									// If no file id is given but an existing file entry is referenced, assign it, this file must not be deleted, only info updated. [*]
									$existing_file_id = null;
									if ( $file_id === null ) {
										if ( $existing_file = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $file_table WHERE path = %s", $path ) ) ) {
											if (
												$type === '' && file_exists( $existing_file->path ) ||
												$type === 'amazon-s3'
											) {
												$file_id = $existing_file->file_id;
												$existing_file_id = $file_id;
											}
										}
									} else { // also if the file id is given and the file does not change
										if ( $existing_file = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $file_table WHERE file_id = %d", $file_id ) ) ) {
											if (
												$type === '' && file_exists( $existing_file->path ) && ( $existing_file->path == $path ) ||
												$type === 'amazon-s3' && ( $existing_file->path == $path )
											) {
												$existing_file_id = $file_id;
											}
										}
									}

									if ( $file_id === null ) {
										// new file entry
										if ( !$test_import ) {
											$inserted = $wpdb->query( $wpdb->prepare(
												"INSERT INTO $file_table (name,description,path,max_count) VALUES (%s,%s,%s,%d)",
												$name,
												$description,
												$path,
												$max_count
											) );
											if ( $inserted !== false ) {
												$entries_added++;
												if ( $file_id = $wpdb->get_var( "SELECT LAST_INSERT_ID()" ) ) {
													do_action( "groups_created_file", $file_id );
													if ( $type === 'amazon-s3' ) {
														// add metas
														$metas = array(
															'type'   => $type,
															'region' => $region,
															'bucket' => $bucket,
															'key'    => $key
														);
														foreach ( $metas as $meta_key => $meta_value ) {
															if ( $wpdb->query( $wpdb->prepare(
																"INSERT INTO $file_meta_table (file_id, meta_key, meta_value) VALUES (%d, %s, %s)",
																$file_id,
																$meta_key,
																$meta_value
															) ) ) {
																$file_meta_id = $wpdb->get_var( "SELECT LAST_INSERT_ID()" );
																do_action( "groups_created_file_meta", $file_meta_id, $meta_key, $meta_value );
															} else {
																error_log( sprintf(
																	esc_html__( 'Groups File Access failed to write file metadata while importing file #%d: %s -> %s', 'groups-file-access' ),
																	intval( $file_id ),
																	esc_html( $meta_key ),
																	esc_html( $meta_value )
																) );
															}
														}
													}
												}
											}
										}
									} else {
										// update existing file entry
										if ( !$test_import ) {
											// See [*] above: don't delete an existing file entry which only needs to be updated.
											if ( $existing_file_id !== $file_id ) {
												$file = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $file_table WHERE file_id = %d", $file_id ) );
												if ( $delete_files && file_exists( $file->path ) ) {
													@unlink( $file->path );
												}
											}
											$updated = $wpdb->query( $wpdb->prepare(
												"UPDATE $file_table SET name = %s, description = %s, path = %s, max_count = %d WHERE file_id = %d",
												$name,
												$description,
												$path,
												$max_count,
												$file_id
											) );
											if ( $updated !== false ) {
												$entries_updated++;
												do_action( "groups_updated_file", $file_id );
												// update metas
												$metas = array(
													'type'   => $type,
													'region' => $region,
													'bucket' => $bucket,
													'key'    => $key
												);
												foreach ( $metas as $meta_key => $meta_value ) {
													$file_meta = $wpdb->get_row( $wpdb->prepare(
														"SELECT * FROM $file_meta_table WHERE file_id=%d AND meta_key=%s",
														$file_id,
														$meta_key
													) );
													if ( $file_meta ) {
														if ( $file_meta->meta_value !== $meta_value ) {
															if ( $wpdb->query( $wpdb->prepare(
																"UPDATE $file_meta_table SET meta_value=%s WHERE meta_id=%d",
																$meta_value,
																$file_meta->meta_id
															) ) ) {
																do_action( "groups_updated_file_meta", $file_meta->meta_id, $meta_key, $meta_value );
															}
														}
													} else {
														if ( $wpdb->query( $wpdb->prepare(
															"INSERT INTO $file_meta_table (file_id, meta_key, meta_value) VALUES (%d, %s, %s)",
															$file_id,
															$meta_key,
															$meta_value
														) ) ) {
															$meta_id = $wpdb->get_var( "SELECT LAST_INSERT_ID()" );
															do_action( "groups_created_file_meta", $meta_id, $meta_key, $meta_value );
														} else {
															error_log( sprintf(
																esc_html__( 'Groups File Access failed to write file metadata while importing file #%d: %s -> %s', 'groups-file-access' ),
																intval( $file_id ),
																esc_html( $meta_key ),
																esc_html( $meta_value )
															) );
														}
													}
												}
											}
										}
									}

									// must use strict comparison, e.g. $updated can be 0 when no changes where made but we need to know if we should enter here
									if ( $inserted !== false || $updated !== false ) {

										if ( !$test_import ) {
											// remove group assignments that should no longer exist
											$current_file_groups = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $file_group_table fg LEFT JOIN $group_table g ON fg.group_id = g.group_id WHERE fg.file_id = %d", intval( $file_id ) ) );
											foreach ( $current_file_groups as $current_file_group ) {
												if ( !in_array( $current_file_group->name, $group_names ) ) {
													if ( $wpdb->query( $wpdb->prepare( "DELETE FROM $file_group_table WHERE file_id = %d AND group_id = %d", intval( $file_id ), intval( $current_file_group->group_id ) ) ) > 0 ) {
														do_action( "groups_deleted_file_group", intval( $file_id ), intval( $current_file_group->group_id ) );
													}
												}
											}

											// add file-group relations
											foreach( $group_names as $group_name ) {
												if ( $group = Groups_Group::read_by_name( $group_name ) ) {
													// we need to IGNORE for duplicate keys here (quick & dirty)
													if ( $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO $file_group_table (file_id,group_id) VALUES (%d,%d)", intval( $file_id ), intval( $group->group_id ) ) ) ) {
														do_action( "groups_created_file_group", intval( $file_id ), intval( $group->group_id ) );
													}
												}
											}
										}
									}

									$imported++;

								} else {
									$invalid++;
									$invalid_lines[] = array(
										'line' => $line_number,
										'message' => sprintf( __( 'Invalid file or type: %s', 'groups-file-access' ), $filename )
									);
									continue;
								}
							} else {
								$empty++;
							}
						}

						@fclose( $h );

						if ( !$test_import ) {
							self::$admin_messages[] = sprintf( __( 'Results after importing from <code>%s</code> :', 'groups-file-access' ), wp_strip_all_tags( $_FILES['file']['name'] ) );
							self::$admin_messages[] = sprintf( _n( '1 file has been imported', '%d files have been imported', $imported, 'groups-file-access' ), $imported );
							self::$admin_messages[] = sprintf( _n( '1 entry has been added', '%d entries have been added', $entries_added, 'groups-file-access' ), $entries_added );
							self::$admin_messages[] = sprintf( _n( '1 entry has been updated', '%d entries have been updated', $entries_updated, 'groups-file-access' ), $entries_updated );
							if ( $invalid > 0 ) {
								self::$admin_messages[] = sprintf( _n( '1 invalid line was skipped', '%d invalid lines were skipped', $invalid, 'groups-file-access' ), $invalid );
							}
							if ( $skipped_file > 0 ) {
								self::$admin_messages[] = sprintf( _n( '1 existing file was skipped', '%d existing files were skipped', $skipped_file, 'groups-file-access' ), $skipped_file );
							}
						} else {
							self::$admin_messages[] = sprintf( __( 'Results after importing (test only) from <code>%s</code> :', 'groups-file-access' ), wp_strip_all_tags( $_FILES['file']['name'] ) );
							self::$admin_messages[] = sprintf( _n( '1 file would have been imported', '%d files would have been imported', $imported, 'groups-file-access' ), $imported );
							if ( $invalid > 0 ) {
								self::$admin_messages[] = sprintf( _n( '1 invalid line was detected', '%d invalid lines were detected', $invalid, 'groups-file-access' ), $invalid );
							}
							if ( $skipped_file > 0 ) {
								self::$admin_messages[] = sprintf( _n( '1 existing file would have been skipped', '%d existing files would have been skipped', $skipped_file, 'groups-file-access' ), $skipped_file );
							}
						}

						$suffix = null;
						if ( count( $invalid_lines ) > self::MAX_INVALID_LINES_SHOWN ) {
							array_splice( $invalid_lines, self::MAX_INVALID_LINES_SHOWN );
							$suffix = '&hellip;';
						}
						if ( count( $invalid_lines ) > 0 ) {
							foreach ( $invalid_lines as $invalid_line ) {
								self::$admin_messages[] = sprintf( '%d : %s', $invalid_line['line'], $invalid_line['message'] );
							}
							if ( $suffix !== null ) {
								self::$admin_messages[] = $suffix;
							}
						}
					} else {
						self::$admin_messages[] = __( 'Import failed (error opening temporary file).', 'groups-file-access' );
					}
				}
			}
		}

	}

	/**
	 * Export files.
	 */
	public static function export_files() {
		global $wpdb;
		if ( !headers_sent() ) {
			$charset = get_bloginfo( 'charset' );
			$now     = date( 'Y-m-d-H-i-s', time() );
			header( 'Content-Description: File Transfer' );
			if ( !empty( $charset ) ) {
				header( 'Content-Type: text/plain; charset=' . $charset );
			} else {
				header( 'Content-Type: text/plain' );
			}
			header( "Content-Disposition: attachment; filename=\"groups-file-access-export-$now.txt\"" );
			$group_table      = _groups_get_tablename( 'group' );
			$file_table       = _groups_get_tablename( 'file' );
			$file_meta_table = _groups_get_tablename( 'file_meta' );
			$file_group_table = _groups_get_tablename( 'file_group' );
			$separator        = "\t";
			if ( $results = $wpdb->get_results( "SELECT * FROM $file_table ORDER BY file_id" ) ) {
				foreach( $results as $result ) {

					// metas
					$type   = '';
					$region = '';
					$bucket = '';
					$key    = '';
					$file_meta_table = _groups_get_tablename( 'file_meta' );
					$file_metas = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $file_meta_table WHERE file_id = %d", intval( $result->file_id ) ) );
					foreach ( $file_metas as $file_meta ) {
						$meta_key = $file_meta->meta_key;
						$meta_value = $file_meta->meta_value;
						switch ( $meta_key ) {
							case 'type':
								$type = $meta_value;
								break;
							case 'region':
								$region = $meta_value;
								break;
							case 'bucket':
								$bucket = $meta_value;
								break;
							case 'key':
								$key = $meta_value;
								break;
						}
					}

					// groups
					$group_names = array();
					if ( $groups = $wpdb->get_results( $wpdb->prepare( "SELECT g.* FROM $file_group_table fg LEFT JOIN $group_table g ON fg.group_id = g.group_id WHERE file_id = %d ORDER BY g.name", $result->file_id ) ) ) {
						foreach ( $groups as $group ) {
							$group_names[] = $group->name;
						}
					}
					if ( count( $group_names ) > 0 ) {
						$group_names = implode( ',', $group_names );
					} else {
						$group_names = '';
					}

					$values = array(
						gfa_basename( $result->path ),
						intval( $result->file_id ),
						stripslashes( $result->name ),
						stripslashes( preg_replace( '/(\n|\r|\t)+/', ' ', $result->description ) ),
						intval( $result->max_count ),
						stripslashes( $group_names ),
						$type,
						$region,
						$bucket,
						$key
					);

					$line = implode( $separator, $values );
					$line .= "\n";

					echo $line; // WPCS: XSS ok.
				}
				echo "\n";
			}
			die;
		} else {
			wp_die( 'ERROR: headers already sent' );
		}
	}

	/**
	 * Export file access.
	 */
	public static function export_file_access() {
		global $wpdb;
		if ( !headers_sent() ) {
			$charset = get_bloginfo( 'charset' );
			$now     = date( 'Y-m-d-H-i-s', time() );
			header( 'Content-Description: File Transfer' );
			if ( !empty( $charset ) ) {
				header( 'Content-Type: text/plain; charset=' . $charset );
			} else {
				header( 'Content-Type: text/plain' );
			}
			header( "Content-Disposition: attachment; filename=\"groups-file-access-export-file-access-$now.txt\"" );
			$group_table       = _groups_get_tablename( 'group' );
			$file_table        = _groups_get_tablename( 'file' );
			$file_access_table = _groups_get_tablename( 'file_access' );
			$file_meta_table   = _groups_get_tablename( 'file_meta' );
			$file_group_table  = _groups_get_tablename( 'file_group' );
			$separator         = "\t";

			$limit = isset( $_REQUEST['limit'] ) ? intval( $_REQUEST['limit'] ) : null;
			$offset = isset( $_REQUEST['offset'] ) ? intval( $_REQUEST['offset'] ) : null;
			if ( $limit <= 0 ) {
				$limit = null;
				$offset = null;
			}
			if ( $offset <= 0 ) {
				$offset = null;
			}

			$file_id = isset( $_REQUEST['file_id'] ) ? intval( $_REQUEST['file_id'] ) : null;
			if ( $file_id <= 0 ) {
				$file_id = null;
			}

			$user_id = isset( $_REQUEST['user_id'] ) ? intval( $_REQUEST['user_id'] ) : null;
			if ( $user_id <= 0 ) {
				$user_id = null;
			}

			echo implode( $separator, array(
				esc_html__( 'File ID', 'groups-file-access' ),
				esc_html__( 'Count', 'groups-file-access' ),
				esc_html__( 'Created', 'groups-file-access' ),
				esc_html__( 'Updated', 'groups-file-access' ),
				esc_html__( 'Name', 'groups-file-access' ),
				esc_html__( 'Path', 'groups-file-access' ),
				esc_html__( 'Max Count', 'groups-file-access' ),
				esc_html__( 'User ID', 'groups-file-access' ),
				esc_html__( 'User Login', 'groups-file-access' ),
				esc_html__( 'User Email', 'groups-file-access' ),
				esc_html__( 'Groups', 'groups-file-access' )
			) );
			echo "\n";

			$query =
				"SELECT fa.*, f.name, f.path, f.max_count, u.ID user_id, u.user_login, u.user_email FROM $file_access_table fa " .
				"LEFT JOIN $file_table f ON fa.file_id = f.file_id " .
				"LEFT JOIN $wpdb->users u ON fa.user_id = u.ID ";

			$where = '';
			$wheres = array();
			if ( $file_id !== null ) {
				$wheres[] = ' fa.file_id = ' . $file_id;
			}
			if ( $user_id !== null ) {
				$wheres[] = ' fa.user_id = ' . $user_id;
			}
			if ( count( $wheres ) > 0 ) {
				$where = ' WHERE ' . implode( ' AND ', $wheres );
			}
			$query .= $where;

			if ( $limit !== null ) {
				$query .= ' LIMIT ' . $limit;
				if ( $offset !== null ) {
					$query .= ' OFFSET ' . $offset;
				}
			}

			$results = $wpdb->get_results( $query );

			if ( $results !== null && is_array( $results ) ) {
				foreach( $results as $result ) {

					// groups
					$group_names = array();
					if ( $groups = $wpdb->get_results( $wpdb->prepare( "SELECT g.* FROM $file_group_table fg LEFT JOIN $group_table g ON fg.group_id = g.group_id WHERE file_id = %d ORDER BY g.name", $result->file_id ) ) ) {
						foreach ( $groups as $group ) {
							$group_names[] = $group->name;
						}
					}
					if ( count( $group_names ) > 0 ) {
						$group_names = implode( ',', $group_names );
					} else {
						$group_names = '';
					}

					$values = array(
						intval( $result->file_id ),
						intval( $result->count ),
						esc_html( $result->created ),
						esc_html( $result->updated ),
						stripslashes( $result->name ),
						esc_html( $result->path ),
						intval( $result->max_count ),
						intval( $result->user_id ),
						esc_html( $result->user_login ),
						esc_html( $result->user_email ),
						stripslashes( $group_names )
					);

					$line = implode( $separator, $values );
					$line .= "\n";

					echo $line; // WPCS: XSS ok.
				}
				echo "\n";
			}
			die;
		} else {
			wp_die( 'ERROR: headers already sent' );
		}
	}

	/**
	 * Scan for files.
	 */
	public static function scan_files() {
		global $wpdb;
		if ( !headers_sent() ) {
			$charset = get_bloginfo( 'charset' );
			$now     = date( 'Y-m-d-H-i-s', time() );
			header( 'Content-Description: File Transfer' );
			if ( !empty( $charset ) ) {
				header( 'Content-Type: text/plain; charset=' . $charset );
			} else {
				header( 'Content-Type: text/plain' );
			}
			header( "Content-Disposition: attachment; filename=\"groups-file-access-scan-$now.txt\"" );
			$group_table      = _groups_get_tablename( 'group' );
			$file_table       = _groups_get_tablename( 'file' );
			$file_group_table = _groups_get_tablename( 'file_group' );
			$separator        = "\t";

			$paths = array();
			if ( $h = @opendir( GFA_UPLOADS_DIR ) ) {
				while ( false !== ( $path = @readdir( $h ) ) ) {
					if ( !is_dir( $path ) ) {
						if ( substr( $path, 0, 1 ) != '.' && $path != 'index.html' && $path != 'index.php' ) { // not hidden, or index file
							$paths[] = $path;
						}
					}
				}
				@closedir( $h );
			}

			$exclude_existing = !empty( $_POST['exclude_existing'] );
			foreach( $paths as $path ) {
				$filename = gfa_basename( $path );
				$full_path = GFA_File_Upload::path_filter( GFA_UPLOADS_DIR . '/' . $filename );
				$results   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $file_table WHERE path = %s", $full_path ) );
				if ( count( $results ) == 0 ) { // doesn't already exist as file entry
					$file_id = '';
					$filetrunk = $filename;
					$extension = pathinfo( $filename, PATHINFO_EXTENSION );
					if ( strlen( $extension ) > 0 ) {
						$k = strrpos( $filename, $extension );
						if ( $k !== false ) {
							if ( --$k > 0 ) {
								$filetrunk = substr( $filename, 0, $k );
							}
						}
					}
					$name = preg_replace( '/(\p{P})+/u', ' ', $filetrunk ); // @since 1.9.0 fix UTF-8 replacement issue producing ? in string
					if ( function_exists( 'mb_convert_case' ) ) {
						$name = mb_convert_case( $name, MB_CASE_TITLE );
					} else {
						$name = ucwords( $name );
					}
					$description = stripslashes( !empty( $_POST['description'] ) ? trim( $_POST['description'] ) : '' );
					$max_count = !empty( $_POST['max_count'] ) ? intval( $_POST['max_count'] ) : 0;
					if ( $max_count < 0 ) {
						$max_count = 0;
					}
					$group_names = array();
					if ( !empty( $_POST['group_names'] ) ) {
						$_group_names = explode( ',', $_POST['group_names'] );
						foreach( $_group_names as $group_name ) {
							$group_names[] = trim( $group_name );
						}
					}
					if ( count( $group_names ) > 0 ) {
						$group_names = implode( ',', $group_names );
					} else {
						$group_names = '';
					}
					printf(
						"%s%s%s%s%s%s%s%s%d%s%s\n", // note that the $file_id placeholder must be %s here because it can be empty
						$filename,
						$separator,
						$file_id,
						$separator,
						$name,
						$separator,
						$description,
						$separator,
						$max_count,
						$separator,
						$group_names
					); // WPCS: XSS ok.
				} else if ( !$exclude_existing ) {
					foreach( $results as $result ) {
						$group_names = array();
						if ( $groups = $wpdb->get_results( $wpdb->prepare( "SELECT g.* FROM $file_group_table fg LEFT JOIN $group_table g ON fg.group_id = g.group_id WHERE file_id = %d ORDER BY g.name", $result->file_id ) ) ) {
							foreach ( $groups as $group ) {
								$group_names[] = $group->name;
							}
						}
						if ( count( $group_names ) > 0 ) {
							$group_names = implode( ',', $group_names );
						} else {
							$group_names = '';
						}
						printf(
							"%s%s%d%s%s%s%s%s%d%s%s\n",
							gfa_basename( $result->path ),
							$separator,
							intval( $result->file_id ),
							$separator,
							stripslashes( $result->name ),
							$separator,
							stripslashes( preg_replace( '/(\n|\r|\t)+/', ' ', $result->description ) ),
							$separator,
							intval( $result->max_count ),
							$separator,
							stripslashes( $group_names )
						); // WPCS: XSS ok.
					}
				}
			}
			echo "\n";
			die;
		} else {
			wp_die( 'ERROR: headers already sent' );
		}
	}
}
Groups_File_Access_Scan_Import::init();
