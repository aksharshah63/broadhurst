<?php
/**
 * Plugin Name: Client Portal
 * Plugin URI: http://www.cozmoslabs.com/
 * Description:  Build a company site with a client portal where clients login and see a restricted-access, personalized page of content with links and downloads.
 * Version: 1.1.8
 * Author: Cozmoslabs, Madalin Ungureanu, Antohe Cristian
 * Author URI: http://www.cozmoslabs.com
 * Text Domain: client-portal
 * License: GPL2
 */
/*  Copyright 2015 Cozmoslabs (www.cozmoslabs.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/*
* Define plugin path
*/

define( 'CP_URL', plugin_dir_url( __FILE__ ) );

class CL_Client_Portal
{
    private $slug;
    private $defaults;
    public $options;


    function __construct()
    {
        $this->slug = 'cp-options';
        $this->options = get_option( $this->slug );
        $this->defaults = array(
                                'page-slug' => 'private-page',
                                'support-comments' => 'no',
                                'restricted-message' => __( 'You do not have permission to view this page.', 'client-portal' ),
                                'portal-log-in-message' => __( 'Please log in in order to access the client portal.', 'client-portal' ),
                                'above-page-content' => '',
                                'default-page-content' => '',
                                'below-page-content' => '',
                                'redirect-private-pages' => 'yes',
                                );

        /* register the post type */
        add_action( 'init', array( $this, 'cp_create_post_type' ) );
        /* action to create a private page when a user registers */
        add_action( 'user_register', array( $this, 'cp_create_private_page' ) );
        /* remove the page when a user is deleted */
        add_action( 'delete_user', array( $this, 'cp_delete_private_page' ), 10, 2 );
        /* restrict the content of the page only to the user */
        add_filter( 'the_content', array( $this, 'cp_restrict_content' ) );
        /* restrict access to private-page and redirect to HOME or to a page selected by the user */
        add_action( 'template_redirect', array( $this, 'cp_redirect_non_permitted_users' ) );
        //restrict comments reply form
        add_filter( 'comments_open', array( $this, 'cp_comments_restrict_replying' ), 20, 2 );
        //restrict comments
        add_filter( 'wp_list_comments_args', array( $this, 'cp_comments_change_callback_function' ), 999 );
        //restrict private comments from being displayed in widgets
        add_filter( 'the_comments', array( $this, 'cp_exclude_restricted_comments' ), 10, 2 );
        // add page template
        add_filter( 'template_include', array( $this, 'cp_set_page_template' ) );
        /* add a link in the Users List Table in admin area to access the page */
        add_filter( 'user_row_actions', array( $this, 'cp_add_links_to_private_page' ), 10, 2);

        /* add bulk action to create private user pages */
        add_filter( 'admin_footer-users.php', array( $this, 'cp_create_private_page_bulk_actions' ) );
        add_action( 'admin_action_create_private_page', array( $this, 'cp_create_private_pages_in_bulk' ) );

        /* create client portal extra information */
        add_filter('the_content', array( $this, 'cp_add_private_page_general_info_above_content'));

        /* create client portal extra information */
        add_filter('the_content', array( $this, 'cp_add_private_page_info'));

        /* create client portal extra information */
        add_filter('the_content', array( $this, 'cp_add_private_page_general_info_below_content'));

        /* create the shortcode for the main page */
        add_shortcode( 'client-portal', array( $this, 'cp_shortcode' ) );

        /* create the shortcode for the private page content */
        add_shortcode( 'cp-private-page-content', array( $this, 'cp_get_private_page_content' ) );

        /* create the settings page */
        add_action( 'admin_menu', array( $this, 'cp_add_settings_page' ) );
        /* register the settings */
        add_action( 'admin_init', array( $this, 'cp_register_settings' ) );
        /* show notices on the admin settings page */
        add_action( 'admin_notices', array( $this, 'cp_admin_notices' ) );
        // Enqueue scripts on the admin side
        add_action( 'admin_enqueue_scripts', array( $this, 'cp_enqueue_admin_scripts' ) );
        /* flush the rewrite rules when settings saved in case page slug was changed */
        add_action('init', array( $this, 'cp_flush_rules' ), 20 );

        /* make sure we don't have post navigation on the private pages */
        add_filter( "get_previous_post_where", array( $this, 'cp_exclude_from_post_navigation' ), 10, 5 );
        add_filter( "get_next_post_where", array( $this, 'cp_exclude_from_post_navigation' ), 10, 5 );

        add_action( "init", array( $this, 'cp_init_translation' ), 8 );

        //actions run during plugin activation
        register_activation_hook( __FILE__, array( $this, 'client_portal_activation_link' ) );

        //add settings link to plugins listing
        add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'cp_settings_link' ) );

        //show installation notice for Profile Builder, if not installed
        add_action( 'admin_notices', array( $this, 'cp_install_pb_admin_notice' ) );


    }

    /**
     * Function that runs on plugin activation
     */
    function client_portal_activation_link(){
        //set a flush rewrite rules flag on activation so we know to do it just once
        if ( ! get_option( 'client_portal_flush_rewrite_rules_flag' ) ) {
            add_option( 'client_portal_flush_rewrite_rules_flag', true );
        }
        //save first activation date and time
        if ( ! get_option( 'cp_activation_date_time' ) ) {
            add_option( 'cp_activation_date_time', time() );
        }
    }

    /**
     * Load plugin textdomain
     */
    public function cp_init_translation(){
        load_plugin_textdomain( 'client-portal' );
    }

    /**
     * Function that registers the post type
     */
    function cp_create_post_type() {

        $labels = array(
            'name'               => _x( 'Private Pages', 'post type general name', 'client-portal' ),
            'singular_name'      => _x( 'Private Page', 'post type singular name', 'client-portal' ),
            'menu_name'          => _x( 'Private Page', 'admin menu', 'client-portal' ),
            'name_admin_bar'     => _x( 'Private Page', 'add new on admin bar', 'client-portal' ),
            'add_new'            => _x( 'Add New', 'private Page', 'client-portal' ),
            'add_new_item'       => __( 'Add New Private Page', 'client-portal' ),
            'new_item'           => __( 'New Private Page', 'client-portal' ),
            'edit_item'          => __( 'Edit Private Page', 'client-portal' ),
            'view_item'          => __( 'View Private Page', 'client-portal' ),
            'all_items'          => __( 'All Private Pages', 'client-portal' ),
            'search_items'       => __( 'Search Private Pages', 'client-portal' ),
            'parent_item_colon'  => __( 'Parent Private Page:', 'client-portal' ),
            'not_found'          => __( 'No Private Pages found.', 'client-portal' ),
            'not_found_in_trash' => __( 'No Private Pages found in Trash.', 'client-portal' )
        );

        $args = array(
            'labels'                => $labels,
            'description'           => __( 'Description.', 'client-portal' ),
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => false,
            'query_var'             => true,
            'capability_type'       => 'post',
            'has_archive'           => false,
            'hierarchical'          => true,
            'supports'              => array( 'title', 'editor', 'author', 'thumbnail' ),
            'exclude_from_search'   => true,
            'show_in_rest'          => true
        );

        if( !empty( $this->options['page-slug'] ) ){
            $args['rewrite'] = array( 'slug' => $this->options['page-slug'] );
        }
        else{
            $args['rewrite'] = array( 'slug' => $this->defaults['page-slug'] );
        }

        if( !empty( $this->options['support-comments'] ) && $this->options['support-comments'] == 'yes' )
            $args['supports'][] = 'comments';

        register_post_type( 'private-page', $args );
    }

    /**
     * Function that creates the private page for a user
     * @param $user_id the id of the user for which to create the page
     */
    function cp_create_private_page( $user_id ){

        /* check to see if we already have a page for the user */
        $users_private_pages = $this->cp_get_private_page_for_user( $user_id );
        if( !empty( $users_private_pages ) )
            return;

        /* make sure get_userdata() is available at this point */
        if(is_admin()) require_once( ABSPATH . 'wp-includes/pluggable.php' );

        $user = get_userdata( $user_id );
        $display_name = '';
        if( $user ){
            $display_name = ($user->display_name) ? ($user->display_name) : ($user->user_login);
        }

        if( !empty( $this->options['default-page-content'] ) )
            $post_content = $this->options['default-page-content'];
        else
            $post_content = $this->defaults['default-page-content'];

        $private_page = array(
            'post_title'    => $display_name,
            'post_status'   => 'publish',
            'post_type'     => 'private-page',
            'post_author'   => $user_id,
            'post_content'  => $post_content
        );

        // Insert the post into the database
        wp_insert_post( $private_page );
    }

    /**
     * Function that deletes the private page when the user is deleted
     * @param $id the id of the user which page we are deleting
     * @param $reassign
     */
    function cp_delete_private_page( $id, $reassign ){
        $private_page_id = $this->cp_get_private_page_for_user( $id );
        if( !empty( $private_page_id ) ){
            wp_delete_post( $private_page_id, true );
        }
    }

    /**
     * Function that restricts the content only to the author of the page
     * @param $content the content of the page
     * @return mixed
     */
    function cp_restrict_content( $content ){
        global $post;
        if( $post->post_type == 'private-page' ){

            if( !empty( $this->options['restricted-message'] ) )
                $message = $this->options['restricted-message'];
            else
                $message = $this->defaults['restricted-message'];

            if( is_user_logged_in() ){
                if( ( get_current_user_id() == $post->post_author ) || current_user_can('delete_user') ){
                    return $content;
                }
                else return $message;
            }
            else return $message;

        }
        return $content;
    }

    /**
     * Function that redirects users trying to access a Private Page
     */
    function cp_redirect_non_permitted_users() {

        if( !isset( $this->options['redirect-private-pages'] ) || $this->options['redirect-private-pages'] == 'no' )
            return;

        if( current_user_can( 'manage_options' ) )
            return;

        global $post;

        if( !empty( $post->post_type ) && $post->post_type == 'private-page' ){

            if( is_user_logged_in() && !empty( $post->post_author ) && get_current_user_id() == $post->post_author )
                return;

            if ( !empty( $this->options["redirect-pp-to-page-id"] ))
                $redirect_url = get_permalink( $this->options["redirect-pp-to-page-id"] );
            else $redirect_url = get_home_url( );

            wp_redirect( $redirect_url );
            exit;

        }

    }

    function cp_comments_restrict_replying( $open, $post_id ) {
        $this_post = get_post($post_id);
        if( $this_post->post_type == 'private-page' ) {
            // Show for administrators
            if (current_user_can('manage_options') && is_admin())
                return $open;

            if( is_user_logged_in() && ( get_current_user_id() == $this_post->post_author  || current_user_can('delete_user') ) )
                return $open;
            else
                return false;
        }

        return $open;
    }

    function cp_comments_change_callback_function( $args ) {
        global $post;

        if ( empty( $post->ID ) ) return $args;

        if( $post->post_type == 'private-page' ) {
            if( !( is_user_logged_in() && ( get_current_user_id() == $post->post_author  || current_user_can('delete_user') ) ) ) {
                $args['callback'] = array( $this, 'cp_comments_restrict_view' );
            }
        }

        return $args;
    }

    /**
     * Function that restricts private comments from being displayed in widgets
     */
    function cp_exclude_restricted_comments( $comments, $query ){
        if( !function_exists( 'pms_exclude_restricted_comments' ) && !function_exists( 'wppb_exclude_restricted_comments' ) ){
            if( !empty( $comments ) && !current_user_can( 'manage_options' ) ){
                $user_id = get_current_user_id();
                foreach ( $comments as $key => $comment ){
                    $post = get_post( $comment->comment_post_ID );
                    if( ( $post->post_type == 'private-page' && $user_id != (int)$post->post_author ) || ( function_exists( 'wppb_content_restriction_is_post_restricted' ) && wppb_content_restriction_is_post_restricted( $comment->comment_post_ID ) ) || ( function_exists( 'pms_is_post_restricted' ) && pms_is_post_restricted( $comment->comment_post_ID ) ) ){
                        unset( $comments[$key] );
                    }
                }
            }
        }
        return $comments;
    }

    function cp_comments_restrict_view( $comment, $args, $depth ) {
        global $cp_message_shown;

        if ( !$cp_message_shown ) {

            if( !empty( $this->options['restricted-message'] ) )
                $message = $this->options['restricted-message'];
            else
                $message = $this->defaults['restricted-message'];

            printf( '<p>%s</p>', $message );

            $cp_message_shown = true;
        }
    }

    /**
     * Function that allows loading the page.php template if the user selects it
     * @param $template the path to template
     * @return string
     */
    function cp_set_page_template( $template ){
        global $post;
        if ( isset( $post ) && isset( $post->post_type ) ){
            $template_directory = get_stylesheet_directory();
            if ( $post->post_type === 'private-page' && !empty( $this->options[ 'load-template' ] ) && $this->options[ 'load-template' ] == 'page' && file_exists( $template_directory . '/page.php' ) ) {
                $template = get_stylesheet_directory() . '/page.php';
            }
        }

        return $template;
    }

    /**
     * Function that adds EDIT and VIEW links in the user listing in admin area for the private page
     * @param $actions The actions available on the user listing in admin area
     * @param $user_object The user object
     * @return mixed
     */
    function cp_add_links_to_private_page( $actions, $user_object ){
        $private_page_id = $this->cp_get_private_page_for_user( $user_object->ID );
        if( !empty( $private_page_id ) ){
            $actions['private_page_edit_link'] = "<a class='cp_private_page' href='" . admin_url( "post.php?post=$private_page_id&action=edit") . "'>" . __( 'Edit Page', 'client-portal' ) . "</a>";
            $actions['private_page_view_link'] = "<a class='cp_private_page' href='" . get_permalink( $private_page_id ) . "'>" . __( 'View Page', 'client-portal' ) . "</a>";
        }

        return $actions;
    }

    /**
     * Function that creates a private page general information div above the content
     * @param $content the content of the private page
     * @return mixed
     */
    function cp_add_private_page_general_info_above_content( $content ){
        if ( !empty( $this->options['above-page-content'] ) && is_singular('private-page') && is_user_logged_in() ){
            $general_info = "<div class='cp-general-info-above-content' >".$this->options['above-page-content']."</div>";

            return  $general_info . $content;
        }

        return $content;
    }

    /**
     * Function that creates a private page extra information div
     * @param $content the content of the private page
     * @return mixed
     */
    function cp_add_private_page_info( $content ){
        global $post;
        if ( is_singular('private-page') && is_user_logged_in() ){
            // logout link
            $logout_link = wp_loginout( home_url(), false);

            // author display name. Fallback to username if no display name is set.
            $author_id=$post->post_author;
            $user = get_user_by('id', $author_id);
            $display_name = '';
            if( $user ){
                $display_name = ($user->display_name) ? ($user->display_name) : ($user->user_login);
            }

            $extra_info = "<p class='cp-logout' style='border-top: 1px solid #ccc; border-bottom: 1px solid #ccc; padding: 0.5rem 0; text-align: right'> $logout_link - $display_name </p>";

            return  $extra_info . $content;
        }

        return $content;
    }

    /**
     * Function that creates a private page general information div below the content
     * @param $content the content of the private page
     * @return mixed
     */
    function cp_add_private_page_general_info_below_content( $content ){
        if ( !empty( $this->options['below-page-content'] ) && is_singular('private-page') && is_user_logged_in() ){
            $general_info = "<div class='cp-general-info-below-content' >".$this->options['below-page-content']."</div>";

            return  $content . $general_info;
        }

        return $content;
    }

    /**
     * Function that creates a shortcode which redirects the user to its private page
     * @param $atts the shortcode attributes
     */
    function cp_shortcode( $atts ){

        if( !is_user_logged_in() ){
            if( !empty( $this->options['portal-log-in-message'] ) )
                $message = $this->options['portal-log-in-message'];
            else
                $message = $this->defaults['portal-log-in-message'];

            return $message;
        }
        else{
            $user_id = get_current_user_id();
            $private_page_id = $this->cp_get_private_page_for_user( $user_id );
            if( $private_page_id ) {
                $private_page_link = get_permalink($private_page_id);
                $redirect = '<script>
                    window.location.replace("'. $private_page_link .'");
                </script>';
                return $redirect;
            }
        }
    }

    /**
     * Function that creates a shortcode which displays the users private page content
     */
    function cp_get_private_page_content( $user_settings ) {

        if ( !is_user_logged_in() ) {
            if ( !empty( $this->options['portal-log-in-message'] ))
                $message = $this->options['portal-log-in-message'];
            else $message = $this->defaults['portal-log-in-message'];

            return $message;
        }

        $settings = shortcode_atts( array(
            'content_above' => 'show',
            'content_default' => 'show',
            'content_below' => 'show'
        ), $user_settings );


        // Above content
        if ( $settings['content_above'] == 'show' ) {
            if ( !empty( $this->options['above-page-content'] ))
                $above_page_content = '<div class="cp-general-info-above-content" >' . $this->options['above-page-content'] . '</div>';
            else $above_page_content = $this->defaults['above-page-content'];
        } else $above_page_content = '';

        // Content
        if ( $settings['content_default'] == 'show' ) {
            $user_id = get_current_user_id();
            $private_page_id = $this->cp_get_private_page_for_user($user_id);
            $private_page = get_post( $private_page_id );
            $private_page_content = apply_filters( 'the_content', $private_page->post_content );

            if ( !empty( $private_page_content ))
                $page_content = $private_page_content;
            else $page_content = $this->defaults['default-page-content'];
        } else $page_content = '';

        // Below content
        if ( $settings['content_below'] == 'show' ) {
            if ( !empty( $this->options['below-page-content'] ))
                $below_page_content = '<div class="cp-general-info-below-content" >' . $this->options['below-page-content'] . '</div>';
            else $below_page_content = $this->defaults['below-page-content'];
        } else $below_page_content = '';

        return  $above_page_content . $page_content . $below_page_content;
    }

    /**
     * Function that creates the admin settings page under the Users menu
     */
    function cp_add_settings_page(){
        add_users_page( 'Client Portal Settings', 'Client Portal Settings', 'manage_options', 'client_portal_settings', array( $this, 'cp_settings_page_content' ) );
    }

    /**
     * Function that outputs the content for the settings page
     */
    function cp_settings_page_content(){
        /* if the user pressed the generate button then generate pages for existing users */
        if( !empty( $_GET[ 'cp_generate_for_all' ] ) && $_GET[ 'cp_generate_for_all' ] == true ){
            $this->cp_create_private_pages_for_all_users();
        }

        ?>
        <div class="wrap">

            <h2><?php _e( 'Client Portal Settings', 'client-portal'); ?></h2>

            <?php settings_errors(); ?>

            <div class="cl-grid">
                <div class="cl-grid-item">
                    <form method="POST" action="options.php">

                    <?php settings_fields( $this->slug ); ?>

                        <table class="form-table">
                        <tbody>
                            <tr class="scp-form-field-wrapper">
                                <th scope="row"><label class="scp-form-field-label" for="page-slug"><?php echo __( 'Page Slug' , 'client-portal' ) ?></label></th>
                                <td>
                                    <input type="text" class="regular-text" id="page-slug" name="cp-options[page-slug]" value="<?php echo ( isset( $this->options['page-slug'] ) ? esc_attr( $this->options['page-slug'] ) : 'private-page' ); ?>" />
                                    <p class="description"><?php echo __( 'The slug of the pages.', 'client-portal' ); ?></p>
                                </td>
                            </tr>

                            <tr class="scp-form-field-wrapper">
                                <th scope="row"><?php echo __( 'Support Comments' , 'client-portal' ) ?></th>
                                <td>
                                    <label><input type="radio" id="support-comments" name="cp-options[support-comments]" value="no"  <?php if( ( isset( $this->options['support-comments'] ) && $this->options['support-comments'] == 'no' ) || !isset( $this->options['support-comments'] ) ) echo 'checked="checked"' ?> /><?php _e( 'No', 'client-portal' ) ?></label><br />
                                    <label><input type="radio" id="support-comments" name="cp-options[support-comments]" value="yes" <?php if( isset( $this->options['support-comments'] ) && $this->options['support-comments'] == 'yes' ) echo 'checked="checked"' ?> /><?php _e( 'Yes', 'client-portal' ) ?></label>
                                    <p class="description"><?php echo __( 'Add comment support to the private page.', 'client-portal' ); ?></p>
                                </td>
                            </tr>

                            <?php
                            if ( file_exists( get_stylesheet_directory() . '/page.php' ) )
                                $template_directory = get_stylesheet_directory();
                            else $template_directory = get_template_directory();

                            if ( file_exists( $template_directory . '/page.php' ) ){
                                ?>
                                <tr class="scp-form-field-wrapper">
                                    <th scope="row"><?php echo __( 'Client Portal Template' , 'client-portal' ) ?></th>
                                    <td>
                                        <select id="load-template" name="cp-options[load-template]">
                                            <option value="post" <?php if( ( isset( $this->options[ 'load-template' ] ) && $this->options[ 'load-template' ] == 'post' ) || !isset( $this->options[ 'load-template' ] ) ) echo 'selected="selected"' ?> />Post<br />
                                            <option value="page" <?php if( isset( $this->options[ 'load-template' ] ) && $this->options[ 'load-template' ] == 'page' ) echo 'selected="selected"' ?> />Page
                                        </select>
                                        <p class="description"><?php echo __( 'Choose the template to be loaded.', 'client-portal' ); ?></p>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>

                            <tr class="scp-form-field-wrapper">
                                <th scope="row"><?php echo __( 'Generate pages' , 'client-portal' ) ?></th>
                                <td>
                                    <a class="button" href="<?php echo add_query_arg( 'cp_generate_for_all', 'true', admin_url("/users.php?page=client_portal_settings") ) ?>"><?php _e( 'Generate pages for existing users' ); ?></a>
                                    <p class="description"><?php echo __( 'Generate pages for already existing users.', 'client-portal' ); ?></p>
                                </td>
                            </tr>

                            <tr class="scp-form-field-wrapper">
                                <th scope="row"><?php echo __( 'View all private pages' , 'client-portal' ) ?></th>
                                <td>
                                    <a class="button" href="<?php echo admin_url("/edit.php?post_type=private-page"); ?>"><?php _e( 'View All Pages' ); ?></a>
                                    <p class="description"><?php echo __( 'Click here to view all the pages. You can access each private page under <a href="'. admin_url("/users.php") .'">All Users</a> (hover on the username and click <strong>Private Page</strong>)', 'client-portal' ); ?></p>
                                </td>
                            </tr>

                            <tr class="scp-form-field-wrapper">
                                <th scope="row"><?php echo __( 'Redirect Private Pages' , 'client-portal' ) ?></th>

                                <td>
                                    <select name="cp-options[redirect-private-pages]" class="wppb-select" id="redirect-private-pages" onchange="cp_display_redirect_url(this.value)">
                                        <?php if ( ! isset( $this->options['redirect-private-pages'] ) ) $this->options['redirect-private-pages'] = $this->defaults['redirect-private-pages']?>
                                        <option value="yes" <?php selected( $this->options['redirect-private-pages'], 'yes' ); ?>> <?php esc_html_e( 'Yes', 'client-portal' ); ?> </option>
                                        <option value="no" <?php selected( $this->options['redirect-private-pages'], 'no' ); ?>> <?php esc_html_e( 'No', 'client-portal' ); ?> </option>
                                    </select>
                                    <p class="description"><?php echo __( 'Redirect users that are trying to access a Private Page.', 'client-portal' ); ?></p>
                                </td>
                            </tr>

                            <tr class="scp-form-field-wrapper" id="redirect-private-pages-url">
                                <th scope="row"><?php echo __( 'Redirect Private Pages to' , 'client-portal' ) ?></th>
                                <td>
                                    <select name="cp-options[redirect-pp-to-page-id]">
                                        <option value="" <?php if ( empty( $this->options['redirect-private-pages'] ) ) echo 'selected'; ?>>Default</option>
                                        <optgroup label="<?php esc_html_e( 'Existing Pages', 'client-portal' ); ?>">
                                            <?php
                                            $pages = get_pages( array( 'sort_order' => 'ASC', 'sort_column' => 'post_title', 'post_type' => 'page', 'post_status' => array( 'publish' ) ) ) ;

                                            foreach ( $pages as $key => $value ){
                                                echo '<option value="'.esc_attr( $value->ID ).'"';
                                                if ( isset( $this->options['redirect-pp-to-page-id'] ) && $this->options['redirect-pp-to-page-id'] == $value->ID )
                                                    echo ' selected';

                                                echo '>' . esc_html( $value->post_title ) . '</option>';
                                            }
                                            ?>
                                        </optgroup>
                                    </select>
                                    <p class="description"><?php esc_html_e( 'Select where to redirect users trying to access a Private Page. DEFAULT is redirecting to HomePage.', 'client-portal' ); ?></p>
                                </td>
                            </tr>

                            <tr class="scp-form-field-wrapper">
                                <th scope="row"><label class="scp-form-field-label" for="restricted-message"><?php echo __( 'Restricted Message' , 'client-portal' ) ?></label></th>
                                <td>
                                    <textarea name="cp-options[restricted-message]" id="restricted-message" class="large-text" rows="4"><?php echo ( isset( $this->options['restricted-message'] ) ? esc_textarea( $this->options['restricted-message'] ) : $this->defaults['restricted-message'] ); ?></textarea>
                                    <p class="description"><?php echo __( 'The default message shown when users try to view a private page that they do not have access to.', 'client-portal' ); ?></p>
                                </td>
                            </tr>

                            <tr class="scp-form-field-wrapper">
                                <th scope="row"><label class="scp-form-field-label" for="portal-log-in-message"><?php echo __( 'Portal Log In Message' , 'client-portal' ) ?></label></th>
                                <td>
                                    <textarea name="cp-options[portal-log-in-message]" id="portal-log-in-message" class="large-text" rows="4"><?php echo ( isset( $this->options['portal-log-in-message'] ) ? esc_textarea( $this->options['portal-log-in-message'] ) : $this->defaults['portal-log-in-message'] ); ?></textarea>
                                    <p class="description"><?php echo __( 'The default message shown when users that are not logged in try to access their private pages.', 'client-portal' ); ?></p>
                                </td>
                            </tr>

                            <tr class="scp-form-field-wrapper">
                                <th scope="row"><label class="scp-form-field-label" for="above-page-content"><?php echo __( 'Above Page Content' , 'client-portal' ) ?></label></th>
                                <td>
                                    <?php wp_editor( ( isset( $this->options['above-page-content'] ) ? $this->options['above-page-content']  : $this->defaults['above-page-content'] ), 'above-page-content', array( 'textarea_name' => 'cp-options[above-page-content]', 'editor_height' => 250 ) ); ?>
                                    <p class="description"><?php echo __( 'To be shown above the content on all private pages.', 'client-portal' ); ?></p>
                                </td>
                            </tr>

                            <tr class="scp-form-field-wrapper">
                                <th scope="row"><label class="scp-form-field-label" for="default-page-content"><?php echo __( 'Default Page Content' , 'client-portal' ) ?></label></th>
                                <td>
                                    <?php wp_editor( ( isset( $this->options['default-page-content'] ) ?  $this->options['default-page-content']  : $this->defaults['default-page-content'] ), 'default-page-content', array( 'textarea_name' => 'cp-options[default-page-content]', 'editor_height' => 250 ) ); ?>
                                    <p class="description"><?php echo __( 'The default content on the private pages.', 'client-portal' ); ?></p>
                                </td>
                            </tr>

                            <tr class="scp-form-field-wrapper">
                                <th scope="row"><label class="scp-form-field-label" for="below-page-content"><?php echo __( 'Below Page Content' , 'client-portal' ) ?></label></th>
                                <td>
                                    <?php wp_editor( ( isset( $this->options['below-page-content'] ) ? $this->options['below-page-content'] : $this->defaults['below-page-content'] ), 'below-page-content', array( 'textarea_name' => 'cp-options[below-page-content]', 'editor_height' => 250 ) ); ?>
                                    <p class="description"><?php echo __( 'To be shown below the content on all private pages.', 'client-portal' ); ?></p>
                                </td>
                            </tr>

                            </tbody>
                        </table>

                        <?php submit_button( __( 'Save Settings', 'client_portal_settings' ) ); ?>

                    </form>
                </div>

                <div class="cl-grid-item pb-pitch">
                    <h2>Get the most out of Client Portal</h2>
                    <p>Add the <strong>[client-portal]</strong> shortcode to any page and when logged in user will access that page he will be redirected to its private page.</p>
                    <p>Add the <strong>[cp-private-page-content]</strong> shortcode to to any page and display the users private page content.<br>You can use the <em><strong>content_above</strong></em>, <em><strong>content_default</strong></em> and <em><strong>content_below</strong></em> parameters to hide content sections.<br><em>Example:<br>[cp-private-page-content content_below="hide"] - this hides the Content Below Section <em></p>
                    <p style="text-align: center;"><a href="https://wordpress.org/plugins/profile-builder/"><img src="<?php echo CP_URL; ?>assets/logo_landing_pb_2x_red.png" alt="Profile Builder Logo"/></a></p>
                    <p><strong>Client Portal</strong> was designed to work together with
                        <a href="https://wordpress.org/plugins/profile-builder/"><strong>Profile Builder</strong></a> so you can construct the client experience just as you need it.
                    </p>
                    <ul>
                        <li>add a login form with redirect <br/> <strong>[wppb-login redirect_url="http://www.yourdomain.com/page"]</strong></li>
                        <li>allow users to register <strong>[wppb-register]</strong></li>
                        <li>hide the WordPress admin bar for clients</li>
                    </ul>

                    <?php
                    if ( !empty ( $cp_install_pb_link = $this->cp_generate_pb_installation_link() ) ) {
                        echo '<div class="install-button-container""><a class="install-button" href="'. $cp_install_pb_link .'">Install & Activate - Profile Builder</a></div>';
                    }
                    elseif ( !empty ( $cp_activate_pb_link = $this->cp_generate_pb_activation_link() ) )
                        echo '<div class="install-button-container""><a class="install-button" href="'. $cp_activate_pb_link .'">Activate - Profile Builder</a></div>';
                    ?>

                </div>


            </div>

        </div>
    <?php
    }

    /**
     * Function that generates installation link for Profile Builder Free if Profile Builder (Free, Pro, Hobbyist) not installed
     */
    function cp_generate_pb_installation_link() {
        if ( !file_exists( WP_PLUGIN_DIR . '/profile-builder/index.php' ) && !file_exists( WP_PLUGIN_DIR . '/profile-builder-pro/index.php' ) && !file_exists( WP_PLUGIN_DIR . '/profile-builder-hobbyist/index.php' ) ) {
            $cp_install_pb_link = esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'install-plugin', 'plugin' => 'profile-builder' ), admin_url( 'update.php' )), 'install-plugin_profile-builder' ));
            return $cp_install_pb_link;
        }
    }

    /**
     * Function that generates activation link for Profile Builder if Profile Builder Free is installed but not active
     */
    function cp_generate_pb_activation_link() {
        if ( file_exists( WP_PLUGIN_DIR . '/profile-builder/index.php' ) && !is_plugin_active( 'profile-builder/index.php' ) && !is_plugin_active( 'profile-builder-pro/index.php' ) && !is_plugin_active( 'profile-builder-hobbyist/index.php' ) ) {
            $cp_activate_pb_link = esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'activate', 'plugin' => 'profile-builder/index.php' ), admin_url( 'plugins.php' )), 'activate-plugin_profile-builder/index.php' ));;
            return $cp_activate_pb_link;
        }
    }

    /**
     * Function that registers the settings for the settings page with the Settings API
     */
    public function cp_register_settings() {
        register_setting( $this->slug, $this->slug, array( 'sanitize_callback' => array( $this, 'cp_sanitize_options' ) ) );
    }

    /**
     * Function that sanitizes the options of the plugin
     * @param $options
     * @return mixed
     */
    function cp_sanitize_options( $options ){
        if( !empty( $options ) ){
            foreach( $options as $key => $value ){
                if( $key == 'page-slug' || $key == 'support-comments' )
                    $options[$key] = sanitize_text_field( $value );
                elseif( $key == 'restricted-message' || $key == 'portal-log-in-message' )
                    $options[$key] = wp_kses_post( $value );
            }
        }

        return $options;
    }

    /**
     * Function that creates the notice messages on the settings page
     */
    function cp_admin_notices(){
        if( !empty( $_GET['page'] ) && $_GET['page'] == 'client_portal_settings' ) {
            if( !empty( $_GET['cp_generate_for_all'] ) && $_GET['cp_generate_for_all'] == true ) {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e( 'Successfully generated private pages for existing users.', 'client-portal'); ?></p>
                </div>
                <?php
                if( !empty( $_REQUEST['settings-updated'] ) && $_GET['settings-updated'] == 'true' ) {
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php _e( 'Settings saved.', 'client-portal'); ?></p>
                    </div>
                <?php
                }
            }
        }
    }

    /**
     * Function that creates notification about Profile Builder and displays installation link
     */
    function cp_install_pb_admin_notice(){
        $diff_in_days = floor(( time() - get_option( 'cp_activation_date_time' ) ) / 86400 );

        if ( $diff_in_days >= 2 && $this->cp_display_pb_notices() != 'hide' ) {
            if ( !empty ( $cp_install_pb_link = $this->cp_generate_pb_installation_link() ) ) {
                ?>
                    <div class="notice notice-info">
                        <p><?php _e( '<strong>Client Portal</strong> was designed to work together with <strong>Profile Builder</strong> which lets you set up front-end login and registration forms (& more) for your clients. <a class="button-primary" style="margin-left: 30px;" href="'. $cp_install_pb_link .'">Install & Activate - Profile Builder</a> <a href="?cp-notice-pb-dismissed" style="float: right; text-decoration: none;">Dismiss</a>', 'client-portal'); ?></p>
                    </div>
                <?php
            }
            elseif ( !empty ( $cp_activate_pb_link = $this->cp_generate_pb_activation_link() ) ) {
                ?>
                <div class="notice notice-info">
                    <p><?php _e( '<strong><em>Profile Builder – User Profile & User Registration Forms</em></strong> plugin is currently <strong>inactive</strong>!<a class="button-primary" style="margin-left: 30px;" href="'. $cp_activate_pb_link .'">Activate - Profile Builder</a> <a href="?cp-notice-pb-dismissed" style="float: right; text-decoration: none;">Dismiss</a>', 'client-portal'); ?></p>
                </div>
                <?php
            }
        }

    }

    /**
     * Function that checks if PB notices should be displayed
     */
    function cp_display_pb_notices() {
        if ( ! get_option( 'cp_notice_pb_dimiss' ) && isset( $_GET['cp-notice-pb-dismissed'] ) )
            add_option( 'cp_notice_pb_dimiss', 'hide' );

        return get_option( 'cp_notice_pb_dimiss' );
    }

    /**
     * Function that enqueues the scripts on the admin settings page
     */
    function cp_enqueue_admin_scripts() {
        if( !empty( $_GET['page'] ) && $_GET['page'] == 'client_portal_settings' ) {
            wp_enqueue_style( 'cp_style-back-end', plugins_url( 'assets/style.css', __FILE__ ) );
            wp_enqueue_script( 'cp-manage-fields-live-change', plugins_url('assets/jquery-cp-settings.js', __FILE__) );
        }
	    if( !empty( $_GET['post'] ) && !empty( $_GET['action'] ) && $_GET['action'] == 'edit' )
		    wp_enqueue_style( 'cp_style-back-end-editor', plugins_url( 'assets/style-editor.css', __FILE__ ) );
    }

    /**
     * Function that flushes the rewrite rules when we save the settings page
     */
    function cp_flush_rules(){
        if( isset( $_GET['page'] ) && $_GET['page'] == 'client_portal_settings' && isset( $_REQUEST['settings-updated'] ) && $_REQUEST['settings-updated'] == 'true' ) {
            flush_rewrite_rules(false);
        }

        //this should happen only once after plugin activation where we set the flag to true
        if ( get_option( 'client_portal_flush_rewrite_rules_flag' ) ) {
            flush_rewrite_rules();
            delete_option( 'client_portal_flush_rewrite_rules_flag' );
        }
    }


    /**
     * Function that filters the WHERE clause in the select for adjacent posts so we exclude private pages
     * @param $where
     * @param $in_same_term
     * @param $excluded_terms
     * @param $taxonomy
     * @param $post
     * @return mixed
     */
    function cp_exclude_from_post_navigation( $where, $in_same_term, $excluded_terms, $taxonomy, $post ){
        if( $post->post_type == 'private-page' ){
            $where = str_replace( "'private-page'", "'do not show this'", $where );
        }
        return $where;
    }

    /**
     * Function that returns the id for the private page for the provided user
     * @param $user_id the user id for which we want to get teh private page for
     * @return mixed
     */
    function cp_get_private_page_for_user( $user_id ){
        $args = array(
            'author'            =>  $user_id,
            'posts_per_page'    =>  1,
            'post_type'         => 'private-page',
        );
        $users_private_pages = get_posts( $args );

        if( !empty( $users_private_pages ) ){
            foreach( $users_private_pages as $users_private_page ){
                return $users_private_page->ID;
                break;
            }
        }
        /* we don't have a page */
        return false;
    }

    /**
     * Function that returns all the private pages post objects
     * @return array
     */
    function cp_get_all_private_pages(){
        $args = array(
            'posts_per_page'    =>  -1,
            'numberposts'       =>   -1,
            'post_type'         => 'private-page',
        );

        $users_private_pages = get_posts( $args );
        return $users_private_pages;
    }

    /**
     * Function that creates a custom action in the Bulk Dropdown on the Users screen
     */
    function cp_create_private_page_bulk_actions(){
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function(){
                jQuery('<option>').val('create_private_page').text( '<?php _e( 'Create Private Page', 'client-portal' ) ?>').appendTo("select[name='action'], select[name='action2']");
            });
        </script>
    <?php
    }

    /**
     * Function that creates a private page for the selected users in the bulk action
     */
    function cp_create_private_pages_in_bulk(){
        if ( !empty( $_REQUEST['users'] ) && is_array( $_REQUEST['users'] ) ) {
            $users = array_map( 'absint', $_REQUEST['users'] );
            foreach( $users as $user_id ){
                $this->cp_create_private_page( $user_id );
            }
        }
    }

    /**
     *  Function that creates private pages for all existing users
     */
    function cp_create_private_pages_for_all_users(){
        $all_users = get_users( array(  'fields' => array( 'ID' ) ) );
        if( !empty( $all_users ) ){
            foreach( $all_users as $user ){
                $users_private_pages = $this->cp_get_private_page_for_user( $user->ID );
                if( !$users_private_pages ) {
                    $this->cp_create_private_page( $user->ID );
                }
            }
        }
    }

    /**
     *  Function that adds Settings link to Plugins listing
     */
    function cp_settings_link($links) {
        $url = get_admin_url() . 'users.php?page=client_portal_settings';
        $settings_link = '<a href="'.$url.'">' . __( 'Settings', 'client-portal' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

}

$CP_Object = new CL_Client_Portal();
