<?php

namespace VRPAPI;

require 'vendor/autoload.php';
require_once 'config.php';

use CONFIGURATION\Config as Config;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class VRP
{
    private $files_list;
    private $token = null;
    private $refresh_token = null;

    private $config;

    private $extracted_auth_header = null;
    private $auth = false;

    public function __construct($skip_auth_throw = false)
    {
        $this->config = new Config();
        $data = file_get_contents($this->config->file);
        $this->files_list = json_decode($data, true);

        $this->_extract_auth_header($skip_auth_throw);
    }

    private function _checkAuth($skip_auth_throw = false)
    {
        try {
            if (is_null($this->extracted_auth_header)) {
                throw new \Exception("Token not provided");
            }
            $decoded = JWT::decode($this->extracted_auth_header, new Key($this->config->secret_key, 'HS256'));
            $decoded_array = (array) $decoded;
            if (isset($decoded_array['username'])) {
                $this->auth = true;
            } else {
                throw new \Exception("Unable to get username from token");
            }
        } catch (\Exception $e) {
            if (!$skip_auth_throw) {
                echo json_encode(['status' => ['code' => 401, 'message' => 'Unauthorized']]);
                die();
            }
        }
    }
    private function _extract_auth_header($skip_auth_throw = false)
    {
        $headers = getallheaders();
        $this->extracted_auth_header = null;

        if (isset($headers['Authorization'])) {
            $authorizationHeader = $headers['Authorization'];
            $matches = array();
            if (preg_match('/Bearer (.+)/', $authorizationHeader, $matches)) {
                if (isset($matches[1])) {
                    $this->extracted_auth_header = $matches[1];
                }
            }
        }
        $this->_checkAuth($skip_auth_throw);
    }

    private function files_loop($lambda)
    {
        $output = [];
        foreach ($this->files_list["videos"] as $group) {
            $output[] = $lambda($group);
        }
        return $output;
    }

    private function _getGroupID($grp_name)
    {
        return preg_replace("![^a-z0-9]+!i", "-", strtolower($grp_name));
    }
    private function _getCategories()
    {
        return $this->files_loop(function ($group) {
            return ["id" => $this->_getGroupID($group["name"]), "title" => $group["name"]];
        });
    }

    private function _getProjection($screen_type)
    {
        switch ($screen_type) {
            case '360':
            case 'sphere360':
                return "360";
            case 'fisheye':
                return "FSH";
            case 'screen':
                return "FLAT";
            default:
            case 'sphere180':
            case 'tb':
            case 'sbs':
                return "180";
        }
    }

    private function _getStereo($screen_type)
    {
        switch ($screen_type) {
            case '360':
            case 'tb':
                return "TB";
            case 'sphere180':
            case 'sphere360':
            case 'screen':
                return "MONO";
            default:
            case 'fisheye':
            case 'sbs':
                return "LR";
        }
    }

    private function _getVideos($title = null, $include_groups = null, $exclude_groups = null, $full = false)
    {
        $out = $this->files_loop(function ($group) use ($title, $full, $exclude_groups, $include_groups) {
            $output = [];
            if (is_null($exclude_groups) || (!is_null($exclude_groups) && !in_array($this->_getGroupID($group["name"]), explode(',', $exclude_groups)))) {
                if (is_null($include_groups) || (!is_null($include_groups) && in_array($this->_getGroupID($group["name"]), explode(',', $include_groups)))) {
                    foreach ($group["list"] as $video) {
                        if (!is_null($title) && !str_contains(strtolower($video['name']), strtolower($title))) {
                            continue;
                        }
                        $v = [
                            "id" => md5($video['name']),
                            "title" => $video['name'],
                            "preview_image" => current(explode('/', $_SERVER['SERVER_PROTOCOL'])) . "://" . $_SERVER['SERVER_ADDR'] . "/" . $video['thumbnail'],
                            "release_date" => explode('.', strval($video['epoch']))[0]
                        ];
                        if ($full) {
                            $v['details'] = [
                                [
                                    "type" => "full",
                                    "links" => [
                                        [
                                            "is_stream" => true,
                                            "is_download" => false,
                                            "url" => current(explode('/', $_SERVER['SERVER_PROTOCOL'])) . "://" . $_SERVER['SERVER_ADDR'] . "/" . $video['src'],
                                            "unavailable_reason" => null,
                                            "projection" => $this->_getProjection($video['screen_type']),
                                            "stereo" => $this->_getStereo($video['screen_type']),
                                            "quality_name" => "Good",
                                            "quality_order" => 80
                                        ]
                                    ]
                                ]
                            ];
                        }
                        $output[] = $v;
                    }
                }
            }
            return $output;
        });

        return array_merge(...$out);
    }

    private function _getVideoBy_md5($md5)
    {
        foreach ($this->_getVideos(null, null, null, true) as $video) {
            if ($video["id"] === $md5) {
                return $video;
            }
        }
        return null;
    }

    private function _sortVideos($data, $order, $direction)
    {
        usort($data, function ($item1, $item2) use ($direction) {
            if (strtolower($direction) === "desc") {
                return $item2['release_date'] <=> $item1['release_date'];
            } else {
                return $item1['release_date'] <=> $item2['release_date'];
            }
        });
        return $data;
    }

    public function getConfig()
    {
        return [
            'status' => ['code' => 1, 'message' => 'ok'],
            "data" => [
                "site_name" => $this->config->site_name,
                "site_logo" => $this->config->site_logo,
                "actors" => false,
                "categories" => true,
                "studios" => false,
                "categories_groups" => false,
                "analytics" => false
            ]
        ];
    }

    public function getVersion()
    {
        return ['status' => ['code' => 1, 'message' => 'ok'], "data" => $this->config->version];
    }

    public function getProfile()
    {
        return [
            'status' => ['code' => 1, 'message' => 'ok'],
            "data" => [
                "display_name" => $this->config->getDisplayName(),
                "role" => $this->config->getRole()
            ]
        ];
    }

    private function generate_tokens(string $user)
    {
        $secret_key = $this->config->secret_key;
        $now = time();
        $token_payload = [
            'username' => $user,
            'sub' => 101,
            'iat' => $now,
            'exp' => $now + $this->config->token_expires_after
        ];
        $refresh_token_payload = [
            'username' => $user,
            'sub' => 101,
            'iat' => $now,
            'exp' => $now + $this->config->refresh_token_expires_after
        ];

        $this->token = JWT::encode($token_payload, $secret_key, 'HS256');
        $this->refresh_token = JWT::encode($refresh_token_payload, $secret_key, 'HS256');
    }

    private function getTokens()
    {
        return [
            "access_token" => $this->token,
            "refresh_token" => $this->refresh_token
        ];
    }

    private function checkCreds(string $user, string $pass)
    {
        if ($this->config->checkCreds($user, $pass)) {
            $this->generate_tokens($user);
            return "OK";
        }
        return 'BAD_CRED';
    }

    public function login($user, $pass)
    {
        switch ($this->checkCreds($user, $pass)) {
            case 'BAD_CRED':
                return ['status' => ['code' => 401, 'message' => 'Unauthorized']];
            case 'OK':
                return [
                    'status' => ['code' => 1, "message" => "Login successful"],
                    "data" => $this->getTokens()
                ];
        }
    }

    public function refreshToken($token)
    {
        try {
            if (is_null($token)) {
                throw new \Exception("Token not provided");
            }
            $decoded = JWT::decode($token, new Key($this->config->secret_key, 'HS256'));
            $decoded_array = (array) $decoded;
            if (isset($decoded_array['username'])) {
                $this->generate_tokens($decoded_array['username']);
            } else {
                throw new \Exception("Unable to get username from token");
            }
        } catch (\Exception $e) {
            return ['status' => ['code' => 401, 'message' => 'Unauthorized']];
        }

        return [
            'status' => ['code' => 1, "message" => "ok"],
            "data" => $this->getTokens()
        ];
    }

    public function getVideos($page = null, $included_categories = null, $order = null, $direction = null, $excluded_categories = null, $title = null, $page_size = null)
    {
        $q = [];
        if ($page === null) {
            $q['page-index'] = 0;
        } else {
            $q['page-index'] = intval($page);
        }

        if ($page_size === null) {
            $q['page-size'] = 12;
        } else {
            $q['page-size'] = intval($page_size);
        }

        if ($order === null) {
            $q['order'] = 'release_date';
        } else {
            $q['order'] = $order;
        }

        if ($direction === null) {
            $q['direction'] = 'desc';
        } else {
            $q['direction'] = $direction;
        }

        if ($title !== null) {
            $q['title'] = $title;
        } else {
            $q['title'] = null;
        }

        if (!$this->auth) {
            return [
                'status' => ['code' => 1, "message" => "ok"],
                "data" => [
                    "page_index" => 0,
                    "page_size" => $q['page-size'],
                    "page_total" => 0,
                    "item_total" => 0,
                    "content" => []
                ]
            ];
        }

        $merged = $this->_getVideos($q['title'], $included_categories, $excluded_categories);
        $sorted = $this->_sortVideos($merged, $q['order'], $q['direction']);
        $count = count($sorted);

        $output = [
            'status' => ['code' => 1, "message" => "ok"],
            "data" => [
                "page_index" => $q['page-index'],
                "page_size" => $q['page-size'],
                "page_total" => ceil($count / $q['page-size']) - 1,
                "item_total" => $count,
                "content" => array_slice($sorted, $q['page-index'] * $q['page-size'], $q['page-size'])
            ]
        ];

        return $output;
    }

    public function getVideoDetails($id)
    {
        $match = $this->_getVideoBy_md5($id);
        if (is_null($match)) {
            return [
                'status' => ['code' => 3, "message" => "Video not found"],
            ];
        }
        return [
            'status' => ['code' => 1, "message" => "ok"],
            "data" => $match
        ];
    }

    public function getCategories()
    {
        return [
            'status' => ['code' => 1, "message" => "ok"],
            "data" => $this->_getCategories()
        ];
    }

}
