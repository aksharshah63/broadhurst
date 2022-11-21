<?php
/**
 * class-groups-newsletters-story-post-type.php
 *
 * Copyright (c) www.itthinx.com
 *
 * This code is provided subject to the license granted.
 * Unauthorized use and distribution is prohibited.
 * See COPYRIGHT.txt and LICENSE.txt
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This header and all notices must be kept intact.
 *
 * @author Karim Rahimpur
 * @package groups-newsletters
 * @since groups-newsletters 1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Story post type.
 */
class Groups_Newsletters_Story_Post_Type {

	/**
	 * Hooks.
	 */
	public static function init() {

		add_action( 'init', array( __CLASS__, 'wp_init' ) );

		// uncomment when needed
		//add_action( 'save_post', array( __CLASS__, 'save_post' ), 10, 2 );

		// Here we can stick in our menu_order DESC:
		add_filter( 'posts_orderby', array( __CLASS__, 'posts_orderby' ), 10, 2 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'use_block_editor_for_post_type' ), 10, 2 );

	}

	/**
	 * Determines whether the block editor is used for our post type.
	 *
	 * @param boolean $use
	 * @param string $post_type
	 *
	 * @return boolean
	 */
	public static function use_block_editor_for_post_type( $use, $post_type ) {
		return Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_STORY_USE_BLOCK_EDITOR, true );
	}

	/**
	 * Render mini-howto for sticky stories.
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			'rank',
			__( 'Order', 'groups-newsletters' ),
			array( __CLASS__, 'rank' ),
			'story',
			'side'
		);
	}

	/**
	 * Stories of higher order go first using menu_order.
	 *
	 * @param string $orderby
	 * @param WP_Query $query
	 * @return string modified $orderby
	 */
	public static function posts_orderby( $orderby, $query ) {

		global $wpdb;

		// limit to newsletter queries
		if ( isset( $query->query_vars['taxonomy'] ) && ( $query->query_vars['taxonomy'] == 'newsletter' ) ) {
			if ( strpos( $orderby, 'menu_order' ) === false ) {
				$menu_order = "$wpdb->posts.menu_order DESC";
				if ( strlen( $orderby ) > 0 ) {
					$menu_order .= ',';
				}
				$orderby = $menu_order . $orderby;
			}
		}
		return $orderby;
	}

	/**
	 * Renders info about how to make a story sticky.
	 */
	public static function rank() {
		echo wp_kses(
			__( 'Stories can be forced to appear before others when given an order. Indicate a value greater than 0 in the <strong>Order</strong> field (under <em>Attributes</em>). Stories of higher order appear first in newsletters.', 'groups-newsletters' ),
			array(
				'strong' => array(),
				'em'     => array()
			)
		);
	}

	/**
	 * Register post type and taxonomy.
	 */
	public static function wp_init() {
		self::post_type();
		if ( Groups_Newsletters_Options::get_option( 'exclude-from-comments', false ) ) {
			add_filter( 'comments_clauses', array( __CLASS__, 'comments_clauses' ), 10, 2 );
		}
	}

	/**
	 * Register story post type.
	 */
	public static function post_type() {
		register_post_type(
			'story',
			array(
				'labels' => array(
					'name'               => __( 'Stories', 'groups-newsletters' ),
					'singular_name'      => __( 'Story', 'groups-newsletters' ),
					'all_items'          => __( 'Stories', 'groups-newsletters' ),
					'add_new'            => __( 'New Story', 'groups-newsletters' ),
					'add_new_item'       => __( 'Add New Story', 'groups-newsletters' ),
					'edit'               => __( 'Edit', 'groups-newsletters' ),
					'edit_item'          => __( 'Edit Story', 'groups-newsletters' ),
					'new_item'           => __( 'New Story', 'groups-newsletters' ),
					'not_found'          => __( 'No Stories found', 'groups-newsletters' ),
					'not_found_in_trash' => __( 'No Stories found in trash', 'groups-newsletters' ),
					'parent'             => __( 'Parent Story', 'groups-newsletters' ),
					'search_items'       => __( 'Search Stories', 'groups-newsletters' ),
					'view'               => __( 'View Story', 'groups-newsletters' ),
					'view_item'          => __( 'View Story', 'groups-newsletters' ),
					'menu_name'          => __( 'Newsletters', 'groups-newsletters' )
				),
				'capability_type'     => array( 'story', 'stories' ),
				'description'         => __( 'Newsletter story.', 'groups-newsletters' ),
				'exclude_from_search' => Groups_Newsletters_Options::get_option( 'exclude-from-search', false ),
				'has_archive'         => true,
				'hierarchical'        => false,
				'map_meta_cap'        => true,
// 				'menu_position'       => 10,
				'menu_icon'           => GROUPS_NEWSLETTERS_PLUGIN_URL . '/images/groups-newsletters.png',
				'public'              => true,
				'publicly_queryable'  => true,
				'query_var'           => true,
				'rewrite'             => true,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => true,
				'show_ui'             => true,
				'supports'            => array(
					'author',
					'comments',
					'editor',
					'title',
					'revisions',
					'page-attributes'
				),
				'taxonomies' => array( 'newsletter', 'story_tag' )
			)
		);
		self::create_capabilities();
	}

	/**
	 * Creates needed capabilities to handle stories.
	 * This makes sure that these capabilities are present (will recreate them
	 * if deleted).
	 */
	public static function create_capabilities() {
		global $wp_roles;
		if ( $admin = $wp_roles->get_role( 'administrator' ) ) {
			$caps = self::get_capabilities();
			foreach ( $caps as $key => $capability ) {
				// assure admins have all caps
				if ( !$admin->has_cap( $capability ) ) {
					$admin->add_cap( $capability );
				}
				if ( GROUPS_NEWSLETTERS_GROUPS_IS_ACTIVE ) {
					// assure the capabilities exist
					if ( !Groups_Capability::read_by_capability( $capability ) ) {
						Groups_Capability::create( array( 'capability' => $capability ) );
					}
				}
			}
		}
	}

	/**
	 * Returns an array of relevant capabilities for the story post type.
	 *
	 * @return array
	 */
	public static function get_capabilities() {

		// The following capabilities are those obtained for the custom post type.
		// global $wp_post_types;
		// if ( isset( $wp_post_types['story'] ) && isset( $wp_post_types['story']->cap ) ) {
		//	$cap = $wp_post_types['story']->cap;
		// }

		// We only need a sensible subset:
		return array(

			// for the custom post type:

			//'edit_post'              => 'edit_story',
			//'read_post'              => 'read_story',
			//'delete_post'            => 'delete_story',
			'edit_posts'             => 'edit_stories',
			'edit_others_posts'      => 'edit_others_stories',
			'publish_posts'          => 'publish_stories',
			'read_private_posts'     => 'read_private_stories',
			//'read'                   => 'read',
			'delete_posts'           => 'delete_stories',
			'delete_private_posts'   => 'delete_private_stories',
			'delete_published_posts' => 'delete_published_stories',
			'delete_others_posts'    => 'delete_others_stories',
			'edit_private_posts'     => 'edit_private_stories',
			'edit_published_posts'   => 'edit_published_stories',
			//'create_posts'           => 'edit_stories',

			// for the taxonomies:

			'manage_terms' => 'manage_newsletters',
			'edit_terms'   => 'edit_newsletters',
			'delete_terms' => 'delete_newsletters',
			'assign_terms' => 'edit_stories'
		);
	}

	/**
	 * Filter out comments on stories for comment queries that do not specify
	 * their post_type directly.
	 *
	 * @param array $pieces
	 * @param WP_Comment_Query $wp_comment_query
	 * @return array
	 */
	public static function comments_clauses( $pieces, $wp_comment_query ) {
		global $wpdb;
		if ( !$pieces['join'] ) {
			$pieces['join'] = "JOIN $wpdb->posts ON $wpdb->posts.ID = $wpdb->comments.comment_post_ID";
		}
		if ( !$wp_comment_query->query_vars['post_type' ] ) {
			$pieces['where'] .= $wpdb->prepare( " AND {$wpdb->posts}.post_type != %s", 'story' );
		}
		return $pieces;
	}

	/**
	 * Process data for post being saved.
	 * Currently not used.
	 *
	 * @param int $post_id
	 * @param object $post
	 */
	public static function save_post( $post_id = null, $post = null) {
		if ( ! ( ( defined( "DOING_AUTOSAVE" ) && DOING_AUTOSAVE ) ) ) {
			if ( $post->post_type == 'story' ) {
// 				$foo = isset( $_POST['foo'] ) ? check_foo( $_POST['foo'] : null;
// 				update_post_meta( $post_id, '_foo', $foo );

			}
		}
	}

}
Groups_Newsletters_Story_Post_Type::init();
