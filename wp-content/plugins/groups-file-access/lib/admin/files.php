<?php
/**
 * files.php
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

define( 'GFA_FILES_PER_PAGE', 10 );
define( 'GFA_ADMIN_NONCE_1', 'gfa-admin-nonce-1' );
define( 'GFA_ADMIN_NONCE_2', 'gfa-admin-nonce-2' );
define( 'GFA_ADMIN_ACTION_NONCE', 'gfa-action-nonce' );
define( 'GFA_ADMIN_FILTER_NONCE', 'gfa-filter-nonce' );
define( 'GFA_DESCRIPTION_CUT', 100 );

require_once WP_PLUGIN_DIR . '/groups/groups.php';
require_once GROUPS_CORE_LIB . '/class-groups-pagination.php';
require_once GFA_ADMIN_LIB . '/file-add.php';
require_once GFA_ADMIN_LIB . '/file-edit.php';
require_once GFA_ADMIN_LIB . '/file-remove.php';
require_once GFA_FILE_LIB . '/class-gfa-file-renderer.php';
require_once GFA_FILE_LIB . '/class-gfa-file-upload.php';
require_once GFA_UTY_LIB . '/class-gfa-utility.php';

/**
 * Manage files.
 */
function groups_file_access_admin_files() {

	global $wpdb;

	$output = '';
	$today = date( 'Y-m-d', time() );

	if ( !current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups-file-access' ) );
	}

	//
	// handle actions
	//
	if ( isset( $_POST['action'] ) ) {

		//  handle requested action on one item
		switch( $_POST['action'] ) {
			case 'add' :
				if ( !gfa_admin_files_add_submit() ) {
					return gfa_admin_files_add();
				}
				break;
			case 'edit' :
				if ( !gfa_admin_files_edit_submit() ) {
					return gfa_admin_files_edit( $_POST['file-id-field'] );
				}
				break;
			case 'remove' :
				gfa_admin_files_remove_submit();
				break;
			// bulk actions
			case 'files-action' :
				if ( wp_verify_nonce( $_POST[GFA_ADMIN_ACTION_NONCE], 'admin' ) ) {
					$file_ids = isset( $_POST['file_ids'] ) ? $_POST['file_ids'] : null;
					if ( !empty( $file_ids ) ) {
						if ( !empty( $_POST['remove'] ) ) {
							if ( isset( $_POST['confirmed'] ) ) {
								if ( is_array( $file_ids ) && ( count( $file_ids ) > 0 ) ) {
									$params = array();
									$filters = array();
									foreach ( $file_ids as $file_id ) {
										$file_id = intval( $file_id );
										// we delete one-by-one to maintain consistency in case of failure on deleting a file
										$file_table = _groups_get_tablename( 'file' );
										$file_group_table = _groups_get_tablename( 'file_group' );
										$file_meta_table = _groups_get_tablename( 'file_meta' );
										if ( $file = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $file_table WHERE file_id=%d", $file_id ) ) ) {
											if ( !file_exists( $file->path ) || @unlink( $file->path ) ) {
												$wpdb->query( $wpdb->prepare( "DELETE FROM $file_table WHERE file_id = %d", $file_id ) );
												$wpdb->query( $wpdb->prepare( "DELETE FROM $file_group_table WHERE file_id = %d", $file_id ) );
												$wpdb->query( $wpdb->prepare( "DELETE FROM $file_meta_table WHERE file_id = %d", $file_id ) );
												do_action( "groups_deleted_file", $file );
											}
										}
									}
								}
								unset( $_POST['file_ids'] );
							}
						} else if ( !empty( $_POST['add-to-group'] ) ) {
							if ( !empty( $_POST['group_id'] ) ) {
								$group_id = intval( $_POST['group_id'] );
								if ( $group = Groups_Group::read( $group_id ) ) {
									$file_group_table = _groups_get_tablename( 'file_group' );
									foreach ( $file_ids as $file_id ) {
										$file_id = intval( $file_id );
										if ( 0 === intval( $wpdb->get_var( $wpdb->prepare(
											"SELECT COUNT(*) FROM $file_group_table WHERE file_id = %d AND group_id = %d",
											$file_id,
											$group_id ) ) )
										) {
											if ( $wpdb->query( $wpdb->prepare( "INSERT INTO $file_group_table (file_id,group_id) VALUES (%d,%d)", $file_id, $group_id ) ) ) {
												do_action( "groups_created_file_group", $file_id, $group_id );
											}
										}
									}
									unset( $_POST['file_ids'] );
								}
							}
						} else if ( !empty( $_POST['remove-from-group'] ) ) {
							if ( !empty( $_POST['group_id'] ) ) {
								$group_id = intval( $_POST['group_id'] );
								if ( $group = Groups_Group::read( $group_id ) ) {
									$file_group_table = _groups_get_tablename( 'file_group' );
									foreach ( $file_ids as $file_id ) {
										$file_id = intval( $file_id );
										if ( $wpdb->query( $wpdb->prepare( "DELETE FROM $file_group_table WHERE file_id = %d AND group_id = %d", $file_id, $group_id ) ) > 0 ) {
											do_action( "groups_deleted_file_group", $file_id, $group_id );
										}
									}
									unset( $_POST['file_ids'] );
								}
							}
						}
					}
				}
				break;
		}
	} else if ( isset ( $_GET['action'] ) ) {
		// handle action request - show the form
		switch( $_GET['action'] ) {
			case 'add' :
				return gfa_admin_files_add();
				break;
			case 'edit' :
				if ( isset( $_GET['file_id'] ) ) {
					return gfa_admin_files_edit( $_GET['file_id'] );
				}
				break;
			case 'remove' :
				if ( isset( $_GET['file_id'] ) ) {
					return gfa_admin_files_remove( $_GET['file_id'] );
				}
				break;
		}
	}

	//
	// table of files
	//
	if (
		isset( $_POST['submitted'] ) ||
		isset( $_POST['clear_filters'] ) ||
		isset( $_POST['file_id'] ) ||
		isset( $_POST['file_name'] ) ||
		isset( $_POST['file_description'] ) ||
		isset( $_POST['file_path'] ) ||
		isset( $_POST['file_max_count'] )// ||
		//isset( $_POST['file_group_id'] ) // @todo later
	) {
		if ( !wp_verify_nonce( $_POST[GFA_ADMIN_FILTER_NONCE], 'admin' ) ) {
			wp_die( esc_html__( 'Access denied.', 'groups-file-access' ) );
		}
	}

	// filters
	$file_id          = Groups_Options::get_user_option( 'gfa_file_id', null );
	$file_name        = Groups_Options::get_user_option( 'gfa_file_name', null );
	$file_description = Groups_Options::get_user_option( 'gfa_file_description', null );
	$file_path        = Groups_Options::get_user_option( 'gfa_file_path', null );
	$file_max_count   = Groups_Options::get_user_option( 'gfa_file_max_count', null );
	$group_name       = Groups_Options::get_user_option( 'gfa_group_name', null );
	//$file_group_id    = Groups_Options::get_user_option( 'gfa_file_group_id', null );

	// @todo MIME type filter

	if ( isset( $_POST['clear_filters'] ) ) {
		Groups_Options::delete_user_option( 'gfa_file_id' );
		Groups_Options::delete_user_option( 'gfa_file_name' );
		Groups_Options::delete_user_option( 'gfa_file_description' );
		Groups_Options::delete_user_option( 'gfa_file_path' );
		Groups_Options::delete_user_option( 'gfa_file_max_count' );
		Groups_Options::delete_user_option( 'gfa_group_name' );
		Groups_Options::delete_user_option( 'gfa_file_group_id' );
		$file_id = null;
		$file_name = null;
		$file_description = null;
		$file_path = null;
		$file_max_count = null;
		$group_name = null;
		//$file_group_id = null;
	} else if ( isset( $_POST['submitted'] ) ) {

		// filter by id
		if ( !empty( $_POST['file_id'] ) ) {
			$file_id = intval( trim( $_POST['file_id'] ) );
			if ( $file_id < 1 ) {
				$file_id = 1;
			}
			Groups_Options::update_user_option( 'gfa_file_id', $file_id );
		} else if ( isset( $_POST['file_id'] ) ) { // empty && isset => '' => all
			$file_id = null;
			Groups_Options::delete_user_option( 'gfa_file_id' );
		}
		// filter by name, description, ...
		if ( !empty( $_POST['file_name'] ) ) {
			$file_name = trim( $_POST['file_name'] );
			Groups_Options::update_user_option( 'gfa_file_name', $file_name );
		} else {
			$file_name = null;
			Groups_Options::delete_user_option( 'gfa_file_name' );
		}
		if ( !empty( $_POST['file_description'] ) ) {
			$file_description = trim( $_POST['file_description'] );
			Groups_Options::update_user_option( 'gfa_file_description', $file_description );
		} else {
			$file_description = null;
			Groups_Options::delete_user_option( 'gfa_file_description' );
		}
		if ( !empty( $_POST['file_path'] ) ) {
			$file_path = trim( $_POST['file_path'] );
			Groups_Options::update_user_option( 'gfa_file_path', $file_path );
		} else {
			$file_path = null;
			Groups_Options::delete_user_option( 'gfa_file_path' );
		}
		if ( isset( $_POST['file_max_count'] ) && ( strlen( trim( $_POST['file_max_count'] ) ) > 0 ) ) {
			$file_max_count = intval( trim( $_POST['file_max_count'] ) );
			if ( $file_max_count < 0 ) {
				$file_max_count = 0;
			}
			Groups_Options::update_user_option( 'gfa_file_max_count', $file_max_count );
		} else {
			$file_max_count = null;
			Groups_Options::delete_user_option( 'gfa_file_max_count' );
		}
		if ( !empty( $_POST['group_name'] ) ) {
			$group_name = trim( $_POST['group_name'] );
			Groups_Options::update_user_option( 'gfa_group_name', $group_name );
		} else {
			$group_name = null;
			Groups_Options::delete_user_option( 'gfa_group_name' );
		}
		//if ( !empty( trim( $_POST['group_id'] ) ) ) {
		//	if ( $group = Groups_Group::read( trim( $_POST['group_id'] ) ) ) {
		// ...
		//	}
		//	}

	}

	if ( isset( $_POST['row_count'] ) ) {
		if ( !wp_verify_nonce( $_POST[GFA_ADMIN_NONCE_1], 'admin' ) ) {
			wp_die( esc_html__( 'Access denied.', 'groups-file-access' ) );
		}
	}

	if ( isset( $_POST['paged'] ) ) {
		if ( !wp_verify_nonce( $_POST[GFA_ADMIN_NONCE_2], 'admin' ) ) {
			wp_die( esc_html__( 'Access denied.', 'groups-file-access' ) );
		}
	}

	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$current_url = remove_query_arg( 'paged', $current_url );
	$current_url = remove_query_arg( 'action', $current_url );
	$current_url = remove_query_arg( 'file_id', $current_url );

	$file_table = _groups_get_tablename( 'file' );
	$file_meta_table = _groups_get_tablename( 'file_meta' );

	$output .=
		'<div class="manage-files">' .
		'<div>' .
		'<h1>' .
		__( 'Files', 'groups-file-access' ) .
		'</h1>' .
		'</div>';

	$output .=
		'<div class="manage">' .
		"<a title='" . esc_html__( 'Click to add a new file', 'groups-file-access' ) . "' class='add button button-primary' href='" . esc_url( $current_url ) . "&action=add'><img class='icon' alt='" . esc_attr__( 'Add', 'groups-file-access' ) . "' src='". GFA_PLUGIN_URL ."images/add.png'/><span class='label'>" . esc_html__( 'New File', 'groups-file-access' ) . "</span></a>" .
		'</div>';

	$row_count = isset( $_POST['row_count'] ) ? intval( $_POST['row_count'] ) : 0;

	if ($row_count <= 0) {
		$row_count = Groups_Options::get_user_option( 'files_per_page', GFA_FILES_PER_PAGE );
	} else {
		Groups_Options::update_user_option('files_per_page', $row_count );
	}
	$offset = isset( $_GET['offset'] ) ? intval( $_GET['offset'] ) : 0;
	if ( $offset < 0 ) {
		$offset = 0;
	}
	$paged = isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 0;
	if ( $paged < 0 ) {
		$paged = 0;
	}

	$orderby = isset( $_GET['orderby'] ) ? $_GET['orderby'] : null;
	switch ( $orderby ) {
		case 'file_id' :
		case 'name' :
		case 'description' :
		case 'path' :
		case 'max_count' :
		case 'created' :
		case 'updated' :
			break;
		default:
			$orderby = 'name';
	}

	$order = isset( $_GET['order'] ) ? $_GET['order'] : null;
	switch ( $order ) {
		case 'asc' :
		case 'ASC' :
			$switch_order = 'DESC';
			break;
		case 'desc' :
		case 'DESC' :
			$switch_order = 'ASC';
			break;
		default:
			$order = 'ASC';
			$switch_order = 'DESC';
	}

	$filters = array( " 1=%d " );
	$filter_params = array( 1 );
	if ( $file_id ) {
		$filters[] = " $file_table.file_id = %d ";
		$filter_params[] = $file_id;
	}
	if ( $file_name ) {
		$filters[] = " $file_table.name LIKE %s ";
		$filter_params[] = '%' . $wpdb->esc_like( $file_name ) . '%';
	}
	if ( $file_description ) {
		$filters[] = " $file_table.description LIKE %s ";
		$filter_params[] = '%' . $wpdb->esc_like( $file_description ) . '%';
	}
	if ( $file_path ) {
		$filters[] = " $file_table.path LIKE %s ";
		$filter_params[] = '%' . $wpdb->esc_like( $file_path ) . '%';
	}
	if ( $file_max_count !== null ) {
		$filters[] = " $file_table.max_count = %d ";
		$filter_params[] = intval( $file_max_count );
	}
	if ( $group_name ) {
		$file_group_table = _groups_get_tablename( 'file_group' );
		$group_table = _groups_get_tablename( 'group' );
		$filters[] =
			" $file_table.file_id IN " .
			"( " .
			"SELECT DISTINCT file_id FROM $file_group_table " .
			"LEFT JOIN $group_table ON $file_group_table.group_id = $group_table.group_id " .
			"WHERE $group_table.name LIKE %s" .
			" ) ";
		$filter_params[] = '%' . $wpdb->esc_like( $group_name ) . '%';
	}
	if ( !empty( $filters ) ) {
		$filters = " WHERE " . implode( " AND ", $filters );
	} else {
		$filters = '';
	}

	$count_query = $wpdb->prepare( "SELECT COUNT(*) FROM $file_table $filters", $filter_params );
	$count  = $wpdb->get_var( $count_query );
	if ( $count > $row_count ) {
		$paginate = true;
	} else {
		$paginate = false;
	}
	$pages = ceil ( $count / $row_count );
	if ( $paged > $pages ) {
		$paged = $pages;
	}
	if ( $paged != 0 ) {
		$offset = ( $paged - 1 ) * $row_count;
	}

	$query = $wpdb->prepare(
		"SELECT * FROM $file_table
		$filters
		ORDER BY $orderby $order
		LIMIT $row_count OFFSET $offset",
		$filter_params
	);

	$results = $wpdb->get_results( $query, OBJECT );

	$column_display_names = array(
		'file_id'     => __( 'Id', 'groups-file-access' ),
		'name'        => __( 'Name', 'groups-file-access' ),
		'description' => __( 'Description', 'groups-file-access' ),
		'path'        => __( 'Path', 'groups-file-access' ),
		'max_count'   => __( 'Max #', 'groups-file-access' ),
		'groups'      => __( 'Groups', 'groups-file-access' ),
		'created'     => __( 'Created', 'groups-file-access' ),
		'updated'     => __( 'Updated', 'groups-file-access' ),
		'edit'        => __( 'Edit', 'groups-file-access' ),
		'remove'      => __( 'Remove', 'groups-file-access' )
	);

	$column_display_name_titles = array(
		'max_count' => __( 'Maximum number of accesses per user', 'groups-file-access' ),
		'groups'    => __( 'Only group members can access the file', 'groups-file-access' )
	);

	$output .= '<div class="files-overview">';

	$output .=
		'<div class="filters">' .
			'<label class="description" for="setfilters">' . esc_html__( 'Filters', 'groups-file-access' ) . '</label>' .
			'<form id="setfilters" action="" method="post">' .
				'<div style="display:flex;flex-wrap:wrap;padding:4px;">' .
				'<span class="filter-field">' .
				'<label class="file-id-filter" for="file_id">' . esc_html__( 'File Id', 'groups-file-access' ) . '</label>' .
				'<input class="file-id-filter" name="file_id" type="text" value="' . esc_attr( $file_id ) . '"/>' .
				'</span>' .
				'<span class="filter-field">' .
				'<label class="file-name-filter" for="file_name">' . esc_html__( 'Name', 'groups-file-access' ) . '</label>' .
				'<input class="file-name-filter" name="file_name" type="text" value="' . esc_attr( $file_name !== null ? stripslashes( $file_name ) : '' ) . '"/>' .
				'</span>' .
				'<span class="filter-field">' .
				'<label class="file-description-filter" for="file_description">' . esc_html__( 'Description', 'groups-file-access' ) . '</label>' .
				'<input class="file-description-filter" name="file_description" type="text" value="' . esc_attr( $file_description !== null ? stripslashes( $file_description ) : '' ) . '"/>' .
				'</span>' .
				'<span class="filter-field">' .
				'<label class="file-path-filter" for="file_path">' . esc_html__( 'Path', 'groups-file-access' ) . '</label>' .
				'<input class="file-path-filter" name="file_path" type="text" value="' . esc_attr( $file_path !== null ? stripslashes( $file_path ) : '' ) . '"/>' .
				'</span>' .
				'<span class="filter-field">' .
				'<label class="file-max-count-filter" for="file_max_count">' . esc_html__( 'Max #', 'groups-file-access' ) . '</label>' .
				'<input class="file-max-count-filter" name="file_max_count" type="text" value="' . esc_attr( $file_max_count ) . '"/>' .
				'</span>' .
				'<span class="filter-field">' .
				'<label class="file-group-name-filter" for="group_name">' . esc_html__( 'Group', 'groups-file-access' ) . '</label>' .
				'<input class="file-group-name-filter" name="group_name" type="text" value="' . esc_attr( $group_name !== null ? stripslashes( $group_name ) : '' ) . '"/>' .
				'</span>' .
				'</div>' .
				'<div style="display:flex;flex-wrap:wrap;padding:4px;">' .
				wp_nonce_field( 'admin', GFA_ADMIN_FILTER_NONCE, true, false ) .
				'<input class="button" type="submit" value="' . esc_html__( 'Apply', 'groups-file-access' ) . '"/>' .
				'<input class="button" type="submit" name="clear_filters" value="' . esc_html__( 'Clear', 'groups-file-access' ) . '"/>' .
				'<input type="hidden" value="submitted" name="submitted"/>' .
				'</div>' .
			'</form>' .
		'</div>';

	$output .= '<script type="text/javascript">';
	$output .= 'if (typeof jQuery !== "undefined") {';
	$output .= 'jQuery(document).ready(function() {';
	$output .= 'jQuery(".filter-field input").change(function() {';
	$output .= 'if(jQuery(this).val()) {';
	$output .= 'jQuery(this).addClass("filter-active");';
	$output .= '} else {';
	$output .= 'jQuery(this).removeClass("filter-active");';
	$output .= '}';
	$output .= '});';
	$output .= 'jQuery(".filter-field input").change();';
	$output .= '});';
	$output .= '}';
	$output .= '</script>';

	$output .= '
		<div class="page-options">
			<form id="setrowcount" action="" method="post">
				<div>
					<label for="row_count">' . esc_html__('Results per page', 'groups-file-access' ) . '</label>' .
					'<input name="row_count" type="text" size="2" value="' . esc_attr( $row_count ) .'" />
					' . wp_nonce_field( 'admin', GFA_ADMIN_NONCE_1, true, false ) . '
					<input class="button" type="submit" value="' . esc_html__( 'Apply', 'groups-file-access' ) . '"/>
				</div>
			</form>
		</div>
		';

	if ( $paginate ) {
		require_once GROUPS_CORE_LIB . '/class-groups-pagination.php';
		$pagination = new Groups_Pagination( $count, null, $row_count );
		$output .= '<form id="posts-filter" method="post" action="">';
		$output .= '<div>';
		$output .= wp_nonce_field( 'admin', GFA_ADMIN_NONCE_2, true, false );
		$output .= '</div>';
		$output .= '<div class="tablenav top">';
		$output .= $pagination->pagination( 'top' );
		$output .= '</div>';
		$output .= '</form>';
	}

	$output .= '<form id="files-action" method="post" action="">';

	$output .= '<div class="tablenav top">';
	$output .= '<div class="alignleft actions remove">';
	$output .= esc_html__( "Apply to selected", 'groups-file-access' );
	$output .= ' ';

	if ( !isset( $_POST['remove'] ) || empty( $_POST['file_ids'] ) ) {
		$output .= '<input class="button" type="submit" name="remove" value="' . esc_html__( "Remove", 'groups-file-access' ) . '"/>';
	} else {

		$output .= '<input class="button" type="submit" name="remove" value="' . sprintf( esc_html( _n( "Click again to remove 1 entry", "Click again to remove %d entries", count( $_POST['file_ids'] ), 'groups-file-access' ) ), count( $_POST['file_ids'] ) ) . '"/>';
		$output .= '<input type="hidden" name="confirmed" value="1" />';
		$output .= ' ';
		$output .= "<a href='" . esc_url( $current_url ) . "'>" . esc_html__( 'Cancel', 'groups-file-access' ) . "</a>";
	}

	$output .= wp_nonce_field( 'admin', GFA_ADMIN_ACTION_NONCE, true, false );
	$output .= '<input type="hidden" name="action" value="files-action"/>';

	$output .= '</div>'; // .alignleft

	$group_table = _groups_get_tablename( "group" );
	$groups_select = "<select name='group_id'>";
	$groups = $wpdb->get_results( "SELECT * FROM $group_table ORDER BY name" );
	foreach( $groups as $group ) {
		$groups_select .= "<option value='" . esc_attr( $group->group_id ) . "'>" . stripslashes( wp_filter_nohtml_kses( $group->name ) ) . "</option>";
	}
	$groups_select .= "</select>";

	$output .= "<div class='alignleft actions group' style='padding-left:1em'>";
	$output .= "<input class='button' type='submit' name='add-to-group' value='" . esc_html__( "Add", 'groups-file-access' ) . "'/>";
	$output .= " ";
	$output .= "<input class='button' type='submit' name='remove-from-group' value='" . esc_html__( "Remove", 'groups-file-access' ) . "'/>";
	$output .= " ";
	$output .= esc_html__( 'to / from', 'groups-file-access' );
	$output .= " ";
	$output .= "<label class='screen-reader-text' for='group_id'>" . esc_html__( 'Group', 'groups-file-access' ) . "</label>";
	$output .= $groups_select;
	$output .= "</div>";  // .alignleft

	$output .= '</div>'; // .tablenav.top

	$output .= '<table id="" class="wp-list-table widefat files" cellspacing="0">';
	$output .= '<thead>';
	$output .= '<tr>';

	$output .= '<th id="cb" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>';

	foreach ( $column_display_names as $key => $column_display_name ) {
		$column_options = array(
			'orderby' => $key,
			'order' => $switch_order
		);
		$class = $key;
		if ( !in_array( $key, array( 'groups', 'edit', 'remove' ) ) ) {
			if ( strcmp( $key, $orderby ) == 0 ) {
				$lorder = strtolower( $order );
				$class = "$key manage-column sorted $lorder";
			} else {
				$class = "$key manage-column sortable";
			}
			$column_display_name = sprintf(
				'<a href="%s"><span title="%s">%s</span><span class="sorting-indicator"></span></a>',
				esc_url( add_query_arg( $column_options, $current_url ) ),
				!empty( $column_display_name_titles[$key] ) ? esc_attr( $column_display_name_titles[$key] ) : '',
				$column_display_name
			); // WPCS: XSS ok.
		} else {
			$column_display_name = sprintf(
				'<span title="%s">%s</span>',
				!empty( $column_display_name_titles[$key] ) ? esc_attr( $column_display_name_titles[$key] ) : '',
				$column_display_name
			); // WPCS: XSS ok.
		}
		$output .= sprintf( '<th scope="col" class="%s">%s</th>', esc_attr( $class ), wp_kses_post( $column_display_name ) ); // WPCS: XSS ok.
	}

	$output .= '</tr>';
	$output .= '</thead>';
	$output .= '<tbody>';

	$base_url = get_bloginfo( 'url' );

	$options = get_option( Groups_File_Access::PLUGIN_OPTIONS , array() );
	$amazon_s3_enabled    = isset( $options[Groups_File_Access::AMAZON_S3] ) ? $options[Groups_File_Access::AMAZON_S3] : Groups_File_Access::AMAZON_S3_DEFAULT;
	$amazon_s3_access_key = isset( $options[Groups_File_Access::AMAZON_S3_ACCESS_KEY] ) ? $options[Groups_File_Access::AMAZON_S3_ACCESS_KEY] : '';
	$amazon_s3_secret_key = isset( $options[Groups_File_Access::AMAZON_S3_SECRET_KEY] ) ? $options[Groups_File_Access::AMAZON_S3_SECRET_KEY] : '';

	if ( count( $results ) > 0 ) {
		for ( $i = 0; $i < count( $results ); $i++ ) {

			$result = $results[$i];

			$type_query = $wpdb->prepare( "SELECT meta_value FROM $file_meta_table WHERE meta_key = 'type' AND file_id = %d", intval( $result->file_id ) );
			$type = $wpdb->get_var( $type_query );

			$output .= '<tr class="' . ( $i % 2 == 0 ? 'even' : 'odd' ) . '">';

			$output .= '<th class="check-column">';
			$checked = !empty( $_POST['file_ids'] ) && in_array( $result->file_id, $_POST['file_ids'] ) ? ' checked="checked" ' : '';
			$output .= '<input ' . $checked . 'type="checkbox" value="' . esc_attr( $result->file_id ) . '" name="file_ids[]"/>';
			$output .= '</th>';

			$output .= "<td class='file-id'>";
			$output .= $result->file_id;
			$output .= "</td>";

			$output .= "<td class='file-name'>" . stripslashes( $result->name ) . "</td>";

			$desc = htmlentities( stripslashes( $result->description ), ENT_COMPAT, get_bloginfo( 'charset' ) );
			if ( function_exists( 'mb_substr' ) && function_exists( 'mb_strlen' ) ) {
				$shortened_desc = mb_substr( $desc, 0, min( GFA_DESCRIPTION_CUT, mb_strlen( $desc ) ) );
				if ( mb_strlen( $shortened_desc ) < mb_strlen( $desc ) ) {
					$shortened_desc .= "&hellip;";
				}
			} else {
				$shortened_desc = substr( $desc, 0, min( GFA_DESCRIPTION_CUT, strlen( $desc ) ) );
				if ( strlen( $shortened_desc ) < strlen( $desc ) ) {
					$shortened_desc .= "&hellip;";
				}
			}
			$output .= "<td class='file-description'>" . $shortened_desc . "</td>";

			$errors = array();

			$size      = '';
			$mime_type = '';
			$date       = '';

			if ( $type === 'amazon-s3' ) {

				$type   = '';
				$region = '';
				$bucket = '';
				$key    = '';
				$file_meta_table = _groups_get_tablename( 'file_meta' );
				$file_metas = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $file_meta_table WHERE file_id = %d", $result->file_id ) );
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

				if ( $amazon_s3_enabled ) {
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
						$headobject = $s3client->headObject( array( 'Bucket' => $bucket, 'Key' => $key ) );
						/**
						 * @var Aws\Api\DateTimeResult $last_modified
						 */
						$last_modified = $headobject->get( 'LastModified' );
						$size = $headobject->get( 'ContentLength' );
						$mime_type = $headobject->get( 'ContentType' );
						$date = $last_modified->format( 'Y-m-d H:i:s e' );
					} catch ( Exception $ex ) {
						$size = esc_html__( 'ERROR', 'groups-file-access' );
						$mime_type = esc_html__( 'ERROR', 'groups-file-access' );
						$date = esc_html__( 'ERROR', 'groups-file-access' );
						$errors[] = $ex->getMessage();
					}
				}

			} else {

				$size = @filesize( $result->path );
				if ( $size !== false ) {
					$size = GFA_File_Upload::human_bytes( $size );
				} else {
					$size = esc_html__( 'ERROR', 'groups-file-access' );
				}
				$mtime = @filemtime( $result->path );
				if ( $mtime !== false ) {
					$date = date( 'Y-m-d H:i:s', $mtime );
				} else {
					$date = esc_html__( 'ERROR', 'groups-file-access' );
				}

				$mime_type = GFA_Utility::get_mime_type( $result->path );
			}

			$output .= '<td class="file-path">' .
				$result->path .
				'<br/>' .
				sprintf( esc_html__( 'URL: %s', 'groups-file-access' ), wp_kses_post( GFA_File_Renderer::render_url( $result, $base_url ) ) ) .
				'<br/>' .
				sprintf( esc_html__( 'Link: %s', 'groups-file-access' ), wp_kses_post( GFA_File_Renderer::render_link( $result, $base_url ) ) ) .
				'<br/>' .
				sprintf( esc_html__( 'Size: %s', 'groups-file-access' ), wp_kses_post( $size ) ) .
				'<br/>' .
				sprintf( esc_html__( 'Date: %s', 'groups-file-access' ), wp_kses_post( $date ) ) .
				'<br/>' .
				sprintf( esc_html__( 'MIME Type: %s', 'groups-file-access' ), esc_html( $mime_type !== null ? $mime_type : __( 'unknown', 'groups-file-access' ) ) );
			if ( count( $errors ) > 0 ) {
				$output .= '<br/>';
				foreach ( $errors as $error ) {
					$output .= '<div class="error">';
					$output .= wp_kses_post( $error );
					$output .= '</div>';
				}
			}
			if ( $type === 'amazon-s3' && !$amazon_s3_enabled ) {
				$output .= '<div class="gfa-warning">';
				$output .= sprintf(
					esc_html__( 'Amazon S3 file access is disabled in the %s.', 'groups-file-access' ),
					sprintf(
						'<a href="%s">%s</a>',
						esc_attr( admin_url( 'admin.php?page=groups-admin-file-access#' . Groups_File_Access::AMAZON_S3 ) ),
						esc_html__( 'Settings', 'groups-file-access' )
					)
				);
				$output .= '</div>';
			}
			$output .= '</td>';
			$output .= "<td class='file-max-count'>" . intval( $result->max_count ) . "</td>";

			$output .= "<td class='file-groups'>";
			$file_group_table = _groups_get_tablename( 'file_group' );
			if ( $groups = $wpdb->get_results( $wpdb->prepare( "SELECT g.* FROM $file_group_table fg LEFT JOIN $group_table g ON fg.group_id = g.group_id WHERE file_id = %d ORDER BY g.name", $result->file_id ) ) ) {
				$output .= '<ul>';
				foreach ( $groups as $group ) {
					$output .= '<li>';
					$output .= stripslashes( wp_filter_nohtml_kses( $group->name ) );
					$output .= '</li>';
				}
				$output .= '</ul>';
			}
			$output .= "</td>";

			$output .= "<td class='file-created'>" . stripslashes( $result->created ) . "</td>";

			$output .= "<td class='file-updated'>" . stripslashes( $result->updated ) . "</td>";

			$output .= "<td class='edit'>";
			$output .= "<a href='" . esc_url( add_query_arg( 'paged', $paged, $current_url ) ) . "&action=edit&file_id=" . $result->file_id . "' alt='" . esc_attr__( 'Edit', 'groups-file-access' ) . "'><img src='". GFA_PLUGIN_URL . "images/edit.png'/></a>";
			$output .= "</td>";

			$output .= "<td class='remove'>";
			$output .= "<a href='" . esc_url( $current_url ) . "&action=remove&file_id=" . $result->file_id . "' alt='" . esc_attr__( 'Remove', 'groups-file-access' ) . "'><img src='". GFA_PLUGIN_URL . "images/remove.png'/></a>";
			$output .= "</td>";

			$output .= '</tr>';
		}
	} else {
		$output .= '<tr><td colspan="10">' . esc_html__( 'There are no results.', 'groups-file-access' ) . '</td></tr>';
	}

	$output .= '</tbody>';
	$output .= '</table>';

	$output .= '</form>'; // #files-action

	if ( $paginate ) {
		require_once GROUPS_CORE_LIB . '/class-groups-pagination.php';
		$pagination = new Groups_Pagination( $count, null, $row_count );
		$output .= '<div class="tablenav bottom">';
		$output .= $pagination->pagination( 'bottom' );
		$output .= '</div>';
	}

	$output .= '</div>'; // .files-overview
	$output .= '</div>'; // .manage-files

	require_once GFA_VIEWS_LIB . '/class-gfa-help.php';
	$output .= GFA_Help::footer();

	echo $output; // WPCS: XSS ok.
} // function
