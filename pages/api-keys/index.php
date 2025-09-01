<?php
header('Content-Type: text/html; charset=utf-8');
$layout->setLayout('auth');
$config = getConfig();
$setHead(<<<HTML
<title> API Keys - {$config['web']['name']}</title>
HTML);

$api = composables('useApiKey');

$err = null; $msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $label = trim((string)($_POST['label'] ?? ''));
            $res = $api['create']($label ?: null);
            if (($res['status'] ?? '') === 'success') {
                $msg = 'Created new API key';
                $newToken = $res['token'];
            } else { $err = $res['message'] ?? 'Create failed'; }
        } elseif ($action === 'revoke') {
            $id = (int)($_POST['id'] ?? 0);
            $res = $api['revoke']($id);
            if (($res['status'] ?? '') === 'success') { $msg = 'Key revoked'; } else { $err = $res['message'] ?? 'Revoke failed'; }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $res = $api['delete']($id);
            if (($res['status'] ?? '') === 'success') { $msg = 'Key deleted'; } else { $err = $res['message'] ?? 'Delete failed'; }
        }
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

$list = $api['list']();
?>

<div class="max-w-4xl mx-auto px-4 py-8">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">API Keys</h1>
    <form method="post" class="flex items-center gap-2">
      <input type="hidden" name="action" value="create" />
      <input type="text" name="label" placeholder="Label (optional)" class="w-64 input-theme rounded-[10px] h-[36px] px-3" />
      <button type="submit" class="px-3 py-2 rounded bg-green-600 text-white text-sm hover:bg-green-700">+ Create</button>
    </form>
  </div>

  <?php if ($err): ?>
    <div class="mb-4 p-3 rounded bg-red-700/30 text-red-200"><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>
  <?php if ($msg): ?>
    <div class="mb-4 p-3 rounded bg-emerald-700/30 text-emerald-200"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>
  <?php if (!empty($newToken)): ?>
    <div class="mb-4 p-3 rounded bg-yellow-700/30 text-yellow-100">
      <div class="font-semibold mb-1">New API Key (copy now, will not be shown again):</div>
      <code class="break-all text-sm"><?= htmlspecialchars($newToken) ?></code>
    </div>
  <?php endif; ?>

  <div class="bg-theme shadow rounded-lg p-4">
    <table class="min-w-full display stripe hover bg-theme table-theme" id="keysTable" style="width:100%">
      <thead>
        <tr>
          <th class="text-left text-theme">#</th>
          <th class="text-left text-theme">Label</th>
          <th class="text-left text-theme">Token</th>
          <th class="text-left text-theme">Active</th>
          <th class="text-left text-theme">Created</th>
          <th class="text-left text-theme">Last Used</th>
          <th class="text-left text-theme">Action</th>
        </tr>
      </thead>
      <tbody>
      <?php if (is_array($list) && count($list)): ?>
        <?php foreach ($list as $i => $k): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td class="text-theme"><?= htmlspecialchars((string)($k['label'] ?? '')) ?></td>
            <td class="text-theme"><code class="text-xs break-all"><?= htmlspecialchars((string)$k['token']) ?></code></td>
            <td class="text-theme"><?= ((int)($k['active'] ?? 0) === 1 ? 'Yes' : 'No') ?></td>
            <td class="text-theme text-xs"><?= htmlspecialchars((string)($k['created_at'] ?? '')) ?></td>
            <td class="text-theme text-xs"><?= htmlspecialchars((string)($k['last_used_at'] ?? '')) ?></td>
            <td class="space-x-1">
              <?php if ((int)($k['active'] ?? 0) === 1): ?>
                <form method="post" style="display:inline">
                  <input type="hidden" name="action" value="revoke" />
                  <input type="hidden" name="id" value="<?= (int)$k['id'] ?>" />
                  <button type="submit" class="px-2 py-1 rounded bg-amber-500 text-white text-xs hover:bg-amber-600 btn-revoke">Revoke</button>
                </form>
              <?php endif; ?>
              <form method="post" style="display:inline">
                <input type="hidden" name="action" value="delete" />
                <input type="hidden" name="id" value="<?= (int)$k['id'] ?>" />
                <button type="submit" class="px-2 py-1 rounded bg-red-600 text-white text-xs hover:bg-red-700 btn-delete">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
$(function(){
  if ($.fn.DataTable) {
    $('#keysTable').DataTable({
      responsive: true,
      pageLength: 10,
      lengthMenu: [10, 25, 50, 100],
      order: [[0, 'asc']],
      columnDefs: [ { targets: '_all', className: 'text-theme' } ],
      language: {
        search: 'Search:', lengthMenu: 'Show _MENU_', info: 'Showing _START_ to _END_ of _TOTAL_ entries', emptyTable: 'No keys.'
      }
    });
  }

  // SweetAlert: confirm revoke/delete
  $(document).on('click', '.btn-revoke', async function(e){
    e.preventDefault();
    const form = this.closest('form');
    const res = await Swal.fire({
      icon: 'warning', title: 'Revoke this key?', text: 'การกระทำนี้จะปิดการใช้งานคีย์นี้',
      showCancelButton: true, confirmButtonText: 'Revoke', cancelButtonText: 'Cancel', confirmButtonColor: '#f59e0b'
    });
    if (res.isConfirmed) form.submit();
  });
  $(document).on('click', '.btn-delete', async function(e){
    e.preventDefault();
    const form = this.closest('form');
    const res = await Swal.fire({
      icon: 'warning', title: 'Delete this key?', text: 'ลบแล้วไม่สามารถกู้คืนได้',
      showCancelButton: true, confirmButtonText: 'Delete', cancelButtonText: 'Cancel', confirmButtonColor: '#dc2626'
    });
    if (res.isConfirmed) form.submit();
  });

  // SweetAlert: server-side feedback
  <?php if (!empty($err)): ?>
  Swal.fire({ icon:'error', title:'เกิดข้อผิดพลาด', text: <?= json_encode((string)$err) ?> });
  <?php endif; ?>
  <?php if (!empty($msg) && empty($newToken)): ?>
  Swal.fire({ icon:'success', title:'สำเร็จ', text: <?= json_encode((string)$msg) ?>, timer: 1500, showConfirmButton: false });
  <?php endif; ?>
  <?php if (!empty($newToken)): ?>
  Swal.fire({
    icon:'info',
    title:'API Key ใหม่',
    html: '<div style="text-align:left">' +
          '<div class="mb-2 text-sm">คัดลอกเก็บไว้เลย จะแสดงให้เห็นแค่ครั้งนี้เท่านั้น</div>' +
          '<code id="swalToken" class="break-all text-sm" style="display:block;padding:8px;border:1px dashed #ccc;border-radius:8px;margin-bottom:8px;">' + <?= json_encode((string)$newToken) ?> + '</code>' +
          '<button id="btnCopyToken" class="swal2-confirm swal2-styled" style="background:#506BF4">คัดลอก</button>' +
         '</div>',
    didOpen: () => {
      const btn = document.getElementById('btnCopyToken');
      const el = document.getElementById('swalToken');
      if (btn && el) {
        btn.addEventListener('click', async () => {
          try { await navigator.clipboard.writeText(el.textContent || '');
            Swal.update({ icon: 'success', title: 'คัดลอกแล้ว', html: Swal.getHtmlContainer().innerHTML });
          } catch(_) {}
        });
      }
    }
  });
  <?php endif; ?>
});
</script>
