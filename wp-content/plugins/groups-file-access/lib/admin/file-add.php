<?php
/**
 * file-add.php
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

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

/**
 * Show add group form.
 */
function gfa_admin_files_add() {

	global $wpdb;

	if ( !current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups-file-access' ) );
	}

	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$current_url = remove_query_arg( 'paged', $current_url );
	$current_url = remove_query_arg( 'action', $current_url );
	$current_url = remove_query_arg( 'file_id', $current_url );

	$name        = isset( $_POST['name-field'] ) ? wp_filter_nohtml_kses( $_POST['name-field'] ) : '';
	$description = isset( $_POST['description-field'] ) ? sanitize_textarea_field( $_POST['description-field'] ) : '';
	$max_count   = isset( $_POST['max-count-field'] ) ? intval( $_POST['max-count-field'] ) : 0;
	if ( $max_count < 0 ) {
		$max_count = 0;
	}
	$group_ids   = isset( $_POST['group_id'] ) ? $_POST['group_id'] : array();

	$options = get_option( Groups_File_Access::PLUGIN_OPTIONS , array() );
	$amazon_s3  = isset( $options[Groups_File_Access::AMAZON_S3] ) ? $options[Groups_File_Access::AMAZON_S3] : Groups_File_Access::AMAZON_S3_DEFAULT;
	$type       = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : '';
	$region     = isset( $_POST['region'] ) ? trim( sanitize_text_field( $_POST['region'] ) ) : '';
	$bucket     = isset( $_POST['bucket'] ) ? trim( sanitize_text_field( $_POST['bucket'] ) ) : '';
	$key        = isset( $_POST['key'] ) ? trim( sanitize_text_field( $_POST['key'] ) ) : '';

	require_once GFA_FILE_LIB . '/class-gfa-file-upload.php';
	$max_bytes = GFA_File_Upload::get_upload_limit();
	$output = '<div class="manage-files">';

	$output .= '<h1>' . esc_html__( 'Add a new file', 'groups-file-access' ) . '</h1>';

	$output .= '<form enctype="multipart/form-data" id="add-file" method="post" action="' . esc_url( $current_url ) . '">';

	$output .= '<div class="file new">';

	if ( $amazon_s3 ) {

		wp_enqueue_script( 'gfa-admin' );

		require_once GFA_FILE_LIB . '/class-gfa-amazon-s3.php';
		$regions = GFA_Amazon_S3::get_regions();
		if ( $region !== '' && !key_exists( $region, $regions ) ) {
			$region = array_shift( array_keys( $regions ) );
			if ( $region === null ) {
				$region = '';
			}
		}

		// Type
		$output .= '<div id="type-field-container" class="field">';
		$output .= '<label for="type" class="field-label first">' . esc_html__( 'Type', 'groups-file-access' ) . '</label>';
		$output .= '<select id="file-type-selector" name="type">';
		$output .= sprintf( '<option value="" %s>', $type === '' ? 'selected' : '' ) . esc_html__( 'Native', 'groups-file-access' ) . '</option>';
		$output .= sprintf( '<option value="amazon-s3" %s>', $type === 'amazon-s3' ? 'selected' : '' ) . esc_html__( 'Amazon S3', 'groups-file-access' ) . '</option>';
		$output .= '</select>';
		$output .= '</div>';
		// Region
		$output .= sprintf( '<div id="amazon-s3-region-field-container" class="field" style="%s">', $type === 'amazon-s3' ? '' : 'display: none;' );
		$output .= '<label for="amazon-s3-region-field" class="field-label required">' . esc_html__( 'AWS Region', 'groups-file-access' ) . '</label>';
		$output .= '<select id="amazon-s3-region-field" name="region" class="amazon-s3-region-field">';
		$output .= sprintf( '<option value="" %s></option>', $region === '' ? 'selected' : '' );
		foreach ( $regions as $region_code => $region_name ) {
			$output .= sprintf( '<option value="%s" %s>%s [%s]</option>', esc_attr( $region_code ), $region === $region_code ? 'selected' : '', esc_html( $region_name ), esc_html( $region_code ) );
		}
		$output .= '</select>';
		$output .= '<span class="description">' . esc_html__( 'The AWS Region of the object stored in Amazon S3.', 'groups-file-access' ) . '</span>';
		$output .= '</div>';
		// Bucket
		$output .= sprintf( '<div id="amazon-s3-bucket-field-container" class="field" style="%s">', $type === 'amazon-s3' ? '' : 'display: none;' );
		$output .= '<label for="amazon-s3-bucket-field" class="field-label required">' . esc_html__( 'Amazon S3 Bucket', 'groups-file-access' ) . '</label>';
		$output .= sprintf( '<input id="amazon-s3-bucket-field" name="bucket" class="amazon-s3-bucket-field" type="text" value="%s"/>', esc_attr( $bucket ), $type === 'amazon-s3' ? 'required' : '' );
		$output .= '<span class="description">' . esc_html__( 'The Amazon S3 Bucket where the object is stored.', 'groups-file-access' ) . '</span>';
		$output .= '</div>';
		// Key
		$output .= sprintf( '<div id="amazon-s3-key-field-container" class="field" style="%s">', $type === 'amazon-s3' ? '' : 'display: none;' );
		$output .= '<label for="amazon-s3-key-field" class="field-label required">' . esc_html__( 'Amazon S3 Key', 'groups-file-access' ) . '</label>';
		$output .= sprintf( '<input id="amazon-s3-key-field" name="key" class="amazon-s3-key-field" type="text" value="%s"/>', esc_attr( $key ), $type === 'amazon-s3' ? 'required' : '' );
		$output .= '<span class="description">' . esc_html__( 'The object key (name) that identifies it in the bucket.', 'groups-file-access' ) . '</span>';
		$output .= '</div>';
	}

	$output .= sprintf( '<div id="file-field-container" class="field" style="%s">', $type === '' ? '' : 'display: none;' );
	$output .= '<label for="file" class="field-label required">' . esc_html__( 'File', 'groups-file-access' ) . '</label>';
	$output .= sprintf( '<input id="file-field" name="file" class="filefield" type="file" %s />', $type === '' ? 'required' : '' );
	$output .= '<p>' . wp_kses_post( sprintf( __( 'You can upload files up to <strong>%s</strong>.', 'groups-file-access' ), GFA_File_Upload::human_bytes( $max_bytes ) ) ) . '</p>';
	$output .= '</div>';

	$output .= '<div class="field">';
	$output .= '<label for="name-field" class="field-label">' . esc_html__( 'Name', 'groups-file-access' ) . '</label>';
	$output .= '<input id="name-field" name="name-field" class="namefield" type="text" value="' . esc_attr( $name ) . '"/>';
	$output .= '<span class="description">' . esc_html__( 'A descriptive name for the file. If left empty, the filename will be used.', 'groups-file-access' ) . '</span>';
	$output .= '</div>';

	$output .= '<div class="field">';
	$output .= '<label for="description-field" class="field-label description-field">' . esc_html__( 'Description', 'groups-file-access' ) . '</label>';
	$output .= '<textarea id="description-field" name="description-field" class="descriptionfield" rows="5" cols="45">' . htmlentities( stripslashes( $description ), ENT_COMPAT, get_bloginfo( 'charset' ) ) . '</textarea>';
	$output .= '<span class="description">' . esc_html__( 'A detailed description of the file.', 'groups-file-access' ) . '</span>';
	$output .= '</div>';

	$output .= '<div class="field">';
	$output .= '<label for="max-count-field" class="field-label">' . esc_html__( 'Max #', 'groups-file-access' ) . '</label>';
	$output .= '<input id="max-count-field" name="max-count-field" class="maxcountfield" type="text" value="' . esc_attr( $max_count ) . '"/>';
	$output .= '<span class="description">' . esc_html__( 'The maximum number of allowed accesses to the file per user. Use 0 for unlimited accesses.', 'groups-file-access' ) . '</span>';
	$output .= '</div>';

	$group_table = _groups_get_tablename( "group" );
	$groups = $wpdb->get_results( "SELECT * FROM $group_table ORDER BY name" );

	$output .= '<div class="field">';
	$output .= '<fieldset name="groups">';
	$output .= '<legend>';
	$output .= esc_html__( 'Groups', 'groups-file-access' );
	$output .= '</legend>';
	if ( count( $groups ) > 0 ) {
		$output .= '<ul>';
		foreach( $groups as $group ) {
			$output .= '<li>';
			$output .= '<label>';
			$output .= sprintf( '<input type="checkbox" name="group_id[]" value="%d" %s />', $group->group_id, in_array( $group->group_id, $group_ids ) ? ' checked="checked" ' : '' );
			$output .= ' ';
			$output .= stripslashes( wp_filter_nohtml_kses( $group->name ) );
			$output .= '</label>';
			$output .= '</li>';
		}
		$output .= '</ul>';
	} else {
		$output .= esc_html__( 'There are no groups. At least one group must exist.', 'groups-file-access' );
	}
	$output .= '</fieldset>';
	$output .= '<span class="description">' . esc_html__( 'Access to the file is restricted to members of the selected groups.', 'groups-file-access' ) . '</span>';
	$output .= '</div>';

	$output .= '<div class="field">';
	$output .= wp_nonce_field( 'files-add', GROUPS_ADMIN_GROUPS_NONCE, true, false );
	$output .= '<input class="button button-primary" type="submit" value="' . esc_attr__( 'Add', 'groups-file-access' ) . '"/>';
	$output .= '<input type="hidden" value="add" name="action"/>';
	$output .= '<a class="cancel button" href="' . $current_url . '">' . esc_attr__( 'Cancel', 'groups-file-access' ) . '</a>';
	$output .= '</div>';
	$output .= '</div>'; // .group.new
	$output .= '</form>';
	$output .= '</div>'; // .manage-files

	require_once GFA_VIEWS_LIB . '/class-gfa-help.php';
	$output .= GFA_Help::footer();

	echo $output; // WPCS: XSS ok.
} // function gfa_admin_files_add

/**
 * Handle add file form submission.
 * @return int new file's id or false if unsuccessful
 */
function gfa_admin_files_add_submit() {

	require_once GFA_FILE_LIB . '/class-gfa-file-upload.php';

	global $wpdb;
	$file_id = false;

	if ( !current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups-file-access' ) );
	}

	if ( !wp_verify_nonce( $_POST[GROUPS_ADMIN_GROUPS_NONCE], 'files-add' ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups-file-access' ) );
	}

	$type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : '';

	switch ( $type ) {

		//
		// Native
		//
		case '':
			if ( file_exists( GFA_UPLOADS_DIR ) ) {
				if ( isset( $_FILES['file'] ) ) {
					if ( $_FILES['file']['error'] == UPLOAD_ERR_OK ) {
						$tmp_name = $_FILES['file']['tmp_name'];
						$filename = GFA_File_Upload::filename_filter( $_FILES['file']['name'] );
						if ( strlen( $filename ) > 0 ) {
							$path = GFA_File_Upload::path_filter( GFA_UPLOADS_DIR . '/' . $filename );
							if ( file_exists( $path ) ) {
								echo '<div class="error">';
								echo sprintf( esc_html__( 'The file %s already exists.', 'groups-file-access' ), $path ); // WPCS: XSS ok.
								echo '</div>';
							} else {
								if ( !@move_uploaded_file( $tmp_name, $path ) ) {
									echo "<div class='error'>" . esc_html__( 'Could not upload the file.', 'groups-file-access' ) . "</div>";
								} else {
									$name        = !empty( $_POST['name-field'] ) ? wp_filter_nohtml_kses( $_POST['name-field'] ) : $filename;
									$description = isset( $_POST['description-field'] ) ? sanitize_textarea_field( $_POST['description-field'] ) : '';
									$max_count   = isset( $_POST['max-count-field'] ) ? intval( $_POST['max-count-field'] ) : 0;
									if ( $max_count < 0 ) {
										$max_count = 0;
									}
									$file_table = _groups_get_tablename( 'file' );
									$inserted = $wpdb->query( $wpdb->prepare(
										"INSERT INTO $file_table (name,description,path,max_count) VALUES (%s,%s,%s,%d)",
										$name,
										$description,
										$path,
										$max_count
									) );
									if ( $inserted !== false ) {
										if ( $file_id = $wpdb->get_var( "SELECT LAST_INSERT_ID()" ) ) {
											do_action( "groups_created_file", $file_id );
											if ( !empty( $_POST['group_id'] ) ) {
												$file_group_table = _groups_get_tablename( 'file_group' );
												foreach( $_POST['group_id'] as $group_id ) {
													if ( $group = Groups_Group::read( $group_id ) ) {
														if ( $wpdb->query( $wpdb->prepare( "INSERT INTO $file_group_table (file_id,group_id) VALUES (%d,%d)", $file_id, $group_id ) ) ) {
															do_action( "groups_created_file_group", $file_id, $group_id );
														}
													}
												}
											}
										}
									}
								}
							}
						} else {
							echo '<div class="error">';
							echo esc_html__( 'The filename is not acceptable.', 'groups-file-access' );
							echo '</div>';
						}
					}
				} else {
					echo '<div class="error">';
					echo esc_html__( 'You must upload a file.', 'groups-file-access' );
					echo '</div>';
				}
			} else {
				echo '<div class="error">';
				echo esc_html__( 'The upload directory does not seem to exist. Please review the settings under File Access.', 'groups-file-access' );
				echo '</div>';
			}
			break;

		//
		// Amazon S3
		//
		case 'amazon-s3':
			$region  = isset( $_POST['region'] ) ? trim( sanitize_text_field( $_POST['region'] ) ) : '';
			$bucket  = isset( $_POST['bucket'] ) ? trim( sanitize_text_field( $_POST['bucket'] ) ) : '';
			$key     = isset( $_POST['key'] ) ? trim( sanitize_text_field( $_POST['key'] ) ) : '';

			require_once GFA_FILE_LIB . '/class-gfa-amazon-s3.php';
			$regions = GFA_Amazon_S3::get_regions();
			if ( $region !== '' && !key_exists( $region, $regions ) ) {
				$region = array_shift( array_keys( $regions ) );
				if ( $region === null ) {
					$region = '';
				}
			}

			if ( !empty( $region ) && !empty( $bucket ) && !empty( $key ) ) {

				$options = get_option( Groups_File_Access::PLUGIN_OPTIONS , array() );
				$amazon_s3  = isset( $options[Groups_File_Access::AMAZON_S3] ) ? $options[Groups_File_Access::AMAZON_S3] : Groups_File_Access::AMAZON_S3_DEFAULT;
				$amazon_s3_access_key = isset( $options[Groups_File_Access::AMAZON_S3_ACCESS_KEY] ) ? $options[Groups_File_Access::AMAZON_S3_ACCESS_KEY] : '';
				$amazon_s3_secret_key = isset( $options[Groups_File_Access::AMAZON_S3_SECRET_KEY] ) ? $options[Groups_File_Access::AMAZON_S3_SECRET_KEY] : '';

				require_once GFA_AWS_LIB . '/aws-autoloader.php';

				$object_url = '';
				$filename = '';
				try {
					$s3client = new S3Client( array(
						'version' => 'latest',
						'region'  => $region,
						'credentials' => array(
							'key' => $amazon_s3_access_key,
							'secret' => $amazon_s3_secret_key,
						)
					) );
					$object_url = $s3client->getObjectUrl( $bucket, $key );
					// $headobject = $s3client->headObject( array( 'Bucket' => $bucket, 'Key' => $key ) );
					$components = explode( '/', $object_url );
					$filename = GFA_File_Upload::filename_filter( array_pop( $components ) );
				} catch ( Exception $ex ) {
					echo '<div class="error">';
					echo wp_kses_post( $ex->getMessage() );
					echo '</div>';
				}

				$name        = !empty( $_POST['name-field'] ) ? wp_filter_nohtml_kses( $_POST['name-field'] ) : $filename;
				$description = isset( $_POST['description-field'] ) ? sanitize_textarea_field( $_POST['description-field'] ) : '';
				$max_count   = isset( $_POST['max-count-field'] ) ? intval( $_POST['max-count-field'] ) : 0;

				if ( $max_count < 0 ) {
					$max_count = 0;
				}
				$file_table = _groups_get_tablename( 'file' );
				$inserted = $wpdb->query( $wpdb->prepare(
					"INSERT INTO $file_table (name,description,path,max_count) VALUES (%s,%s,%s,%d)",
					$name,
					$description,
					$object_url, // path
					$max_count
				) );
				if ( $inserted !== false ) {
					if ( $file_id = $wpdb->get_var( "SELECT LAST_INSERT_ID()" ) ) {
						do_action( "groups_created_file", $file_id );
						if ( !empty( $_POST['group_id'] ) ) {
							$file_group_table = _groups_get_tablename( 'file_group' );
							foreach( $_POST['group_id'] as $group_id ) {
								if ( $group = Groups_Group::read( $group_id ) ) {
									if ( $wpdb->query( $wpdb->prepare( "INSERT INTO $file_group_table (file_id,group_id) VALUES (%d,%d)", $file_id, $group_id ) ) ) {
										do_action( "groups_created_file_group", $file_id, $group_id );
									}
								}
							}
						}
						// file metas
						$file_meta_table = _groups_get_tablename( 'file_meta' );
						$metas = array(
							'type'   => 'amazon-s3',
							'region' => $region,
							'bucket' => $bucket,
							'key'    => $key
						);

						foreach ( $metas as $key => $value ) {
							if ( $wpdb->query( $wpdb->prepare(
								"INSERT INTO $file_meta_table (file_id, meta_key, meta_value) VALUES (%d, %s, %s)",
								$file_id,
								$key,
								$value
							) ) ) {
								$file_meta_id = $wpdb->get_var( "SELECT LAST_INSERT_ID()" );
								do_action( "groups_created_file_meta", $file_meta_id, $key, $value );
							} else {
								echo '<div class="error">';
								printf( esc_html__( 'Failed to write file metadata: %s -> %s', 'groups-file-access' ), esc_html( $key ), esc_html( $value ) );
								echo '</div>';
							}
						}
					}
				}
			} else {
				echo '<div class="error">';
				echo esc_html__( 'You must provide the Region, Bucket and Key.', 'groups-file-access' );
				echo '</div>';
			}
			break;
	}
	return $file_id;
} // function gfa_admin_files_add_submit
