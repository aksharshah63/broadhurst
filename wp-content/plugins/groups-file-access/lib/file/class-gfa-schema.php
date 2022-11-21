<?php
/**
 * class-gfa-schema.php
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
 * GFA schema definition.
 */
class GFA_Schema {

	/**
	 * Schema version.
	 *
	 * @var integer
	 */
	private static $version = 3;

	// Previously description and path had DEFAULT '' but this gives
	// inconsistent results depending on platform; warning on *nix, error
	// on Windows.
	// See https://bugs.mysql.com/bug.php?id=21532
	// Even today 2013-07-04, quoting from the latest
	// https://dev.mysql.com/doc/refman/5.7/en/blob.html
	// "BLOB and TEXT columns cannot have DEFAULT values."
	//
	// @since 2.1.0 Update and reviewed 2022-06-28 during development:
	// - https://dev.mysql.com/doc/refman/8.0/en/data-type-defaults.html
	//   "The BLOB, TEXT, GEOMETRY, and JSON data types can be assigned a default value only if the value is written as an expression, even if the expression value is a literal: ..."
	//   "If the column can take NULL as a value, the column is defined with an explicit DEFAULT NULL clause."
	// - https://dev.mysql.com/doc/refman/8.0/en/blob.html
	//   "BLOB and TEXT columns cannot have DEFAULT values."
	// Indeed, with MySQL 5.7.17:
	//   INSERT INTO *_groups_file_meta (file_id,meta_key) values (1,'foo');
	//   ... yields NULL for the meta_value of the corresponding row.
	//
	// Our definition of the file_meta table introduced in 2.1.0 seems thus appropriate.
	//
	/**
	 * Schema definition.
	 *
	 * @var array
	 */
	private static $schema = array(
		'file' =>
			"
			file_id       BIGINT(20) UNSIGNED NOT NULL auto_increment,
			name          VARCHAR(255) NULL DEFAULT '',
			description   TEXT NULL,
			path          TEXT NOT NULL,
			max_count     INT NOT NULL DEFAULT 0,
			created       DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY   (file_id),
			INDEX         gfa_f_n (name(20)),
			INDEX         gfa_f_d (description(20)),
			INDEX         gfa_f_p (path(20))
			",
		'file_group' =>
			"
			file_id       BIGINT(20) UNSIGNED NOT NULL,
			group_id      BIGINT(20) UNSIGNED NOT NULL,
			created       DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY   (file_id,group_id)
			",
		'file_access' =>
			"
			file_id       BIGINT(20) UNSIGNED NOT NULL,
			user_id       BIGINT(20) UNSIGNED NOT NULL,
			count         INT NOT NULL DEFAULT 0,
			created       DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY   (file_id,user_id)
			",
		'file_meta' =>
			"
			meta_id       BIGINT(20) UNSIGNED NOT NULL auto_increment,
			file_id       BIGINT(20) UNSIGNED NOT NULL,
			meta_key      VARCHAR(255) DEFAULT NULL,
			meta_value    LONGTEXT,
			PRIMARY KEY   (meta_id),
			INDEX         file_id (file_id),
			INDEX         meta_key (meta_key(20))
			"
	);

	/**
	 * Provide the schema definition.
	 *
	 * @return array
	 */
	public static function get_schema() {
		return self::$schema;
	}

	/**
	 * Provide the schema version.
	 *
	 * @return int
	 */
	public static function get_version() {
		return self::$version;
	}

	/**
	 * Update the schema.
	 *
	 * @param int $from_version
	 * @param int $to_version
	 *
	 * @return boolean true indicates success or false failure to update the schema
	 */
	public static function update( $from_version, $to_version = null ) {

		global $wpdb;

		$result = true;

		if ( function_exists( '_groups_get_tablename' ) ) {
			$queries = array();
			$file_table = _groups_get_tablename( 'file' );
			$file_group_table = _groups_get_tablename( 'file_group' );
			$file_access_table = _groups_get_tablename( 'file_access' );

			if ( $to_version === null ) {
				$to_version = self::$version;
			}
			switch ( $to_version ) {
				case 3:
				case 2:
					switch ( $from_version ) {
						case 1:
							// Update our tables and add the columns "created" and "updated".
							// Existing entries will be updated and have CURRENT_TIMESTAMP set as values for those columns.
							if ( $wpdb->get_var( "SHOW TABLES LIKE '" . $file_table . "'" ) === $file_table ) {
								if ( ! $wpdb->get_var( "SHOW COLUMNS FROM $file_table LIKE 'created'" ) ) {
									$queries[] = "ALTER TABLE $file_table ADD COLUMN created DATETIME DEFAULT CURRENT_TIMESTAMP";
								}
								if ( ! $wpdb->get_var( "SHOW COLUMNS FROM $file_table LIKE 'updated'" ) ) {
									$queries[] = "ALTER TABLE $file_table ADD COLUMN updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
								}
							}
							if ( $wpdb->get_var( "SHOW TABLES LIKE '" . $file_group_table . "'" ) === $file_group_table ) {
								if ( ! $wpdb->get_var( "SHOW COLUMNS FROM $file_group_table LIKE 'created'" ) ) {
									$queries[] = "ALTER TABLE $file_group_table ADD COLUMN created DATETIME DEFAULT CURRENT_TIMESTAMP";
								}
								if ( ! $wpdb->get_var( "SHOW COLUMNS FROM $file_group_table LIKE 'updated'" ) ) {
									$queries[] = "ALTER TABLE $file_group_table ADD COLUMN updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
								}
							}
							if ( $wpdb->get_var( "SHOW TABLES LIKE '" . $file_access_table . "'" ) === $file_access_table ) {
								if ( ! $wpdb->get_var( "SHOW COLUMNS FROM $file_access_table LIKE 'created'" ) ) {
									$queries[] = "ALTER TABLE $file_access_table ADD COLUMN created DATETIME DEFAULT CURRENT_TIMESTAMP";
								}
								if ( ! $wpdb->get_var( "SHOW COLUMNS FROM $file_access_table LIKE 'updated'" ) ) {
									$queries[] = "ALTER TABLE $file_access_table ADD COLUMN updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
								}
							}
							break;
					}
					break;
			}

			if ( count( $queries ) > 0 ) {
				foreach ( $queries as $query ) {
					if ( $wpdb->query( $query ) === false ) {
						$result = false;
						error_log( sprintf( 'Groups File Access encountered this error while trying to update its schema: %s', $wpdb->last_error ) );
					}
				}
			}

			switch ( $to_version ) {
				case 3:
					switch ( $from_version ) {
						case 2:
						case 1:
							Groups_File_Access::schema_update();
							break;
					}
					break;
			}
		} else {
			error_log( sprintf( 'Groups File Access is missing the _groups_get_tablename() function to process its schema update from %d to %d.', $from_version, $to_version ) );
			$result = false;
		}

		return $result;
	}
}
