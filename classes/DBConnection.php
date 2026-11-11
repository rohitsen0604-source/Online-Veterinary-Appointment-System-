<?php
if(!defined('DB_SERVER')){
    require_once("../initialize.php");
}
class DBConnection{

    private $host = DB_SERVER;
    private $username = DB_USERNAME;
    private $password = DB_PASSWORD;
    private $database = DB_NAME;
    private $port = defined('DB_PORT') ? DB_PORT : 3306;
    
    public $conn;
    
    public function __construct(){

        if (!isset($this->conn)) {
            $this->conn = mysqli_init();

            // SSL support for cloud databases (TiDB Cloud, Aiven, or DB_SSL=true)
            $use_ssl = (getenv('DB_SSL') === 'true') || 
                       (strpos($this->host, 'tidbcloud.com') !== false) || 
                       (strpos($this->host, 'aivencloud.com') !== false);

            if ($use_ssl) {
                $this->conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
            }

            $this->conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 15);
            
            $connected = @$this->conn->real_connect(
                $this->host, 
                $this->username, 
                $this->password, 
                $this->database, 
                (int)$this->port,
                null,
                $use_ssl ? MYSQLI_CLIENT_SSL : 0
            );

            if (!$connected) {
                echo '<div style="font-family:sans-serif;max-width:600px;margin:40px auto;padding:25px;border:1px solid #f5c6cb;background:#f8d7da;color:#721c24;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.1);">';
                echo '<h2 style="margin-top:0;">Database Connection Error</h2>';
                echo '<p>Could not connect to database server at <strong>' . htmlspecialchars($this->host) . ':' . htmlspecialchars($this->port) . '</strong>.</p>';
                echo '<p><strong>Error:</strong> ' . htmlspecialchars($this->conn->connect_error) . '</p>';
                echo '<hr style="border-top:1px solid #f5c6cb;">';
                echo '<p>Make sure your Render environment variables are configured correctly:</p>';
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
            if ($check && $check->num_rows == 0) {
                $sql_file = (defined('base_app') ? base_app : __DIR__ . '/../') . 'database/ovas_db.sql';
                if (file_exists($sql_file)) {
                    $sql = file_get_contents($sql_file);
                    // Remove comment lines
                    $lines = explode("\n", $sql);
                    $clean_sql = '';
                    foreach ($lines as $line) {
                        $trimmed = trim($line);
                        if (strpos($trimmed, '--') === 0 || strpos($trimmed, '/*') === 0) {
                            continue;
                        }
                        $clean_sql .= $line . "\n";
                    }

                    if ($this->conn->multi_query($clean_sql)) {
                        do {
                            if ($result = $this->conn->store_result()) {
                                $result->free();
                            }
                        } while ($this->conn->more_results() && $this->conn->next_result());
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
