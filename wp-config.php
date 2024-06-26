<?php
define( 'WP_CACHE', true );
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'riyawordpress' );

/** Database username */
define( 'DB_USER', 'riyawordpress' );


define('WP_HOME','http://localhost/wordpress');
define('WP_SITEURL','http://localhost/wordpress');



/** Database password */
define( 'DB_PASSWORD', 'riyawordpress' );

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
define( 'AUTH_KEY',         'PInvg5^}JFZ5JTL&N$r4,U}Y6=zy-k`q+!*4xwX8y$d8/JuzwV&^1nU8`>UpK*KO' );
define( 'SECURE_AUTH_KEY',  'TqZ6Wn4$|qsTq-4]uE];49BEFho>r [yFb1M%2<x~eL)7o-K~OAm=&Zg`>k)|qk(' );
define( 'LOGGED_IN_KEY',    'GT}!?Qv.VmD9n[M#jEcC*?;Sd V.{*da]E!@c4`.v:EN&0z4,Dy[<>-$6@dLKi8`' );
define( 'NONCE_KEY',        '_-IUsJ_]9Isxsf#L<m9)z:*!Xk[?WT=|)fAK,Y=JnpT5T,IQ?Ir#:<ae@y(|.cYc' );
define( 'AUTH_SALT',        'E1DQ$TI_-Z/}f+It[flZ7H-+tOMx`,1R~Gc+{f^TiaJWvV1k3]u%Fk4 ]=r+DP`{' );
define( 'SECURE_AUTH_SALT', '2j_n.8p>HvT?A JiB55],z:Dkn@Kk#2_)o1qO)gBx-<O80~@bagyv7tQFgB(R/86' );
define( 'LOGGED_IN_SALT',   '@9ivvl/B^OqU!rw=24Ol@1BV,nS;M&88bv2!HRBd6Vn-@PB)Nd2`9I.FH9s(RqVd' );
define( 'NONCE_SALT',       'YmY9[Fbi=Kn[o?7Ua43BqCZeSxHhs@GG>RQ7_Uvu.=Mk#xM7gv}KXCA6vv[}RND/' );

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
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
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
