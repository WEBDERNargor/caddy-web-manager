<?php
namespace App\Controllers;
use App\Controllers\InitController;
use App\Controllers\UserController;
use PDO;
use PDOException;
use App\includes\Database;
use Exception;
class AuthController
{
    private $db;
    private $init;
    private $UserController;
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->init = new InitController();
        $this->init->InitDB();
        $this->UserController = new UserController();
    }
    public function Register($username, $email, $password, $role)
    {
        try {
            $create=$this->UserController->CreateUser($username, $email, $password, $role);
            if($create['status']=='success'){
                return ['status' => 'success', 'message' => 'User registered successfully'];
            }else{
                return ['status' => 'error', 'message' => $create['message']];
            }
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function Login($username, $password)
    {
        try {
            $user = $this->UserController->GetUserByUsername($username);
            if ($user['status'] == 'success') {
                if (password_verify($password, $user['user']['password_hash'])) {
                    $useJWT = composables("useJWT");
                    $token = $useJWT['encode']([
                        'user_id' => $user['user']['id'],
                        'username' => $user['user']['username'],
                        'role' => $user['user']['role']
                    ]);
                  
                    return [
                        'status' => 'success',
                        'message' => 'Login successful',
                        'token' => $token,
                        'user' => $user['user']
                    ];
                } else {
                    return [
                        'status' => 'error',
                        'message' => 'Invalid password'
                    ];
                }
            } else {
                return [
                    'status' => 'error',
                    'message' => 'User not found'
                ];
            }
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }

    }
}
