<?php
/**
 * class-groups-newsletters-search-widget.php
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
 * Newsletters search widget.
 */
class Groups_Newsletters_Search_Widget extends WP_Widget {

	/**
	 * @var string cache id
	 */
	static $cache_id = 'groups_newsletters_search_widget';

	/**
	 * @var string cache flag
	 */
	static $cache_flag = 'widget';

	static $defaults = array(
// 		'order'        => 'ASC',
// 		'orderby'      => 'name'
// 		,
// 		'show_count'   => true
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
			'name'   => __( 'Name', 'groups-newsletters' ),
			'ID'     => __( 'ID', 'groups-newsletters' )
		);
		self::$order_options = array(
			'ASC'  => __( 'Ascending', 'groups-newsletters' ),
			'DESC' => __( 'Descending', 'groups-newsletters' )
		);
// 		if ( !has_action( 'wp_print_styles', array( __CLASS__, '_wp_print_styles' ) ) ) {
// 			add_action( 'wp_print_styles', array( __CLASS__, '_wp_print_styles' ) );
// 		}
		add_action( 'widgets_init', array( __CLASS__, 'widgets_init' ) );
	}

	/**
	 * Registers the widget.
	 */
	static function widgets_init() {
		register_widget( 'Groups_Newsletters_Search_Widget' );
	}

	/**
	 * Creates a Stories widget.
	 */
	function __construct() {
		parent::__construct( false, $name = 'Newsletter Search' );
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
			if ( $widget['name'] == 'Newsletter Search' ) {
				wp_enqueue_style( 'newsletters-search-widget', GROUPS_NEWSLETTERS_PLUGIN_URL . 'css/newsletters-search-widget.css', array(), GROUPS_NEWSLETTERS_PLUGIN_VERSION );
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

// 		// orderby
// 		$orderby = $new_instance['orderby'];
// 		if ( key_exists( $orderby, self::$orderby_options ) ) {
// 			$settings['orderby'] = $orderby;
// 		} else {
// 			unset( $settings['orderby'] );
// 		}

// 		// order
// 		$order = $new_instance['order'];
// 		if ( key_exists( $order, self::$order_options ) ) {
// 			$settings['order'] = $order;
// 		} else {
// 			unset( $settings['order'] );
// 		}

// 		$settings['show_count'] = !empty( $new_instance['show_count'] );

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
		echo '<label for="' .$this->get_field_id( 'title' ) . '">' . __( 'Title', 'groups-newsletters' ) . '</label>';
		echo '<input class="widefat" id="' . $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name( 'title' ) . '" type="text" value="' . esc_attr( $title ) . '" />';
		echo '</p>';

// 		// orderby
// 		$orderby = isset( $instance['orderby'] ) ? $instance['orderby'] : '';
// 		echo '<p>';
// 		echo '<label class="title" title="' . __( "Sorting criteria.", 'groups-newsletters' ) .'" for="' .$this->get_field_id( 'orderby' ) . '">' . __( 'Order by ...', 'groups-newsletters' ) . '</label>';
// 		echo '<select class="widefat" name="' . $this->get_field_name( 'orderby' ) . '">';
// 		foreach ( self::$orderby_options as $orderby_option_key => $orderby_option_name ) {
// 			$selected = ( $orderby_option_key == $orderby ? ' selected="selected" ' : "" );
// 			echo '<option ' . $selected . 'value="' . $orderby_option_key . '">' . $orderby_option_name . '</option>';
// 		}
// 		echo '</select>';
// 		echo '</p>';

// 		// order
// 		$order = isset( $instance['order'] ) ? $instance['order'] : '';
// 		echo '<p>';
// 		echo '<label class="title" title="' . __( "Sort order.", 'groups-newsletters' ) .'" for="' .$this->get_field_id( 'order' ) . '">' . __( 'Sort order', 'groups-newsletters' ) . '</label>';
// 		echo '<select class="widefat" name="' . $this->get_field_name( 'order' ) . '">';
// 		foreach ( self::$order_options as $order_option_key => $order_option_name ) {
// 			$selected = ( $order_option_key == $order ? ' selected="selected" ' : "" );
// 			echo '<option ' . $selected . 'value="' . $order_option_key . '">' . $order_option_name . '</option>';
// 		}
// 		echo '</select>';
// 		echo '</p>';

// 		// show_count
// 		$checked = ( ( ( !isset( $instance['show_count'] ) && self::$defaults['show_count'] ) || ( $instance['show_count'] === true ) ) ? 'checked="checked"' : '' );
// 		echo '<p>';
// 		echo '<input type="checkbox" ' . $checked . ' value="1" name="' . $this->get_field_name( 'show_count' ) . '" />';
// 		echo '<label class="title" title="' . __( "Whether to show the number of stories in a newsletter.", 'groups-newsletters' ) .'" for="' . $this->get_field_id( 'show_count' ) . '">' . __( 'Show number of stories', 'groups-newsletters' ) . '</label>';
// 		echo '</p>';

	}

	/**
	 * Renders the widget.
	 * @param array $instance
	 * @return string widget HTML
	 */
	public static function render( $instance ) {
		return Groups_Newsletters_Shortcodes::groups_newsletters_search( $instance );
	}
}
Groups_Newsletters_Search_Widget::init();
