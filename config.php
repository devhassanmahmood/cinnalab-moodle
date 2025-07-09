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
$CFG->wwwroot   = getenv('MOODLE_SITE_URL') ?: 'http://localhost:8080';
$CFG->dataroot  = getenv('MOODLE_DATA_ROOT') ?: '/tmp/moodledata';
$CFG->admin     = 'admin';

// S3 File Storage Configuration
$CFG->filestorage = 's3';
$CFG->s3 = array(
    'access_key' => getenv('AWS_ACCESS_KEY'),
    'secret_key' => getenv('AWS_SECRET_KEY'),
    'bucket_name' => getenv('AWS_S3_BUCKET') ?: 'documenso-cinnalab',
    'region' => getenv('AWS_REGION') ?: 'eu-north-1',
    'use_path_style_endpoint' => false,
    'use_accelerate_endpoint' => false
);

// Directory permissions
$CFG->directorypermissions = 02777;

// Debug settings
$CFG->debug = getenv('MOODLE_DEBUG') ? (E_ALL) : (E_ALL & ~E_NOTICE & ~E_DEPRECATED);
$CFG->debugdisplay = getenv('MOODLE_DEBUG_DISPLAY') ? 1 : 0;
$CFG->debugstring = '';
$CFG->debugemail = '';
$CFG->debugpageinfo = getenv('MOODLE_DEBUG_PAGEINFO') ? 1 : 0;

// Cache settings
$CFG->cachejs = 1;
$CFG->cachetype = 'file';

// Session settings
$CFG->session_handler_class = '\core\session\file';
$CFG->session_file_save_path = $CFG->dataroot . '/sessions';

// This is a new installation, not an upgrade
$CFG->upgradekey = '';

// $CFG->noemailever = true;

if (!file_exists($CFG->session_file_save_path)) {
    mkdir($CFG->session_file_save_path, 0777, true);
}


require_once(__DIR__ . '/lib/setup.php');