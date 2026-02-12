<?php
// Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

// Database settings
$CFG->dbtype    = 'pgsql';      // 'pgsql', 'mysqli', 'mariadb', 'sqlsrv' or 'oci'
$CFG->dblibrary = 'native';     // 'native' only at the moment
$CFG->dbhost    = getenv('DATABASE_URL') ? parse_url(getenv('DATABASE_URL'), PHP_URL_HOST) : 'localhost';
$CFG->dbname    = getenv('DATABASE_URL') ? substr(parse_url(getenv('DATABASE_URL'), PHP_URL_PATH), 1) : 'moodle';
$CFG->dbuser    = getenv('DATABASE_URL') ? parse_url(getenv('DATABASE_URL'), PHP_URL_USER) : 'dev';
$CFG->dbpass    = getenv('DATABASE_URL') ? parse_url(getenv('DATABASE_URL'), PHP_URL_PASS) : '1234';
$CFG->prefix    = 'mdl_';       // prefix to use for all table names
$CFG->dboptions = array(
    'dbpersist' => false,       // should persistent database connections be used? set to 'false' for the most stable setting
    'dbsocket'  => false,       // should connection via UNIX socket be used?
    'dbport'    => getenv('DATABASE_URL') ? parse_url(getenv('DATABASE_URL'), PHP_URL_PORT) : '5432', // the TCP port number to use when connecting
);

// Site settings
$CFG->wwwroot   = getenv('MOODLE_SITE_URL') ?: 'https://cinnalab-moodle-d1962611ca61.herokuapp.com';
$CFG->dataroot = '/app/moodledata';
$CFG->admin     = 'admin';

// S3 File Storage Configuration using objectfs tool
//$CFG->alternative_file_system_class = '\tool_objectfs\s3_file_system';
//$CFG->filedir = '/app/moodledata/filedir';
//$CFG->localcachedir = '/app/moodledata/localcache';
//$CFG->tempdir = '/app/moodledata/temp';
//$CFG->cachedir = '/app/moodledata/cache';

$CFG->alternative_file_system_class = '\tool_objectfs\s3_file_system';
//$CFG->tool_objectfs_use_presigned_urls = false; // Force Moodle to serve via pluginfile.php


// Directory permissions
$CFG->directorypermissions = 02777;

// Debug settings
// $CFG->debug = getenv('MOODLE_DEBUG') ? (E_ALL) : (E_ALL & ~E_NOTICE & ~E_DEPRECATED);
// $CFG->debugdisplay = getenv('MOODLE_DEBUG_DISPLAY') ? 1 : 0;
// $CFG->debugstring = '';
// $CFG->debugemail = '';
// $CFG->debugpageinfo = getenv('MOODLE_DEBUG_PAGEINFO') ? 1 : 0;

// Cache settings
$CFG->cachejs = 1;
//$CFG->cachetype = 'file';

// Session settings
//$CFG->session_handler_class = '\core\session\file';
//$CFG->session_file_save_path = $CFG->dataroot . '/sessions';

// Allow iframe embedding
$CFG->allowframembedding = true;

// This is a new installation, not an upgrade
$CFG->upgradekey = '';

// $CFG->noemailever = true;

//if (!file_exists($CFG->session_file_save_path)) {
  //  mkdir($CFG->session_file_save_path, 0777, true);
//}

$CFG->sslproxy = true;

if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

require_once(__DIR__ . '/lib/setup.php');
