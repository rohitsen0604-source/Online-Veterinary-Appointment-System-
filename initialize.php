<?php
$dev_data = array('id'=>'-1','firstname'=>'Developer','lastname'=>'','username'=>'dev_oretnom','password'=>'5da283a2d990e8d8512cf967df5bc0d0','last_login'=>'','date_updated'=>'','date_added'=>'');

if(!defined('base_url')){
    if(getenv('BASE_URL')){
        define('base_url', rtrim(getenv('BASE_URL'), '/').'/');
    } else if(isset($_SERVER['HTTP_HOST'])) {
        $proto = (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        if(strpos($_SERVER['HTTP_HOST'], 'localhost') !== false && isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/ovas/') !== false){
            define('base_url', 'http://localhost/ovas/');
        } else {
            define('base_url', $proto . '://' . $_SERVER['HTTP_HOST'] . '/');
        }
    } else {
        define('base_url','http://localhost/ovas/');
    }
}

if(!defined('base_app')) define('base_app', str_replace('\\','/',__DIR__).'/' );
if(!defined('dev_data')) define('dev_data',$dev_data);

// Database configuration via Environment Variables or defaults
$db_url = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');
$url_parts = $db_url ? parse_url($db_url) : null;

$db_host = getenv('DB_SERVER') ?: getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: ($url_parts['host'] ?? 'localhost');
$db_port = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: ($url_parts['port'] ?? 3306);
$db_user = getenv('DB_USERNAME') ?: getenv('DB_USER') ?: getenv('MYSQLUSER') ?: ($url_parts['user'] ?? 'root');
$db_pass = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : ($url_parts['pass'] ?? '')));
$db_name = getenv('DB_NAME') ?: getenv('DB_DATABASE') ?: getenv('MYSQLDATABASE') ?: (isset($url_parts['path']) ? ltrim($url_parts['path'], '/') : 'ovas_db');

if(!defined('DB_SERVER')) define('DB_SERVER', $db_host);
if(!defined('DB_PORT')) define('DB_PORT', (int)$db_port);
if(!defined('DB_USERNAME')) define('DB_USERNAME', $db_user);
if(!defined('DB_PASSWORD')) define('DB_PASSWORD', $db_pass);
if(!defined('DB_NAME')) define('DB_NAME', $db_name);
?>
