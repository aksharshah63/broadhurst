<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'broadhurst' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'LJ:0<HsPY]6W+|g:;d9*l . .I:6K(B5=4FTR<^q]u~M8NEeyreJ-*|}wZm1hCYW' );
define( 'SECURE_AUTH_KEY',  '4R3B6 .83rw@?laCN/g)CH<Pd.is3<:y$p,0)*`_icd8~+ow.z0J~QXKYUM$0ZI$' );
define( 'LOGGED_IN_KEY',    '-ogBM3#{#]5~(ViF6X=Gnbe)m}{>xv.oBer#p]+O:ge~je:O:047un-bJ0Fz!%uM' );
define( 'NONCE_KEY',        '/w3W}aWUnz[+0<.p~kGU+)QAFI{##OnOqO;tXL:QElErBDE)Hwa&_x6P=V0/q ?p' );
define( 'AUTH_SALT',        '>(,`1-kd:]|~W8JA>X/nu+V$9y:OZe!!5,3piHA:ix3RVCUx4x>-<A@*~>xV7}ZV' );
define( 'SECURE_AUTH_SALT', '3q`uKy9;TA:!Zc>*_UF U6%-N`n~0O<B[9N`{xKD&IsL@L{?%M(3{dfS~$EcHvpX' );
define( 'LOGGED_IN_SALT',   'BSo><p{LQ1?8I)&RzYfaw#Gt9vaBH}~E?eV83)t45s_Ck&!EUJon7kp#l(/mc{pQ' );
define( 'NONCE_SALT',       '}a1a09dkyfPAl?~-(0LH2GU90BTklgNxD3?!cC 8TXu|^jTHbF;^P(R/,f.%+wh7' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
define('FS_METHOD','direct');
