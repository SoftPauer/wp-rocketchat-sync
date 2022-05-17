<?php

namespace WP_Rocket_Sync;

class RestAPI
{
    protected $_namespace = 'wp/v2/rocketSync';

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
    }

    public function registerRestRoutes()
    {

        register_rest_route($this->_namespace, '/allData', array(
            array(
                'methods' => 'GET',
                'callback' => [$this, 'get_all_rocket_info']
            )
        ));

        register_rest_route($this->_namespace, '/userData', array(
            array(
                'methods' => 'GET',
                'callback' => [$this, 'get_user_rocket_info'],
                'permission_callback' => function () {
                    if (get_current_user_id() > 0) {
                        return true;
                    }
                }
            )
        ));
    }

    public function get_user_rocket_info()
    {
        error_log("current user in function: " . get_current_user_id());
        $user_id = get_current_user_id();
        return WP_Rocket_Sync::getInstance()->database()->get_rocket_info_by_id($user_id);
    }

    public function get_all_rocket_info($request)
    {
        return WP_Rocket_Sync::getInstance()->database()->get_all_rocket_info();
    }
}
