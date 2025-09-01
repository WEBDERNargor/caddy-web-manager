<?php
namespace App\Controllers;
use App\Controllers\InitController;
use PDO;
use PDOException;
use App\includes\Database;
use Exception;

class UserController
{
    private $db;
    private $init;
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->init = new InitController();
        $this->init->InitDB();
    }

    public function GetUserById($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                return [
                    'status' => 'success',
                    'user' => $user
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'User not found'
                ];
            }
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    public function GetUserByUsername($username)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username OR email=:email");
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':email', $username, PDO::PARAM_STR);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                return [
                    'status' => 'success',
                    'user' => $user
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'User not found'
                ];
            }
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    public function CreateUser($username, $email, $password, $role = 'user')
    {
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, :role)");
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':password_hash', $password_hash, PDO::PARAM_STR);
            $stmt->bindParam(':role', $role, PDO::PARAM_STR);
            $stmt->execute();
            $last_insert_id = $this->db->lastInsertId();
            return [
                'status' => 'success',
                'insert_id' => $last_insert_id,
                'message' => 'User created successfully'
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    public function ListUsers()
    {
        try {
            $stmt = $this->db->query("SELECT id, username, email, role, created_at, updated_at FROM users ORDER BY id ASC");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return [ 'status' => 'success', 'users' => $users ];
        } catch (PDOException $e) {
            return [ 'status' => 'error', 'message' => 'Database error: ' . $e->getMessage() ];
        } catch (Exception $e) {
            return [ 'status' => 'error', 'message' => 'Error: ' . $e->getMessage() ];
        }
    }

    public function UpdateUser($id, $username, $email)
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET username = :username, email = :email WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            return [ 'status' => 'success', 'message' => 'User updated successfully' ];
        } catch (PDOException $e) {
            return [ 'status' => 'error', 'message' => 'Database error: ' . $e->getMessage() ];
        } catch (Exception $e) {
            return [ 'status' => 'error', 'message' => 'Error: ' . $e->getMessage() ];
        }
    }

    public function ChangePassword($id, $currentPassword, $newPassword)
    {
        try {
            // Fetch current hash
            $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return [ 'status' => 'error', 'message' => 'User not found' ];
            }
            if (!password_verify($currentPassword, $row['password_hash'])) {
                return [ 'status' => 'error', 'message' => 'Current password is incorrect' ];
            }
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $up = $this->db->prepare("UPDATE users SET password_hash = :h WHERE id = :id");
            $up->bindParam(':h', $newHash, PDO::PARAM_STR);
            $up->bindParam(':id', $id, PDO::PARAM_INT);
            $up->execute();
            return [ 'status' => 'success', 'message' => 'Password updated' ];
        } catch (PDOException $e) {
            return [ 'status' => 'error', 'message' => 'Database error: ' . $e->getMessage() ];
        } catch (Exception $e) {
            return [ 'status' => 'error', 'message' => 'Error: ' . $e->getMessage() ];
        }
    }

    public function UpdateRole($id, $role)
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET role = :role WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':role', $role, PDO::PARAM_STR);
            $stmt->execute();
            return [ 'status' => 'success', 'message' => 'Role updated' ];
        } catch (PDOException $e) {
            return [ 'status' => 'error', 'message' => 'Database error: ' . $e->getMessage() ];
        } catch (Exception $e) {
            return [ 'status' => 'error', 'message' => 'Error: ' . $e->getMessage() ];
        }
    }

    public function DeleteUser($id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return [ 'status' => 'success', 'message' => 'User deleted' ];
        } catch (PDOException $e) {
            return [ 'status' => 'error', 'message' => 'Database error: ' . $e->getMessage() ];
        } catch (Exception $e) {
            return [ 'status' => 'error', 'message' => 'Error: ' . $e->getMessage() ];
        }
    }
}
