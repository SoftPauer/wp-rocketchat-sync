<?php
/**
 * Plugin Name: WP Rocket.chat Sync
 * Plugin URI: https://github.com/SoftPauer/wp-rocketchat-sync
 * Description: Provides a way to sync users between wordpress and rocketchat
 * Version: 1.1.5
 * Author: Tahmid Hoque
 * Author URI: https://github.com/SoftPauer/wp-rocketchat-sync
 * GitHub Plugin URI: https://github.com/SoftPauer/wp-rocketchat-sync
 **/

use WP_Rocket_Sync\Database;
use WP_Rocket_Sync\WP_Rocket_Sync;

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

function add_rocketchat_cred($response, $user, $request)
{
  if (!function_exists('get_fields')) return $response;

  if (isset($user)) {
    $rocket = WP_Rocket_Sync::getInstance()->database()->get_rocket_info_by_id($user->id);
    $response->data['rocket_user_id'] = $rocket[0]->rocketUserId;
    $response->data['rocket_token'] = $rocket[0]->rocketToken;

  }
  return $response;
}
add_filter('rest_prepare_user', 'add_rocketchat_cred', 10, 3);