<?php
defined( 'ABSPATH' ) || exit;

class WWA_Session{
    private static $table = 'wpcom_sessions';
    public static function set($name, $value, $expired=''){
        global $wpcom_wpdb, $wpdb;
        self::init_database();
        $table = $wpdb->prefix . self::$table;
        $session = array();
        if(!preg_match('/^_/i', $name)) $name = self::session_prefix() . '_' . $name;
        $session['name'] = $name;
        $session['value'] = $value;
        $session['expired'] = $expired && is_numeric($expired) ? $expired : 900;
        $session['time'] = current_time( 'mysql', 1 );
        $option = @$wpcom_wpdb->get_row( "SELECT * FROM `$table` WHERE name = '$name'" );
        if($option && isset($option->value)) {
            unset($session['name']);
            $res = $wpcom_wpdb->update($table, $session, array('name' => $name));
        }else{
            $res = $wpcom_wpdb->insert($table, $session);
        }
        return $res;
    }

    public static function get($name){
        global $wpcom_wpdb, $wpdb;
        self::init_database();
        $table = $wpdb->prefix . self::$table;
        if($name) {
            if(!preg_match('/^_/i', $name)) $name = self::session_prefix() . '_' . $name;
            $row = $wpcom_wpdb->get_row("SELECT * FROM `$table` WHERE name = '$name'");
            if($row && isset($row->value)){
                if( (get_date_from_gmt($row->time, 'U') + $row->expired) > current_time( 'timestamp', 1 ) ) {
                    return $row->value;
                } else {
                    self::delete($row->ID);
                }
            }
        }
    }

    public static function delete($id='', $name=''){
        global $wpcom_wpdb, $wpdb;
        self::init_database();
        $table = $wpdb->prefix . self::$table;
        if( $wpcom_wpdb->get_var("SHOW TABLES LIKE '$table'") == $table ) {
            $array = array();
            if($id) $array['ID'] = $id;
            if($name) {
                if(!preg_match('/^_/i', $name)) $name = self::session_prefix() . '_' . $name;
                $array['name'] = $name;
            }
            @$wpcom_wpdb->delete($table, $array);
        }
    }

    public static function cron(){
        global $wpcom_wpdb, $wpdb;
        self::init_database();
        $table = $wpdb->prefix . self::$table;
        if( $wpcom_wpdb->get_var("SHOW TABLES LIKE '$table'") == $table ) {
            $timestamp = current_time( 'timestamp', 1 );
            $temps = $wpcom_wpdb->get_results("SELECT * FROM `$table` WHERE UNIX_TIMESTAMP(time)+expired < $timestamp");
            if ($temps) {
                foreach ($temps as $temp) {
                    @$wpcom_wpdb->delete($table, array('ID' => $temp->ID));
                }
            }
        }
    }

    private static function init_database(){
        global $wpcom_wpdb, $wpdb;
        self::int_wpdb();
        $table = $wpdb->prefix . self::$table;
        if( $wpcom_wpdb->get_var("SHOW TABLES LIKE '$table'") != $table ){
            $charset_collate = $wpcom_wpdb->get_charset_collate();
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

            // 缓存表
            $create_sql = "CREATE TABLE $table (".
                "ID BIGINT(20) NOT NULL auto_increment,".
                "name text NOT NULL,".
                "value longtext NOT NULL,".
                "expired text,".
                "time datetime,".
                "PRIMARY KEY (ID)) $charset_collate;";

            dbDelta( $create_sql );
        }
    }

    public static function session_prefix(){
        return '';
    }
    // 防止缓存插件更换过 $wpdb，所以自己重新初始化
    private  static function int_wpdb() {
        global $wpcom_wpdb;
        if ( isset( $wpcom_wpdb ) ) return false;
        $dbuser     = defined( 'DB_USER' ) ? DB_USER : '';
        $dbpassword = defined( 'DB_PASSWORD' ) ? DB_PASSWORD : '';
        $dbname     = defined( 'DB_NAME' ) ? DB_NAME : '';
        $dbhost     = defined( 'DB_HOST' ) ? DB_HOST : '';

        $wpcom_wpdb = new wpdb( $dbuser, $dbpassword, $dbname, $dbhost );
    }
}