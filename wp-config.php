<?php
define( 'WP_CACHE', true );


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
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'diffmerc_mercourierdb' );

/** Database username */
define( 'DB_USER', 'user_merc' );

/** Database password */
define( 'DB_PASSWORD', 'password_merc' );

/** Database hostname */
define( 'DB_HOST', 'db_merc' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',          '0D`}-G+j*XWQ}bNczZI&JA_Z.J]CNmr?Ao=8U>I*AW{sD7U4Ppwk BTkp{/]FL,V' );
define( 'SECURE_AUTH_KEY',   'C1qo!M1nu2j6SkM8t,I_rfcex&6Os{l2;&dA1:T][y>1b$qJqr7R(ZoYD7aB$njY' );
define( 'LOGGED_IN_KEY',     '/ VzjOUtY+i{H`VwT]jmNPj<vWV0zgB;1@h{jg2vk]647g<MB/!KC?n`9MWH7G3$' );
define( 'NONCE_KEY',         '9X!74)70[S[p*-kv2O=i]%.iMrSP}c_g5!z8O)YgDK^-GJ=fptF-A,zD6)o/ 3o$' );
define( 'AUTH_SALT',         'q <j`R4_SN&m=L7>hYA:]6u. --Ac0M2l$AIE)X1B3ckmt[;HiEdjeB.:Ol1Q=L!' );
define( 'SECURE_AUTH_SALT',  '(h((t<Exl.8wu/7iRf;1s}*8$+r,kTB9J8oOtBq*zlwv(TUc8uJs0Lz$-{pOMWSB' );
define( 'LOGGED_IN_SALT',    'SgycpNZrMgo^59YAP)g0:lN*QZ/d1Z^JV`8BoZ[36p=D0WfVr9x8}=- t+Ul:2|k' );
define( 'NONCE_SALT',        'hymv[Wmz(cOEyFDDyG_oQXIlJ))upg-]v)DAI5pFqG?l<hck2>*E`LY~FIRtLRm/' );
define( 'WP_CACHE_KEY_SALT', 'u.)A}XGCG|^w6q-_uiQ:2thRxHn#D*BvmCw}m|U+:[E4jM9C8X7_~xiS1[v$W/Zg' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
// if ( ! defined( 'WP_DEBUG' ) ) {
// 	define( 'WP_DEBUG', false );
// }
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', 0 );

define( 'FS_METHOD', 'direct' );
define( 'COOKIEHASH', '052eadb8b53fe136dba7b0fe1eaa9fbd' );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );

// Optimizaciones para upload de múltiples imágenes en POD (evitar 504 timeout)
@ini_set( 'max_execution_time', 300 );
@ini_set( 'max_input_time', 300 );
@ini_set( 'memory_limit', '256M' );
@ini_set( 'post_max_size', '100M' );
@ini_set( 'upload_max_filesize', '100M' );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
