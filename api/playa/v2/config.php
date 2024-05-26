<?php

namespace CONFIGURATION;

class Config
{
    public $version = "1.0.0";
    public $site_name = "Local Playa v2";
    public $file = "/var/www/html/api/files.json";
    public $site_logo = "http://10.10.10.10/api/playa/v2/logo.png";
    public $secret_key = "e2hO61ZS1P4hDlu3iFUN24r6fXUeVvRByx1OAXMQvLYzNrDxA066Fae8DGjXh0KX";
    public $token_expires_after = 3600; // 1 hour
    public $refresh_token_expires_after = 604800; // 7 days
    public $display_name = "User";
    public $role = "premium";
    private $username = "user";
    private $password = "pass";


    /*
        Implement your own auth
    */
    public function checkCreds($u, $p)
    {
        if ($u === $this->username && $p === $this->password) {
            return true;
        }
        return false;
    }

    public function getDisplayName($u = null)
    {
        return $this->display_name;
    }

    public function getRole($u = null)
    {
        return $this->role;
    }

}