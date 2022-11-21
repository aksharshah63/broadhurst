<?php
/**
 * class-groups-newsletters-subscribe-widget.php
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
 * Subscribe widget.
 */
class Groups_Newsletters_Subscribe_Widget extends WP_Widget {

	/**
	 * @var string cache id
	 */
	static $cache_id = 'groups_newsletters_subscribe_widget';

	/**
	 * @var string cache flag
	 */
	static $cache_flag = 'widget';

	/**
	 * Initialize.
	 */
	static function init() {
// 		if ( !has_action( 'wp_print_styles', array( __CLASS__, '_wp_print_styles' ) ) ) {
// 			add_action( 'wp_print_styles', array( __CLASS__, '_wp_print_styles' ) );
// 		}
		add_action( 'widgets_init', array( __CLASS__, 'widgets_init' ) );
	}

	/**
	 * Registers the widget.
	 */
	static function widgets_init() {
		register_widget( 'Groups_Newsletters_Subscribe_Widget' );
	}

	/**
	 * Creates a Stories widget.
	 */
	function __construct() {
		parent::__construct( false, $name = 'Newsletter Subscription' );
	}

	/**
	 * Clears cached widget.
	 */
	static function cache_delete() {
		wp_cache_delete( self::$cache_id, self::$cache_flag );
	}

	/**
	 * Enqueue styles if at least one widget is used.
	 * Currently not used.
	 */
	static function _wp_print_styles() {
		global $wp_registered_widgets;
		foreach ( $wp_registered_widgets as $widget ) {
			if ( $widget['name'] == 'Newsletter Subscription' ) {
				wp_enqueue_style( 'newsletters-widget', GROUPS_NEWSLETTERS_PLUGIN_URL . 'css/newsletters-widget.css', array(), GROUPS_NEWSLETTERS_PLUGIN_VERSION );
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

		// @since 2.1.0 don't use cached versions of this widget, avoid potential issues with cached widget content before/after subscribing
		//
		// $widget_id = isset( $args['widget_id'] ) ? $args['widget_id'] : null;
		//
		// $cache = wp_cache_get( self::$cache_id, self::$cache_flag );
		// if ( ! is_array( $cache ) ) {
		// 	$cache = array();
		// }
		// if ( isset( $cache[$widget_id] ) ) {
		// 	echo $cache[$widget_id];
		// 	return;
		// }

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

		// if ( $widget_id !== null ) {
		// 	$cache[$widget_id] = $output;
		// 	wp_cache_set( self::$cache_id, $cache, self::$cache_flag );
		// }
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
		$settings['title'] = trim( strip_tags( $new_instance['title'] ) );

		$settings['recaptcha']             = !empty( $new_instance['recaptcha'] ) ? 'yes' : 'no';
		$settings['recaptcha_public_key']  = trim( $new_instance['recaptcha_public_key'] );
		$settings['recaptcha_private_key'] = trim( $new_instance['recaptcha_private_key'] );
		$settings['recaptcha_options']     = trim( $new_instance['recaptcha_options'] );
		$settings['recaptcha_widget']      = trim( $new_instance['recaptcha_widget'] );

		$this->cache_delete();

		return $settings;
	}

	/**
	 * Output admin widget options form
	 *
	 * @see WP_Widget::form()
	 */
	function form( $instance ) {

		// title
		$title = isset( $instance['title'] ) ? $instance['title'] : "";
		echo '<p>';
		printf( '<label class="title" title="%s">', esc_html__( 'Widget title', 'groups-newsletters' ) );
		echo ' ';
		_e( 'Title', 'groups-newsletters' );
		printf(
			'<input class="widefat" id="%s" name="%s" type="text" value="%s" />',
			esc_attr( $this->get_field_id( 'title' ) ),
			esc_attr( $this->get_field_name( 'title' ) ),
			esc_attr( $title )
		);
		echo '</label>';
		echo '</p>';

		// recaptcha
		$recaptcha = isset( $instance['recaptcha'] ) ? $instance['recaptcha'] == 'yes' : false;
		echo '<p>';
		printf( '<label class="title" title="%s">', esc_html__( 'Include a reCAPTCHA to check for humans.', 'groups-newsletters' ) );
		printf( '<input type="checkbox" %s value="yes" name="%s" />', $recaptcha ? ' checked="checked" ': '', $this->get_field_name( 'recaptcha' ) );
		echo ' ';
		printf(
			esc_html__(
				'Use %sreCAPTCHA%s',
				'groups-newsletters'
			),
			'<a href="http://www.google.com/recaptcha">',
			'</a>'
		);
		echo '</label>';
		echo '</p>';

		$recaptcha_public_key = isset( $instance['recaptcha_public_key'] ) ? $instance['recaptcha_public_key'] : '';
		echo '<p>';
		printf( '<label class="recaptcha_public_key" title="%s">', esc_html__( 'Set your reCAPTCHA public key here.', 'groups-newsletters' ) );
		echo ' ';
		esc_html_e( 'reCAPTCHA Public Key', 'groups-newsletters' );
		printf(
			'<input class="widefat" id="%s" name="%s" type="text" value="%s" />',
			esc_attr( $this->get_field_id( 'recaptcha_public_key' ) ),
			esc_attr( $this->get_field_name( 'recaptcha_public_key' ) ),
			esc_attr( $recaptcha_public_key )
		);
		echo '</label>';
		echo '</p>';

		$recaptcha_private_key = isset( $instance['recaptcha_private_key'] ) ? $instance['recaptcha_private_key'] : '';
		echo '<p>';
		printf( '<label class="recaptcha_private_key" title="%s">', esc_html__( 'Set your reCAPTCHA private key here.', 'groups-newsletters' ) );
		echo ' ';
		esc_html_e( 'reCAPTCHA Private Key', 'groups-newsletters' );
		printf(
			'<input class="widefat" id="%s" name="%s" type="text" value="%s" />',
			esc_attr( $this->get_field_id( 'recaptcha_private_key' ) ),
			esc_attr( $this->get_field_name( 'recaptcha_private_key' ) ),
			esc_attr( $recaptcha_private_key )
		);
		echo '</label>';
		echo '</p>';

		$recaptcha_options = isset( $instance['recaptcha_options'] ) ? $instance['recaptcha_options'] : '';
		echo '<p>';
		printf( '<label class="recaptcha_options" title="%s">', esc_html__( 'Set your reCAPTCHA options here.', 'groups-newsletters' ) );
		echo ' ';
		esc_html_e( 'reCAPTCHA Options', 'groups-newsletters' );
		printf(
			'<input class="widefat" id="%s" name="%s" type="text" value="%s" />',
			esc_attr( $this->get_field_id( 'recaptcha_options' ) ),
			esc_attr( $this->get_field_name( 'recaptcha_options' ) ),
			esc_attr( $recaptcha_options )
		);
		echo '</label>';
		echo '</p>';

		$recaptcha_widget = isset( $instance['recaptcha_widget'] ) ? $instance['recaptcha_widget'] : 'groups_newsletters_neutral';
		echo '<p>';
		printf( '<label class="recaptcha_widget" title="%s">', esc_attr__( 'Set your reCAPTCHA widget HTML here, use groups_newsletters_neutral or leave empty for reCAPTCHA themes.', 'groups-newsletters' ) );
		echo ' ';
		esc_html_e( 'reCAPTCHA Widget HTML', 'groups-newsletters' );
		printf(
			'<textarea class="widefat" id="%s" name="%s" type="text">%s</textarea>',
			esc_attr( $this->get_field_id( 'recaptcha_widget' ) ),
			esc_attr( $this->get_field_name( 'recaptcha_widget' ) ),
			esc_textarea( $recaptcha_widget )
		);
		echo '</label>';
		echo '</p>';
	}

	/**
	 * Render the widget.
	 * @param array $instance
	 * @return string
	 */
	public static function render( $instance ) {
		return Groups_Newsletters_Shortcodes::groups_newsletters_subscribe( $instance );
	}
}// class Groups_Newsletters_Subscribe_Widget

Groups_Newsletters_Subscribe_Widget::init();
