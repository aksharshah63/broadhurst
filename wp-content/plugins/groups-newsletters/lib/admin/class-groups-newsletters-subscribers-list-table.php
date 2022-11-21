<?php
/**
 * class-groups-newsletters-subscribers-list-table.php
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
 * @since 1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Subscribers admin screen and table; leveraging WP_List_Table.
 *
 * Drawbacks : WP_List_Table still (WordPress 3.6-beta-3) has @access private.
 */
class Groups_Newsletters_Subscribers_List_Table extends WP_List_Table {

	/**
	 * Adds admin menu hook.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_filter( 'set-screen-option', array( __CLASS__, 'set_screen_option' ), 10, 3 );
	}

	/**
	 * Add the Subscribers menu item.
	 */
	public static function admin_menu() {
		$page = add_submenu_page(
			'edit.php?post_type=story',
			__( 'Groups Newsletters Subscribers', 'groups-newsletters' ),
			__( 'Subscribers', 'groups-newsletters' ),
			'manage_options',
			'groups-newsletters-subscribers',
			array( __CLASS__, 'subscribers' )
		);
		add_action( "load-$page", array( __CLASS__, 'load' ) );

	}

	/**
	 * Add screen options.
	 */
	public static function load() {
		add_screen_option(
			'per_page',
			array(
				'option'  => 'subscribers_per_page',
				'label'   => esc_html_x( 'Subscribers', 'subscribers per page (screen options)', 'groups-newsletters' ),
				'default' => 20,
			)
		);
	}

	/**
	 * Saves are screen options.
	 *
	 * @param string $default
	 * @param string $option
	 * @param string $value
	 * @return mixed
	 */
	public static function set_screen_option( $default, $option, $value ) {
		$result = $default;
		if ( $option == 'subscribers_per_page' ) {
			if ( intval( $value ) > 0 ) {
				$result = $value;
			} else {
				$result = 1;
			}
		}
		return $result;
	}

	/**
	 * Subscribers screen.
	 */
	public static function subscribers() {
		if ( !current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'groups-newsletters' ) );
		}
		echo
			'<h2>' .
			esc_html__( 'Subscribers', 'groups-newsletters' ) .
			'</h2>';
		echo '<div class="groups-newsletters-subscribers">';
		$t = new Groups_Newsletters_Subscribers_List_Table( array( 'screen' => get_current_screen() ) );
		$t->prepare_items();
		echo '<form id="subscribers-filter" method="get">';
		echo '<div>';
		printf( '<input type="hidden" name="page" value="%s" />', esc_attr( $_REQUEST['page'] ) );
		printf( '<input type="hidden" name="post_type" value="%s" />', esc_attr( $_REQUEST['post_type'] ) );
		$t->display();
		echo '</div>';
		echo '</form>';
		echo '</div>'; // .groups-newsletters-subscribers
	}

	/**
	 * Class Constructor
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		parent::__construct( array(
			'singular' => 'subscriber',
			'plural'   => 'subscribers',
			'ajax'     => false,
			'screen'   => isset( $args['screen'] ) ? $args['screen'] : null,
		) );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @see WP_List_Table::ajax_user_can()
	 */
	function ajax_user_can() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Subscriber checkbox.
	 *
	 * @param object $item
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="subscriber_id[]" value="%d" />',
			$item->subscriber_id
		);
	}

	/**
	 * Render column content for item.
	 *
	 * @param object $item
	 * @param string $column_name
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		$output = '';
		switch ( $column_name ) {
			case 'subscriber_id' :
				$output = intval( $item->subscriber_id );
				break;
			case 'email' :
				$output = $item->email;
				break;
			case 'status' :
				if ( $item->status ) {
					$output = esc_html__( 'Active', 'groups-newsletters' );
				} else {
					$output = esc_html__( 'Inactive', 'groups-newsletters' );
				}
				break;
			case 'subscribed' :
				$output = sprintf(
					'<span class="date">%s</span>',
					esc_html( mysql2date( __( 'M j, Y @ G:i', 'groups-newsletters' ) , $item->subscribed ) )
				);
				break;
		}
		return $output;
	}

	/**
	 * Get Columns
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb' => '<input type="checkbox" />',
			'subscriber_id' => __( 'ID', 'groups-newsletters' ),
			'email'         => __( 'Email', 'groups-newsletters' ),
			'status'        => __( 'Status', 'groups-newsletters' ),
			'subscribed'    => __( 'Since', 'groups-newsletters' ),
		);
	}

	/**
	 * Get hidded columns
	 *
	 * @return array
	 */
	public function get_hidden_columns() {
		return array();
	}

	/**
	 * Get sortable columns
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'subscriber_id' => array( 'subscriber_id', false ),
			'email'         => array( 'email', false ),
			'status'        => array( 'status', false ),
			'subscribed'    => array( 'subscribed', false )
		);
	}

	/**
	 * Get bulk actions
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'delete'      => __( 'Delete', 'groups-newsletters' ),
			'subscribe'   => __( 'Subscribe', 'groups-newsletters' ),
			'unsubscribe' => __( 'Unsubscribe', 'groups-newsletters' )
		);
	}

	/**
	 * Bulk DB actions
	 */
	public function do_bulk_action() {
		$action = $this->current_action();
		switch ( $action ) {
			case 'delete' :
				$subscriber_ids = !empty( $_REQUEST['subscriber_id'] ) ? $_REQUEST['subscriber_id'] : array();
				if ( !empty( $subscriber_ids ) ) {
					global $wpdb;
					$subscriber_table = Groups_Newsletters_Controller::get_tablename( 'subscriber' );
					foreach ( $subscriber_ids as $subscriber_id ) {
						$wpdb->query( $wpdb->prepare( "DELETE FROM $subscriber_table WHERE subscriber_id = %d", intval( $subscriber_id ) ) );
					}
				}
				break;
			case 'subscribe' :
				$subscriber_ids = !empty( $_REQUEST['subscriber_id'] ) ? $_REQUEST['subscriber_id'] : array();
				if ( !empty( $subscriber_ids ) ) {
					global $wpdb;
					$subscriber_table = Groups_Newsletters_Controller::get_tablename( 'subscriber' );
					foreach ( $subscriber_ids as $subscriber_id ) {
						$wpdb->query( $wpdb->prepare( "UPDATE $subscriber_table SET status = 1 WHERE subscriber_id = %d", intval( $subscriber_id ) ) );
					}
				}
				break;
			case 'unsubscribe' :
				$subscriber_ids = !empty( $_REQUEST['subscriber_id'] ) ? $_REQUEST['subscriber_id'] : array();
				if ( !empty( $subscriber_ids ) ) {
					global $wpdb;
					$subscriber_table = Groups_Newsletters_Controller::get_tablename( 'subscriber' );
					foreach ( $subscriber_ids as $subscriber_id ) {
						$wpdb->query( $wpdb->prepare( "UPDATE $subscriber_table SET status = 0 WHERE subscriber_id = %d", intval( $subscriber_id ) ) );
					}
				}
				break;
		}
	}

	/**
	 * Pagination
	 */
	public function prepare_items() {
		global $wpdb;
		$per_page = $this->get_items_per_page( 'subscribers_per_page' );
		$current_page = $this->get_pagenum();
		$columns = $this->get_columns();
		$hidden = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->do_bulk_action();

		$limit = $per_page;
		$offset = ( $current_page - 1 ) * $per_page;

		$orderby = !empty( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : 'email';
		$order = !empty( $_REQUEST['order'] ) ? strtoupper( $_REQUEST['order'] ) : 'ASC';
		switch ( $orderby ) {
			case 'subscriber_id' :
			case 'email' :
			case 'status' :
			case 'subscribed' :
				break;
			default :
				$orderby = 'email';
		}
		switch ( $order ) {
			case 'ASC' :
			case 'DESC' :
				break;
			default :
				$order = 'ASC';
		}

		$subscriber_table = Groups_Newsletters_Controller::get_tablename( 'subscriber' );
		$n = $wpdb->get_var( "SELECT COUNT(*) FROM $subscriber_table" );
		$subscribers = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $subscriber_table ORDER BY $orderby $order LIMIT %d OFFSET %d",
			$limit,
			$offset
		) );
		$this->items = $subscribers;

		$this->set_pagination_args( array(
			'total_items' => $n,
			'per_page'    => $per_page,
			'total_pages' => ceil( $n / $per_page )
		) );

	}

}
Groups_Newsletters_Subscribers_List_Table::init();
