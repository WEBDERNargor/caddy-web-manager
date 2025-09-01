<?php
use App\Controllers\UserController;

$layout->setLayout('auth');
$config = getConfig();
$setHead(<<<HTML
<title> Profile - {$config['web']['name']}</title>
HTML);

if (!isset($_SESSION['user_data'])) {
    header('Location: /login');
    exit();
}

$UserController = new UserController();
$user = $_SESSION['user_data'];
$message = null; $error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update-profile') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        if ($username === '' || $email === '') {
            $error = 'Username and Email are required';
        } else {
            $res = $UserController->UpdateUser((int)$user['id'], $username, $email);
            if ($res['status'] === 'success') {
                $message = 'Profile updated';
                // refresh session data from DB
                $fresh = $UserController->GetUserById((int)$user['id']);
                if ($fresh['status'] === 'success') {
                    $_SESSION['user_data'] = $fresh['user'];
                    $user = $_SESSION['user_data'];
                }
            } else { $error = $res['message'] ?? 'Update failed'; }
        }
    } elseif ($action === 'change-password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if ($new === '' || $confirm === '' || $current === '') {
            $error = 'All password fields are required';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match';
        } else {
            $res = $UserController->ChangePassword((int)$user['id'], $current, $new);
            if ($res['status'] === 'success') {
                $message = 'Password changed successfully';
            } else { $error = $res['message'] ?? 'Password change failed'; }
        }
    }
}
?>

<div class="max-w-3xl mx-auto px-4 py-8">
  <h1 class="text-2xl font-bold mb-6 text-theme">My Profile</h1>
  <?php if ($message): ?><div class="mb-4 p-3 rounded bg-green-600 text-white text-sm"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
  <?php if ($error): ?><div class="mb-4 p-3 rounded bg-red-600 text-white text-sm"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

  <div class="bg-theme shadow rounded-lg p-4 mb-8">
    <h2 class="text-lg font-semibold mb-4 text-theme">Account Info</h2>
    <form method="post" class="space-y-4">
      <input type="hidden" name="action" value="update-profile" />
      <div>
        <label class="block text-sm mb-1 text-theme" for="username">Username</label>
        <input id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" class="w-full input-theme rounded-[10px] h-[40px] px-3" />
      </div>
      <div>
        <label class="block text-sm mb-1 text-theme" for="email">Email</label>
        <input id="email" name="email" type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="w-full input-theme rounded-[10px] h-[40px] px-3" />
      </div>
      <div class="pt-2 flex justify-end gap-2">
        <button type="submit" class="px-5 py-2 rounded-[50px] bg-[#506BF4] text-white text-sm hover:opacity-90">Save</button>
      </div>
    </form>
  </div>

  <div class="bg-theme shadow rounded-lg p-4">
    <h2 class="text-lg font-semibold mb-4 text-theme">Change Password</h2>
    <form method="post" class="space-y-4">
      <input type="hidden" name="action" value="change-password" />
      <div>
        <label class="block text-sm mb-1 text-theme" for="current_password">Current Password</label>
        <input id="current_password" name="current_password" type="password" class="w-full input-theme rounded-[10px] h-[40px] px-3" />
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm mb-1 text-theme" for="new_password">New Password</label>
          <input id="new_password" name="new_password" type="password" class="w-full input-theme rounded-[10px] h-[40px] px-3" />
        </div>
        <div>
          <label class="block text-sm mb-1 text-theme" for="confirm_password">Confirm Password</label>
          <input id="confirm_password" name="confirm_password" type="password" class="w-full input-theme rounded-[10px] h-[40px] px-3" />
        </div>
      </div>
      <div class="pt-2 flex justify-end gap-2">
        <button type="submit" class="px-5 py-2 rounded-[50px] bg-[#506BF4] text-white text-sm hover:opacity-90">Update Password</button>
      </div>
    </form>
  </div>
</div>
