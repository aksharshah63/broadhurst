<?php
/**
 * class-groups-newsletters-stories-widget.php
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
 * Stories widget.
 */
class Groups_Newsletters_Stories_Widget extends WP_Widget {

	/**
	 * @var string cache id
	 */
	static $cache_id = 'groups_newsletters_stories_widget';

	/**
	 * @var string cache flag
	 */
	static $cache_flag = 'widget';

	static $defaults = array(
		"number"        => 10,
		"order"         => "DESC",
		"orderby"       => "post_date",
		"show_author"   => true,
		'newsletter_id' => null,
		'show_date'     => true,
		'show_comment_count' => true
	);

	/**
	 * Sort criteria and labels.
	 * @var array
	 */
	static $orderby_options;

	/**
	 * Sort direction and labels.
	 * @var array
	 */
	static $order_options;

	/**
	 * Initialize.
	 */
	static function init() {
		self::$orderby_options = array(
			'post_author'   => __( 'Author', 'groups-newsletters' ),
			'post_date'     => __( 'Date', 'groups-newsletters' ),
			'post_title'    => __( 'Title', 'groups-newsletters' ),
			'comment_count' => __( 'Comment Count', 'groups-newsletters' ),
		);
		self::$order_options = array(
			'ASC'  => __( 'Ascending', 'groups-newsletters' ),
			'DESC' => __( 'Descending', 'groups-newsletters' )
		);
// 		if ( !has_action( 'wp_print_styles', array( __CLASS__, '_wp_print_styles' ) ) ) {
// 			add_action( 'wp_print_styles', array( __CLASS__, '_wp_print_styles' ) );
// 		}
		if ( !has_action( 'comment_post', array( __CLASS__, 'cache_delete' ) ) ) {
			add_action( 'comment_post', array(__CLASS__, 'cache_delete' ) );
		}
		if ( !has_action( 'transition_comment_status', array( __CLASS__, 'cache_delete' ) ) ) {
			add_action( 'transition_comment_status', array( __CLASS__, 'cache_delete' ) );
		}
		add_action( 'widgets_init', array( __CLASS__, 'widgets_init' ) );
	}

	/**
	 * Registers the widget.
	 */
	static function widgets_init() {
		register_widget( 'Groups_Newsletters_Stories_Widget' );
	}

	/**
	 * Creates a Stories widget.
	 */
	function __construct() {
		parent::__construct( false, $name = 'Stories' );
	}

	/**
	 * Clears cached widget.
	 */
	static function cache_delete() {
		wp_cache_delete( self::$cache_id, self::$cache_flag );
	}

	/**
	 * Enqueue styles if at least one widget is used.
	 */
	static function _wp_print_styles() {
		global $wp_registered_widgets;
		foreach ( $wp_registered_widgets as $widget ) {
			if ( $widget['name'] == 'Stories' ) {
				wp_enqueue_style( 'stories-widget', GROUPS_NEWSLETTERS_PLUGIN_URL . 'css/stories-widget.css', array(), GROUPS_NEWSLETTERS_PLUGIN_VERSION );
				break;
			}
		}
	}

	/**
	 * Widget output
	 *
	 * @see WP_Widget::widget()
	 * @link http://codex.wordpress.org/Class_Reference/WP_Object_Cache
	 */
	function widget( $args, $instance ) {

		extract( $args );

		$widget_id = isset( $args['widget_id'] ) ? $args['widget_id'] : null;

		$cache = wp_cache_get( self::$cache_id, self::$cache_flag );
		if ( ! is_array( $cache ) ) {
			$cache = array();
		}
		if ( isset( $cache[$widget_id] ) ) {
			echo $cache[$widget_id];
			return;
		}

		$title = apply_filters( 'widget_title', isset( $instance['title'] ) ? $instance['title'] : '' );

		// output
		$output = '';
		$output .= $before_widget;
		if ( !empty( $title ) ) {
			$output .= $before_title . $title . $after_title;
		}
		$output .= self::render( $instance );
		$output .= $after_widget;
		echo $output;

		if ( $widget_id !== null ) {
			$cache[$widget_id] = $output;
			wp_cache_set( self::$cache_id, $cache, self::$cache_flag );
		}
	}

	/**
	 * Save widget options
	 *
	 * @see WP_Widget::update()
	 */
	function update( $new_instance, $old_instance ) {

		global $wpdb;

		$settings = $old_instance;

		// title
		$settings['title'] = strip_tags( $new_instance['title'] );

		// number
		$number = isset( $new_instance['number'] ) ? intval( $new_instance['number'] ) : 0;
		if ( $number > 0 ) {
			$settings['number'] = $number;
		} else {
			unset( $settings['number'] );
		}

		// orderby
		$orderby = $new_instance['orderby'];
		if ( key_exists( $orderby, self::$orderby_options ) ) {
			$settings['orderby'] = $orderby;
		} else {
			unset( $settings['orderby'] );
		}

		// order
		$order = $new_instance['order'];
		if ( key_exists( $order, self::$order_options ) ) {
			$settings['order'] = $order;
		} else {
			unset( $settings['order'] );
		}

		// newsletter_id
		$newsletter_id = isset( $new_instance['newsletter_id'] ) ? $new_instance['newsletter_id'] : null;
		if ( empty( $newsletter_id ) ) {
			unset( $settings['newsletter_id'] );
		} else if ( ("[current]" == $newsletter_id ) || ("{current}" == $newsletter_id ) )  {
			$settings['newsletter_id'] = "{current}";
		} else if ( $newsletter = get_term( $newsletter_id, 'newsletter' ) && !is_wp_error( $newsletter ) ) {
			$settings['newsletter_id'] = $newsletter_id;
		}

		// show ...
		$settings['show_author']        = !empty( $new_instance['show_author'] );
		$settings['show_date']          = !empty( $new_instance['show_date'] );
		$settings['show_comment_count'] = !empty( $new_instance['show_comment_count'] );

		$this->cache_delete();

		return $settings;
	}

	/**
	 * Output admin widget options form
	 *
	 * @see WP_Widget::form()
	 */
	function form( $instance ) {

		extract( self::$defaults );

		// title
		$title = isset( $instance['title'] ) ? $instance['title'] : "";
		echo "<p>";
		echo '<label for="' . esc_attr( $this->get_field_id( 'title' ) ) . '">' . esc_html__( 'Title', 'groups-newsletters' ) . '</label>';
		echo '<input class="widefat" id="' . esc_attr( $this->get_field_id( 'title' ) ) . '" name="' . esc_attr( $this->get_field_name( 'title' ) ) . '" type="text" value="' . esc_attr( $title ) . '" />';
		echo '</p>';

		// number
		$number = isset( $instance['number'] ) ? intval( $instance['number'] ) : '';
		echo "<p>";
		echo '<label class="title" title="' . esc_attr__( "The number of stories to show.", 'groups-newsletters' ) .'" for="' . esc_attr( $this->get_field_id( 'number' ) ) . '">' . esc_html__( 'Number of stories', 'groups-newsletters' ) . '</label>';
		echo '<input class="widefat" id="' . esc_attr( $this->get_field_id( 'number' ) ) . '" name="' . esc_attr( $this->get_field_name( 'number' ) ) . '" type="text" value="' . esc_attr( $number ) . '" />';
		echo '</p>';

		// orderby
		$orderby = isset( $instance['orderby'] ) ? $instance['orderby'] : '';
		echo '<p>';
		echo '<label class="title" title="' . esc_attr__( "Sorting criteria.", 'groups-newsletters' ) .'" for="' . esc_attr( $this->get_field_id( 'orderby' ) ) . '">' . esc_html__( 'Order by ...', 'groups-newsletters' ) . '</label>';
		echo '<select class="widefat" name="' . $this->get_field_name( 'orderby' ) . '">';
		foreach ( self::$orderby_options as $orderby_option_key => $orderby_option_name ) {
			$selected = ( $orderby_option_key == $orderby ? ' selected="selected" ' : "" );
			echo '<option ' . $selected . 'value="' . esc_attr( $orderby_option_key ) . '">' . esc_html( $orderby_option_name ) . '</option>';
		}
		echo '</select>';
		echo '</p>';

		// order
		$order = isset( $instance['order'] ) ? $instance['order'] : '';
		echo '<p>';
		echo '<label class="title" title="' . esc_attr__( "Sort order.", 'groups-newsletters' ) .'" for="' . esc_attr( $this->get_field_id( 'order' ) ) . '">' . esc_html__( 'Sort order', 'groups-newsletters' ) . '</label>';
		echo '<select class="widefat" name="' . esc_attr( $this->get_field_name( 'order' ) ) . '">';
		foreach ( self::$order_options as $order_option_key => $order_option_name ) {
			$selected = ( $order_option_key == $order ? ' selected="selected" ' : "" );
			echo '<option ' . $selected . 'value="' . esc_attr( $order_option_key ) . '">' . esc_html( $order_option_name ) . '</option>';
		}
		echo '</select>';
		echo '</p>';

		// newsletter_id
		$newsletter_id = '';
		if ( isset( $instance['newsletter_id'] ) ) {
			if ( ( '[current]' == strtolower( $instance['newsletter_id'] ) ) || ( '{current}' == strtolower( $instance['newsletter_id'] ) ) ) {
				$newsletter_id = '{current}';
			} else {
				$newsletter_id = $instance['newsletter_id'];
			}
		}
		echo "<p>";
		echo '<label class="title" title="' . esc_attr__( "Leave empty to show stories for all newsletters. To show stories for a specific newsletter indicate the newsletter ID. To show stories for the current newsletter, indicate: {current} (when not on a newsletter page, stories for all newsletters are displayed).", 'groups-newsletters' ) . '" for="' . esc_attr( $this->get_field_id( 'newsletter_id' ) ) . '">' . esc_html__( 'Newsletter ID', 'groups-newsletters' ) . '</label>';
		echo '<input class="widefat" id="' . esc_attr( $this->get_field_id( 'newsletter_id' ) ) . '" name="' . esc_attr( $this->get_field_name( 'newsletter_id' ) ) . '" type="text" value="' . esc_attr( $newsletter_id ) . '" />';
		echo '<br/>';
		echo '<span class="description">' . esc_html__( "Empty, newsletter ID or {current}", 'groups-newsletters' ) . '</span>';
		if ( !empty( $newsletter_id ) && ( $newsletter = get_term( $newsletter_id, 'newsletter' ) ) && !is_wp_error( $newsletter ) ) {
			echo '<br/>';
			echo '<span class="description"> ' . esc_html( sprintf( __( 'Newsletter: %s', 'groups-newsletters' ) , esc_html( $newsletter->name ) ) ) . '</span>';
		}
		echo '</p>';

		// show_author
		$checked = ( ( ( !isset( $instance['show_author'] ) && self::$defaults['show_author'] ) || ( isset( $instance['show_author'] ) && ( $instance['show_author'] === true ) ) ) ? 'checked="checked"' : '' );
		echo '<p>';
		echo '<input type="checkbox" ' . $checked . ' value="1" name="' . esc_attr( $this->get_field_name( 'show_author' ) ) . '" />';
		echo '<label class="title" title="' . esc_attr__( "Whether to show the author of each story.", 'groups-newsletters' ) .'" for="' . esc_attr( $this->get_field_id( 'show_author' ) ) . '">' . esc_html__( 'Show author', 'groups-newsletters' ) . '</label>';
		echo '</p>';

		// show_date
		$checked = ( ( ( !isset( $instance['show_date'] ) && self::$defaults['show_date'] ) || ( isset( $instance['show_date'] ) && ( $instance['show_date'] === true ) ) ) ? 'checked="checked"' : '' );
		echo '<p>';
		echo '<input type="checkbox" ' . $checked . ' value="1" name="' . esc_attr( $this->get_field_name( 'show_date' ) ) . '" />';
		echo '<label class="title" title="' . esc_attr__( "Whether to show the date of each story.", 'groups-newsletters' ) .'" for="' . esc_attr( $this->get_field_id( 'show_date' ) ) . '">' . esc_html__( 'Show date', 'groups-newsletters' ) . '</label>';
		echo '</p>';

		// show_comment_count
		$checked = ( ( ( !isset( $instance['show_comment_count'] ) && self::$defaults['show_comment_count'] ) || ( isset( $instance['show_comment_count'] ) && ( $instance['show_comment_count'] === true ) ) ) ? 'checked="checked"' : '' );
		echo '<p>';
		echo '<input type="checkbox" ' . $checked . ' value="1" name="' . esc_attr( $this->get_field_name( 'show_comment_count' ) ) . '" />';
		echo '<label class="title" title="' . esc_attr__( "Whether to show the comment count for each story.", 'groups-newsletters' ) .'" for="' . esc_attr( $this->get_field_id( 'show_comment_count' ) ) . '">' . esc_html__( 'Show number of replies', 'groups-newsletters' ) . '</label>';
		echo '</p>';
	}

	public static function render( $instance ) {

		$args = array_merge( $instance, array( 'post_type' => 'story' ) );

		if ( isset( $args['number'] ) ) {
			$args['numberposts'] = $args['number'];
			unset( $args['number'] );
		}

		$show_author = isset( $args['show_author'] ) && $args['show_author'];
		unset( $args['show_author'] );

		$show_date = isset( $args['show_date'] ) && $args['show_date'];
		unset( $args['show_date'] );

		$show_comment_count = isset( $args['show_comment_count'] ) && $args['show_comment_count'];
		unset( $args['show_comment_count'] );

		// dumb but post_title won't work
		if ( isset( $args['orderby'] ) && ( $args['orderby'] == 'post_title' ) ) {
			$args['orderby'] = 'title';
		}

		if ( !empty( $args['newsletter_id'] ) ) {
			if ( ( $args['newsletter_id'] == '[current]' ) || $args['newsletter_id'] == '{current}' ) {
				$newsletter_id = null;
				global $wp_query;
				if ( $o = $wp_query->get_queried_object() ) {
					if ( isset( $o->taxonomy ) && ( $o->taxonomy == 'newsletter' ) ) {
						$newsletter_id = $o->term_id;
					}
				}
			} else {
				$newsletter_id = $args['newsletter_id'];
			}
			if ( $newsletter_id ) {
				$args['tax_query'] = array(
					array(
						'taxonomy' => 'newsletter',
						'field' => 'id',
						'terms' => $newsletter_id,
						'include_children' => false
					)
				);
			}
			unset( $args['newsletter_id'] );
		}

		$output = '';
		$stories = get_posts( $args );
		if ( count( $stories ) > 0 ) {
			$output .= '<ul>';
			foreach( $stories as $story ) {
				$author = '';
				if ( $show_author ) {
					$author = ' ' . sprintf( '<span class="author">by %s</span>', esc_html( get_the_author_meta('display_name', $story->post_author ) ) );
					// get_author_posts_url( $story->post_author )
				}
				$date = '';
				if ( $show_date ) {
					$date = sprintf(
						', <span class="date">%s</span>',
						esc_html( mysql2date( get_option('date_format'), $story->post_date ) )
					);
				}
				$comment_count = '';
				if ( $show_comment_count ) {
					$comment_count = ', ' . '<span class="comment_count">' . esc_html( sprintf( _n( '1 reply', '%d replies', $story->comment_count ), $story->comment_count ) ) . '</span>';
				}
				$output .= sprintf( '<li><a href="%s">%s</a>%s%s%s</li>', esc_url( get_permalink( $story->ID ) ), wp_strip_all_tags( $story->post_title ), $author, $date, $comment_count );
			}
			$output .= '</ul>';
		} else {
			$output .= esc_html__( 'There are no stories.', 'groups-newsletters' );
		}
		return $output;
	}
}// class Groups_Newsletters_Stories_Widget

Groups_Newsletters_Stories_Widget::init();
