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
$CFG->dbuser    = getenv('DATABASE_URL') ? parse_url(getenv('DATABASE_URL'), PHP_URL_USER) : 'moodle';
$CFG->dbpass    = getenv('DATABASE_URL') ? parse_url(getenv('DATABASE_URL'), PHP_URL_PASS) : '';
$CFG->prefix    = 'mdl_';       // prefix to use for all table names
$CFG->dboptions = array(
    'dbpersist' => false,       // should persistent database connections be used? set to 'false' for the most stable setting
    'dbsocket'  => false,       // should connection via UNIX socket be used?
    'dbport'    => getenv('DATABASE_URL') ? parse_url(getenv('DATABASE_URL'), PHP_URL_PORT) : '', // the TCP port number to use when connecting
);

// Site settings
$CFG->wwwroot   = getenv('MOODLE_SITE_URL') ?: 'http://localhost';
$CFG->dataroot  = '/tmp/moodledata';
$CFG->admin     = 'admin';

// Directory permissions
$CFG->directorypermissions = 02777;

// Debug settings
$CFG->debug = (E_ALL & ~E_NOTICE & ~E_DEPRECATED);
$CFG->debugdisplay = 0;
$CFG->debugstring = '';
$CFG->debugemail = '';
$CFG->debugpageinfo = 0;

// Cache settings
$CFG->cachejs = 1;
$CFG->cachetype = 'file';

// Session settings
$CFG->session_handler_class = '\core\session\file';
$CFG->session_file_save_path = $CFG->dataroot . '/sessions';

// This is a new installation, not an upgrade
$CFG->upgradekey = '';

require_once(__DIR__ . '/lib/setup.php'); 