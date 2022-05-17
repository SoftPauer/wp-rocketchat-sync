<?php

namespace WP_Rocket_Sync;

class Database
{
    protected $rs_table_name, $rocketSync_db_version;

    public function __construct()
    {
        global $wpdb;
        $this->rs_table_name = $wpdb->prefix . 'rocket_sync';
        $this->rocketSync_db_version = '1.0';
        register_activation_hook(WP_ROCKET_SYNC_PLUGIN_DIR . "includes/database.php", array($this, 'static_install'));
    }

    public function static_install()
    {
        custom_logs("static install function");
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->rs_table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            userId text NOT NULL,
            rocketUserId text NOT NULL,
            rocketToken text NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        dbDelta($sql);
        add_option('rocketSync_db_version', $this->rocketSync_db_version);
    }

    public function get_rocket_info_by_id($user_id)
    {
        global $wpdb;
        $map = $wpdb->get_results("SELECT userID, rocketToken, rocketUserId FROM $this->rs_table_name WHERE userID=$user_id");

        return $map;
    }

    public function remove_rocket_user_on_delete($user_id)
    {
        global $wpdb;
        $wpdb->get_results("DELETE from $this->rs_table_name WHERE userID=$user_id");
    }

    public function get_all_rocket_info()
    {
        global $wpdb;
        $map = $wpdb->get_results("SELECT userID, rocketToken, rocketUserId FROM $this->rs_table_name");

        return $map;
    }

    public function insert_user_rocket_token($user_id, $rocketUserId, $rocketToken)
    {
        custom_logs("This WPUserID is: " . $user_id);
        custom_logs("This RocketUserID is: " . $rocketUserId);
        custom_logs("This RocketToken is: " . $rocketToken);


        global $wpdb;

        $sql = "INSERT INTO $this->rs_table_name
        (userid, rocketuserid, rockettoken) VALUES";

        $sql = $sql . "('$user_id','$rocketUserId','$rocketToken')";

        custom_logs("sql: " . $sql);

        $wpdb->get_results($sql);
    }
}
