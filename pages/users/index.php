<?php
use App\Controllers\UserController;

$layout->setLayout('auth');
$config = getConfig();
$setHead(<<<HTML
<title> Users - {$config['web']['name']}</title>
HTML);

if (!isset($_SESSION['user_data']) || ($_SESSION['user_data']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo '<div class="max-w-3xl mx-auto px-4 py-8"><div class="p-3 rounded bg-red-600 text-white">Forbidden</div></div>';
    return;
}

$UserController = new UserController();
$message = null; $error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = trim($_POST['role'] ?? 'user');
        if ($username === '' || $email === '' || $password === '') {
            $error = 'All fields are required';
        } else {
            $res = $UserController->CreateUser($username, $email, $password, $role ?: 'user');
            if ($res['status'] === 'success') $message = 'User created'; else $error = $res['message'] ?? 'Create failed';
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? 'user');
        if ($id <= 0 || $username === '' || $email === '') { $error = 'Invalid input'; }
        else {
            $a = $UserController->UpdateUser($id, $username, $email);
            $b = $UserController->UpdateRole($id, $role ?: 'user');
            if (($a['status'] ?? '') === 'success' && ($b['status'] ?? '') === 'success') $message = 'User updated'; else $error = ($a['message'] ?? '') . ' ' . ($b['message'] ?? '');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { $error = 'Invalid user id'; }
        else {
            // prevent self-delete
            if ($id === (int)($_SESSION['user_data']['id'] ?? 0)) {
                $error = 'You cannot delete your own account';
            } else {
                $res = $UserController->DeleteUser($id);
                if ($res['status'] === 'success') $message = 'User deleted'; else $error = $res['message'] ?? 'Delete failed';
            }
        }
    } elseif ($action === 'reset-password') {
        // Simple admin reset to a new password without knowing old one
        $id = (int)($_POST['id'] ?? 0);
        $new = trim($_POST['new_password'] ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');
        if ($id <= 0 || $new === '' || $confirm === '') { $error = 'Password fields required'; }
        elseif ($new !== $confirm) { $error = 'Passwords do not match'; }
        else {
            // direct set hash
            try {
                $db = App\includes\Database::getInstance();
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $db->prepare('UPDATE users SET password_hash=:h WHERE id=:id');
                $stmt->bindParam(':h', $hash, PDO::PARAM_STR);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->execute();
                $message = 'Password reset';
            } catch (Throwable $e) { $error = 'Reset failed: ' . $e->getMessage(); }
        }
    }
}

$list = $UserController->ListUsers();
$users = ($list['status'] === 'success') ? ($list['users'] ?? []) : [];
?>

<div class="max-w-6xl mx-auto px-4 py-8">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-theme">Users</h1>
    <button id="btnOpenCreate" class="px-3 py-1.5 rounded bg-green-600 text-white text-sm hover:bg-green-700">+ Create</button>
  </div>

  <?php if ($message): ?><div class="mb-4 p-3 rounded bg-green-600 text-white text-sm"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
  <?php if ($error): ?><div class="mb-4 p-3 rounded bg-red-600 text-white text-sm"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

  <div class="bg-theme shadow rounded-lg p-4 overflow-x-auto">
    <table id="usersTable" class="min-w-full display nowrap stripe hover bg-theme" style="width:100%">
      <thead>
        <tr>
          <th class="text-left text-theme">#</th>
          <th class="text-left text-theme">Username</th>
          <th class="text-left text-theme">Email</th>
          <th class="text-left text-theme">Role</th>
          <th class="text-left text-theme">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $i => $u): ?>
          <tr>
            <td><?php echo $i+1; ?></td>
            <td class="text-theme"><?php echo htmlspecialchars($u['username']); ?></td>
            <td class="text-theme"><?php echo htmlspecialchars($u['email']); ?></td>
            <td class="text-theme"><?php echo htmlspecialchars($u['role']); ?></td>
            <td class="space-x-1">
              <button class="btnEdit px-2 py-1 rounded bg-amber-500 text-white text-xs hover:bg-amber-600" data-user='<?php echo json_encode($u, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>'>Edit</button>
              <button class="btnReset px-2 py-1 rounded bg-indigo-600 text-white text-xs hover:bg-indigo-700" data-id="<?php echo (int)$u['id']; ?>">Reset Password</button>
              <form method="post" class="inline delete-user"
                    data-delete-user="1">
                <input type="hidden" name="action" value="delete" />
                <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>" />
                <button type="submit" class="px-2 py-1 rounded bg-red-600 text-white text-xs hover:bg-red-700">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Create/Edit Modal -->
<div id="userModal" class="fixed inset-0 hidden" aria-hidden="true">
  <div id="userBackdrop" class="absolute inset-0 bg-black/50"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-theme shadow-xl rounded-lg overflow-hidden">
      <div class="px-4 py-3 border-b border-slate-700 flex items-center justify-between">
        <h3 id="userModalTitle" class="text-lg font-semibold text-theme">Create User</h3>
        <button id="btnCloseUser" class="px-3 py-1.5 rounded text-theme hover:bg-slate-700/50">✕</button>
      </div>
      <form id="userForm" method="post" class="p-4 space-y-4">
        <input type="hidden" name="action" id="userAction" value="create" />
        <input type="hidden" name="id" id="userId" />
        <div>
          <label for="username" class="block text-sm mb-1 text-theme">Username</label>
          <input id="f_username" name="username" class="w-full input-theme rounded-[10px] h-[40px] px-3" />
        </div>
        <div>
          <label for="email" class="block text-sm mb-1 text-theme">Email</label>
          <input id="f_email" name="email" type="email" class="w-full input-theme rounded-[10px] h-[40px] px-3" />
        </div>
        <div id="passwordRow">
          <label for="password" class="block text-sm mb-1 text-theme">Password</label>
          <input id="f_password" name="password" type="password" class="w-full input-theme rounded-[10px] h-[40px] px-3" />
        </div>
        <div>
          <label for="role" class="block text-sm mb-1 text-theme">Role</label>
          <select id="f_role" name="role" class="w-full input-theme rounded-[10px] h-[40px] px-3">
            <option value="user">user</option>
            <option value="admin">admin</option>
          </select>
        </div>
        <div class="pt-2 flex justify-end gap-2">
          <button type="button" id="btnCloseUser2" class="px-5 py-2 rounded-[50px] text-white text-sm bg-slate-600 hover:bg-slate-500">ยกเลิก</button>
          <button type="submit" class="px-5 py-2 rounded-[50px] bg-[#506BF4] text-white text-sm hover:opacity-90">บันทึก</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Reset Password Modal -->
<div id="resetModal" class="fixed inset-0 hidden" aria-hidden="true">
  <div id="resetBackdrop" class="absolute inset-0 bg-black/50"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-theme shadow-xl rounded-lg overflow-hidden">
      <div class="px-4 py-3 border-b border-slate-700 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-theme">Reset Password</h3>
        <button id="btnCloseReset" class="px-3 py-1.5 rounded text-theme hover:bg-slate-700/50">✕</button>
      </div>
      <form method="post" class="p-4 space-y-4">
        <input type="hidden" name="action" value="reset-password" />
        <input type="hidden" name="id" id="resetUserId" />
        <div>
          <label class="block text-sm mb-1 text-theme" for="new_password">New Password</label>
          <input id="new_password" name="new_password" type="password" class="w-full input-theme rounded-[10px] h-[40px] px-3" />
        </div>
        <div>
          <label class="block text-sm mb-1 text-theme" for="confirm_password">Confirm Password</label>
          <input id="confirm_password" name="confirm_password" type="password" class="w-full input-theme rounded-[10px] h-[40px] px-3" />
        </div>
        <div class="pt-2 flex justify-end gap-2">
          <button type="button" id="btnCloseReset2" class="px-5 py-2 rounded-[50px] text-white text-sm bg-slate-600 hover:bg-slate-500">ยกเลิก</button>
          <button type="submit" class="px-5 py-2 rounded-[50px] bg-[#506BF4] text-white text-sm hover:opacity-90">Reset</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$(function(){
  const base = '<?= defined('BASE_PATH') ? addslashes(BASE_PATH) : '' ?>';
  // DataTable theme hook (if available)
  try { if (typeof document !== 'undefined') document.dispatchEvent(new Event('theme:changed')); } catch(_){ }

  function openModal(){ $('#userModal').removeClass('hidden'); setTimeout(()=>$('#f_username').trigger('focus'),0); }
  function closeModal(){ $('#userModal').addClass('hidden'); }
  function openReset(){ $('#resetModal').removeClass('hidden'); setTimeout(()=>$('#new_password').trigger('focus'),0); }
  function closeReset(){ $('#resetModal').addClass('hidden'); }

  $('#btnOpenCreate').on('click', function(){
    $('#userModalTitle').text('Create User');
    $('#userAction').val('create');
    $('#userId').val('');
    $('#f_username').val('');
    $('#f_email').val('');
    $('#f_password').val('');
    $('#passwordRow').show();
    $('#f_role').val('user');
    openModal();
  });
  $('#btnCloseUser, #btnCloseUser2, #userBackdrop').on('click', closeModal);
  $('#btnCloseReset, #btnCloseReset2, #resetBackdrop').on('click', closeReset);

  $(document).on('click', '.btnEdit', function(){
    const u = $(this).data('user');
    $('#userModalTitle').text('Edit User');
    $('#userAction').val('update');
    $('#userId').val(u.id);
    $('#f_username').val(u.username);
    $('#f_email').val(u.email);
    $('#passwordRow').hide();
    $('#f_role').val(u.role);
    openModal();
  });

  $(document).on('click', '.btnReset', function(){
    const id = $(this).data('id');
    $('#resetUserId').val(id);
    openReset();
  });

  // SweetAlert2 confirmation for delete user
  $(document).on('submit', 'form.delete-user', async function(e){
    if (this.dataset.confirmed === '1') return; // allow actual submit after confirm
    e.preventDefault();
    const conf = await Swal.fire({
      icon: 'warning',
      title: 'Delete this user?',
      text: 'This action cannot be undone.',
      showCancelButton: true,
      confirmButtonText: 'Delete',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#dc2626'
    });
    if (conf.isConfirmed) { this.dataset.confirmed = '1'; this.submit(); }
  });
});
</script>
