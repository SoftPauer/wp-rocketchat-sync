<?php


namespace WP_Rocket_Sync;

final class WP_Rocket_Sync
{

    /**
     * @var WP_Rocket_Sync
     */
    private static $instance = null;
    /**
     * @var RestApi
     */
    private $restAPI;

    /**
     * @var Database
     */
    private $database;

    private function __construct()
    {
        require_once WP_ROCKET_SYNC_PLUGIN_DIR . 'includes/load.php';

        add_action('init', array($this, 'init'), 0);
        add_action('user_register', array($this, 'syncRocketOnCreate'), 0);
        add_action('delete_user', array($this, 'onUserDeleted'), 10);
        register_activation_hook(WP_ROCKET_SYNC_PLUGIN_DIR . "includes/database.php", 'static_install');
    }

    public function init()
    {
        $this->restAPI = new RestAPI();
        $this->database = new Database();
    }

    public function database()
    {
        return $this->database;
    }
    public function rest()
    {
        return $this->restAPI;
    }


    /**
     * @return WP_Rocket_Sync
     */
    public static function getInstance()
    {

        if (self::$instance == null) {
            self::$instance = new WP_Rocket_Sync();
        }
        return self::$instance;
    }

    public function onUserDeleted($user_id)
    {
        $user = get_userdata($user_id);
        $adminAuth = WP_Rocket_Sync::rocketGetAuth("soft", "12qwaszx");
        $adminAuthToken = $adminAuth->data->authToken;
        $adminAuthUserId = $adminAuth->data->userId;

        $removeUserFromRocket = WP_Rocket_Sync::deleteUserFromRocket($adminAuthToken, $adminAuthUserId, $user->user_login);

        if ($removeUserFromRocket->success == false) {
            custom_logs("Error with removing rocket user: " . $removeUserFromRocket->error);
            die('Error with this action, Please contact Softpauer!');
        } else {
            WP_Rocket_Sync::getInstance()->database()->remove_rocket_user_on_delete($user_id);
            custom_logs("User: " . $user->user_login . " successfully deleted from rocket.chat server");
        }
    }


    public function syncRocketOnCreate()
    {
        $adminAuth = WP_Rocket_Sync::rocketGetAuth("soft", "12qwaszx");
        $adminAuthToken = $adminAuth->data->authToken;
        $adminAuthUserId = $adminAuth->data->userId;
        $users = get_users();

        foreach ($users as $user) {
            $userExists = WP_Rocket_Sync::doesUserExist($adminAuthToken, $adminAuthUserId, $user->user_login);
            if (!$userExists && $user->user_login != "admin") {
                //generate random password for rocket.chat account 
                $randomPassword = wp_generate_password();

                //create new user in rocket 
                $newUser = WP_Rocket_Sync::createRocketUser($adminAuthToken, $adminAuthUserId, $user, $randomPassword);
                custom_logs("New User Object: " . json_encode($newUser));

                //auth new user 

                $newUserAuthObj = WP_Rocket_Sync::rocketGetAuth($newUser->user->username, $randomPassword);

                //create new personal access token for the user
                $accessTokenObj = WP_Rocket_Sync::createPersonalAccessToken($newUserAuthObj, "eventr-token");
                custom_logs("New access Token call: " . json_encode($accessTokenObj));

                //insert the rocket access token, userid and wp user id into database
                WP_Rocket_Sync::getInstance()->database()->insert_user_rocket_token($user->id, $newUser->user->_id, $accessTokenObj->token);
            }
        }
    }

    public function deleteUserFromRocket($adminRocketToken, $adminRocketUserID, $WPuserName)
    {
        $request_headers = array(
            "X-Auth-token:" . $adminRocketToken,
            "X-User-Id:" . $adminRocketUserID,
            "Content-Type:" . "application/json", "charset:UTF- 8",
        );

        $payload = json_encode(array("username" => $WPuserName));

        $request = WP_Rocket_Sync::curlRequest($payload, $request_headers, "http://rocketchat:3000/api/v1/users.delete");

        return $request;
    }


    public function doesUserExist($adminRocketToken, $adminRocketUserID, $WPuserName)
    {

        $request_headers = array(
            "X-Auth-Token:" . $adminRocketToken,
            "X-User-Id:" . $adminRocketUserID
        );

        $payload = null;

        $request = WP_Rocket_Sync::curlRequest($payload, $request_headers, "http://rocketchat:3000/api/v1/users.info?username=" . $WPuserName);

        return ($request->success);
    }

    public function createPersonalAccessToken($userObj, $tokenName)
    {




        $data = array(
            "tokenName" => $tokenName,
            "bypassTwoFactor" => true
        );

        $request_headers = array(
            "Content-Type:" . "application/json", "charset:UTF- 8",
            "X-Auth-Token:" . $userObj->data->authToken,
            "X-User-Id:" . $userObj->data->userId
        );
        custom_logs("auth-token: " . $userObj->data->authToken);
        custom_logs("userId: " . $userObj->data->userId);


        $payload = json_encode($data);

        $request = WP_Rocket_Sync::curlRequest($payload, $request_headers, "http://rocketchat:3000/api/v1/users.generatePersonalAccessToken");

        return ($request);
    }

    public function createRocketUser($adminRocketToken, $adminRocketUserID, $WPuser, $password)
    {
        $data = array(
            "name" => $WPuser->display_name,
            "email" => $WPuser->user_email,
            "password" => $password,
            "username" => $WPuser->user_login,
        );

        $request_headers = array(
            "Content-Type:" . "application/json", "charset:UTF- 8",
            "X-Auth-Token:" . $adminRocketToken,
            "X-User-Id:" . $adminRocketUserID
        );

        $payload = json_encode($data);

        $request = WP_Rocket_Sync::curlRequest($payload, $request_headers, "http://rocketchat:3000/api/v1/users.create");
        return ($request);
    }

    public static function rocketGetAuth($user, $password)
    {
        $data = array(
            "user" => $user,
            "password" => $password
        );

        $request_headers = array(
            "Content-Type:" . "application/json", "charset:UTF- 8"
        );

        $payload = json_encode($data);

        $request = WP_Rocket_Sync::curlRequest($payload, $request_headers, "http://rocketchat:3000/api/v1/login");

        return $request;
    }

    public function curlRequest($payload, $requestHeaders, $restEndpoint)
    {
        $ch = curl_init($restEndpoint);

        // curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($payload != null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);


        $request = curl_exec($ch);

        if (curl_errno($ch)) {
            print "Error: " . curl_error($ch);
            exit();
        }

        curl_close($ch);

        return json_decode($request);
    }
}
