<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
$config = getConfig();
$key = $config['web']['jwt_secret'];
return [
    "encode" => function ($payload) use ($key) {
        $jwt = JWT::encode($payload, $key, 'HS256');
        return $jwt;
    },
    "decode" => function ($token) use ($key) {
        try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            return (array) $decoded;
        } catch (Exception $e) {
            return null;
        }
    }
];