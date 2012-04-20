<?php

if(!defined('LOADED_SAFELY')) die('You cannot access this file directly.');

// Enable for troubleshooting. Disable for improved performance.
define(    'CFG_DEBUG_ENV',  true );

// A large, randomized string of characters to salt your passwords with.
define(         'CFG_SALT',  '' );

// Identity of your installation.
define(         'CFG_NAME',  'YourID' );
define(          'CFG_URL',  'http://yourdomain.com' );
define(    'CFG_MAIL_FROM',  'You <you@yourdomain.com>' );

// Memcached connectivity.
define(    'CFG_MEMCACHED',  '127.0.0.1' );

// Database connectivity.
define(     'CFG_SQL_HOST',  '127.0.0.1' );
define(     'CFG_SQL_USER',  'root' );
define( 'CFG_SQL_PASSWORD',  'root' );
define( 'CFG_SQL_DATABASE',  'riverid' );

// How long an API hit should count against an app's cap.
define('CFG_RATELIMIT_SEC',  3600 );

// How long before a user session expires.
define('CFG_USER_SESSION_EXPIRES',  86400 );

// Use the Sendgrid.com API to send mail?
define(  'CFG_USE_SENDGRID',  false );
define( 'CFG_SENDGRID_USER',  '' );
define(  'CFG_SENDGRID_KEY',  '' );
