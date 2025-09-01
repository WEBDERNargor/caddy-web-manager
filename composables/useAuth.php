<?php

use App\Controllers\UserController;
$UserController = new UserController();
$useJWT = composables("useJWT");
return [
    "check_login" => function () use ($UserController, $useJWT) {
        $token = getcookie("login_token");
        if ($token) {
            $decode = $useJWT['decode']($token);
            if ($decode && isset($decode['user_id'])) {
                $user = $UserController->GetUserById($decode['user_id']);
                if ($user['status'] == 'success') {
                    return [
                        'status' => 'success',
                        'user' => $user['user']
                    ];
                } else {
                    return [
                        'status' => 'error',
                        'message' => 'User not found'
                    ];
                }
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Invalid token'
                ];
            }
        } else {
            return [
                'status' => 'error',
                'message' => 'No token provided'
            ];
        }
    }
];