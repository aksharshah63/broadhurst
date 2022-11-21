<?php
/**
 * settings.php
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
 * @package groups-newsletters 1.0.0
 * @since groups-newsletters 1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( !current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Access denied.', 'groups-newsletters' ) );
}

$comment_statuses = array(
	'0' => __( 'Comments on stories must await moderation', 'groups-newsletters' ),
	'1' => __( 'Approve comments on stories', 'groups-newsletters' )
);
$comment_status_logics = array(
	''        => __( 'Apply the Comment Approval setting', 'groups-newsletters' ),
	'or'      => __( 'Discussion Settings or Comment Approval can approve', 'groups-newsletters' ),
	'and'     => __( 'Discussion Settings and Comment Approval must approve', 'groups-newsletters' ),
	'default' => __( 'Apply the Discussion Settings', 'groups-newsletters' )
);

$comment_registration_options = array(
	'0' => __( 'Anyone can comment on a story', 'groups-newsletters' ),
	'1' => __( 'Only registered users can comment on a story', 'groups-newsletters' ),
	'default' => __( 'Apply the Discussion Settings', 'groups-newsletters' )
);

// settings
if ( isset( $_POST['action'] ) && ( $_POST['action'] == 'set' ) && wp_verify_nonce( $_POST['groups-newsletters-settings'], 'admin' ) ) {

	Groups_Newsletters_Options::update_option( GROUPS_NEWSLETTERS_STORY_USE_BLOCK_EDITOR, !empty( $_POST[GROUPS_NEWSLETTERS_STORY_USE_BLOCK_EDITOR] ) );
	Groups_Newsletters_Options::update_option( GROUPS_NEWSLETTERS_SHOW_PROFILE_OPT_IN, !empty( $_POST[GROUPS_NEWSLETTERS_SHOW_PROFILE_OPT_IN] ) );
	Groups_Newsletters_Options::update_option( GROUPS_NEWSLETTERS_SHOW_REGISTRATION_OPT_IN, !empty( $_POST[GROUPS_NEWSLETTERS_SHOW_REGISTRATION_OPT_IN] ) );
	Groups_Newsletters_Options::update_option( GROUPS_NEWSLETTERS_REGISTRATION_OPT_IN_CHECKED, !empty( $_POST[GROUPS_NEWSLETTERS_REGISTRATION_OPT_IN_CHECKED] ) );

	Groups_Newsletters_Options::update_option( GROUPS_NEWSLETTERS_USE_SMTP, !empty( $_POST[GROUPS_NEWSLETTERS_USE_SMTP] ) );
	Groups_Newsletters_Options::update_option( GROUPS_NEWSLETTERS_SMTP_EMAIL, wp_strip_all_tags( trim( $_POST[GROUPS_NEWSLETTERS_SMTP_EMAIL] ) ) );
	Groups_Newsletters_Options::update_option( GROUPS_NEWSLETTERS_SMTP_NAME, wp_strip_all_tags( trim( $_POST[GROUPS_NEWSLETTERS_SMTP_NAME] ) ) );
	Groups_Newsletters_Options::update_option( GROUPS_NEWSLETTERS_SMTP_SET_RETURN_PATH, !empty( $_POST[GROUPS_NEWSLETTERS_SMTP_SET_RETURN_PATH] ) );
	Groups_Newsletters_Options::update_option( GROUPS_NEWSLETTERS_SMTP_HOST, wp_strip_all_tags( trim( $_POST[GROUPS_NEWSLETTERS_SMTP_HOST] ) ) );
	Groups_Newsletters_Options::update_option( GROUPS_NEWSLETTERS_SMTP_PORT, absint( trim( $_POST[GROUPS_NEWSLETTERS_SMTP_PORT] ) ) );
	Groups_Newsletters_Options::update_option( GROUPS_NEWSLETTERS_SMTP_ENCRYPTION, wp_strip_all_tags( trim( $_POST[GROUPS_NEWSLETTERS_SMTP_ENCRYPTION] ) ) );
	Groups_Newsletters_Options::update_option( GROUPS_NEWSLETTERS_SMTP_AUTHENTICATION, !empty( $_POST[GROUPS_NEWSLETTERS_SMTP_AUTHENTICATION] ) );
	Groups_Newsletters_Options::update_option( GROUPS_NEWSLETTERS_SMTP_USER, wp_strip_all_tags( trim( $_POST[GROUPS_NEWSLETTERS_SMTP_USER] ) ) );
	Groups_Newsletters_Options::update_option( GROUPS_NEWSLETTERS_SMTP_PASSWORD, wp_strip_all_tags( trim( $_POST[GROUPS_NEWSLETTERS_SMTP_PASSWORD] ) ) );

	if ( !empty( $_POST[GROUPS_NEWSLETTERS_WORK_CYCLE] ) ) {
		$minutes = round( $_POST[GROUPS_NEWSLETTERS_WORK_CYCLE], 2 );
		if ( $minutes > 0 ) {
			$seconds = intval( $minutes * 60 );
			if ( $seconds <= 0 ) {
				$seconds = 1;
			}
			Groups_Newsletters_Options::update_option( GROUPS_NEWSLETTERS_WORK_CYCLE, $seconds );
		}
	}

	if ( !empty( $_POST[GROUPS_NEWSLETTERS_LIMIT_PER_WORK_CYCLE] ) ) {
		$limit_per_work_cycle = intval( $_POST[GROUPS_NEWSLETTERS_LIMIT_PER_WORK_CYCLE] );
		if ( $limit_per_work_cycle > 0 ) {
			Groups_Newsletters_Options::update_option( GROUPS_NEWSLETTERS_LIMIT_PER_WORK_CYCLE, $limit_per_work_cycle );
		}
	}

	Groups_Newsletters_Options::update_option( 'exclude-from-search', !empty( $_POST['exclude_from_search'] ) );
	Groups_Newsletters_Options::update_option( 'exclude-from-comments', !empty( $_POST['exclude_from_comments'] ) );
	if ( key_exists( $_POST['comment_approved'], $comment_statuses ) ) {
		Groups_Newsletters_Options::update_option( 'default-comment-approved', $_POST['comment_approved'] );
	}
	if ( key_exists( $_POST['comment_status_logic'], $comment_status_logics ) ) {
		Groups_Newsletters_Options::update_option( 'default-comment-status-logic', $_POST['comment_status_logic'] );
	}
	if ( key_exists( $_POST['comment_registration'], $comment_registration_options ) ) {
		Groups_Newsletters_Options::update_option( 'comment-registration', $_POST['comment_registration'] );
	}

	Groups_Newsletters_Options::update_option( Groups_Newsletters::DELETE_DATA, !empty( $_POST['delete-data'] ) );

	echo
		'<p class="info">' .
		esc_html__( 'The settings have been saved.', 'groups-newsletters' ) .
		'</p>';
}

// Story Editors
if ( GROUPS_NEWSLETTERS_GROUPS_IS_ACTIVE ) {
	if ( isset( $_POST['action'] ) && ( $_POST['action'] == 'create' ) && wp_verify_nonce( $_POST['groups-newsletters-create'], 'admin' ) ) {
		$group_id = false;
		$group = Groups_Group::read_by_name( 'Story Editors' );
		if ( $group ) {
			$group_id = $group->group_id;
		} else {
			if ( $group_id = Groups_Group::create( array( 'name' => 'Story Editors' ) ) ) {
				echo
					'<p class="info">' .
					esc_html__( 'The Story Editors group has been created.', 'groups-newsletters' ) .
					'</p>';
			}
		}
		if ( $group_id ) {
			$assigned_capabilities = array();
			$caps = Groups_Newsletters_Story_Post_Type::get_capabilities();
			foreach ( $caps as $key => $capability ) {
				if ( $c = Groups_Capability::read_by_capability( $capability ) ) {
					if ( Groups_Group_Capability::create(
						array(
							'group_id'      => $group_id,
							'capability_id' => $c->capability_id
						)
					)
					) {
						$assigned_capabilities[] = $capability;
					}
				}
			}
			$n = count( $assigned_capabilities );
			printf( '<p class="%s">', $n > 0 ? 'info' : 'warning' );
			echo esc_html(
				sprintf(
					_n(
						'1 capability has been added to the Story Editors group.',
						'%d capabilities  have been added to the Story Editors group.',
						$n,
						'groups-newsletters'
					),
					$n
				)
			);
			echo '</p>';
		} else {
			echo
				'<p class="error">' .
				esc_html__( 'The Story Editors group could not be created.', 'groups-newsletters' ) .
				'</p>';
		}
	}
}

// Newsletters page
if ( isset( $_POST['action'] ) && ( $_POST['action'] == 'create_newsletters_page' ) && wp_verify_nonce( $_POST['groups-newsletters-create-newsletters-page'], 'admin' ) ) {
	$postarr = array(
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
		'post_content'   =>
'[groups_newsletters]
<h2>Search the newsletters</h2>
[groups_newsletters_search]
<h2>Popular story tags</h2>
[groups_newsletters_tags]',
		'post_status'    => 'publish',
		'post_title'     => __( 'Newsletters', 'groups-newsletters' ),
		'post_type'      => 'page'
	);
	$newsletters_page_id = wp_insert_post( $postarr );
	if ( $newsletters_page_id instanceof WP_Error ) {
		echo '<p class="error">';
		echo sprintf( esc_html__( 'A newsletters page could not be created. Error: %s', 'groups-newsletters' ), esc_html( $newsletters_page_id->get_error_message() ) );
		echo '</p>';
	} else {
		$page_link = '<a href="' . esc_url( get_permalink( $newsletters_page_id ) ) . '" target="_blank">' . esc_html( get_the_title( $newsletters_page_id ) ) . '</a>';
		echo '<p class="info">';
		echo sprintf( esc_html__( 'The %s page has been created.', 'groups-newsletters' ), $page_link );
		echo '</p>';
	}
}

// Activation page
if ( isset( $_POST['action'] ) && ( $_POST['action'] == 'create_activation_page' ) && wp_verify_nonce( $_POST['groups-newsletters-create-activation-page'], 'admin' ) ) {
	if ( $_POST['action'] == 'create_activation_page' ) {
		$postarr = array(
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'post_content'   =>
			'[groups_newsletters_activation][groups_newsletters_subscribe]',
			'post_status'    => 'publish',
			'post_title'     => __( 'Newsletter Subscription', 'groups-newsletters' ),
			'post_type'      => 'page'
		);
		$activation_page_id = wp_insert_post( $postarr );
		if ( $activation_page_id instanceof WP_Error ) {
			echo
			'<p class="error">' .
			__( sprintf( 'An activation page could not be created. Error: %s', $activation_page_id->get_error_message() ), 'groups-newsletters' ) .
			'</p>';
		} else {
			$page_link = '<a href="' . esc_url( get_permalink( $activation_page_id ) ) . '" target="_blank">' . esc_html( get_the_title( $activation_page_id ) ) . '</a>';
			echo
			'<p class="info">' .
			__( sprintf( 'The %s page has been created.', $page_link ), 'groups-newsletters' ) .
			'</p>';
			Groups_Newsletters_Options::update_option( 'activation-post-id', $activation_page_id );
		}
	}
}
if ( isset( $_POST['action'] ) && ( $_POST['action'] == 'save_activation_page' ) && wp_verify_nonce( $_POST['groups-newsletters-save-activation-page'], 'admin' ) ) {
	if ( !empty( $_POST['activation_post_id'] ) ) {
		Groups_Newsletters_Options::update_option( 'activation-post-id', intval( $_POST['activation_post_id'] ) );
	} else {
		Groups_Newsletters_Options::delete_option( 'activation-post-id' );
	}
}

$story_use_block_editor      = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_STORY_USE_BLOCK_EDITOR, true );
$show_profile_opt_in         = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SHOW_PROFILE_OPT_IN, true );
$show_registration_opt_in    = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SHOW_REGISTRATION_OPT_IN, true );
$registration_opt_in_checked = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_REGISTRATION_OPT_IN_CHECKED, true );

$use_smtp               = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_USE_SMTP, false );
$smtp_email             = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_EMAIL, '' );
$smtp_name              = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_NAME, 'newsletters' );
$smtp_set_return_path   = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_SET_RETURN_PATH, false );
$smtp_host              = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_HOST, '' );
$smtp_port              = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_PORT, '' );
$smtp_encryption        = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_ENCRYPTION, null );
$smtp_authentication    = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_AUTHENTICATION, true );
$smtp_user              = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_USER, '' );
$smtp_password          = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SMTP_PASSWORD, '' );

$work_cycle             = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_WORK_CYCLE, GROUPS_NEWSLETTERS_WORK_CYCLE_DEFAULT );
$limit_per_work_cycle   = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_LIMIT_PER_WORK_CYCLE, GROUPS_NEWSLETTERS_LIMIT_PER_WORK_CYCLE_DEFAULT );

$exclude_from_search    = Groups_Newsletters_Options::get_option( 'exclude-from-search', false );
$exclude_from_comments  = Groups_Newsletters_Options::get_option( 'exclude-from-comments', false );

$comment_approved       = Groups_Newsletters_Options::get_option( 'default-comment-approved', '0' );
$comment_status_logic   = Groups_Newsletters_Options::get_option( 'default-comment-status-logic', '' );
$comment_registration   = Groups_Newsletters_Options::get_option( 'comment-registration', '1' );
$delete_data            = Groups_Newsletters_Options::get_option( Groups_Newsletters::DELETE_DATA, Groups_Newsletters::DELETE_DATA_DEFAULT );

// test email
$email = '';
if ( isset( $_POST['test_email_action'] ) && ( $_POST['test_email_action'] == 'send' ) && wp_verify_nonce( $_POST['groups-newsletters-test-email'], 'admin' ) ) {
	if ( !empty( $_POST['test_email'] ) ) {
		$email = wp_strip_all_tags( $_POST['test_email'] );
		if ( is_email( $email ) ) {
			require_once GROUPS_NEWSLETTERS_CORE_LIB . '/class-groups-newsletters-mailer.php';
			$result = Groups_Newsletters_Mailer::test( $email );
			if ( empty( $result ) ) {
				$message = '<div style="background-color:#efe;padding:1em">' .
					sprintf( __( 'The test email has been sent to %s.', 'groups-newsletters' ), esc_html( $email ) ) .
					'</div>';
			} else {
				$message = '<div style="background-color:#fee;padding:1em">' .
					'<p>' .
					sprintf( __( 'Failed to send the test email to %s.', 'groups-newsletters' ), esc_html( $email ) ) .
					'</p>' .
					'<p>' .
					$result .
					'</p>' .
					'</div>';
			}
		} else {
			$message = '<div style="background-color:#ffe;padding:1em">' .
				sprintf( __( '%s is not a valid email address.', 'groups-newsletters' ), esc_html( $email ) ) .
				'</div>';
		}
	}
}
?>
<style type="text/css">
.groups-newsletters-settings-container {
	display: flex;
	flex-direction: row-reverse;
}
.test-email-container .test-email-panel {
	width: 360px;
	border: 1px solid #ccc;
	border-radius: 4px;
	margin: 8px !important;
	padding: 8px;
	background-color: #fefefe;
	overflow-wrap: break-word;
}
@media screen and (max-width: 800px) {
	.groups-newsletters-settings-container {
		flex-direction: column-reverse;
	}
	.test-email-container .test-email-panel {
		width: 62%;
	}
</style>
<div class="groups-newsletters-settings-container">
<div class="test-email-container">
<?php
echo '<div id="test_email_panel" class="test-email-panel">';
echo '<h3>' . esc_html__( 'Send test email', 'groups-newsletters' ) . '</h3>';
if ( isset( $message ) ) {
	echo stripslashes( wp_filter_post_kses( $message ) );
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
echo esc_html__( 'You can send a test email to the specified email address.', 'groups-newsletters' );
echo '</p>';
echo '</div>';
?>
</div><!-- #right-column -->
<div id="left-column" style="">
<div class="settings">
<form name="settings" method="post" action="">
<div>

<div class="buttons">
<input class="import button button-primary" type="submit" name="submit" value="<?php echo esc_attr__( 'Save', 'groups-newsletters' ); ?>" />
</div>
<?php
	echo '<h3>' . esc_html__( 'General', 'groups-newsletters' ) . '</h3>';

	echo '<p>';
	echo '<label>';
	printf( '<input type="checkbox" name="%s" %s />', GROUPS_NEWSLETTERS_STORY_USE_BLOCK_EDITOR, $story_use_block_editor ? ' checked="checked" ' : '' );
	echo ' ';
	echo esc_html__( 'Enable the block editor to edit stories', 'groups-newsletters' );
	echo '</label>';
	echo '</p>';
	echo '<p class="description">';
	echo esc_html__( 'Disable this option if you do not want to use the block editor to edit stories.', 'groups-newsletters' );
	echo '</p>';

	echo '<h3>' . esc_html__( 'Opt-in Settings', 'groups-newsletters' ) . '</h3>';

	echo '<p>';
	echo '<label>';
	printf( '<input type="checkbox" name="%s" %s />', GROUPS_NEWSLETTERS_SHOW_PROFILE_OPT_IN, $show_profile_opt_in ? ' checked="checked" ' : '' );
	echo ' ';
	echo esc_html__( 'Show in user profile', 'groups-newsletters' );
	echo '</p>';
	echo '</label>';
	echo '<p class="description">';
	echo esc_html__( 'Allow to opt in or out on receiving newsletters on the user profile page.', 'groups-newsletters' );
	echo '</p>';

	echo '<p>';
	echo '<label>';
	printf( '<input type="checkbox" name="%s" %s />', GROUPS_NEWSLETTERS_SHOW_REGISTRATION_OPT_IN, $show_registration_opt_in ? ' checked="checked" ' : '' );
	echo ' ';
	echo esc_html__( 'Show on registration form', 'groups-newsletters' );
	echo '</p>';
	echo '</label>';
	echo '<p class="description">';
	echo esc_html__( 'Allow to opt in on receiving newsletters on the registration form.', 'groups-newsletters' );
	echo '</p>';

	echo '<p>';
	echo '<label>';
	printf( '<input type="checkbox" name="%s" %s />', GROUPS_NEWSLETTERS_REGISTRATION_OPT_IN_CHECKED, $registration_opt_in_checked ? ' checked="checked" ' : '' );
	echo ' ';
	echo esc_html__( 'Opt in is checked by default', 'groups-newsletters' );
	echo '</p>';
	echo '</label>';
	echo '<p class="description">';
	echo esc_html__( 'On the registration form, the opt-in for receiving newsletters is checked by default.', 'groups-newsletters' );
	echo '</p>';

	echo '<h3>' . esc_html__( 'SMTP Email Settings', 'groups-newsletters' ) . '</h3>';
	echo '<p>';
	echo esc_html__( 'If SMTP is enabled, these settings will only be used to send newsletters.', 'groups-newsletters' );
	echo '<br/>';
	echo esc_html__( 'Otherwise, the email settings for the site will be used.', 'groups-newsletters' );
	echo '</p>';

	echo '<p>';
	echo '<label>';
	printf( '<input type="checkbox" name="%s" %s />', GROUPS_NEWSLETTERS_USE_SMTP, $use_smtp ? ' checked="checked" ' : '' );
	echo ' ';
	echo esc_html__( 'Enable SMTP', 'groups-newsletters' );
	echo '</label>';
	echo '</p>';
	echo '<p class="description">';
	echo esc_html__( 'Send newsletters using these settings', 'groups-newsletters' );
	echo '</p>';

	echo '<p>';
	echo '<label>';
	echo wp_kses(
		__( '<em>From</em> Email Address', 'groups-newsletters' ),
		array(
			'em' => array()
		)
	);
	echo ' ';
	printf( '<input type="text" name="%s" value="%s" />', GROUPS_NEWSLETTERS_SMTP_EMAIL, esc_attr( $smtp_email ) );
	echo '</label>';
	echo '</p>';

	echo '<p>';
	echo '<label>';
	echo wp_kses(
		__( '<em>From</em> Name', 'groups-newsletters' ),
		array(
			'em' => array()
		)
	);
	echo ' ';
	printf( '<input type="text" name="%s" value="%s" />', GROUPS_NEWSLETTERS_SMTP_NAME, esc_attr( $smtp_name ) );
	echo '</label>';
	echo '</p>';

	echo '<p>';
	echo '<label>';
	printf( '<input type="checkbox" name="%s" %s />', GROUPS_NEWSLETTERS_SMTP_SET_RETURN_PATH, $smtp_set_return_path ? ' checked="checked" ' : '' );
	echo ' ';
	echo wp_kses(
		__( 'Use the <em>From</em> Email Address as the return path.', 'groups-newsletters' ),
		array(
			'em' => array()
		)
	);
	echo '</label>';
	echo '</p>';

	echo '<p>';
	echo '<label>';
	echo esc_html__( 'SMTP Host', 'groups-newsletters' );
	echo ' ';
	printf( '<input type="text" name="%s" value="%s" />', GROUPS_NEWSLETTERS_SMTP_HOST, esc_attr( $smtp_host ) );
	echo '</label>';
	echo '</p>';

	echo '<p>';
	echo '<label>';
	echo esc_html__( 'SMTP Port', 'groups-newsletters' );
	echo ' ';
	printf( '<input type="text" name="%s" value="%s" />', GROUPS_NEWSLETTERS_SMTP_PORT, esc_attr( $smtp_port ) );
	echo '</label>';
	echo '</p>';

	echo '<p>';
	echo '<label>';
	echo esc_html__( 'Encryption', 'groups-newsletters' );
	echo ' ';
	printf( '<select name="%s">', GROUPS_NEWSLETTERS_SMTP_ENCRYPTION );
	printf( '<option value="" %s>%s</option>', empty( $smtp_encryption ) ? ' selected="selected" ' : '', esc_html__( 'None', 'groups-newsletters' ) );
	printf( '<option value="ssl" %s>%s</option>', $smtp_encryption == 'ssl' ? ' selected="selected" ' : '', esc_html__( 'SSL', 'groups-newsletters' ) );
	printf( '<option value="tls" %s>%s</option>', $smtp_encryption == 'tls' ? ' selected="selected" ' : '', esc_html__( 'TLS', 'groups-newsletters' ) );
	echo '</select>';
	echo '</label>';
	echo '</p>';

	echo '<p>';
	echo '<label>';
	printf( '<input type="checkbox" name="%s" %s />', GROUPS_NEWSLETTERS_SMTP_AUTHENTICATION, $smtp_authentication ? ' checked="checked" ' : '' );
	echo ' ';
	echo esc_html__( 'SMTP Authentication', 'groups-newsletters' );
	echo '</label>';
	echo '</p>';
	echo '<p class="description">';
	echo wp_kses(
		__( 'Do SMTP authentication with the <em>Username</em> and <em>Password</em> provided:', 'groups-newsletters' ),
		array(
			'em' => array()
		)
	);
	echo '</p>';

	echo '<p>';
	echo '<label>';
	echo esc_html__( 'Username', 'groups-newsletters' );
	echo ' ';
	printf( '<input type="text" name="%s" value="%s" />', GROUPS_NEWSLETTERS_SMTP_USER, esc_attr( $smtp_user ) );
	echo '</label>';
	echo '</p>';

	echo '<p>';
	echo '<label>';
	echo esc_html__( 'Password', 'groups-newsletters' );
	echo ' ';
	printf( '<input type="text" name="%s" value="%s" />', GROUPS_NEWSLETTERS_SMTP_PASSWORD, esc_attr( $smtp_password ) );
	echo '</label>';
	echo '</p>';

	echo '<h3>' . esc_html__( 'Batch Sending', 'groups-newsletters' ) . '</h3>';
?>
<p>
<label>
<?php
	$minutes = round( $work_cycle / 60, 2 );
	echo esc_html__( 'Cycle: ', 'groups-newsletters' );
	echo ' ';
	if ( intval( $minutes ) != $minutes ) {
		echo sprintf( '<input type="text" name="%s" value="%.2f" style="text-align:right; width:4em;"/>', GROUPS_NEWSLETTERS_WORK_CYCLE, $minutes );
	} else {
		echo sprintf( '<input type="text" name="%s" value="%d" style="text-align:right; width:4em;"/>', GROUPS_NEWSLETTERS_WORK_CYCLE, $minutes );
	}
	echo ' ';
	echo '<span class="description">' . esc_html( _n( 'Minute', 'Minutes', $minutes, 'groups-newsletters' ) ) . '</span>';
?>
</label>
</p>
<p>
<label>
<?php
	echo esc_html__( 'Limit per Cycle: ', 'groups-newsletters' );
	echo ' ';
	echo sprintf( '<input type="text" name="%s" value="%d" style="text-align:right; width:4em;"/>', GROUPS_NEWSLETTERS_LIMIT_PER_WORK_CYCLE, intval( $limit_per_work_cycle ) );
	echo ' ';
	echo '<span class="description">' . esc_html( _n( 'Email', 'Emails', $limit_per_work_cycle, 'groups-newsletters' ) ) . '</span>';
?>
</label>
</p>
<p class="description">
<?php
	$hourly_limit = ( 60 / $minutes ) * $limit_per_work_cycle;
	$time_for_thousand = round( 1000 / $hourly_limit, 0 );
	echo esc_html__( 'Newsletter emails are sent out in batches every cycle.', 'groups-newsletters' );
	echo ' ';
	echo esc_html__( 'The settings impose an upper limit to the amount of emails that can be sent per hour.', 'groups-newsletters' );
	echo ' ';
	if ( intval( $minutes ) != $minutes ) {
		echo sprintf( esc_html__( 'The current settings of %d emails every %.2f minutes allow for a maximum of %d emails to be sent per hour.', 'groups-newsletters' ), $limit_per_work_cycle, $minutes, $hourly_limit );
	} else {
		echo sprintf( esc_html__( 'The current settings of %d emails every %d minutes allow for a maximum of %d emails to be sent per hour.', 'groups-newsletters' ), $limit_per_work_cycle, $minutes, $hourly_limit );
	}
	echo ' ';
	echo sprintf( esc_html__( 'With these settings, it will take around %d hours to send out one newsletter to 1000 recipients.', 'groups-newsletters' ), $time_for_thousand );
	echo ' ';
	echo esc_html__( 'It is important to note that the number of emails that are sent should not exceed your server capacity or the limits imposed by your email service provider.', 'groups-newsletters' );
?>
</p>
<div class="separator"></div>

<?php
	echo '<h3>' . esc_html__( 'Search', 'groups-newsletters' ) . '</h3>';
?>
<label>
<?php
	echo sprintf( '<input type="checkbox" name="exclude_from_search" %s />', $exclude_from_search ? ' checked="checked" ' : '' );
	echo ' ';
	echo esc_html__( 'Exclude stories from normal search results', 'groups-newsletters' );
?>
</label>
<p class="description">
<?php
	echo esc_html__( 'Stories are included in search results by default. Activate this option to exclude them.', 'groups-newsletters' );
?>
</p>
<div class="separator"></div>

<label>
<?php
	echo sprintf( '<input type="checkbox" name="exclude_from_comments" %s />', $exclude_from_comments ? ' checked="checked" ' : '' );
	echo ' ';
	echo esc_html__( 'Exclude comments on stories', 'groups-newsletters' );
?>
</label>
<p class="description">
<?php
	echo esc_html__( 'Comments on stories are included in results for comments in general by default. Activate this option to exclude them.', 'groups-newsletters' );
?>
</p>
<div class="separator"></div>

<?php
	echo '<h3>' . esc_html__( 'Story Comments', 'groups-newsletters' ) . '</h3>';
?>
<label>
<?php
	echo esc_html__( 'Comment Approval', 'groups-newsletters' );
	echo ' ';
?>
<select name="comment_approved">
<?php
	foreach ( $comment_statuses as $key => $name ) {
		echo sprintf( '<option value="%s" %s>%s</option>', esc_attr( $key ), $comment_approved == $key ? ' selected="selected" ' : '', esc_html( $name ) );
	}
?>
</select>
</label>
<p class="description">
<?php
	echo esc_html__( 'The default comment status when submitted by users on the front end.', 'groups-newsletters' );
?>
</p>

<div class="separator"></div>

<label>
<?php
	echo esc_html__( 'Approval Logic', 'groups-newsletters' );
	echo ' ';
?>
<select name="comment_status_logic">
<?php
	foreach ( $comment_status_logics as $key => $name ) {
		echo sprintf( '<option value="%s" %s>%s</option>', esc_attr( $key ), $comment_status_logic == $key ? ' selected="selected" ' : '', esc_html( $name ) );
	}
?>
</select>
</label>
<p class="description">
<?php
	echo esc_html__( 'Comment approval for stories is based on this setting.', 'groups-newsletters' );
?>
</p>

<div class="separator"></div>

<label>
<?php
	echo esc_html__( 'Comment Registration', 'groups-newsletters' );
	echo ' ';
?>
<select name="comment_registration">
<?php
	foreach ( $comment_registration_options as $key => $name ) {
		echo sprintf( '<option value="%s" %s>%s</option>', esc_attr( $key ), $comment_registration == $key ? ' selected="selected" ' : '', esc_html( $name ) );
	}
?>
</select>
</label>
<p class="description">
<?php
	echo esc_html__( 'The registration requirements for posting on stories.', 'groups-newsletters' );
?>
</p>

<div class="separator"></div>

<?php
	echo
		'<h3>' . esc_html__( 'Deleting all settings and data', 'groups-newsletters' ) . '</h3>' .
		'<p>' .
		'<label>' .
		'<input name="delete-data" type="checkbox" ' . ( $delete_data ? 'checked="checked"' : '' ) . '/>' .
		' ' .
		esc_html__( 'Delete data on plugin deactivation', 'groups-newsletters' ) .
		'</label>' .
		'</p>' .
		'<p class="description warning">' .
		esc_html__( 'CAUTION: If this option is active while the plugin is deactivated, ALL of this plugin\'s settings and data will be DELETED.', 'groups-newsletters' ) .
		' ' .
		wp_kses(
			__( 'This will <strong>DELETE ALL stories, newsletters and campaigns</strong> when the plugin is deactivated and can NOT be undone.', 'groups-newsletters' ),
			array(
				'strong' => array()
			)
		) .
		' ' .
		wp_kses(
			__( 'If you are going to use this option, make a backup <strong>NOW</strong>.', 'groups-newsletters' ),
			array(
				'strong' => array()
			)
		) .
		' ' .
		esc_html__( 'By enabling this option you agree to be solely responsible for any loss of data or any other consequences thereof.', 'groups-newsletters' ) .
		'</p>';
?>

<div class="separator"></div>

<?php wp_nonce_field( 'admin', 'groups-newsletters-settings', true, true ); ?>

<div class="buttons">
<input class="import button" type="submit" name="submit" value="<?php echo esc_attr__( 'Save', 'groups-newsletters' ); ?>" />
<input type="hidden" name="action" value="set" />
</div>

</div>
</form>
</div>

<div class="separator strong"></div>

<div class="generator">
<form name="create" method="post" action="">
<div>
<br/>
<?php
	echo '<h2>' . esc_html__( 'Story Editors', 'groups-newsletters' ) . '</h2>';
	if ( GROUPS_NEWSLETTERS_GROUPS_IS_ACTIVE ) {
		echo '<p>';
		echo wp_kses(
			__( 'A <em>Story Editors</em> group with full capabilities to manage stories can be created here:', 'groups-newsletters' ),
			array(
				'em' => array()
			)
		);
		echo '</p>';
		echo '<ul>';
		echo '<li>';
		echo esc_html__( 'The group will have all capabilities needed to manage newsletters and stories.', 'groups-newsletters' );
		echo '</li>';
		echo '<li>';
		echo wp_kses(
			__( 'Users who should be allowed to manage newsletters and stories, can be added to the <em>Story Editors</em> group.', 'groups-newsletters' ),
			array(
				'em' => array()
			)
		);
		echo '</li>';
		echo '<li>';
		echo wp_kses(
			__( 'You should only add fully trusted users to the <em>Story Editors</em> group, as members of the group can publish stories and send out newsletters.', 'groups-newsletters' ),
			array(
				'em' => array()
			)
		);
		echo '</li>';
		echo '</ul>';

		echo '<p>';
		if ( !Groups_Group::read_by_name( 'Story Editors' ) ) {
			echo wp_kses(
				__( 'Press the button to create the <em>Story Editors</em> group now.', 'groups-newsletters' ),
				array(
					'em' => array()
				)
			);
		} else {
			echo wp_kses(
				__( 'A group named <em>Story Editors</em> already exists. Press the button to assign all story capabilities to this group now.', 'groups-newsletters' ),
				array(
					'em' => array()
				)
			);
		}
		echo '</p>';
	} else {
		echo '<p>' .
			wp_kses(
				__( 'The <a href="https://www.itthinx.com/plugins/groups/">Groups</a> plugin is not activated or missing.', 'groups-newsletters' ),
				array(
					'a' => array(
						'href' => array()
					)
				)
			) .
			'</p>';
		echo '<p>';
		echo wp_kses(
			__( 'When <em>Groups</em> is installed, a <em>Story Editors</em> group with full capabilities to manage stories can be created here.', 'groups-newsletters' ),
			array(
				'em' => array()
			)
		);
		echo '</p>';
	}
?>
<?php wp_nonce_field( 'admin', 'groups-newsletters-create', true, true ); ?>
<div class="buttons">
<?php if ( GROUPS_NEWSLETTERS_GROUPS_IS_ACTIVE ) : ?>
<input class="create button" type="submit" name="submit" value="<?php echo esc_attr__( 'Create', 'groups-newsletters' ); ?>" />
<input type="hidden" name="action" value="create" />
<?php endif; ?>
</div>
</div>
</form>
</div>

<div class="separator strong"></div>

<div class="generator">
<form name="create-newsletters-page" method="post" action="">
<div>
<br/>
<?php
	echo '<h2>' . esc_html__( 'Newsletters Page', 'groups-newsletters' ) . '</h2>';
	echo '<p>';
	echo esc_html__( 'This will create a page where all newsletters are listed, along with a newsletter search form and a story tag cloud.', 'groups-newsletters' );
	echo '</p>';
	echo '<p>';
	echo esc_html__( 'Shortcodes are used to render these and you can customize the page as desired.', 'groups-newsletters' );
	echo '</p>';

	echo '<p>';
	echo wp_kses(
		__( 'Press the button to create a <em>Newsletters</em> page now.', 'groups-newsletters' ),
		array(
			'em' => array()
		)
	);
	echo '</p>';
?>
<?php wp_nonce_field( 'admin', 'groups-newsletters-create-newsletters-page', true, true ); ?>
<div class="buttons">
<input class="create button" type="submit" name="submit" value="<?php echo esc_attr__( 'Create', 'groups-newsletters' ); ?>" />
<input type="hidden" name="action" value="create_newsletters_page" />
</div>
</div>
</form>
</div>

<div class="separator strong"></div>

<div class="generator">
<form name="save-newsletters-page" method="post" action="">
<div>
<br/>
<?php
	$activation_post_id = Groups_Newsletters_Options::get_option( 'activation-post-id', null );

	global $wpdb;
	$post_options = '';
	$post_ids = array();
	$posts = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_content LIKE '%[groups_newsletters_activation]%' AND post_status = 'publish'" );
	foreach ( $posts as $post ) {
		$post_title = get_the_title( $post->ID );
		$post_options .= sprintf( '<option value="%d" %s>%s</option>', esc_attr( $post->ID ), $activation_post_id == $post->ID ? ' selected="selected" ' : '', esc_html( $post_title ) );
		$post_ids[] = $post->ID;
	}
	$no_post = empty( $activation_post_id ) || !in_array( $activation_post_id, $post_ids );
?>
<?php
	echo '<h2>' . esc_html__( 'Subscription Activation Page', 'groups-newsletters' ) . '</h2>';
?>

<div class="<?php echo $no_post ? 'warning' : ''; ?>">

<label>
<?php
	echo esc_html__( 'Page', 'groups-newsletters' );
	echo ' ';
?>
<select name="activation_post_id">
<option value="" <?php echo empty( $activation_post_id ) ? ' selected="selected" ' : ''; ?>></option>
<?php
	echo $post_options;
?>
</select>
</label>

<p class="description">
<?php
	echo wp_kses(
		__( 'The page where visitors can subscribe and get redirected to after activating or cancelling a newsletter subscription. Create and select a page that contains the <code>[groups_newsletters_activation]</code> shortcode. If the page only contains that shortcode, they will not be able to subscribe on that page and you should provide the <code>[groups_newsletters_subscribe]</code> shortcode on another page or add a widget to let visitors subscribe.', 'groups-newsletters' ),
		array(
			'code' => array()
		)
	);
?>
</p>
<?php wp_nonce_field( 'admin', 'groups-newsletters-save-activation-page', true, true ); ?>
<div class="buttons">
<input class="save button" type="submit" name="submit" value="<?php echo esc_attr__( 'Save', 'groups-newsletters' ); ?>" />
<input type="hidden" name="action" value="save_activation_page" />
</div>
</div>

</div>
</form>

<?php
	if ( count( $posts ) == 0 ) {
		echo '<p>';
		echo wp_kses(
			__( 'It seems that you do not have any page with the <code>[groups_newsletters_subscribe]</code> and <code>[groups_newsletters_activation]</code> shortcodes. It is advisable to have such a page, so that users can subscribe and get feedback after activating or cancelling their newsletter subscription.', 'groups-newsletters' ),
			array(
				'code' => array()
			)
		);
		echo '</p>';
		?>
		<form name="create-newsletters-page" method="post" action="">
		<div>
		<?php
		wp_nonce_field( 'admin', 'groups-newsletters-create-activation-page', true, true );
		?>
		<div class="buttons">
		<input class="create button" type="submit" name="submit" value="<?php echo esc_attr__( 'Create', 'groups-newsletters' ); ?>" />
		<input type="hidden" name="action" value="create_activation_page" />
		<?php echo ' <span class="description">' . esc_html__( 'Click to create a page where visitors can subscribe and see the status of their newsletter subscription.', 'groups-newsletters' ) . '</span>'; ?>
		</div>
		</div>
		</form>
		<?php
	}
?>
</div>

<div class="separator strong"></div>

<div class="generator">
<form name="export-subscribers" method="post" action="">
<div>
<br/>
<?php

	echo '<h2>' . esc_html__( 'Export Subscribers', 'groups-newsletters' ) . '</h2>';
	echo '<p>';
	echo esc_html__( 'This will create a text file with subscriber data.', 'groups-newsletters' );
	echo '</p>';

	echo '<p>';
	echo esc_html__( 'Format:', 'groups-newsletters' );
	echo ' ';
	echo sprintf( '<label title="%s">', esc_html__( 'Comma-separated values', 'groups-newsletters' ) );
	echo '<input type="radio" name="format" value="csv" />';
	echo ' ';
	echo esc_html__( 'CSV', 'groups-newsletters' );
	echo '</label>';
	echo ' ';
	echo sprintf( '<label title="%s">', esc_html__( 'Separate values by tabs', 'groups-newsletters' ) );
	echo '<input type="radio" name="format" value="tab" checked="checked" />';
	echo ' ';
	echo esc_html__( 'Tabs', 'groups-newsletters' );
	echo '</label>';
	echo '</p>';

	$active   = !isset( $_REQUEST['submit'] ) || isset( $_REQUEST['status'] ) && is_array( $_REQUEST['status'] ) && in_array( '1', $_REQUEST['status'] );
	$inactive = isset( $_REQUEST['submit'] ) && isset( $_REQUEST['status'] ) && is_array( $_REQUEST['status'] ) && in_array( '0', $_REQUEST['status'] );

	echo '<p>';
	echo '<label>';
	printf( '<input type="checkbox" name="status[]" value="1" %s />', $active ? ' checked="checked" ' : '' );
	echo ' ';
	echo esc_html__( 'Include activated subscribers', 'groups-newsletters' );
	echo '</p>';

	echo '<p>';
	echo '<label>';
	printf( '<input type="checkbox" name="status[]" value="0" %s />', $inactive ? ' checked="checked" ': '' );
	echo ' ';
	echo esc_html__( 'Include unconfirmed subscribers', 'groups-newsletters' );
	echo '</p>';

	$subscribers = isset( $_REQUEST['submit'] ) ? !empty( $_REQUEST['subscribers'] ) : true;
	echo '<p>';
	echo '<label>';
	printf( '<input type="checkbox" name="subscribers" value="1" %s />', $subscribers ? ' checked="checked" ' : '' );
	echo ' ';
	echo esc_html__( 'Include subscribers without a user account', 'groups-newsletters' );
	echo '</p>';

	$users = isset( $_REQUEST['submit'] ) ? !empty( $_REQUEST['users'] ) : true;
	echo '<p>';
	echo '<label>';
	printf( '<input type="checkbox" name="users" value="1" %s />', $users ? ' checked="checked" ' : '' );
	echo ' ';
	echo esc_html__( 'Include subscribed user accounts', 'groups-newsletters' );
	echo '</p>';
?>
<?php wp_nonce_field( 'export', 'groups-newsletters-export', true, true ); ?>
<div class="buttons">
<input class="export button" type="submit" name="submit" value="<?php echo esc_attr__( 'Export', 'groups-newsletters' ); ?>" />
<input type="hidden" name="action" value="export_subscribers" />
</div>
</div>
</form>
</div>


<div class="separator strong"></div>

<div class="generator">
<form enctype="multipart/form-data" name="import-subscribers" method="post" action="">
<div>
<br/>
<?php

	echo '<h2>' . esc_html__( 'Import Subscribers', 'groups-newsletters' ) . '</h2>';

	echo '<p>';
	echo esc_html__( 'Import subscriber data from a text file.', 'groups-newsletters' );
	echo '</p>';

	echo '<p>';
	echo esc_html__( 'The accepted format is values separated by tabs, email address in the first column, optionally the date subscribed in the second column as YYYY-MM-DD HH:MM:SS (if not given or the format is invalid, the current date will be used) and optionally the subscription status in the third column (0 for Inactive, 1 for Active). All subscribers will be marked as activated by default (when the status is omitted). No new entries will be created for email addresses that are already subscribed or assigned to a user account.', 'groups-newsletters' );
	echo '</p>';

	echo '<p>';
	echo esc_html__( 'An import can not be undone, when in doubt, run the import on a test installation first.', 'groups-newsletters' );
	echo '</p>';

	$subscribe_users = isset( $_REQUEST['submit'] ) ? !empty( $_REQUEST['subscribe_users'] ) : false;
	echo '<p>';
	echo '<label>';
	printf( '<input type="checkbox" name="subscribe_users" value="1" %s />', $subscribe_users ? ' checked="checked" ' : '' );
	echo ' ';
	echo esc_html__( 'Subscribe existing user accounts', 'groups-newsletters' );
	echo '</p>';

// 	echo '<p>';
// 	echo esc_html__( 'Format:', 'groups-newsletters' );
// 	echo ' ';
// 	echo sprintf( '<label title="%s">', esc_html__( 'Comma-separated values', 'groups-newsletters' ) );
// 	echo '<input type="radio" name="format" value="csv" />';
// 	echo ' ';
// 	echo esc_html__( 'CSV', 'groups-newsletters' );
// 	echo '</label>';
// 	echo ' ';
// 	echo sprintf( '<label title="%s">', esc_html__( 'Separate values by tabs', 'groups-newsletters' ) );
// 	echo '<input type="radio" name="format" value="tab" checked="checked" />';
// 	echo ' ';
// 	echo esc_html__( 'Tabs', 'groups-newsletters' );
// 	echo '</label>';
// 	echo '</p>';

// 	echo '<p>';
// 	echo '<label>';
// 	echo '<input type="checkbox" name="status[]" value="1" checked="checked" />';
// 	echo ' ';
// 	echo esc_html__( 'Include activated subscribers', 'groups-newsletters' );
// 	echo '</p>';

// 	echo '<p>';
// 	echo '<label>';
// 	echo '<input type="checkbox" name="status[]" value="0" />';
// 	echo ' ';
// 	echo esc_html__( 'Include unconfirmed entries', 'groups-newsletters' );
// 	echo '</p>';
?>
<?php wp_nonce_field( 'import', 'groups-newsletters-import', true, true ); ?>
<div class="buttons">
<input type="file" name="file" /> <input class="import button" type="submit" name="submit" value="<?php echo esc_attr__( 'Import', 'groups-newsletters' ); ?>" />
<input type="hidden" name="action" value="import_subscribers" />
</div>
</div>
</form>
</div>
<div class="separator strong"></div>
</div><!-- #left-column -->

</div><!-- grid -->