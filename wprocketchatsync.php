<?php

use WP_Rocket_Sync\Database;
use WP_Rocket_Sync\WP_Rocket_Sync;

/**
 * Plugin Name: WP Rocket.chat Sync
 * Plugin URI: https://github.com/SoftPauer/wp-rocketchat-sync
 * Description: Provides a way to sync users between wordpress and rocketchat
 * Version: 1.1.3
 * Author: Tahmid Hoque
 * Author URI: https://github.com/SoftPauer/wp-rocketchat-sync
 * GitHub Plugin URI: https://github.com/SoftPauer/wp-rocketchat-sync
 **/


function custom_logs($message)
{
    if (is_array($message)) {
        $message = json_encode($message);
    }
    $file = fopen("/var/www/html/wp-content/plugins/wp-rocketchat-sync/custom_logs.log", "a");
    fwrite($file, "\n" . date('Y-m-d h:i:s') . " :: " . $message);
    fclose($file);
}

function onPluginRegister()
{
    initDatabase();
}
register_activation_hook(__FILE__, 'onPluginRegister');

function initDatabase()
{
    require_once('includes/database.php');

    $database = new Database(__FILE__);

    $database->static_install();
}



function wpRocketInit()
{

    return \WP_Rocket_Sync\WP_Rocket_Sync::getInstance();
}

if (!class_exists('WP_Rocket_Sync\WP_Rocket_Sync')) {
    if (!defined('WP_ROCKET_SYNC_PLUGIN_DIR')) {
        define('WP_ROCKET_SYNC_PLUGIN_DIR', plugin_dir_path(__FILE__));
    }

    include_once WP_ROCKET_SYNC_PLUGIN_DIR . 'includes/wprocketchatsync.php';

    wpRocketInit();
}
