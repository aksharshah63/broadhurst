<?php
/**
 * class-gfa-shortcodes.php
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
 * Shortcode handlers.
 */
class GFA_Shortcodes {

	/**
	 * @var string show if user has file access
	 */
	const CAN_ACCESS = 'can_access';

	/**
	 * @var string always show
	 */
	const ALWAYS = 'always';

	/**
	 * Used to register shortcode attribute handlers.
	 * @var int
	 */
	const LATE = 99999;

	/**
	 * @var string current default visibility to apply
	 */
	private static $visibility = self::CAN_ACCESS;

	/**
	 * Register the plugin's shortcodes.
	 */
	public static function init() {
		// content by file access
		add_shortcode( 'groups_can_access_file', array( __CLASS__, 'groups_can_access_file' ) );
		add_shortcode( 'groups_can_not_access_file', array( __CLASS__, 'groups_can_not_access_file' ) );
		// file information
		add_shortcode( 'groups_file_info', array( __CLASS__, 'groups_file_info' ) );
		// Link
		add_shortcode( 'groups_file_link', array( __CLASS__, 'groups_file_link' ) );
		// URL
		add_shortcode( 'groups_file_url', array( __CLASS__, 'groups_file_url' ) );
		// determine default visibility
		add_shortcode( 'groups_file_visibility', array( __CLASS__, 'groups_file_visibility' ) );
		// render the service key
		add_shortcode( 'groups_file_access_service_key', array( __CLASS__, 'groups_file_access_service_key' ) );

		// Would register shortcode attribute handlers if that were an option.
		// add_action( 'init', array( __CLASS__, 'wp_init' ), self::LATE );

		// We can't register our "preprocessor" as a normal shortcode ...
		// add_shortcode( 'groups_file_access_process', array( __CLASS__, 'groups_file_access_process' ) );
		// ... but must process the content instead:
		add_filter( 'the_content', array( __CLASS__, 'the_content' ), 0 );
	}

	/**
	 * Executes our groups_file_access_process shortcode if found in the content.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public static function the_content( $content ) {

		global $shortcode_tags;

		// Temporarily limit shortcodes to our groups_file_access_process only:
		$_shortcode_tags = $shortcode_tags;
		$shortcode_tags = array( 'groups_file_access_process' => array( __CLASS__, 'groups_file_access_process' ) );

		if ( has_shortcode( $content, 'groups_file_access_process' ) ) {
			// Now execute our groups_file_access_process shortcode.
			// do_shortcode does more than we want and might mess up what's inside ...
			//$content = do_shortcode( $content );
			// ... so we just have our specific handler executed:
			$pattern = get_shortcode_regex();
			$content = preg_replace_callback( "/$pattern/s", 'do_shortcode_tag', $content );
		}

		// Reestablish registered shortcodes:
		$shortcode_tags = $_shortcode_tags;

		return $content;
	}

	/**
	 * Intended to register filters that would allow to process shortcode attributes.
	 *
	 * Currently not used.
	 */
	public static function wp_init() {
		//global $shortcode_tags;
		//$shortcode_tags = array( 'groups_file_access_process' => array( __CLASS__, 'groups_file_access_process' ) ) + $shortcode_tags;

		// Possible ways of processing shortcode attributes:

		// 1) Use the existing shortcode_atts_{$shortcode} filter.
		// This will not work in most cases because shortcodes would have to call
		// shortcode_atts( ..., ..., $shortcode )
		// and most don't do that (the third parameter is pretty new).
		// As the filter is only called when $shortcode is not empty (it is '' by default),
		// we won't be able to actually process the attribute.

		// foreach( $shortcode_tags as $shortcode => $callback ) {
		// 	// $out = apply_filters( "shortcode_atts_{$shortcode}", $out, $pairs, $atts, $shortcode );
		// 	add_filter( "shortcode_atts_{$shortcode}", array( __CLASS__, 'shortcode_atts_' ), self::LATE, 4 );
		// }

		// 2) Use a hypothetical shortcode_atts filter.
		// This would be great if there existed a filter
		// in addition to the existing filter mentioned above.
		// $out = apply_filters( "shortcode_atts", $out, $pairs, $atts );
		// add_filter( 'shortcode_atts', array( __CLASS__, 'shortcode_atts' ), self::LATE, 3 );
	}

	/**
	 * Unused.
	 *
	 * Filter placed on "shortcode_atts_{$shortcode}".
	 *
	 * @param string $out
	 * @param array $pairs
	 * @param array $atts
	 * @param string $shortcode
	 *
	 * @return string
	 */
	public static function shortcode_atts_( $out, $pairs, $atts, $shortcode ) {
		if ( !empty( $atts ) ) {
			// check the attributes for presence of [groups_file_url ...] and substitute it
		}
		return $out;
	}

	/**
	 * Unused.
	 *
	 * Filter placed on "shortcode_atts" (which currently WP 4.4 doesn't exist).
	 *
	 * @param string $out
	 * @param array $pairs
	 * @param array $atts
	 *
	 * @return string
	 */
	public static function shortcode_atts( $out, $pairs, $atts ) {
		if ( !empty( $atts ) ) {
			// check the attributes for presence of [groups_file_url ...] and substitute it
		}
		return $out;
	}

	/**
	 * Process content: render [groups_file_url] anywhere by default.
	 *
	 * Other Groups File Access shortcodes can be provided by
	 * indicating the 'shortcodes' attribute:
	 * - groups_can_access_file
	 * - groups_can_not_access_file
	 * - groups_file_access_service_key
	 * - groups_file_info
	 * - groups_file_link
	 * - groups_file_url
	 *
	 * @param array $atts
	 * @param string $content
	 *
	 * @return string
	 */
	public static function groups_file_access_process( $atts, $content = null ) {

		$atts = shortcode_atts(
			array(
				'shortcodes' => 'groups_file_url'
			),
			$atts,
			'groups_file_access_process'
		);

		$shortcodes = array_map( 'trim', explode( ',', $atts['shortcodes'] ) );

		if ( !empty( $content ) ) {
			global $shortcode_tags;
			$_shortcode_tags = $shortcode_tags;
			$shortcode_tags = array();
			foreach( $shortcodes as $shortcode ) {
				switch( $shortcode ) {
					case 'groups_can_access_file':
					case 'groups_can_not_access_file' :
					case 'groups_file_access_service_key' :
					case 'groups_file_info' :
					case 'groups_file_link' :
					case 'groups_file_url' :
						$shortcode_tags = $shortcode_tags + array( $shortcode => array( __CLASS__, $shortcode ) );
						break;
				}
			}

			// Instead of running shortcodes on the whole content ...
			// $content = do_shortcode( $content );
			// ... only run it on the parts of content that actually belong
			// to the shortcodes we want to execute:
			$pattern = get_shortcode_regex();
			// $pattern will find those pieces of the content that match the currently
			// enabled shortcodes
			preg_match_all( "/$pattern/s", $content, $matches );
			// $matches[0] holds instances of our matched shortcodes
			if ( !empty( $matches[0] ) && is_array( $matches[0] ) ) {
				foreach( $matches[0] as $match ) {
					$substitute = do_shortcode( $match );
					// Either do replacements in bulk ...
					//$content = str_replace( $match, $substitute, $content );
					// ... or we could substitute in order, once for each match:
					if ( ( $i = strpos( $content, $match ) ) !== false ) {
						$content = substr_replace( $content, $substitute, $i, strlen( $match ) );
					}
				}
			}

			$shortcode_tags = $_shortcode_tags;
		}
		return $content;
	}

	/**
	 * Renders a file URL.
	 *
	 * Attributes:
	 * - "file_id" : id of the file
	 * - "visibility" : "can_access" (default) renders only if current user is authorized to access the file, "always" renders in any case
	 *
	 * @param array $atts attributes
	 * @param string $content not used
	 *
	 * @return string rendered URL
	 */
	public static function groups_file_url( $atts, $content = null ) {
		global $wpdb;
		$output = "";
		$options = shortcode_atts(
			array(
				'file_id'        => null,
				'visibility'     => self::$visibility,
				'session_access' => Groups_File_Access_Session::enabled() ? 'yes' : 'no'
			),
			$atts
		);
		if ( $options['file_id'] !== null ) {
			$file_id = intval( $options['file_id'] );
			$can_see = false;
			switch( $options['visibility'] ) {
				case self::ALWAYS :
					$can_see = true;
					break;
				default :
					$user_id = get_current_user_id();
					$can_see = Groups_File_Access::can_access( $user_id, $file_id );
			}
			if ( $can_see ) {
				$file_table = _groups_get_tablename( 'file' );
				$file = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $file_table WHERE file_id = %d", $file_id ) );
				$base_url = get_bloginfo( 'url' );
				$output = GFA_File_Renderer::render_url( $file, $base_url, array( 'session_access' => $options['session_access'] ) );
			}
		}
		return $output;
	}

	/**
	 * Renders a link to a file.
	 *
	 * Required attributes are either "file_id" or "group".
	 *
	 * Basic attributes:
	 * - "file_id" : id of the file
	 * - "visibility" : "can_access" or "always" see GFA_Shortcodes::groups_file_url()
	 * - "group" : group name or ID - will list files for the given group sorted by name
	 * - "user_id" : allows to indicate a user ID, otherwise the current user's ID will be used @since 2.3.0
	 * - "description" : defaults to "no", "yes" shows description for each entry (only "group")
	 * - "order" : ASC or DESC sort order (only for "group")
	 * - "list_prefix" : defaults to "<ul>"
	 * - "list_suffix" : defaults to "</ul>"
	 * - "item_prefix" : defaults to "<li>"
	 * - "item_suffix" : defaults to "</li>"
	 *
	 * Note that the prefixes and suffixes are very limited due to filters applied.
	 *
	 * Allowed link attributes: accesskey, alt, charset, coords, class, dir, hreflang, id, lang, name, rel, rev, shape, style, tabindex, target
	 *
	 * @see GFA_Shortcodes::groups_file_url()
	 *
	 * @param array $atts attributes
	 * @param string $content not used
	 *
	 * @return string rendered link
	 */
	public static function groups_file_link( $atts, $content = null ) {
		global $wpdb;
		$output = '';
		$options = shortcode_atts(
			array(
				// attributes
				'file_id'     => null,
				'visibility'  => self::$visibility,
				'group'       => null,
				'description' => 'no',
				'description_filter' => 'wp_filter_kses',
				'order'       => 'ASC',
				'orderby'     => 'name',
				'list_prefix' => '<ul>',
				'list_suffix' => '</ul>',
				'item_prefix' => '<li>',
				'item_suffix' => '</li>',
				// link attributes
				'accesskey' => null,
				'alt'       => null,
				'charset'   => null,
				'coords'    => null,
				'class'     => null,
				'dir'       => null,
				'hreflang'  => null,
				'id'        => null,
				'lang'      => null,
				'name'      => null,
				'rel'       => null,
				'rev'       => null,
				'shape'     => null,
				'style'     => null,
				'tabindex'  => null,
				'target'    => null,
				'session_access' => Groups_File_Access_Session::enabled() ? 'yes' : 'no',
				// user
				'user_id'   => null
			),
			$atts
		);
		foreach( $options as $key => $value ) {
			if ( $value === null ) {
				unset( $options[$key] );
			} else {
				$options[$key] = $value;
			}
		}
		// current user or user_id provided
		$user_id = get_current_user_id();
		if ( isset( $options['user_id'] ) && $options['user_id'] !== null ) {
			$user_id = 0;
			if ( is_numeric( $options['user_id'] ) ) {
				$maybe_user_id = intval( $options['user_id'] );
				if ( $maybe_user_id > 0 ) {
					$user = get_user_by( 'id', $maybe_user_id );
					if ( $user instanceof WP_User ) {
						$user_id = $maybe_user_id;
					}
				}
			}
		}
		// file by ID
		if ( isset( $options['file_id'] ) && ( $options['file_id'] !== null ) ) {
			$file_id = intval( $options['file_id'] );
			switch( $options['visibility'] ) {
				case self::ALWAYS :
					$can_see = true;
					break;
				default :
					$can_see = Groups_File_Access::can_access( $user_id, $file_id );
			}
			if ( $can_see ) {
				$file_table = _groups_get_tablename( 'file' );
				$file = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $file_table WHERE file_id = %d", $file_id ) );
				$base_url = get_bloginfo( 'url' );
				unset( $options['file_id'] );
				unset( $options['group'] );
				unset( $options['order'] );
				unset( $options['orderby'] );
				unset( $options['visibility'] );
				unset( $options['list_prefix'] );
				unset( $options['list_suffix'] );
				unset( $options['item_prefix'] );
				unset( $options['item_suffix'] );
				$output = GFA_File_Renderer::render_link( $file, $base_url, $options, array( 'session_access' => $options['session_access'] )  );
			}
		} else if ( isset( $options['group'] ) && ( $options['group'] !== null ) ) {
			// file by group
			$file_group_where = '';
			$group_ids        = array();
			$groups           = array_map( 'trim', explode( ',', trim( $options['group'] ) ) );
			if ( in_array( '*', $groups ) ) {
				// files for all groups
				$group_table = _groups_get_tablename( 'group' );
				$_groups = $wpdb->get_results( "SELECT group_id FROM $group_table" );
				foreach( $_groups as $_group ) {
					$group_ids[] = $_group->group_id;
				}
			} else {
				foreach( $groups as $group ) {
					$group = addslashes( trim( $group ) );
					$the_group = Groups_Group::read_by_name( $group );
					if ( !$the_group ) {
						if ( is_numeric( $group ) ) {
							$the_group = Groups_Group::read( $group );
						}
					}
					if ( $the_group ) {
						$group_ids[] = $the_group->group_id;
					}
				}
			}
			if ( count( $group_ids )  > 0 ) {
				$file_group_where = ' WHERE group_id IN ( ' . implode( ',', $group_ids ) . ' ) ';
				$file_table       = _groups_get_tablename( 'file' );
				$file_group_table = _groups_get_tablename( 'file_group' );
				$order = strtoupper( isset( $options['order'] ) ? trim( $options['order'] ) : 'ASC' );
				switch ( $order ) {
					case 'ASC' :
					case 'DESC' :
						break;
					default :
						$order = 'ASC';
				}
				$orderby = isset( $options['orderby'] ) ? trim( $options['orderby'] ) : 'name';
				switch( $orderby ) {
					case 'file_id' :
					case 'name' :
					case 'description' :
					case 'path' :
					case 'max_count' :
						break;
					default :
						$orderby = 'name';
				}
				$description = isset( $options['description'] ) ? strtolower( trim( $options['description'] ) ) : 'no';
				switch ( $description ) {
					case 'yes' :
					case 'true' :
					case '1' :
						$show_description = true;
						break;
					default :
						$show_description = false;
				}
				if ( $file_ids = $wpdb->get_results(
					"SELECT * FROM $file_table WHERE file_id IN ( SELECT file_id FROM $file_group_table $file_group_where ) ORDER BY $orderby $order"
				) ) {
					$visibility = $options['visibility'];
					$list_prefix = html_entity_decode( !empty( $options['list_prefix'] ) ? $options['list_prefix'] : '' );
					$list_suffix = html_entity_decode( !empty( $options['list_suffix'] ) ? $options['list_suffix'] : '' );
					$item_prefix = html_entity_decode( !empty( $options['item_prefix'] ) ? $options['item_prefix'] : '' );
					$item_suffix = html_entity_decode( !empty( $options['item_suffix'] ) ? $options['item_suffix'] : '' );
					unset( $options['file_id'] );
					unset( $options['group'] );
					unset( $options['order'] );
					unset( $options['visibility'] );
					unset( $options['list_prefix'] );
					unset( $options['list_suffix'] );
					unset( $options['item_prefix'] );
					unset( $options['item_suffix'] );
					$base_url = get_bloginfo( 'url' );
					$output .= $list_prefix;
					foreach ( $file_ids as $file_id ) {
						$file_id = $file_id->file_id;
						switch( $visibility ) {
							case self::ALWAYS :
								$can_see = true;
								break;
							default :
								$can_see = Groups_File_Access::can_access( $user_id, $file_id );
						}
						if ( $can_see ) {
							$file = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $file_table WHERE file_id = %d", $file_id ) );
							$output .= $item_prefix;
							$output .= $show_description ? '<div class="name">' : '';
							$output .= GFA_File_Renderer::render_link( $file, $base_url, $options, array( 'session_access' => $options['session_access'] ) );
							$output .= $show_description ? '</div>' : '';
							if ( $show_description ) {
								$output .= '<div class="description">';
								$output .= self::groups_file_info( array(
									'file_id'    => $file_id,
									'visibility' => $visibility,
									'show'       => 'description',
									'filter'     => $options['description_filter']
								) );
								$output .= '</div>';
							}
							$output .= $item_suffix;
						}
					}
					$output .= $list_suffix;
				}

			}
		}
		return $output;
	}

	/**
	 * Shows enclosed content if the current user can access the file.
	 *
	 * @param array $atts attributes - must provide the "file_id"
	 * @param string $content content to render
	 *
	 * @return string
	 */
	public static function groups_can_access_file( $atts, $content = null ) {
		$output = "";
		$options = shortcode_atts( array( "file_id" => null ), $atts );
		if ( $content !== null ) {
			if ( $options['file_id'] !== null ) {
				$file_id = intval( $options['file_id'] );
				$user_id = get_current_user_id();
				if ( Groups_File_Access::can_access( $user_id, $file_id ) ) {
					remove_shortcode( 'groups_can_access_file' );
					$content = do_shortcode( $content );
					add_shortcode( 'groups_can_access_file', array( __CLASS__, 'groups_can_access_file' ) );
					$output = $content;
				}
			}
		}
		return $output;
	}

	/**
	 * Shows enclosed content if the current user can not access the file.
	 *
	 * @param array $atts attributes - must provide the "file_id"
	 * @param string $content content to render
	 *
	 * @return string
	 */
	public static function groups_can_not_access_file( $atts, $content = null ) {
		$output = "";
		$options = shortcode_atts( array( "file_id" => null ), $atts );
		if ( $content !== null ) {
			if ( $options['file_id'] !== null ) {
				$file_id = intval( $options['file_id'] );
				$user_id = get_current_user_id();
				if ( !Groups_File_Access::can_access( $user_id, $file_id ) ) {
					remove_shortcode( 'groups_can_not_access_file' );
					$content = do_shortcode( $content );
					add_shortcode( 'groups_can_not_access_file', array( __CLASS__, 'groups_can_not_access_file' ) );
					$output = $content;
				}
			}
		}
		return $output;
	}

	/**
	 * Renders file information.
	 *
	 * Attributes:
	 * - "file_id" : id of the file
	 * - "visibility" : "can_access" (default) or "always"
	 * - "show" : "name", "description", "count", "max_count", "remaining", "file_id", "size", "sizeb"
	 *
	 * @param array $atts attributes
	 * @param string $content not used
	 *
	 * @return string rendered file information
	 */
	public static function groups_file_info( $atts, $content = null ) {
		global $wpdb;
		$output = "";
		$options = shortcode_atts(
			array(
				'file_id'    => null,
				'visibility' => self::$visibility,
				'show'       => 'name',
				'filter'     => 'wp_filter_kses'
			),
			$atts
		);
		if ( $options['file_id'] !== null ) {
			$file_id = intval( $options['file_id'] );
			$user_id = get_current_user_id();
			$can_see = false;
			switch( $options['visibility'] ) {
				case self::ALWAYS :
					$can_see = true;
					break;
				default :
					$can_see = Groups_File_Access::can_access( $user_id, $file_id );
			}
			if ( $can_see ) {
				$file_table = _groups_get_tablename( 'file' );
				$file = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $file_table WHERE file_id = %d", $file_id ) );
				switch ( $options['show'] ) {
					case 'description' :
						$output = $file->description;
						break;
					case 'count' :
						$output = Groups_File_Access::get_count( $user_id, $file_id );
						break;
					case 'max_count' :
						$output = Groups_File_Access::get_max_count( $file_id );
						break;
					case 'remaining' :
						$remaining = Groups_File_Access::get_remaining( $user_id, $file_id );
						if ( $remaining !== INF ) {
							$output = $remaining;
						} else {
							$output = '&infin;';
						}
						break;
					case 'file_id' :
						$output = intval( $file_id );
						break;
					case 'size' :
					case 'sizeb' :
						if ( $size = @filesize( $file->path ) ) {
							if ( $options['show'] !== 'sizeb' ) {
								$units = 'BKMGTP';
								$power = floor( ( strlen( $size ) - 1 ) / 3 );
								if ( $power > strlen( $units ) - 1 ) {
									$power = strlen( $units ) - 1;
								}
								$output = sprintf(
									"<span class='size'>%.2f</span> <span class='unit'>%s</span>",
									$size / pow( 1024, $power ),
									@$units[$power] . 'B'
								);
							} else {
								$output = sprintf(
									"<span class='size'>%d</span> <span class='unit'>%s</span>",
									$size,
									'B'
								);
							}
						}
						break;
					default :
						$output = $file->name;
				}
				switch ( $options['filter'] ) {
					case '' :
					case 'none' :
						$output = stripslashes( $output );
						break;
					default :
						if ( function_exists( $options['filter'] ) ) {
							$output = call_user_func( $options['filter'], $output );
							$output = stripslashes( $output );
						}
				}

			}
		}
		return $output;
	}

	/**
	 * Allows to switch the default visibility setting for shortcodes handled by GFA_Shortcodes.
	 *
	 * @param array $atts attributes must specify the "visibility" with allowed values "always", "can_access"
	 * @param string $content not used
	 */
	public static function groups_file_visibility( $atts, $content = null ) {
		$options = shortcode_atts( array( "visibility" => self::$visibility ), $atts );
		switch( $options['visibility'] ) {
			case self::ALWAYS :
			case self::CAN_ACCESS :
				self::$visibility = $options['visibility'];
				break;
		}
	}

	/**
	 * Render the service key for the current user.
	 *
	 * @param array $atts
	 * @param string  $content not used
	 *
	 * @return string
	 */
	public static function groups_file_access_service_key( $atts, $content = null ) {
		$output = '';
		$user_id = get_current_user_id();
		if ( $user_id ) {
			$service_key = Groups_File_Access::get_service_key();
			if ( $service_key !== null ) {
				$output = $service_key;
			}
		}
		return $output;
	}
}
GFA_Shortcodes::init();
