<?php

if(!defined('LOADED_SAFELY')) die('You cannot access this file directly.');

// Enable for troubleshooting. Disable for improved performance.
define(    'CFG_DEBUG_ENV',  true );

define(         'CFG_SALT',  '' );
define(         'CFG_NAME',  'CrowdmapID' );
define(          'CFG_URL',  'http://crowdmapid.com' );

define(     'CFG_SQL_HOST',  '127.0.0.1' );
define(     'CFG_SQL_USER',  'root' );
define( 'CFG_SQL_PASSWORD',  'root' );
define( 'CFG_SQL_DATABASE',  'riverid' );

define('CFG_RATELIMIT_SEC',  3600 );
