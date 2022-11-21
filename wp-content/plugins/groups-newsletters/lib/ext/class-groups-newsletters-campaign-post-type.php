<?php
/**
 * class-groups-newsletters-campaign-post-type.php
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
 * Campaign post type.
 *
 * Campaigns and how they relate to Newsletters:
 *
 * Campaigns can be used to send one or more newsletters. The target groups are
 * related to the newsletters, which allows to send newsletters to different
 * audiences through the same campaign.
 *
 * Running campaigns:
 *
 * Campaigns start in a pending status. Even when published, a campaign must
 * still be run in order to start sending out emails. This, among other
 * benefits, avoids people sending out emails to the world without realising
 * it. Once a campaign is running, emails are sent. This process can be paused
 * by putting the campaign on hold. This can be useful to give priority to
 * other more important campaigns or to lighten the burden put on the email
 * server due to temporary circumstances. A campaign which has been put on hold
 * can be continued to send out remaining emails. The process can be repeated
 * as long as the campaign has remaining recipients to whom email haven't been
 * sent yet. Once all emails have been sent, the campaign's status changes
 * automatically to executed.
 */
class Groups_Newsletters_Campaign_Post_Type {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'wp_init' ) );
		add_filter( 'post_updated_messages', array( __CLASS__, 'post_updated_messages' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 10, 2 );
		add_action( 'save_post', array( __CLASS__, 'save_post' ), 10, 2 );
		add_filter( 'manage_campaign_posts_columns', array( __CLASS__, 'manage_campaign_posts_columns' ) );
		add_filter( 'manage_edit-campaign_sortable_columns', array( __CLASS__, 'manage_campaign_posts_sortable_columns' ) );
		add_action( 'manage_campaign_posts_custom_column', array( __CLASS__, 'manage_campaign_posts_custom_column' ), 10, 2 );
	}

	/**
	 * Add the newsletters column to the stories overview.
	 *
	 * @param array $posts_columns
	 * @return array
	 */
	public static function manage_campaign_posts_columns( $posts_columns ) {
		$posts_columns['campaign_status'] = __( 'Status', 'groups-newsletters' );
		$posts_columns['newsletters']     = __( 'Newsletters', 'groups-newsletters' );
		return $posts_columns;
	}

	/**
	 * Adds sortable columns.
	 *
	 * @param array $posts_columns
	 * @return array
	 */
	public static function manage_campaign_posts_sortable_columns( $posts_columns ) {
		$posts_columns['campaign_status'] = 'campaign_status';
		return $posts_columns;
	}

	/**
	 * Render additional columns for campaigns.
	 *
	 * @param string $column_name
	 * @param int $post_id
	 */
	public static function manage_campaign_posts_custom_column( $column_name, $post_id ) {

		switch ( $column_name ) {

			case 'campaign_status' :
				$current_status = self::get_status( $post_id );
				echo esc_html( self::get_status_name( $current_status ) );
				if ( $current_status == 'on-hold' | $current_status == 'running' ) {
					$queued = get_post_meta( $post_id, 'campaign_queued', true );
					if ( $queued == 'yes' ) {
						echo ' ' . esc_html__( '(queued)', 'groups-newsletters' );
						global $wpdb;
						$queue_table = Groups_Newsletters_Controller::get_tablename( 'queue' );
						$pending_count = $wpdb->get_var( $wpdb->prepare(
							"SELECT COUNT(*) FROM $queue_table WHERE campaign_id = %d AND status = %d",
							intval( $post_id ),
							0
						) );
						$total_emails = get_post_meta( $post_id, 'campaign_total_emails', true );
						echo ' ';
						printf( esc_html__( '%d / %d', 'groups-newsletters' ), esc_attr( intval( $total_emails ) - intval( $pending_count ) ), esc_attr( intval( $total_emails ) ) );

					} else {
						echo ' ' . esc_html__( '(waiting)', 'groups-newsletters' );
					}
				}
				break;

			case 'newsletters' :
				$newsletters = array();
				$terms = wp_get_post_terms( $post_id, 'newsletter' );
				foreach ( $terms as $term ) {
					$args = array();
					$args['action'] = 'edit';
					$args['tag_ID'] = $term->term_id;
					$args['post_type'] = 'story';
					$args['taxonomy'] = 'newsletter';
					$newsletters[] = sprintf(
						'<a href="%s">%s</a>',
						esc_url(
							add_query_arg(
								$args,
								'edit-tags.php'
							)
						),
						esc_html( sanitize_term_field( 'name', $term->name, $term->term_id, 'newsletter', 'display' ) )
					);
				}
				if ( count( $newsletters ) > 0 ) {
					echo implode( ', ', $newsletters ); // @codingStandardsIgnoreLine
				}
				break;

		}
	}

	/**
	 * Overrides default messages for the campaign CPT.
	 *
	 * @param array $messages
	 */
	public static function post_updated_messages( $messages ) {
		global $post, $post_ID;
		$messages['campaign'] = array(
			0 => '',
			1 => __( 'Campaign updated.', 'groups-newsletters' ),
			2 => __( 'Custom field updated.', 'groups-newsletters' ),
			3 => __( 'Custom field deleted.', 'groups-newsletters' ),
			4 => __( 'Campaign updated.', 'groups-newsletters' ),
			5 => isset( $_GET['revision'] ) ? sprintf( __( 'Campaign restored to revision from %s', 'groups-newsletters' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => __( 'Campaign updated.', 'groups-newsletters' ),
			7 => __( 'Campaign saved.', 'groups-newsletters' ),
			8 => __( 'Campaign submitted.', 'groups-newsletters' ),
			9 => sprintf(
				__( 'Campaign scheduled for: <strong>%1$s</strong>.', 'groups-newsletters' ),
				date_i18n( __( 'M j, Y @ G:i', 'groups-newsletters' ), strtotime( $post->post_date ) )
			),
			10 => __( 'Campaign draft updated.', 'groups-newsletters' )
		);
		return $messages;
	}

	/**
	 * Register post type and taxonomy.
	 */
	public static function wp_init() {
		self::post_type();
	}

	/**
	 * Register the campaign post type.
	 */
	public static function post_type() {
		register_post_type(
			'campaign',
			array(
				'labels' => array(
					'name'               => __( 'Campaigns', 'groups-newsletters' ),
					'singular_name'      => __( 'Campaign', 'groups-newsletters' ),
					'all_items'          => __( 'Campaigns', 'groups-newsletters' ),
					'add_new'            => __( 'New Campaign', 'groups-newsletters' ),
					'add_new_item'       => __( 'Add New Campaign', 'groups-newsletters' ),
					'edit'               => __( 'Edit', 'groups-newsletters' ),
					'edit_item'          => __( 'Edit Campaign', 'groups-newsletters' ),
					'new_item'           => __( 'New Campaign', 'groups-newsletters' ),
					'not_found'          => __( 'No Campaigns found', 'groups-newsletters' ),
					'not_found_in_trash' => __( 'No Campaigns found in trash', 'groups-newsletters' ),
					'parent'             => __( 'Parent Campaign', 'groups-newsletters' ),
					'search_items'       => __( 'Search Campaigns', 'groups-newsletters' ),
					'view'               => __( 'View Campaign', 'groups-newsletters' ),
					'view_item'          => __( 'View Campaign', 'groups-newsletters' ),
					'menu_name'          => __( 'Campaigns', 'groups-newsletters' )
				),
				'capability_type'     => array( 'campaign', 'campaigns' ),
				'description'         => __( 'Newsletter campaign.', 'groups-newsletters' ),
				'exclude_from_search' => true,
				'has_archive'         => false,
				'hierarchical'        => false,
				'map_meta_cap'        => true,
// 				'menu_position'       => 10,
// 				'menu_icon'           => GROUPS_NEWSLETTERS_PLUGIN_URL . '/images/groups-newsletters.png',
				'public'              => false,
				'publicly_queryable'  => false,
				'query_var'           => true,
				'rewrite'             => false,
				'show_in_nav_menus'   => false,
				'show_ui'             => true,
				'supports'            => array( 'title' ),
				'taxonomies'          => array( 'newsletter' ), // the newsletter(s) that this campaign will distribute
				'show_in_menu'        => 'edit.php?post_type=story' // an item in the newsletters menu
			)
		);
		self::create_capabilities();
	}

	/**
	 * Creates needed capabilities to handle campaigns.
	 * This makes sure that these capabilities are present
	 * and will recreate them if they were deleted.
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
	 * Returns an array of relevant capabilities for the campaign post type.
	 *
	 * @return array
	 */
	public static function get_capabilities() {
		return array(
			'edit_posts'             => 'edit_campaigns',
			'edit_others_posts'      => 'edit_others_campaigns',
			'publish_posts'          => 'publish_campaigns',
			'read_private_posts'     => 'read_private_campaigns',
			'delete_posts'           => 'delete_campaigns',
			'delete_private_posts'   => 'delete_private_campaigns',
			'delete_published_posts' => 'delete_published_campaigns',
			'delete_others_posts'    => 'delete_others_campaigns',
			'edit_private_posts'     => 'edit_private_campaigns',
			'edit_published_posts'   => 'edit_published_campaigns',
		);
	}

	/**
	 * Adds and removes meta boxes.
	 *
	 * @param string $post_type
	 * @param object $post
	 */
	public static function add_meta_boxes( $post_type, $post ) {
		if ( $post_type == 'campaign' ) {

			remove_meta_box( 'submitdiv', 'campaign', 'side' );
			add_meta_box(
				'submitdiv',
				__( 'Save', 'groups-newsletters' ),
				array( __CLASS__, 'save_campaign_meta_box' ),
				'campaign',
				'side',
				'high'
			);

			remove_meta_box( 'tagsdiv-newsletter', 'campaign', 'side' );
			add_meta_box(
				'tagsdiv-newsletter',
				__( 'Newsletters', 'groups-newsletters' ),
				array( __CLASS__, 'post_tags_meta_box' ),
				'campaign',
				'normal',
				'high',
				array( 'taxonomy' => 'newsletter' )
			);

			add_meta_box(
				'status',
				__( 'Status', 'groups-newsletters' ),
				array( __CLASS__, 'status_meta_box' ),
				'campaign',
				'normal',
				'high'
			);
		}
	}

	/**
	 * Renders the status meta box.
	 *
	 * @param object $post campaign
	 */
	public static function status_meta_box( $post ) {
		$output = '';

		$current_status = self::get_status( $post->ID );

		$output .= '<p>';
		if ( $current_status != 'executed' ) {
			$output .= sprintf( esc_html__( 'This campaign is currently %s.', 'groups-newsletters' ), esc_html( self::get_status_name( $current_status ) ) );
		} else {
			$output .= wp_kses(
				esc_html__( 'This campaign has been executed.', 'groups-newsletters' ),
				array( 'strong' => array() )
			);
		}
		$output .= '</p>';

		$newsletter_terms = wp_get_object_terms( $post->ID, 'newsletter' );
		if ( count( $newsletter_terms ) > 0 ) {

			// show info on how many emails in total, how many need to be sent yet
			if ( $current_status == 'on-hold' || $current_status == 'running' ) {
				$queued = get_post_meta( $post->ID, 'campaign_queued', true );
				if ( $queued == 'yes' ) {
					$total_emails = get_post_meta( $post->ID, 'campaign_total_emails', true );
					if ( $total_emails ) {

						global $wpdb;
						$queue_table = Groups_Newsletters_Controller::get_tablename( 'queue' );
						$pending_count = $wpdb->get_var( $wpdb->prepare(
							"SELECT COUNT(*) FROM $queue_table WHERE campaign_id = %d AND status = %d",
							intval( $post->ID ),
							0
						) );

						$output .= '<p>';
						$pending = intval( $pending_count );
						$total   = intval( $total_emails );
						$sent    = $total - $pending;
						$output .= sprintf(
							_n( '1 email of %2$d in this campaign has been sent.', '%1$d emails of %2$d in this campaign have been sent.', esc_html( $sent ), 'groups-newsletters' ),
							esc_html( $sent ),
							esc_html( $total )
						);
						$output .= '</p>';

						$output .= '<p>';
						$output .= sprintf(
							_n( '1 is in the queue.', '%d are in the queue.', esc_html( $pending ), 'groups-newsletters' ),
							esc_html( $pending )
						);
						$output .= '</p>';
					}
				} else {
					$output .= '<p>';
					$output .= esc_html__( 'This campaign is waiting to be queued.', 'groups-newsletters' );
					$output .= '</p>';
				}
			}

			$output .= '<p>';
			if ( $current_status == 'pending' ) {
				$output .= '<input type="hidden" name="campaign_action" value="run" />';
				$output .= sprintf( '<input type="submit" class="button run button-primary" name="do_campaign_action" value="%s" title="%s" />', esc_attr__( 'Run', 'groups-newsletters' ), esc_attr__( 'Run the campaign', 'groups-newsletters' ) );
				$output .= ' ';
				switch ( $post->post_status ) {
					case 'publish' :
						$output .= esc_html__( 'Running the campaign now will start sending out emails to the newsletter recipients immediately.', 'groups-newsletters' );
						break;
					case 'future' :
						$output .= esc_html__( 'Running the campaign will start sending out emails to the newsletter recipients at the scheduled date and time.', 'groups-newsletters' );
						break;
					default :
						$output .= esc_html__( 'Running the campaign now will start sending out emails to the newsletter recipients once the campaign is published.', 'groups-newsletters' );
				}
			}
			if ( $current_status == 'on-hold' ) {
				$output .= '<input type="hidden" name="campaign_action" value="continue" />';
				$output .= sprintf( '<input type="submit" class="button continue button-primary" name="do_campaign_action" value="%s" title="%s" />', esc_attr__( 'Continue', 'groups-newsletters' ), esc_attr__( 'Continue the campaign', 'groups-newsletters' ) );
				$output .= ' ';
				switch ( $post->post_status ) {
					case 'publish' :
						$output .= esc_html__( 'Continue sending out emails to the newsletter recipients immediately.', 'groups-newsletters' );
						break;
					case 'future' :
						$output .= esc_html__( 'Continue sending out emails to the newsletter recipients at the scheduled date and time.', 'groups-newsletters' );
						break;
					default :
						$output .= esc_html__( 'Continue sending out emails to the newsletter recipients once the campaign is published.', 'groups-newsletters' );
				}
			}
			if ( $current_status == 'running' ) {
				$output .= '<input type="hidden" name="campaign_action" value="hold" />';
				$output .= sprintf( '<input type="submit" class="button hold button-primary" name="do_campaign_action" value="%s" title="%s" />', esc_attr__( 'Hold', 'groups-newsletters' ), esc_attr__( 'Hold the campaign', 'groups-newsletters' ) );
				$output .= ' ';
				$output .= esc_html__( 'While the campaign is on hold, no emails will be sent to newsletter recipients.', 'groups-newsletters' );
			}
			$output .= '</p>';
		} else {
			$output .= '<p>';
			$output .= esc_html__( 'A campaign must have at least one newsletter assigned.', 'groups-newsletters' );
			$output .= '</p>';
			$output .= '<p>';
			$output .= esc_html__( 'Once you have assigned a newsletter to the campaign, it can be run to send out emails.', 'groups-newsletters' );
			$output .= '</p>';
			$output .= '<p>';
			$output .= wp_kses(
				__( 'Use the <em>Newsletters</em> box to choose one or more newsletters, then update the campaign.', 'groups-newsletters' ),
				array( 'em' => array() )
			);
			$output .= '</p>';
		}

		// @codingStandardsIgnoreStart
		echo $output;
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Customized meta box, allows to add newsletters while the campaign is pending.
	 *
	 * @param object $post
	 * @param array $box
	 */
	public static function post_tags_meta_box( $post, $box ) {
		$status = self::get_status( $post->ID );
		if ( $status == 'pending' ) {
			echo '<p>';
			esc_html_e( 'Assign one or more newsletters that will be sent with this campaign.', 'groups-newsletters' );
			echo '</p>';
			echo '<p>';
			// translators: %1$s and %2$s are placeholders for opening and closing HTML elements
			echo sprintf( esc_html__( 'Newsletters can only be assigned to a %1$spending%2$s campaign.', 'groups-newsletters' ), '<em>', '</em>' );
			echo '</p>';
			echo '<p>';
			// translators: %1$s is an opening em tag, %2$s is a closing em tag, %3$s is an opening em tag, %4$s is a closing em tag
			echo sprintf( esc_html__( 'Once the campaign is %1$srunning%2$s or %3$son hold%4$s, you cannot assign additional newsletters.', 'groups-newsletters' ), '<em>', '</em>', '<em>', '</em>' );
			echo '</p>';

			$defaults = array( 'taxonomy' => 'post_tag' );
			if ( !isset( $box['args'] ) || !is_array( $box['args'] ) ) {
				$args = array();
			} else {
				$args = $box['args'];
			}
			extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
			$tax_name = esc_attr( $taxonomy );
			$taxonomy = get_taxonomy( $taxonomy );
			$user_can_assign_terms = current_user_can( $taxonomy->cap->assign_terms );
			$comma = esc_html_x( ',', 'tag delimiter' );
			?>
			<div class="tagsdiv" id="<?php echo $tax_name; ?>">
				<div class="jaxtag">
					<div class="nojs-tags hide-if-js">
					<p><?php echo $taxonomy->labels->add_or_remove_items; ?></p>
					<textarea name="<?php echo "tax_input[$tax_name]"; ?>" rows="3" cols="20" class="the-tags" id="tax-input-<?php echo esc_attr( $tax_name ); ?>" <?php disabled( ! $user_can_assign_terms ); ?>><?php echo str_replace( ',', $comma . ' ', get_terms_to_edit( $post->ID, $tax_name ) ); // textarea_escaped by esc_attr() ?></textarea></div>
			 		<?php if ( $user_can_assign_terms ) : ?>
						<div class="ajaxtag hide-if-no-js">
							<label class="screen-reader-text" for="new-tag-<?php echo esc_attr( $tax_name ); ?>"><?php echo esc_attr( $box['title'] ); ?></label>
							<div class="taghint"><?php echo $taxonomy->labels->add_new_item; ?></div>
							<p><input data-wp-taxonomy="<?php echo $tax_name; ?>" type="text" id="new-tag-<?php echo esc_attr( $tax_name ); ?>" name="newtag[<?php echo esc_attr( $tax_name ); ?>]" class="newtag form-input-tip" size="16" autocomplete="off" value="" />
							<input type="button" class="button tagadd" value="<?php esc_attr_e('Add'); ?>" /></p>
						</div>
						<p class="howto"><?php echo $taxonomy->labels->separate_items_with_commas; ?></p>
					<?php endif; ?>
				</div>
				<div class="tagchecklist"></div>
			</div>
			<?php if ( $user_can_assign_terms ) : ?>
				<p class="hide-if-no-js"><a href="#titlediv" class="tagcloud-link" id="link-<?php echo esc_attr( $tax_name ); ?>"><?php echo esc_html( $taxonomy->labels->choose_from_most_used ); ?></a></p>
			<?php endif; ?>
			<?php
		} else {
			$newsletter_terms = wp_get_object_terms( $post->ID, 'newsletter' );
			if ( count( $newsletter_terms ) > 0 ) {
				echo '<ul>';
				foreach ( $newsletter_terms as $term ) {
					echo '<li>';
					printf( '<a href="%s">%s</a>', esc_url( get_term_link( $term->slug, 'newsletter' ) ), esc_html( $term->name) );
					echo '</li>';
				}
				echo '</ul>';
			} else {
				esc_html_e( 'Uh-oh, there are no newsletters related to this campaign. You better create a new one.', 'groups-newsletters' );
			}
		}
	}

	/**
	 * Renders the save meta box.
	 *
	 * @param object $post
	 */
	public static function save_campaign_meta_box( $post ) {
		global $action;

		$post_type = $post->post_type;
		$post_type_object = get_post_type_object( $post_type );
		$can_publish = current_user_can( $post_type_object->cap->publish_posts );
		?>
		<div class="submitbox" id="submitpost">
			<div id="minor-publishing">
			<?php // Hidden submit button early on so that the browser chooses the right button when form is submitted with Return key ?>
				<div style="display:none;">
				<?php submit_button( esc_html__( 'Save', 'groups-newsletters' ), 'button', 'save' ); ?>
				</div>

				<div id="minor-publishing-actions">
					<div id="save-action">
						<?php if ( 'publish' != $post->post_status && 'future' != $post->post_status && 'pending' != $post->post_status ) { ?>
							<input <?php if ( 'private' == $post->post_status ) { ?>style="display:none"<?php } ?> type="submit" name="save" id="save-post" value="<?php esc_attr_e('Save Draft'); ?>" class="button" />
						<?php } elseif ( 'pending' == $post->post_status && $can_publish ) { ?>
							<input type="submit" name="save" id="save-post" value="<?php esc_attr_e('Save as Pending'); ?>" class="button" />
						<?php } ?>
						<span class="spinner"></span>
					</div>
					<?php if ( $post_type_object->public ) : ?>
						<div id="preview-action">
							<?php
								if ( 'publish' == $post->post_status ) {
									$preview_link = esc_url( get_permalink( $post->ID ) );
									$preview_button = esc_html__( 'Preview Changes', 'groups-newsletters' );
								} else {
									$preview_link = set_url_scheme( get_permalink( $post->ID ) );
									$preview_link = esc_url( apply_filters( 'preview_post_link', add_query_arg( 'preview', 'true', $preview_link ) ) );
									$preview_button = esc_html__( 'Preview', 'groups-newsletters' );
								}
							?>
							<a class="preview button" href="<?php echo esc_url( $preview_link ); ?>" target="wp-preview" id="post-preview"><?php echo esc_html( $preview_button ); ?></a>
							<input type="hidden" name="wp-preview" id="wp-preview" value="" />
						</div>
					<?php endif; // public post type ?>
					<div class="clear"></div>
				</div><!-- #minor-publishing-actions -->

				<div id="misc-publishing-actions">

					<div class="misc-pub-section"><label for="post_status"><?php esc_html_e( 'Status:', 'groups-newsletters' ) ?></label>
						<span id="post-status-display">
							<?php
							switch ( $post->post_status ) {
								case 'private' :
									esc_html_e( 'Privately Published', 'groups-newsletters' );
									break;
								case 'publish' :
									esc_html_e( 'Published', 'groups-newsletters' );
									break;
								case 'future' :
									esc_html_e( 'Scheduled', 'groups-newsletters' );
									break;
								case 'pending' :
									esc_html_e( 'Pending Review', 'groups-newsletters' );
									break;
								case 'draft':
								case 'auto-draft' :
									esc_html_e( 'Draft', 'groups-newsletters');
									break;
							}
							?>
						</span>
						<?php if ( 'publish' == $post->post_status || 'private' == $post->post_status || $can_publish ) { ?>
							<a href="#post_status" <?php if ( 'private' == $post->post_status ) { ?>style="display:none;" <?php } ?>class="edit-post-status hide-if-no-js"><?php esc_html_e( 'Edit', 'groups-newsletters' ) ?></a>
							<div id="post-status-select" class="hide-if-js">
								<input type="hidden" name="hidden_post_status" id="hidden_post_status" value="<?php echo esc_attr( ('auto-draft' == $post->post_status ) ? 'draft' : $post->post_status); ?>" />
								<select name='post_status' id='post_status'>
									<?php if ( 'publish' == $post->post_status ) : ?>
									<option<?php selected( $post->post_status, 'publish' ); ?> value='publish'><?php esc_html_e( 'Published', 'groups-newsletters' ) ?></option>
									<?php elseif ( 'private' == $post->post_status ) : ?>
									<option<?php selected( $post->post_status, 'private' ); ?> value='publish'><?php esc_html_e( 'Privately Published', 'groups-newsletters' ) ?></option>
									<?php elseif ( 'future' == $post->post_status ) : ?>
									<option<?php selected( $post->post_status, 'future' ); ?> value='future'><?php esc_html_e( 'Scheduled', 'groups-newsletters' ) ?></option>
									<?php endif; ?>
									<option<?php selected( $post->post_status, 'pending' ); ?> value='pending'><?php esc_html_e( 'Pending Review', 'groups-newsletters' ) ?></option>
									<?php if ( 'auto-draft' == $post->post_status ) : ?>
									<option<?php selected( $post->post_status, 'auto-draft' ); ?> value='draft'><?php esc_html_e( 'Draft', 'groups-newsletters' ) ?></option>
									<?php else : ?>
									<option<?php selected( $post->post_status, 'draft' ); ?> value='draft'><?php esc_html_e( 'Draft', 'groups-newsletters' ) ?></option>
									<?php endif; ?>
								</select>
								<a href="#post_status" class="save-post-status hide-if-no-js button"><?php esc_html_e( 'OK', 'groups-newsletters' ); ?></a>
								<a href="#post_status" class="cancel-post-status hide-if-no-js"><?php esc_html_e( 'Cancel', 'groups-newsletters' ); ?></a>
							</div>
						<?php } ?>
					</div><!-- .misc-pub-section -->
					<?php
					// translators: Publish box date format, see http://php.net/date
					$datef = __( 'M j, Y @ G:i', 'groups-newsletters' );
					if ( 0 != $post->ID ) {
						if ( 'future' == $post->post_status ) { // scheduled for publishing at a future date
							$stamp = esc_html__( 'Scheduled for: %1$s', 'groups-newsletters' );
						} else if ( 'publish' == $post->post_status || 'private' == $post->post_status ) { // already published
							$stamp = esc_html__( 'Published on: %1$s', 'groups-newsletters' );
						} else if ( '0000-00-00 00:00:00' == $post->post_date_gmt ) { // draft, 1 or more saves, no date specified
							$stamp = esc_html__( 'Publish immediately', 'groups-newsletters' );
						} else if ( time() < strtotime( $post->post_date_gmt . ' +0000' ) ) { // draft, 1 or more saves, future date specified
							$stamp = esc_html__( 'Schedule for: %1$s', 'groups-newsletters' );
						} else { // draft, 1 or more saves, date specified
							$stamp = esc_html__( 'Publish on: %1$s', 'groups-newsletters' );
						}
						$date = date_i18n( $datef, strtotime( $post->post_date ) );
					} else { // draft (no saves, and thus no date specified)
						$stamp = esc_html__( 'Publish immediately', 'groups-newsletters' );
						$date = date_i18n( $datef, strtotime( current_time('mysql') ) );
					}

					if ( $can_publish ) : // Contributors don't get to choose the date of publish ?>
					<div class="misc-pub-section curtime">
						<span id="timestamp">
						<?php printf( $stamp, esc_html( $date ) ); ?></span>
						<a href="#edit_timestamp" class="edit-timestamp hide-if-no-js"><?php esc_html_e( 'Edit', 'groups-newsletters' ) ?></a>
						<div id="timestampdiv" class="hide-if-js"><?php touch_time( ( $action == 'edit' ), 1 ); ?></div>
					</div><?php // /misc-pub-section ?>
					<?php endif; ?>

					<?php do_action( 'post_submitbox_misc_actions' ); ?>
				</div>
				<div class="clear"></div>
			</div>

			<div id="major-publishing-actions">

			<?php do_action('post_submitbox_start'); ?>

				<div id="delete-action">
				<?php
				if ( current_user_can( "delete_post", $post->ID ) ) {
					if ( !EMPTY_TRASH_DAYS )
						$delete_text = esc_html__( 'Delete Permanently', 'groups-newsletters' );
					else
						$delete_text = esc_html__( 'Move to Trash', 'groups-newsletters' );
					?>
				<a class="submitdelete deletion" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>"><?php echo esc_html( $delete_text ); ?></a><?php
				} ?>
				</div>

				<div id="publishing-action">
				<span class="spinner"></span>
				<?php
				if ( !in_array( $post->post_status, array( 'publish', 'future', 'private' ) ) || 0 == $post->ID ) {
					if ( $can_publish ) :
						if ( !empty($post->post_date_gmt) && time() < strtotime( $post->post_date_gmt . ' +0000' ) ) : ?>
						<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Schedule', 'groups-newsletters' ); ?>" />
						<?php submit_button( esc_html__( 'Schedule', 'groups-newsletters' ), 'primary button-large', 'publish', false, array( 'accesskey' => 'p' ) ); ?>
				<?php	else : ?>
						<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Publish') ?>" />
						<?php submit_button( esc_html__( 'Publish', 'groups-newsletters' ), 'primary button-large', 'publish', false, array( 'accesskey' => 'p' ) ); ?>
				<?php	endif;
					else : ?>
						<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Submit for Review') ?>" />
						<?php submit_button( esc_html__( 'Submit for Review', 'groups-newsletters' ), 'primary button-large', 'publish', false, array( 'accesskey' => 'p' ) ); ?>
				<?php
					endif;
				} else { ?>
						<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Update') ?>" />
						<input name="save" type="submit" class="button button-primary button-large" id="publish" accesskey="p" value="<?php esc_attr_e( 'Update', 'groups-newsletters' ) ?>" />
				<?php
				} ?>
				</div>
				<div class="clear"></div>
			</div>
		</div>

	<?php
	}

	/**
	 * Process data for post being saved.
	 * Currently not used.
	 *
	 * @param int $post_id
	 * @param object $post
	 */
	public static function save_post( $post_id = null, $post = null ) {
		if ( ! ( ( defined( "DOING_AUTOSAVE" ) && DOING_AUTOSAVE ) ) ) {
			if ( $post->post_type == 'campaign' ) {
				if ( isset( $_POST['do_campaign_action'] ) && isset( $_POST['campaign_action'] ) ) {
					switch ( $_POST['campaign_action'] ) {
						case 'run' :
							self::update_status( $post_id, 'running' );
							break;
						case 'continue' :
							self::update_status( $post_id, 'running' );
							break;
						case 'hold' :
							self::update_status( $post_id, 'on-hold' );
							break;
					}
				}
			}
		}
	}

	/**
	 * Updates the status of the campaign identified by $post_id, if the
	 * transition is valid.
	 *
	 * @param int $post_id
	 * @param string $new_status
	 * @return boolean true if the new status is valid, false otherwise
	 */
	public static function update_status( $post_id, $new_status ) {
		$result = false;
		if ( !in_array( $new_status, self::get_statuses() ) ) {
			$new_status = 'pending';
		}
		$current_status = self::get_status( $post_id );
		$new_status = apply_filters( 'groups_newsletters_campaign_status_update', $new_status, $current_status, $post_id );
		if ( $current_status != $new_status ) {
			switch ( $current_status ) {
				case 'pending' :
				case 'on-hold' :
					$valid = $new_status == 'running';
					break;
				case 'running' :
					$valid = $new_status == 'executed' || $new_status == 'on-hold';
					break;
			}
			if ( $valid ) {
				if ( update_post_meta( $post_id, 'campaign_status', $new_status, $current_status ) ) {
					$result = true;
					do_action( 'groups_newsletters_campaign_status_updated', $new_status, $current_status, $post_id );
					if ( $new_status == 'running' ) {
						// set up the campaign immediately
						$c = new Groups_Newsletters_Campaign( $post_id );
						$c->work();
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Returns the current status for the campaign identified by $post_id.
	 *
	 * @param int $post_id
	 * @return string status
	 */
	public static function get_status( $post_id ) {
		$status = get_post_meta( $post_id, 'campaign_status', true );
		if ( empty( $status ) ) {
			$status = 'pending';
		}
		return $status;
	}

	/**
	 * Returns an array of all valid statuses.
	 *
	 * @return array of string
	 */
	public static function get_statuses() {
		return array( 'pending', 'on-hold', 'running', 'executed' );
	}

	/**
	 * Returns the name of a status for display.
	 *
	 * @param string $status
	 * @return string status display name
	 */
	public static function get_status_name( $status ) {
		$name = null;
		switch ( $status ) {
			case 'pending' :
				$name = __( 'pending', 'groups-newsletters' );
				break;
			case 'on-hold' :
				$name = __( 'on hold', 'groups-newsletters' );
				break;
			case 'running' :
				$name = __( 'running', 'groups-newsletters' );
				break;
			case 'executed' :
				$name = __( 'executed', 'groups-newsletters' );
				break;
		}
		return $name;
	}
}
Groups_Newsletters_Campaign_Post_Type::init();
