<?php
if(!defined('DB_SERVER')){
    require_once(__DIR__ . "/../initialize.php");
}
class DBConnection{

    private $host;
    private $username;
    private $password;
    private $database;
    private $port;
    
    public $conn;
    
    public function __construct(){
        $this->host = defined('DB_SERVER') ? DB_SERVER : 'localhost';
        $this->username = defined('DB_USERNAME') ? DB_USERNAME : 'root';
        $this->password = defined('DB_PASSWORD') ? DB_PASSWORD : '';
        $this->database = defined('DB_NAME') ? DB_NAME : 'ovas_db';
        $this->port = defined('DB_PORT') ? (int)DB_PORT : 3306;

        if (!isset($this->conn)) {
            mysqli_report(MYSQLI_REPORT_OFF);
            $this->conn = mysqli_init();

            // SSL support for cloud databases (TiDB Cloud, Aiven, or DB_SSL=true)
            $use_ssl = (getenv('DB_SSL') === 'true') || 
                       (strpos($this->host, 'tidbcloud.com') !== false) || 
                       (strpos($this->host, 'aivencloud.com') !== false);

            if ($use_ssl) {
                $this->conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
            }

            $this->conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 15);
            
            $connect_error = '';
            try {
                $connected = @$this->conn->real_connect(
                    $this->host, 
                    $this->username, 
                    $this->password, 
                    $this->database, 
                    (int)$this->port,
                    null,
                    $use_ssl ? MYSQLI_CLIENT_SSL : 0
                );
            } catch (Exception $e) {
                $connected = false;
                $connect_error = $e->getMessage();
            }

            if (!$connected) {
                $err = !empty($connect_error) ? $connect_error : $this->conn->connect_error;
                echo '<div style="font-family:sans-serif;max-width:600px;margin:40px auto;padding:25px;border:1px solid #f5c6cb;background:#f8d7da;color:#721c24;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.1);">';
                echo '<h2 style="margin-top:0;">Database Connection Error</h2>';
                echo '<p>Could not connect to database server at <strong>' . htmlspecialchars($this->host) . ':' . htmlspecialchars($this->port) . '</strong> as user <strong>' . htmlspecialchars($this->username) . '</strong>.</p>';
                echo '<p><strong>Error:</strong> ' . htmlspecialchars($err) . '</p>';
                echo '<hr style="border-top:1px solid #f5c6cb;">';
                echo '<p><strong>Tips for Aiven users:</strong> Aiven default username is <code>avnadmin</code> (not <code>root</code>).</p>';
                echo '<p>Please check your environment variables in Render:</p>';
                echo '<ul><li><code>DB_SERVER</code></li><li><code>DB_PORT</code></li><li><code>DB_USERNAME</code></li><li><code>DB_PASSWORD</code></li><li><code>DB_NAME</code></li></ul>';
                echo '</div>';
                exit;
            }

            $this->conn->set_charset("utf8mb4");

            // Auto-initialize tables and seed data if database is fresh/empty
            $this->auto_initialize();
        }    
        
    }

    private function auto_initialize(){
        try {
            $check = $this->conn->query("SHOW TABLES LIKE 'system_info'");
            if (!$check || $check->num_rows == 0) {
                $sql_file = (defined('base_app') ? base_app : (__DIR__ . '/../')) . 'database/ovas_db.sql';
                if (file_exists($sql_file)) {
                    $sql = file_get_contents($sql_file);
                    // Split into statements by semicolon followed by newline
                    $statements = preg_split('/;\s*[\r\n]+/', $sql);
                    foreach ($statements as $stmt) {
                        $stmt = trim($stmt);
                        if (!empty($stmt)) {
                            $this->conn->query($stmt);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Auto initialize error: " . $e->getMessage());
        }
    }

    public function __destruct(){
        if (isset($this->conn) && $this->conn instanceof mysqli) {
            @$this->conn->close();
        }
    }
}
?>
