<?php
/**
 * i-groups-file-access.php
 *
 * Copyright (c) "kento" Karim Rahimpur www.itthinx.com
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
 * @package groups-file-access
 * @since groups-file-access 1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GFA base interface.
 */
interface I_Groups_File_Access {

	//
	// general
	//
	const PLUGIN_OPTIONS        = 'groups_file_access';
	const NONCE                 = 'gfa_nonce';
	const SET_ADMIN_OPTIONS     = 'set_admin_options';
	const FOLDERS               = 'folders';
	const LOGIN_REDIRECT        = 'login_redirect';
	const DELETE_DATA           = 'delete_data';
	const DELETE_DATA_ON_DEACTIVATE         = 'delete_data_on_deactivate';
	const NETWORK_DELETE_DATA_ON_DEACTIVATE = 'network_delete_data_on_deactivate';
	const DISMISS_HELP          = 'dismiss_help';
	const KEY                   = 'key';

	//
	// user profiles
	//
	const USER_PROFILE_SHOW_FOR_ADMINS = 'user_profile_show_for_admins';
	const USER_PROFILE_SHOW_SERVICE_KEY = 'user_profile_show_service_key';
	const USER_PROFILE_SHOW_FILES = 'user_profile_show_files';

	//
	// sanity
	//
	const SCHEMA_UPDATED        = 'schema_updated';
	const SCHEMA_VERSION        = 'schema_version';

	//
	// email notifications
	//
	const NOTIFY_ADMIN          = 'notify_admin';
	const NOTIFY_ADMIN_DEFAULT  = true;
	const ADMIN_EMAIL           = 'admin_email';
	const ADMIN_SUBJECT         = 'admin_subject';
	const ADMIN_DEFAULT_SUBJECT = "File ID [file_id] accessed by user ID [user_id] at [site_title]";
	const ADMIN_MESSAGE         = 'admin_message';
	const ADMIN_DEFAULT_MESSAGE =
"The file [file_path] with ID [file_id] has been accessed through [file_url] via [referrer] at <a href='[site_url]'>[site_title]</a> by the user [username] with ID [user_id] via the IP address [ip] at [datetime] GMT. Server IP address [server_ip].<br/>
<br/>
This is an automated confirmation message produced by the Groups File Access system.<br/>
<br/>
[site_title]<br/>
[site_url]<br/>
<br/>
Request URI : [request_uri]<br/>
Request data : [request]<br/>
";

	//
	// service
	//
	const SERVICE_ACTION_PROBE = 0;
	const SERVICE_ACTION_SERVE = 1;

	//
	// MIME types
	//
	const APPLY_MIME_TYPES         = 'apply_mime_types';
	const APPLY_MIME_TYPES_DEFAULT = true;

	//
	// session access
	//
	const SESSION_ACCESS         = 'session_access';
	const SESSION_ACCESS_DEFAULT = false;
	const SESSION_ACCESS_TIMEOUT = 'session_access_timeout';
	const SESSION_ACCESS_TIMEOUT_DEFAULT = 60;

	//
	// content disposition
	//
	const CONTENT_DISPOSITION            = 'content_disposition';
	const CONTENT_DISPOSITION_ATTACHMENT = 'attachment';
	const CONTENT_DISPOSITION_INLINE     = 'inline';
	const CONTENT_DISPOSITION_DEFAULT    = self::CONTENT_DISPOSITION_ATTACHMENT;

	//
	// Amazon S3
	//
	const AMAZON_S3                  = 'amazon-s3';
	const AMAZON_S3_DEFAULT          = false;
	const AMAZON_S3_ACCESS_KEY       = 'amazon-s3-access-key';
	const AMAZON_S3_SECRET_KEY       = 'amazon-s3-secret-key';
	const AMAZON_S3_EXPIRES          = 'amazon-s3-expires';
	const AMAZON_S3_EXPIRES_DEFAULT  = 5;
	const AMAZON_S3_EXPIRES_MIN      = 1;
	const AMAZON_S3_REDIRECT         = 'amazon-s3-redirect';
	const AMAZON_S3_REDIRECT_DEFAULT = false;
	const AMAZON_S3_DIRECT           = 'amazon-s3-direct';
	const AMAZON_S3_DIRECT_DEFAULT   = true;

}
