<?php
/**
 * class-groups-newsletters-taxonomy.php
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
 * Newsletter taxonomy.
 */
class Groups_Newsletters_Taxonomy {

	/**
	 * Hooks.
	 */
	public static function init() {

		// Registers the newsletter taxonomy.
		add_action( 'init', array( __CLASS__, 'wp_init' ) );

		// Groups checkboxes when adding a new newsletter.
		// hook: do_action($taxonomy . '_add_form_fields', $taxonomy);
		add_action( 'newsletter_add_form_fields', array( __CLASS__, 'newsletter_add_form_fields' ) );

		// preview & send test email
		add_action( 'newsletter_pre_edit_form', array( __CLASS__, 'newsletter_pre_edit_form' ), 10, 2 );

		// Groups checkboxes when editing a newsletter.
		// hook: do_action($taxonomy . '_edit_form', $tag, $taxonomy);
		add_action( 'newsletter_edit_form', array( __CLASS__, 'newsletter_edit_form' ), 10, 2 );

		// Save groups for a new newsletter.
		// hook: add_action( 'edited_newsletter', array( __CLASS__, 'edited_newsletter' ), 10, 2 );
		add_action( 'created_newsletter', array( __CLASS__, 'created_newsletter' ), 10, 2 );

		// Save groups for a newsletter.
		// hook: do_action("edited_$taxonomy", $term_id, $tt_id);
		add_action( 'edited_newsletter', array( __CLASS__, 'edited_newsletter' ), 10, 2 );

		// Remove groups in options when newsletter is deleted.
		// hook: do_action( "delete_$taxonomy", $term, $tt_id, $deleted_term );
		add_action( 'delete_newsletter', array( __CLASS__, 'delete_newsletter' ), 10, 3 );

		// Remove group in options when group is deleted.
		add_action( 'groups_deleted_group', array( __CLASS__, 'groups_deleted_group' ) );

		// hook: $_sortable = apply_filters( "manage_{$this->screen->id}_sortable_columns", $this->get_sortable_columns() );
		add_filter( 'manage_edit-newsletter_sortable_columns', array( __CLASS__, 'manage_edit_newsletter_sortable_columns' ) );

		// Add the Groups column in the Newsletters overview.
		// hook: $column_headers[ $screen->id ] = apply_filters( 'manage_' . $screen->id . '_columns', array() );
		add_filter( 'manage_edit-newsletter_columns', array( __CLASS__, 'manage_edit_newsletter_columns' ) );

		// Render the group names in the Groups column.
		// hook: return apply_filters( "manage_{$this->screen->taxonomy}_custom_column", '', $column_name, $tag->term_id );
		add_filter( 'manage_newsletter_custom_column', array( __CLASS__, 'manage_newsletter_custom_column' ), 10, 3 );

		// Add a Newsletters column to the Stories table
		add_filter( 'manage_story_posts_columns', array( __CLASS__, 'manage_story_posts_columns' ) );

		// Render the newsletters in the Newsletters column for a story
		add_action( 'manage_story_posts_custom_column', array( __CLASS__, 'manage_story_posts_custom_column' ), 10, 2 );

	}

	/**
	 * Init hook.
	 */
	public static function wp_init() {
		// register taxonomies
		self::taxonomy();
		// intercept preview
		if ( isset( $_REQUEST['groups-newsletters-preview'] ) ) {
			self::preview();
		}
	}

	/**
	 * Registers the taxonomies.
	 */
	public static function taxonomy() {
		register_taxonomy(
			'newsletter',
			array( 'story', 'campaign' ),
			array(
				'hierarchical' => false,
				'labels'       => array(
					'name'              => _x( 'Newsletters', 'taxonomy general name', 'groups-newsletters' ),
					'singular_name'     => _x( 'Newsletter', 'taxonomy singular name', 'groups-newsletters' ),
					'search_items'      => __( 'Search Newsletters', 'groups-newsletters' ),
					'all_items'         => __( 'All Newsletters', 'groups-newsletters' ),
					'parent_item'       => __( 'Parent Newsletter', 'groups-newsletters' ),
					'parent_item_colon' => __( 'Parent Newsletter:', 'groups-newsletters' ),
					'edit_item'         => __( 'Edit Newsletter', 'groups-newsletters' ),
					'update_item'       => __( 'Update Newsletter', 'groups-newsletters' ),
					'add_new_item'      => __( 'Add New Newsletter', 'groups-newsletters' ),
					'new_item_name'     => __( 'New Newsletter Name', 'groups-newsletters' ),
					'menu_name'         => __( 'Newsletters', 'groups-newsletters' ),
					'separate_items_with_commas' => __( 'Assign one or more newsletters separated by commas. You can create a new newsletter on the fly, or start typing and select an existing one.', 'groups-newsletters' ),
					'choose_from_most_used'      => __( 'Choose from most used newsletters', 'groups-newsletters' ),
					'not_found'                  => __( 'No newsletters found.', 'groups-newsletters' )
				),
				'public'       => true,
				'query_var'    => true,
				'rewrite'      => array( 'slug' => 'newsletter' ),
				'show_in_rest' => true,
				'show_in_nav_menus' => true,
				'show_tagcloud'     => true,
				'show_ui'           => true,
				'capabilities' => array(
					'manage_terms' => 'manage_newsletters',
					'edit_terms'   => 'edit_newsletters',
					'delete_terms' => 'delete_newsletters',
					'assign_terms' => 'edit_stories'
				),
				'update_count_callback' => '_update_post_term_count',
				'show_admin_column' => false
			)
		);

		register_taxonomy(
			'story_tag',
			array( 'story' ),
			array(
				'hierarchical' => false,
				'labels'       => array(
					'name'          => _x( 'Newsletter Tags', 'taxonomy general name', 'groups-newsletters' ),
					'singular_name' => _x( 'Newsletter Tag', 'taxonomy singular name', 'groups-newsletters' ),
				),
				'query_var' => true,
				'rewrite' => array( 'slug' => 'story_tag' ),
				'public' => true,
				'show_in_rest' => true,
				'show_ui' => true,
				'show_admin_column' => true,
				'capabilities' => array(
					'manage_terms' => 'manage_newsletters',
					'edit_terms'   => 'edit_newsletters',
					'delete_terms' => 'delete_newsletters',
					'assign_terms' => 'edit_stories'
				),
				'update_count_callback' => '_update_post_term_count'
			)
		);

	}

	/**
	 * Groups checkboxes rendered before the Add New Newsletter button.
	 */
	public static function newsletter_add_form_fields( $taxonomy ) {
		if ( $taxonomy == 'newsletter' ) {
			self::groups_panel( $taxonomy );
		}
	}

	/**
	 * Pre-edit form
	 *
	 * @param object $tag
	 * @param object $taxonomy
	 */
	public static function newsletter_pre_edit_form( $tag, $taxonomy ) {
		self::preview_panel( $tag, $taxonomy );
		self::test_email_panel( $tag, $taxonomy );
	}

	/**
	 * Hook in wp-admin/edit-tag-form.php - add group info.
	 *
	 * @param object $tag
	 * @param object $taxonomy
	 */
	public static function newsletter_edit_form( $tag, $taxonomy ) {
		if ( $taxonomy == 'newsletter' ) {
			self::groups_panel( $tag, $taxonomy );
			self::panel_javascript( $tag, $taxonomy );
		}
	}

	/**
	 * Renders the groups meta box.
	 *
	 * @param object $tag
	 */
	public static function groups_panel( $tag = null ) {

		global $post, $wpdb;

		$subscribers            = Groups_Newsletters_Options::get_option( 'subscribers', array() );
		$newsletter_subscribers = isset( $tag->term_id ) && isset( $subscribers[$tag->term_id] ) ? $subscribers[$tag->term_id] : true;
		$terms                  = Groups_Newsletters_Options::get_option( 'terms', array() );
		$newsletter_groups      = isset( $tag->term_id ) && isset( $terms[$tag->term_id] ) ? $terms[$tag->term_id] : array();

		echo '<div style="border-top: 1px solid #fcfcfc; border-bottom: 1px solid #e0e0e0; margin-top: 1em;"></div>';

		echo '<div class="groups_panel">';

		echo '<h3>' . esc_html__( 'Recipients', 'groups-newsletters' ) . '</h3>';
		echo '<p>' . esc_html__( 'Select who should receive the newsletter. At this stage, no emails will be sent and you can also change your selection later.', 'groups-newsletters' ) . '</p>';

		echo '<h4>' . esc_html__( 'Subscribers', 'groups-newsletters' ) . '</h4>';
		echo '<p>' . esc_html__( 'Select if normal subscribers should receive the newsletter.', 'groups-newsletters' ) . '</p>';

		echo '<label>';
		echo '<input type="hidden" name="_subscribers_ui" value="1" />';
		echo sprintf(
			'<input type="checkbox" name="%s" %s />',
			'_subscribers',
			$newsletter_subscribers ? ' checked="checked" ' : ''
		);
		echo ' ';
		echo esc_html__( 'Newsletter subscribers', 'groups-newsletters' );
		echo '</label>';

		echo '<h4>' . esc_html__( 'Groups', 'groups-newsletters' ) . '</h4>';
		if ( GROUPS_NEWSLETTERS_GROUPS_IS_ACTIVE ) {
			echo '<p>' . esc_html__( 'Select the groups whose members should receive the newsletter.', 'groups-newsletters' ) . '</p>';

			$group_table = _groups_get_tablename( "group" );
			$groups = $wpdb->get_results( "SELECT * FROM $group_table ORDER BY name" );

			if ( count( $groups ) > 0 ) {
				echo '<ul>';
				foreach( $groups as $group ) {
					echo '<li>';
					echo '<label>';
					echo sprintf(
							'<input type="checkbox" name="%s" %s />',
							'_groups_groups-' . esc_attr( $group->group_id ),
							in_array( $group->group_id, $newsletter_groups ) ? ' checked="checked" ' : ''
					);
					echo ' ';
					echo stripslashes( wp_filter_nohtml_kses( $group->name ) );
					echo '</label>';
					echo '</li>';
				}
				echo '</ul>';
			}
		} else {
			echo '<p>' .
				wp_kses(
					__( 'The <a href="https://www.itthinx.com/plugins/groups/">Groups</a> plugin is not activated or missing. Install <em>Groups</em> to be able to select the groups whose members should receive the newsletter.', 'groups-newsletters' ),
					array(
						'a' => array( 'href' => array() ),
						'em' => array()
					)
				) .
				'</p>';
		}
		echo '</div>';
	}

	/**
	 * Renders a panel with button to get a preview of the newsletter.
	 *
	 * @param object $tag
	 */
	public static function preview_panel( $tag = null ) {
		if ( isset( $tag->term_id ) ) {
			$preview_url = add_query_arg(
				array(
					'groups-newsletters-preview' => 'true',
					'taxonomy' => 'newsletter',
					'term_id' => $tag->term_id,
					'preview-nonce' => wp_create_nonce( '_preview_newsletter' )
				),
				remove_query_arg( array( 'groups-newsletters-preview', 'taxonomy', 'term_id', 'preview-nonce' ) )
			);
			echo '<div id="preview_panel" class="preview_panel">';
			echo '<div style="border-top: 1px solid #fcfcfc; border-bottom: 1px solid #e0e0e0; margin-top: 1em;"></div>';
			echo '<h3>' . esc_html__( 'Preview', 'groups-newsletters' ) . '</h3>';
			echo '<p>';
			echo esc_html__( 'Click to preview the newsletter:', 'groups-newsletters' );
			echo ' ';
			printf( '<a href="%s" class="button" target="groups-newsletters-preview">%s</a>', esc_url( $preview_url ), esc_html__( 'Preview', 'groups-newsletters' ) );
			echo '</p>';
			echo '<p>';
			echo esc_html__( 'This will render the newsletter with all its stories, as it will be sent to the newsletter recipients.', 'groups-newsletters' );
			echo '</p>';
			echo '</div>';
		}
	}

	/**
	 * Renders a panel with a form to send the newsletter to a specific email address.
	 *
	 * @param object $tag
	 */
	public static function test_email_panel( $tag = null ) {
		if ( isset( $tag->term_id ) ) {
			// test email
			$email = '';
			if ( isset( $_POST['test_email_action'] ) && ( $_POST['test_email_action'] == 'send' ) && wp_verify_nonce( $_POST['groups-newsletters-test-email'], 'admin' ) ) {
				if ( !empty( $_POST['test_email'] ) ) {
					$email = wp_strip_all_tags( $_POST['test_email'] );
					if ( is_email( $email ) ) {
						require_once GROUPS_NEWSLETTERS_CORE_LIB . '/class-groups-newsletters-newsletter.php';
						$n = new Groups_Newsletters_Newsletter( $tag->term_id );
						if ( $n->send( $email ) ) {
							$message = '<div style="background-color:#efe;padding:1em">' .
								sprintf( __( 'The newsletter has been sent to %s.', 'groups-newsletters' ), esc_html( $email ) ) .
								'</div>';
						} else {
							$message = '<div style="background-color:#fee;padding:1em">' .
								sprintf( __( 'Failed to send the newsletter to %s.', 'groups-newsletters' ), esc_html( $email ) ) .
								'</div>';
						}
					} else {
						$message = '<div style="background-color:#ffe;padding:1em">' .
							sprintf( __( '%s is not a valid email address.', 'groups-newsletters' ), esc_html( $email ) ) .
							'</div>';
					}
				}
			}
			echo '<div id="test_email_panel" class="test_email_panel">';
			echo '<div style="border-top: 1px solid #fcfcfc; border-bottom: 1px solid #e0e0e0; margin-top: 1em;"></div>';
			echo '<h3>' . esc_html__( 'Send test email', 'groups-newsletters' ) . '</h3>';
			if ( isset( $message ) ) {
				echo $message;
			}
			echo '<form name="test_email" action="" method="post">';
			echo '<div>';
			echo '<label>';
			echo esc_html__( 'Email address:', 'groups-newsletters' );
			echo ' ';
			printf( '<input type="text" value="%s" name="test_email" />', esc_attr( $email ) );
			echo '</label>';
			echo ' ';
			printf( '<input class="button" type="submit" name="submit" value="%s" />', esc_attr__( 'Send', 'groups-newsletters' ) );
			echo '<input type="hidden" name="test_email_action" value="send" />';
			wp_nonce_field( 'admin', 'groups-newsletters-test-email', true, true );
			echo '</div>';
			echo '</form>';
			echo '<p>';
			echo esc_html__( 'You can send the newsletter to a specific email address to test it.', 'groups-newsletters' );
			echo '</p>';
			echo '</div>';
		}
	}

	/**
	 * Moves the preview and test email panels below the form.
	 * There's no other way to do it as there is no suitable action invoked
	 * below the form.
	 *
	 * @param object $tag
	 */
	public static function panel_javascript( $tag = null ) {
		if ( isset( $tag->term_id ) ) {
			echo '<script type="text/javascript">';
			echo 'var gn_preview_panel = document.getElementById("preview_panel");';
			echo 'var gn_test_email_panel = document.getElementById("test_email_panel");';
			echo 'var gn_edittag = document.getElementById("edittag");';
			echo 'if ((typeof gn_edittag !== "undefined")){';
			echo 'if ((typeof gn_preview_panel !== "undefined")){';
			echo 'gn_preview_panel.parentNode.removeChild(gn_preview_panel);';
			echo 'edittag.parentNode.appendChild(gn_preview_panel);';
			echo '}';
			echo 'if ((typeof gn_preview_panel !== "undefined")){';
			echo 'gn_test_email_panel.parentNode.removeChild(gn_test_email_panel);';
			echo 'edittag.parentNode.appendChild(gn_test_email_panel);';
			echo '}';
			echo '}';
			echo '</script>';
		}
	}

	/**
	 * Renders a preview of the newsletter.
	 */
	public static function preview() {
		$is_preview = isset( $_REQUEST['groups-newsletters-preview'] ) ? $_REQUEST['groups-newsletters-preview'] : null;
		if ( $is_preview == 'true' ) {
			$nonce = isset( $_REQUEST['preview-nonce'] ) ? $_REQUEST['preview-nonce'] : null;
			if ( wp_verify_nonce( $nonce, '_preview_newsletter' ) ) {
				$taxonomy = isset( $_REQUEST['taxonomy'] ) ? $_REQUEST['taxonomy'] : null;
				$term_id  = isset( $_REQUEST['term_id'] ) ? $_REQUEST['term_id'] : null;
				if ( $taxonomy == 'newsletter' && $term_id !== null ) {
					require_once GROUPS_NEWSLETTERS_CORE_LIB . '/class-groups-newsletters-newsletter.php';
					$n = new Groups_Newsletters_Newsletter( $term_id );
					echo $n->get_content();
					die;
				}
			}
		}
	}

	/**
	 * Save newsletter groups for new newsletter.
	 *
	 * @param int $term_id
	 * @param int $tt_id
	 */
	public static function created_newsletter( $term_id, $tt_id ) {
		self::edited_newsletter( $term_id, $tt_id );
	}

	/**
	 * Save newsletter groups.
	 *
	 * @param int $term_id of the newsletter
	 * @param int $tt_id taxonomy id
	 */
	public static function edited_newsletter( $term_id, $tt_id ) {

		global $wpdb;

		if ( term_exists( $term_id, 'newsletter' ) ) {

			$subscribers = Groups_Newsletters_Options::get_option( 'subscribers', array() );
			$subscribers[$term_id] = isset( $_POST['_subscribers_ui'] ) ? !empty( $_POST['_subscribers'] ) : true; // add subscribers by default when it's not through the groups_panel
			Groups_Newsletters_Options::update_option( 'subscribers', $subscribers );

			if ( GROUPS_NEWSLETTERS_GROUPS_IS_ACTIVE ) {
				$terms = Groups_Newsletters_Options::get_option( 'terms', array() );

				// refresh groups, clear all, then assign checked
				$terms[$term_id] = array();

				// iterate over groups
				$group_table = _groups_get_tablename( 'group' );
				$groups = $wpdb->get_results( "SELECT group_id FROM $group_table" );
				if ( count( $groups ) > 0 ) {
					foreach ( $groups as $group ) {
						if ( !empty( $_POST['_groups_groups-'. $group->group_id] ) ) {
							$terms[$term_id][] = $group->group_id;
						}
					}
				}
				Groups_Newsletters_Options::update_option( 'terms', $terms );
			}
		}
	}

	/**
	 * Remove groups for newsletter.
	 *
	 * @param int $term_id
	 * @param int $tt_id taxonomy
	 * @param object $deleted_term
	 */
	public static function delete_newsletter( $term_id, $tt_id, $deleted_term ) {
		$subscribers = Groups_Newsletters_Options::get_option( 'subscribers', array() );
		unset( $subscribers[$term_id] );
		Groups_Newsletters_Options::update_option( 'subscribers', $subscribers );

		$terms = Groups_Newsletters_Options::get_option( 'terms', array() );
		unset( $terms[$term_id] );
		Groups_Newsletters_Options::update_option( 'terms', $terms );
	}

	/**
	 * Remove deleted group related to newsletters.
	 *
	 * @param int $group_id
	 */
	public static function groups_deleted_group( $group_id ) {
		$terms = Groups_Newsletters_Options::get_option( 'terms', array() );
		if ( count( $terms ) > 0 ) {
			foreach ( $terms as $term_id => $group_ids ) {
				foreach ( $group_ids as $key => $gid ) {
					if ( $group_id == $gid ) {
						unset( $group_ids[$key] );
					}
				}
				$terms[$term_id] = $group_ids;
			}
			Groups_Newsletters_Options::update_option( 'terms', $terms );
		}
	}

	/**
	 * Sortable columns.
	 *
	 * @param array $columns
	 */
	public static function manage_edit_newsletter_sortable_columns( $columns ) {
		// @todo it would be nice to be able to sort by number of stories or campaigns, but the way the table is built makes it hard ... see below [1]
// 		$columns['stories']   = 'stories';
// 		$columns['campaigns'] = 'campaigns';
		return $columns;
	}

	/**
	 * Adds columns.
	 *
	 * @param array $columns
	 * @return array
	 * @see http://core.trac.wordpress.org/ticket/19031
	 * @see http://core.trac.wordpress.org/ticket/14084
	 */
	public static function manage_edit_newsletter_columns( $columns ) {

		// see http://core.trac.wordpress.org/ticket/19031
		unset( $columns['posts'] ); // WP 3.6-beta3 this would show counts for both stories AND campaigns, under a column labeled 'Stories'
		$columns['stories']     = __( 'Stories', 'groups-newsletters' );
		$columns['campaigns']   = __( 'Campaigns', 'groups-newsletters' );

		$columns['subscribers'] = __( 'Subscribers', 'groups-newsletters' );
		if ( GROUPS_NEWSLETTERS_GROUPS_IS_ACTIVE ) {
			$columns['groups']      = __( 'Groups', 'groups-newsletters' );
		}

		return $columns;
	}

	/**
	 * Render groups for a newsletter.
	 *
	 * @param string $content
	 * @param string $column_name
	 * @param int $term_id
	 * @return string
	 */
	public static function manage_newsletter_custom_column( $content, $column_name, $term_id ) {
		global $wpdb;
		switch ( $column_name ) {
			case 'stories' :
				$story_ids = get_posts(
					array(
						'fields'      => 'ids',
						'numberposts' => -1,
						'post_type'   => 'story',
						'tax_query'  => array(
							array(
								'taxonomy' => 'newsletter',
								'field'    => 'id',
								'terms'    => $term_id
							)
						)
					)
				);
				if ( $story_ids ) {
					$n = count( $story_ids );
					if ( $term = get_term_by( 'id', $term_id, 'newsletter' ) ) {
						$url = add_query_arg( 'post_type', 'story', add_query_arg( 'newsletter', $term->slug, admin_url( 'edit.php' ) ) );
						$content .= sprintf( '<a href="%s"><span class="count">%d</span></a>', $url, intval( $n ) );
					}
				}
				break;
			case 'campaigns' :
				$ids = get_posts(
					array(
						'fields'      => 'ids',
						'numberposts' => -1,
						'post_type'   => 'campaign',
						'tax_query'   => array(
							array(
								'taxonomy' => 'newsletter',
								'field'    => 'id',
								'terms'    => $term_id
							)
						)
					)
				);
				if ( $ids ) {
					$n = count( $ids );
					if ( $term = get_term_by( 'id', $term_id, 'newsletter' ) ) {
						$url = add_query_arg( 'post_type', 'campaign', add_query_arg( 'newsletter', $term->slug, admin_url( 'edit.php' ) ) );
						$content .= sprintf( '<a href="%s"><span class="count">%d</span></a>', $url, intval( $n ) );
					}
				}
				break;
			case 'subscribers' :
				$subscribers = Groups_Newsletters_Options::get_option( 'subscribers', array() );
				if ( isset( $subscribers[$term_id] ) && $subscribers[$term_id] ) {
					$content .= __( 'Yes', 'groups-newsletters' );
				} else {
					$content .= __( '-', 'groups-newsletters' );
				}
				break;
			case 'groups' :
				$content .= self::get_group_list_html( $term_id );
				break;
		}
		return $content;
	}

	/**
	 * Add the newsletters column to the stories overview.
	 *
	 * @param array $posts_columns
	 * @return array
	 */
	public static function manage_story_posts_columns( $posts_columns ) {
		$posts_columns['newsletters'] = __( 'Newsletters', 'groups-newsletters' );
		return $posts_columns;
	}

	/**
	 * Render newsletter names in stories overview.
	 *
	 * @param string $column_name
	 * @param int $post_id
	 */
	public static function manage_story_posts_custom_column( $column_name, $post_id ) {
		if ( $column_name == 'newsletters' ) {
			$terms = wp_get_post_terms( $post_id, 'newsletter' );
			$entries = array();
			foreach ( $terms as $term ) {
				$args = array();
				$args['action'] = 'edit';
				$args['tag_ID'] = $term->term_id;
				$args['post_type'] = 'story';
				$args['taxonomy'] = 'newsletter';
				$entries[] = sprintf( '<a href="%s">%s</a>',
					esc_url( add_query_arg( $args, 'edit-tags.php' ) ),
					esc_html( sanitize_term_field( 'name', $term->name, $term->term_id, 'newsletter', 'display' ) )
				);
			}
			echo implode( ' ', $entries );
		}
	}

	/**
	 * Render the list of groups for the given newsletter (taxonomy term).
	 *
	 * @param int $term_id newsletter term id
	 * @return string HTML
	 */
	private static function get_group_list_html( $term_id ) {
		$content = '';
		$terms = Groups_Newsletters_Options::get_option( 'terms', array() );
		$groups = isset( $terms[$term_id] ) ? $terms[$term_id] : array();
		if ( count( $groups ) > 0 ) {
			$content .= '<ul>';
			foreach ( $groups as $group_id ) {
				if ( $group = Groups_Group::read( $group_id ) ) {
					$content .= '<li>';
					$content .= wp_filter_nohtml_kses( $group->name );
					$content .= '</li>';
				}
			}
			$content .= '</ul>';
		}
		return $content;
	}

}
Groups_Newsletters_Taxonomy::init();
