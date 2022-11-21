<?php
/**
 * file-edit.php
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
 * Show edit file form.
 * @param int $file_id file id
 */
function gfa_admin_files_edit( $file_id ) {

	global $wpdb;

	if ( !current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups-file-access' ) );
	}

	$file_table = _groups_get_tablename( 'file' );
	$file_group_table = _groups_get_tablename( 'file_group' );
	$file_id = intval( $file_id );
	$file = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $file_table WHERE file_id = %d", $file_id ) );

	$options = get_option( Groups_File_Access::PLUGIN_OPTIONS , array() );
	$amazon_s3  = isset( $options[Groups_File_Access::AMAZON_S3] ) ? $options[Groups_File_Access::AMAZON_S3] : Groups_File_Access::AMAZON_S3_DEFAULT;
	$amazon_s3_access_key = isset( $options[Groups_File_Access::AMAZON_S3_ACCESS_KEY] ) ? $options[Groups_File_Access::AMAZON_S3_ACCESS_KEY] : '';
	$amazon_s3_secret_key = isset( $options[Groups_File_Access::AMAZON_S3_SECRET_KEY] ) ? $options[Groups_File_Access::AMAZON_S3_SECRET_KEY] : '';
	$type   = '';
	$region = '';
	$bucket = '';
	$key    = '';
	$file_meta_table = _groups_get_tablename( 'file_meta' );
	$file_metas = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $file_meta_table WHERE file_id = %d", $file_id ) );
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
	$type   = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : $type;
	$region = isset( $_POST['region'] ) ? trim( sanitize_text_field( $_POST['region'] ) ) : $region;
	$bucket = isset( $_POST['bucket'] ) ? trim( sanitize_text_field( $_POST['bucket'] ) ) : $bucket;
	$key    = isset( $_POST['key'] ) ? trim( sanitize_text_field( $_POST['key'] ) ) : $key;

	if ( empty( $file ) ) {
		wp_die( esc_html__( 'No such file.', 'groups-file-access' ) );
	}

	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$current_url = remove_query_arg( 'action', $current_url );
	$current_url = remove_query_arg( 'file_id', $current_url );

	$name        = isset( $_POST['name-field'] ) ? wp_filter_nohtml_kses( $_POST['name-field'] ) : $file->name;
	$description = isset( $_POST['description-field'] ) ? $_POST['description-field'] : $file->description;
	$path        = $file->path;
	$max_count   = isset( $_POST['max-count-field'] ) ? intval( $_POST['max-count-field'] ) : $file->max_count;
	if ( $max_count < 0 ) {
		$max_count = 0;
	}
	$group_ids = array();
	if ( $_group_ids  = $wpdb->get_results( $wpdb->prepare( "SELECT group_id FROM $file_group_table WHERE file_id = %d", $file_id ) ) ) {
		foreach( $_group_ids as $group_id ) {
			$group_ids[] = $group_id->group_id;
		}
	}

	$base_url = get_bloginfo( 'url' );

	$output = '<div class="manage-files">';
	$output .= '<div>';
	$output .= '<h1>';
	$output .= esc_html__( 'Edit a file', 'groups-file-access' );
	$output .= '</h1>';
	$output .= '</div>';

	$output .= '<p>';
	$output .= sprintf( esc_html__( 'File Id: %d', 'groups-file-access' ), esc_html( $file_id ) );
	$output .= '<p>';
	$output .= '<p>';
	$output .= sprintf( esc_html__( 'Path: %s', 'groups-file-access' ), esc_html( $path ) );
	$output .= '<br/>';
	$output .= sprintf( esc_html__( 'URL: %s', 'groups-file-access' ), GFA_File_Renderer::render_url( $file, $base_url ) ); // WPCS: XSS ok.
	$output .= '<br/>';
	$output .= sprintf( esc_html__( 'Link: %s', 'groups-file-access' ), GFA_File_Renderer::render_link( $file, $base_url ) ); // WPCS: XSS ok.
	$output .= '</p>';

	$output .= '<form enctype="multipart/form-data" id="add-file" method="post" action="' . $current_url . '">';

	$output .= '<div class="file edit">';

	$output .= '<input id="file-id-field" name="file-id-field" type="hidden" value="' . esc_attr( intval( $file_id ) ) . '"/>';

	if ( $amazon_s3 || $type === 'amazon-s3' ) {

		wp_enqueue_script( 'gfa-admin' );

		require_once GFA_FILE_LIB . '/class-gfa-amazon-s3.php';
		$regions = GFA_Amazon_S3::get_regions();
		if ( $region !== '' && !key_exists( $region, $regions ) ) {
			$region = array_shift( array_keys( $regions ) );
			if ( $region === null ) {
				$region = '';
			}
		}

		// Type - cannot be changed at edit
		$output .= '<div class="field">';
		$output .= '<label for="type" class="field-label first">' . esc_html__( 'Type', 'groups-file-access' ) . '</label>';
		$output .= '<select name="type" disabled>';
		$output .= sprintf( '<option value="" %s>', $type === '' ? 'selected' : '' ) . esc_html__( 'Native', 'groups-file-access' ) . '</option>';
		$output .= sprintf( '<option value="amazon-s3" %s>', $type === 'amazon-s3' ? 'selected' : '' ) . esc_html__( 'Amazon S3', 'groups-file-access' ) . '</option>';
		$output .= '</select>';
		if ( !$amazon_s3 ) {
			$output .= '<div class="gfa-warning">';
			$output .= sprintf(
				esc_html__( 'Amazon S3 file access is disabled in the %s.', 'groups-file-access' ),
				sprintf(
					'<a href="%s">%s</a>',
					esc_attr( admin_url( 'admin.php?page=groups-admin-file-access#' . Groups_File_Access::AMAZON_S3 ) ),
					esc_html__( 'Settings', 'groups-file-access' )
				)
			);
			$output .= '</div>'; // .gfa-warning
		}
		$output .= '</div>'; // .field

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

	// allow to upload a new file
	$output .= sprintf( '<div id="file-field-container" class="field" style="%s">', $type === '' ? '' : 'display: none;' );
	$output .= '<p>';
	$output .= esc_html__( 'The current file can be replaced by a new file. You can select a new file below.', 'groups-file-access' );
	$output .= '</p>';
	$output .= '<label for="file" class="field-label">' . esc_html__( 'File', 'groups-file-access' ) . '</label>';
	$output .= '<input id="file-field" name="file" class="filefield" type="file" />';
	$output .= '<span class="description">';
	$output .= wp_kses_post( __( 'If a new file is chosen here, the current file will be <strong>deleted</strong>.', 'groups-file-access' ) );
	$output .= '</span>';
	$output .= '</div>';

	$output .= '<div class="field">';
	$output .= '<label for="name-field" class="field-label first required">' . esc_html__( 'Name', 'groups-file-access' ) . '</label>';
	$output .= '<input id="name-field" name="name-field" class="namefield" type="text" value="' . esc_attr( stripslashes( $name ) ) . '"/>';
	$output .= '</div>';

	$output .= '<div class="field">';
	$output .= '<label for="description-field" class="field-label description-field">' . esc_html__( 'Description', 'groups-file-access' ) . '</label>';
	$output .= '<textarea id="description-field" name="description-field" class="descriptionfield" rows="5" cols="45">' . htmlentities( stripslashes( $description ), ENT_COMPAT, get_bloginfo( 'charset' ) ) . '</textarea>';
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
			$output .= sprintf( '<input type="checkbox" name="group_id[]" value="%d" %s />', esc_attr( $group->group_id ), in_array( $group->group_id, $group_ids ) ? ' checked="checked" ' : '' );
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
	$output .= '<span class="description">';
	$output .= esc_html__( 'Access to the file is restricted to members of the selected groups.', 'groups-file-access' );
	$output .= '</span>';
	$output .= '</div>';

	$output .= '<div class="field">';
	$output .= wp_nonce_field( 'files-edit', GROUPS_ADMIN_GROUPS_NONCE, true, false );
	$output .= '<input class="button button-primary" type="submit" value="' . esc_attr__( 'Save', 'groups-file-access' ) . '"/>';
	$output .= '<input type="hidden" value="edit" name="action"/>';
	$output .= '<a class="cancel button" href="' . $current_url . '">' . esc_attr__( 'Cancel', 'groups-file-access' ) . '</a>';
	$output .= '</div>';
	$output .= '</div>'; // .file.edit
	$output .= '</form>';
	$output .= '</div>'; // .manage-files

	require_once GFA_VIEWS_LIB . '/class-gfa-help.php';
	$output .= GFA_Help::footer();

	echo $output; // WPCS: XSS ok.
} // function

/**
 * Handle edit form submission.
 */
function gfa_admin_files_edit_submit() {

	require_once GFA_FILE_LIB . '/class-gfa-file-upload.php';

	global $wpdb;

	if ( !current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups-file-access' ) );
	}
	if ( !wp_verify_nonce( $_POST[GROUPS_ADMIN_GROUPS_NONCE],  'files-edit' ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups-file-access' ) );
	}

	$file_id = isset( $_POST['file-id-field'] ) ? $_POST['file-id-field'] : null;
	$file_id = intval( $file_id );
	$file_table = _groups_get_tablename( 'file' );
	$file = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $file_table WHERE file_id = %d", $file_id ) );
	if ( empty( $file ) ) {
		wp_die( esc_html__( 'No such file.', 'groups-file-access' ) );
	}

	$options = get_option( Groups_File_Access::PLUGIN_OPTIONS , array() );
	$amazon_s3  = isset( $options[Groups_File_Access::AMAZON_S3] ) ? $options[Groups_File_Access::AMAZON_S3] : Groups_File_Access::AMAZON_S3_DEFAULT;
	$amazon_s3_access_key = isset( $options[Groups_File_Access::AMAZON_S3_ACCESS_KEY] ) ? $options[Groups_File_Access::AMAZON_S3_ACCESS_KEY] : '';
	$amazon_s3_secret_key = isset( $options[Groups_File_Access::AMAZON_S3_SECRET_KEY] ) ? $options[Groups_File_Access::AMAZON_S3_SECRET_KEY] : '';

	$type   = '';
	$region = '';
	$bucket = '';
	$key    = '';
	$file_meta_table = _groups_get_tablename( 'file_meta' );
	$file_metas = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $file_meta_table WHERE file_id = %d", $file_id ) );
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

	$errors = 0;

	switch ( $type ) {

		//
		// Native
		//
		case '':
			$new_path = null;
			if ( !empty( $_FILES['file']['tmp_name'] ) ) {
				if ( file_exists( GFA_UPLOADS_DIR ) ) {
					if ( $_FILES['file']['error'] == UPLOAD_ERR_OK ) {
						$tmp_name = $_FILES['file']['tmp_name'];
						$filename = GFA_File_Upload::filename_filter( $_FILES['file']['name'] );
						if ( strlen( $filename ) > 0 ) {
							$path = GFA_File_Upload::path_filter( GFA_UPLOADS_DIR . '/' . $filename );
							if ( ( $path !== $file->path ) && file_exists( $path ) ) {
								echo "<div class='error'>" . sprintf( esc_html__( 'The file %s already exists but it is not related to this entry. The existing file is not replaced and the current file for this entry is maintained.', 'groups-file-access' ), $path ) . "</div>"; // WPCS: XSS ok.
								$errors++;
							} else {
								if ( file_exists( $file->path ) ) {
									@unlink( $file->path );
								}
								if ( !@move_uploaded_file( $tmp_name, $path ) ) {
									echo "<div class='error'>" . esc_html__( 'Could not upload the file.', 'groups-file-access' ) . "</div>";
									$errors++;
								} else {
									$new_path = $path;
								}
							}
						} else {
							echo "<div class='error'>" . esc_html__( 'The filename is not acceptable.', 'groups-file-access' ) . "</div>";
							$errors++;
						}
					}
				} else {
					echo "<div class='error'>" . esc_html__( 'The upload directory does not seem to exist. Please review the settings under File Access.', 'groups-file-access' ) . "</div>";
					$errors++;
				}
			}
			$path        = $file->path;
			$name        = isset( $_POST['name-field'] ) ? wp_filter_nohtml_kses( $_POST['name-field'] ) : '';
			$description = isset( $_POST['description-field'] ) ? $_POST['description-field'] : '';
			$max_count   = isset( $_POST['max-count-field'] ) ? intval( $_POST['max-count-field'] ) : 0;
			if ( $max_count < 0 ) {
				$max_count = 0;
			}
			if ( $new_path !== null ) {
				$path = $new_path;
			}
			$updated = $wpdb->query( $wpdb->prepare(
				"UPDATE $file_table SET name=%s, description=%s, path=%s, max_count=%d WHERE file_id=%d",
				$name,
				$description,
				$path,
				$max_count,
				$file_id
			) );
			if ( $updated !== false ) {
				do_action( "groups_updated_file", $file_id );
				$file_group_table  = _groups_get_tablename( 'file_group' );
				$new_group_ids     = !empty( $_POST['group_id'] ) ? $_POST['group_id'] : array();
				$current_group_ids = array();
				if ( $_group_ids  = $wpdb->get_results( $wpdb->prepare( "SELECT group_id FROM $file_group_table WHERE file_id = %d", $file_id ) ) ) {
					foreach( $_group_ids as $group_id ) {
						$current_group_ids[] = $group_id->group_id;
					}
				}
				foreach( $current_group_ids as $current_group_id ) {
					if ( !in_array( $current_group_id, $new_group_ids ) ) {
						if ( $wpdb->query( $wpdb->prepare( "DELETE FROM $file_group_table WHERE file_id = %d AND group_id = %d", $file_id, $current_group_id ) ) > 0 ) {
							do_action( "groups_deleted_file_group", $file_id, $current_group_id );
						}
					}
				}
				foreach( $new_group_ids as $new_group_id ) {
					if ( !in_array( $new_group_id, $current_group_ids ) ) {
						if ( $group = Groups_Group::read( $new_group_id ) ) {
							if ( $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO $file_group_table (file_id,group_id) VALUES (%d,%d)", $file_id, $new_group_id ) ) ) {
								do_action( "groups_created_file_group", $file_id, $new_group_id );
							}
						}
					}
				}
			} else {
				$errors++;
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
					$object_url = $s3client->getObjectUrl( $bucket, $key );
				} catch ( Exception $ex ) {
					$errors++;
					echo '<div class="error">';
					echo wp_kses_post( $ex->getMessage() );
					echo '</div>';
				}

				$name        = isset( $_POST['name-field'] ) ? wp_filter_nohtml_kses( $_POST['name-field'] ) : '';
				$description = isset( $_POST['description-field'] ) ? $_POST['description-field'] : '';
				$max_count   = isset( $_POST['max-count-field'] ) ? intval( $_POST['max-count-field'] ) : 0;
				if ( $max_count < 0 ) {
					$max_count = 0;
				}

				$updated = $wpdb->query( $wpdb->prepare(
					"UPDATE $file_table SET name=%s, description=%s, path=%s, max_count=%d WHERE file_id=%d",
					$name,
					$description,
					$object_url,
					$max_count,
					$file_id
				) );
				if ( $updated !== false ) {
					do_action( "groups_updated_file", $file_id );
					$file_group_table  = _groups_get_tablename( 'file_group' );
					$new_group_ids     = !empty( $_POST['group_id'] ) ? $_POST['group_id'] : array();
					$current_group_ids = array();
					if ( $_group_ids  = $wpdb->get_results( $wpdb->prepare( "SELECT group_id FROM $file_group_table WHERE file_id = %d", $file_id ) ) ) {
						foreach( $_group_ids as $group_id ) {
							$current_group_ids[] = $group_id->group_id;
						}
					}
					foreach( $current_group_ids as $current_group_id ) {
						if ( !in_array( $current_group_id, $new_group_ids ) ) {
							if ( $wpdb->query( $wpdb->prepare( "DELETE FROM $file_group_table WHERE file_id = %d AND group_id = %d", $file_id, $current_group_id ) ) > 0 ) {
								do_action( "groups_deleted_file_group", $file_id, $current_group_id );
							}
						}
					}
					foreach( $new_group_ids as $new_group_id ) {
						if ( !in_array( $new_group_id, $current_group_ids ) ) {
							if ( $group = Groups_Group::read( $new_group_id ) ) {
								if ( $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO $file_group_table (file_id,group_id) VALUES (%d,%d)", $file_id, $new_group_id ) ) ) {
									do_action( "groups_created_file_group", $file_id, $new_group_id );
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
								echo '<div class="error">';
								printf( esc_html__( 'Failed to write file metadata: %s -> %s', 'groups-file-access' ), esc_html( $key ), esc_html( $value ) );
								echo '</div>';
							}
						}
					}
				} else {
					$errors++;
					echo '<div class="error">';
					echo esc_html__( 'Failed to update file data.', 'groups-file-access' );
					echo '</div>';
				}
			} else {
				$errors++;
				echo '<div class="error">';
				echo esc_html__( 'You must provide the Region, Bucket and Key.', 'groups-file-access' );
				echo '</div>';
			}
			break;
	}

	if ( $errors > 0 ) {
		return false;
	} else {
		return $file_id;
	}
} // function
