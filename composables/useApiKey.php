<?php
use App\includes\Database;

return [
    'create' => function(string $label = null) {
        $pdo = Database::getInstance();
        if (!$pdo) return ['status'=>'error','message'=>'DB not available'];
        // Generate token
        $token = bin2hex(random_bytes(24));
        $stmt = $pdo->prepare('INSERT INTO api_keys (token, label, active) VALUES (:t, :l, 1)');
        $stmt->execute([':t'=>$token, ':l'=>$label]);
        return ['status'=>'success','token'=>$token];
    },
    'list' => function() {
        $pdo = Database::getInstance();
        if (!$pdo) return [];
        $rows = $pdo->query('SELECT id, token, label, active, created_at, last_used_at FROM api_keys ORDER BY id DESC')->fetchAll();
        return $rows ?: [];
    },
    'revoke' => function(int $id) {
        $pdo = Database::getInstance();
        if (!$pdo) return ['status'=>'error','message'=>'DB not available'];
        $stmt = $pdo->prepare('UPDATE api_keys SET active = 0 WHERE id = :id');
        $stmt->execute([':id'=>$id]);
        return ['status'=>'success'];
    },
    'delete' => function(int $id) {
        $pdo = Database::getInstance();
        if (!$pdo) return ['status'=>'error','message'=>'DB not available'];
        $stmt = $pdo->prepare('DELETE FROM api_keys WHERE id = :id');
        $stmt->execute([':id'=>$id]);
        return ['status'=>'success'];
    },
    'verify' => function(string $token) {
        $pdo = Database::getInstance();
        if (!$pdo) return false;
        $stmt = $pdo->prepare('SELECT id, active FROM api_keys WHERE token = :t LIMIT 1');
        $stmt->execute([':t'=>$token]);
        $row = $stmt->fetch();
        if (!$row || (int)$row['active'] !== 1) return false;
        try {
            $up = $pdo->prepare('UPDATE api_keys SET last_used_at = CURRENT_TIMESTAMP WHERE id = :id');
            $up->execute([':id'=>$row['id']]);
        } catch (\Throwable $e) {}
        return true;
    }
];
