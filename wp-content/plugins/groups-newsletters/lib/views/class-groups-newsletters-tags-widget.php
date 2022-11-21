<?php
/**
 * class-groups-newsletters-tags-widget.php
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
 * Newsletter story tags widget.
 */
class Groups_Newsletters_Tags_Widget extends WP_Widget {

	/**
	 * @var string cache id
	 */
	static $cache_id = 'groups_newsletters_tags_widget';

	/**
	 * @var string cache flag
	 */
	static $cache_flag = 'widget';

	static $defaults = array(
		'smallest'  => 8,
		'largest'   => 22,
		'unit'      => 'pt',
		'number'    => 45,
		'format'    => 'flat',
		'separator' => "\n",
		'orderby'   => 'name',
		'order'     => 'ASC'
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
	 * Output format.
	 * @var array
	 */
	static $format_options;

	/**
	 * Separator options.
	 * @var array
	 */
	static $separator_options;

	/**
	 * Initialize.
	 */
	static function init() {
		self::$orderby_options = array(
			'name'   => __( 'Name', 'groups-newsletters' ),
			'count'  => __( 'Count', 'groups-newsletters' )
		);
		self::$order_options = array(
			'ASC'  => __( 'Ascending', 'groups-newsletters' ),
			'DESC' => __( 'Descending', 'groups-newsletters' ),
			'RAND' => __( 'Random', 'groups-newsletters' )
		);
		self::$format_options = array(
			'flat' => __( 'Flat', 'groups-newsletters' ),
			'list' => __( 'List', 'groups-newsletters' )
		);
		self::$separator_options = array(
			"\n"  => __( 'New line', 'groups-newsletters' ),
			" "   => __( 'Space', 'groups-newsletters' ),
			", "  => __( 'Comma', 'groups-newsletters' ),
			" - " => __( 'Dash', 'groups-newsletters' )
		);
// 		if ( !has_action( 'wp_print_styles', array( __CLASS__, '_wp_print_styles' ) ) ) {
// 			add_action( 'wp_print_styles', array( __CLASS__, '_wp_print_styles' ) );
// 		}
		if ( !has_action( 'created_story_tag', array( __CLASS__, 'cache_delete' ) ) ) {
			add_action( 'created_story_tag', array(__CLASS__, 'cache_delete' ) );
		}
		if ( !has_action( 'delete_story_tag', array( __CLASS__, 'cache_delete' ) ) ) {
			add_action( 'delete_story_tag', array( __CLASS__, 'cache_delete' ) );
		}
		add_action( 'widgets_init', array( __CLASS__, 'widgets_init' ) );
	}

	/**
	 * Registers the widget.
	 */
	static function widgets_init() {
		register_widget( 'Groups_Newsletters_Tags_Widget' );
	}

	/**
	 * Creates a Stories widget.
	 */
	function __construct() {
		parent::__construct( false, $name = 'Story Tags' );
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
			if ( $widget['name'] == 'Story Tags' ) {
				wp_enqueue_style( 'newsletters-tags-widget', GROUPS_NEWSLETTERS_PLUGIN_URL . 'css/newsletters-tags-widget.css', array(), GROUPS_NEWSLETTERS_PLUGIN_VERSION );
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

		$settings['title'] = strip_tags( $new_instance['title'] );

		$settings['smallest'] = absint( $new_instance['smallest'] );

		$settings['largest'] = absint( $new_instance['largest'] );

		switch( $new_instance['unit'] ) {
			case 'pt' :
			case 'px' :
			case '%' :
			case 'em' :
				$settings['unit'] = $new_instance['unit'];
				break;
		}

		if ( key_exists( $new_instance['format'], self::$format_options ) ) {
			$settings['format'] = $new_instance['format'];
		} else {
			unset( $settings['format'] );
		}

		if ( key_exists( $new_instance['separator'], self::$separator_options ) ) {
			$settings['separator'] = $new_instance['separator'];
		} else {
			unset( $settings['separator'] );
		}

		$orderby = $new_instance['orderby'];
		if ( key_exists( $orderby, self::$orderby_options ) ) {
			$settings['orderby'] = $orderby;
		} else {
			unset( $settings['orderby'] );
		}

		$order = $new_instance['order'];
		if ( key_exists( $order, self::$order_options ) ) {
			$settings['order'] = $order;
		} else {
			unset( $settings['order'] );
		}

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

		$title = isset( $instance['title'] ) ? $instance['title'] : "";
		echo "<p>";
		echo '<label for="' .$this->get_field_id( 'title' ) . '">' . __( 'Title', 'groups-newsletters' ) . '</label>';
		echo '<input class="widefat" id="' . $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name( 'title' ) . '" type="text" value="' . esc_attr( $title ) . '" />';
		echo '</p>';

		$smallest = isset( $instance['smallest'] ) ? $instance['smallest'] : $smallest;
		echo "<p>";
		echo '<label>';
		echo __( 'Smallest', 'groups-newsletters' );
		echo ' ';
		echo '<input class="widefat" id="' . $this->get_field_id( 'smallest' ) . '" name="' . $this->get_field_name( 'smallest' ) . '" type="text" value="' . esc_attr( $smallest ) . '" />';
		echo '</label>';
		echo '</p>';

		$largest = isset( $instance['largest'] ) ? $instance['largest'] : $largest;
		echo "<p>";
		echo '<label>';
		echo __( 'Largest', 'groups-newsletters' );
		echo ' ';
		echo '<input class="widefat" id="' . $this->get_field_id( 'larget' ) . '" name="' . $this->get_field_name( 'largest' ) . '" type="text" value="' . esc_attr( $largest ) . '" />';
		echo '</label>';
		echo '</p>';

		$unit = isset( $instance['unit'] ) ? $instance['unit'] : $unit;
		echo "<p>";
		echo '<label>';
		echo __( 'Unit', 'groups-newsletters' );
		echo ' ';
		echo '<input class="widefat" id="' . $this->get_field_id( 'unit' ) . '" name="' . $this->get_field_name( 'unit' ) . '" type="text" value="' . esc_attr( $unit ) . '" />';
		echo '</label>';
		echo '</p>';

		$format = isset( $instance['format'] ) ? $instance['format'] : $format;
		echo '<p>';
		echo '<label >';
		echo __( 'Format', 'groups-newsletters' );
		echo ' ';
		echo '<select class="widefat" name="' . $this->get_field_name( 'format' ) . '">';
		foreach ( self::$format_options as $option_key => $option_name ) {
			$selected = ( $option_key == $orderby ? ' selected="selected" ' : "" );
			echo '<option ' . $selected . 'value="' . $option_key . '">' . $option_name . '</option>';
		}
		echo '</select>';
		echo '</label>';
		echo '</p>';

		$separator = isset( $instance['separator'] ) ? $instance['separator'] : $separator;
		echo '<p>';
		echo '<label>';
		echo __( 'Separator', 'groups-newsletters' );
		echo ' ';
		echo '<select class="widefat" name="' . $this->get_field_name( 'separator' ) . '">';
		foreach ( self::$separator_options as $option_key => $option_name ) {
			$selected = ( $option_key == $separator ? ' selected="selected" ' : "" );
			echo '<option ' . $selected . 'value="' . esc_attr( $option_key ) . '">' . $option_name . '</option>';
		}
		echo '</select>';
		echo '</label>';
		echo '</p>';

		// orderby
		$orderby = isset( $instance['orderby'] ) ? $instance['orderby'] : $orderby;
		echo '<p>';
		echo '<label>';
		echo __( 'Order by &hellip;', 'groups-newsletters' );
		echo ' ';
		echo '<select class="widefat" name="' . $this->get_field_name( 'orderby' ) . '">';
		foreach ( self::$orderby_options as $orderby_option_key => $orderby_option_name ) {
			$selected = ( $orderby_option_key == $orderby ? ' selected="selected" ' : "" );
			echo '<option ' . $selected . 'value="' . $orderby_option_key . '">' . $orderby_option_name . '</option>';
		}
		echo '</select>';
		echo '</label>';
		echo '</p>';

		// order
		$order = isset( $instance['order'] ) ? $instance['order'] : $order;
		echo '<p>';
		echo '<label>';
		echo __( 'Sort order', 'groups-newsletters' );
		echo ' ';
		echo '<select class="widefat" name="' . $this->get_field_name( 'order' ) . '">';
		foreach ( self::$order_options as $order_option_key => $order_option_name ) {
			$selected = ( $order_option_key == $order ? ' selected="selected" ' : "" );
			echo '<option ' . $selected . 'value="' . $order_option_key . '">' . $order_option_name . '</option>';
		}
		echo '</select>';
		echo '</label>';
		echo '</p>';
	}

	/**
	 * Renders the widget.
	 * @param array $instance
	 * @return string widget HTML
	 */
	public static function render( $instance ) {
		return Groups_Newsletters_Shortcodes::groups_newsletters_tags( $instance );
	}
}
Groups_Newsletters_Tags_Widget::init();
