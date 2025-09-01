<?php
header('Content-Type: text/html; charset=utf-8');
$layout->setLayout('auth');
$config = getConfig();
global $router;
$params = $router->getParams();
$serverName = isset($params['server']) ? $params['server'] : '';
$safeTitle = htmlspecialchars($serverName ?: 'Unknown');
$setHead(<<<HTML
<title> Routes - {$safeTitle}</title>
HTML);

// Direct Caddy Admin API URL
$caddyUrl = rtrim($config['web']['caddy_url'] ?? 'http://127.0.0.1:2019', '/');
$url = $caddyUrl . '/config/';

// Handle POST actions (PHP-only API calls to Caddy)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirect = (defined('BASE_PATH') ? BASE_PATH : '') . '/route/' . urlencode($serverName);
    $status = 'ok'; $msg = '';
    try {
        // 1) Load current config
        $ch = curl_init($caddyUrl . '/config/');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        ]);
        $res = curl_exec($ch);
        if ($res === false) throw new \RuntimeException('Failed to load config: ' . curl_error($ch));
        $cfg = json_decode($res, true);
        curl_close($ch);
        if (!is_array($cfg)) throw new \RuntimeException('Invalid config JSON');

        if (!isset($cfg['apps']['http']['servers'][$serverName])) {
            throw new \RuntimeException('Server not found in config: ' . $serverName);
        }
        $srv =& $cfg['apps']['http']['servers'][$serverName];
        if (!isset($srv['routes']) || !is_array($srv['routes'])) $srv['routes'] = [];

        if ($action === 'save_route') {
            $hostsRaw = (string)($_POST['hosts'] ?? '');
            $hosts = array_values(array_filter(array_map(function($s){ return trim($s); }, preg_split('/[,\n\s]+/', $hostsRaw)), 'strlen'));
            $dial = trim((string)($_POST['dial'] ?? ''));
            $insecure = isset($_POST['insecure']) && $_POST['insecure'] == '1';
            $editIndex = isset($_POST['edit_index']) && $_POST['edit_index'] !== '' ? (int)$_POST['edit_index'] : null;
            if ($dial === '') throw new \RuntimeException('Missing dial');

            $route = [ 'handle' => [ [ 'handler' => 'reverse_proxy', 'upstreams' => [ [ 'dial' => $dial ] ] ] ] ];
            if ($insecure) {
                $route['handle'][0]['transport'] = [ 'protocol' => 'http', 'tls' => [ 'insecure_skip_verify' => true ] ];
            }
            if (!empty($hosts)) { $route['match'] = [ [ 'host' => $hosts ] ]; }

            if ($editIndex === null) {
                $srv['routes'][] = $route;
            } else {
                // Merge into existing
                $existing = $srv['routes'][$editIndex] ?? [];
                $handles = isset($existing['handle']) && is_array($existing['handle']) ? $existing['handle'] : [];
                // find reverse_proxy
                $found = false;
                foreach ($handles as &$h) {
                    if (is_array($h) && ($h['handler'] ?? '') === 'reverse_proxy') {
                        $h['upstreams'] = [ [ 'dial' => $dial ] ];
                        if ($insecure) {
                            $h['transport'] = [ 'protocol' => 'http', 'tls' => [ 'insecure_skip_verify' => true ] ];
                        } else {
                            if (isset($h['transport'])) unset($h['transport']);
                        }
                        $found = true; break;
                    }
                }
                unset($h);
                if (!$found) { $handles[] = [ 'handler' => 'reverse_proxy', 'upstreams' => [ [ 'dial' => $dial ] ] ]; }
                $existing['handle'] = $handles;
                if (!empty($hosts)) $existing['match'] = [ [ 'host' => $hosts ] ]; else if (isset($existing['match'])) unset($existing['match']);
                $srv['routes'][$editIndex] = $existing;
            }
        } elseif ($action === 'delete_route') {
            $idx = isset($_POST['idx']) ? (int)$_POST['idx'] : -1;
            if ($idx < 0 || $idx >= count($srv['routes'])) throw new \RuntimeException('Invalid route index');
            array_splice($srv['routes'], $idx, 1);
        } else {
            throw new \RuntimeException('Unknown action');
        }

        // 3) POST full config back to /load
        $ch2 = curl_init($caddyUrl . '/load');
        curl_setopt_array($ch2, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_POSTFIELDS => json_encode($cfg),
        ]);
        $res2 = curl_exec($ch2);
        if ($res2 === false) throw new \RuntimeException('Update failed: ' . curl_error($ch2));
        $code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE) ?: 500;
        curl_close($ch2);
        if ($code2 < 200 || $code2 >= 300) {
            $body = (string)$res2;
            $msg = 'Caddy responded ' . $code2 . ' ' . $body;
            $status = 'error';
        }
    } catch (\Throwable $e) {
        $status = 'error';
        $msg = $e->getMessage();
    }
    // Redirect with status
    $qs = 'status=' . urlencode($status) . ($msg !== '' ? ('&msg=' . urlencode($msg)) : '');
    header('Location: ' . $redirect . '?' . $qs);
    exit;
}

// Fetch config directly from Caddy
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_CONNECTTIMEOUT => 2,
    CURLOPT_TIMEOUT => 6,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
));
$response = curl_exec($curl);
curl_close($curl);

$data = json_decode($response ?? '', true);

// Extract routes for the selected server
$routes = [];
if ($serverName && is_array($data)) {
    $apps = $data['apps'] ?? null;
    if (is_array($apps)) {
        $http = $apps['http'] ?? null;
        if (is_array($http)) {
            $srvMap = $http['servers'] ?? null;
            if (is_array($srvMap) && isset($srvMap[$serverName]) && is_array($srvMap[$serverName])) {
                $routes = $srvMap[$serverName]['routes'] ?? [];
            }
        }
    }
}

// Helper to summarize a route's matchers
function summarize_matchers($route) {
    $parts = [];
    if (!is_array($route)) return '';
    if (!empty($route['match']) && is_array($route['match'])) {
        foreach ($route['match'] as $m) {
            if (isset($m['host'])) {
                $parts[] = 'host=' . implode(',', (array)$m['host']);
            }
            if (isset($m['path'])) {
                $parts[] = 'path=' . implode(',', (array)$m['path']);
            }
            if (isset($m['method'])) {
                $parts[] = 'method=' . implode(',', (array)$m['method']);
            }
        }
    }
    return implode(' | ', $parts);
}

// Extract host list from a route's matchers
function extract_hosts($route) {
    if (!is_array($route) || empty($route['match']) || !is_array($route['match'])) return '';
    $hosts = [];
    foreach ($route['match'] as $m) {
        if (isset($m['host'])) {
            foreach ((array)$m['host'] as $h) {
                $hosts[] = $h;
            }
        }
    }
    $hosts = array_values(array_unique($hosts));
    return implode(', ', $hosts);
}

// Same as extract_hosts but returns an array for easier rendering
function extract_hosts_array($route) {
    if (!is_array($route) || empty($route['match']) || !is_array($route['match'])) return [];
    $hosts = [];
    foreach ($route['match'] as $m) {
        if (isset($m['host'])) {
            foreach ((array)$m['host'] as $h) {
                $hosts[] = $h;
            }
        }
    }
    return array_values(array_unique($hosts));
}

// Extract upstream dial targets (ip[:port]) from reverse_proxy handles
function extract_dials($route) {
    if (!is_array($route) || empty($route['handle']) || !is_array($route['handle'])) return '';
    $dials = [];
    foreach ($route['handle'] as $h) {
        if (is_array($h) && ($h['handler'] ?? '') === 'reverse_proxy') {
            if (!empty($h['upstreams']) && is_array($h['upstreams'])) {
                foreach ($h['upstreams'] as $u) {
                    if (isset($u['dial']) && is_string($u['dial'])) {
                        $dials[] = $u['dial'];
                    }
                }
            }
        }
    }
    $dials = array_values(array_unique($dials));
    return implode(', ', $dials);
}
?>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Routes · <span class="text-theme"><?= htmlspecialchars($serverName) ?></span></h1>
        <div class="flex items-center gap-3">
            <a href="<?= defined('BASE_PATH') ? BASE_PATH : '' ?>/" class="text-sm text-theme hover:underline">← Back</a>
            <button id="btnOpenAddRoute" class="px-3 py-1.5 rounded bg-green-600 text-white text-sm hover:bg-green-700">+ Add Route</button>
        </div>
    </div>

    <div class="bg-theme shadow rounded-lg p-4 overflow-x-auto">
        <table class="min-w-full display nowrap stripe hover bg-theme" id="routesTable" style="width:100%">
            <thead>
                <tr>
                    <th class="text-left text-theme">#</th>
                    <th class="text-left text-theme">Host</th>
                    <th class="text-left text-theme">Dial</th>
                    <th class="text-left text-theme">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($routes) && is_array($routes)): ?>
                    <?php foreach ($routes as $i => $r): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td class="text-theme">
                                <?php $hs = extract_hosts_array($r); ?>
                                <?php if (!empty($hs)): ?>
                                    <?php foreach ($hs as $h): ?>
                                        <span class="inline-block px-2 py-0.5 rounded bg-slate-700/40 mr-1 mb-1"><?= htmlspecialchars($h) ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-theme"><?= htmlspecialchars(extract_dials($r)) ?></td>
                            <td class="space-x-1">
                                <?php
                                    // Prepare prefill data
                                    $prefillHosts = implode(', ', extract_hosts_array($r));
                                    $prefillDial = extract_dials($r);
                                    $insecure = false;
                                    if (!empty($r['handle']) && is_array($r['handle'])) {
                                        foreach ($r['handle'] as $h) {
                                            if (is_array($h) && ($h['handler'] ?? '') === 'reverse_proxy') {
                                                if (!empty($h['transport']['tls']['insecure_skip_verify'])) { $insecure = true; }
                                            }
                                        }
                                    }
                                ?>
                                <button class="btnEditRoute px-2 py-1 rounded bg-amber-500 text-white text-xs hover:bg-amber-600"
                                    data-idx="<?= $i ?>"
                                    data-hosts="<?= htmlspecialchars($prefillHosts) ?>"
                                    data-dial="<?= htmlspecialchars($prefillDial) ?>"
                                    data-insecure="<?= $insecure ? '1' : '0' ?>">Edit</button>
                                <form method="post" class="inline formDeleteRoute">
                                    <input type="hidden" name="action" value="delete_route" />
                                    <input type="hidden" name="idx" value="<?= $i ?>" />
                                    <button type="submit" class="px-2 py-1 rounded bg-red-600 text-white text-xs hover:bg-red-700">Delete</button>
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
    const caddyUrl = '<?= addslashes($caddyUrl) ?>';
    if ($.fn.DataTable) {
        $('#routesTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [[0, 'asc']],
            columnDefs: [ { targets: '_all', className: 'text-theme' } ],
            language: { search: 'Search:', lengthMenu: 'Show _MENU_', info: 'Showing _START_ to _END_ of _TOTAL_ entries', emptyTable: 'No routes found for this server.' }
        });

    // Auto-open modal if redirected with ?new=1 from navbar
    try {
        const usp = new URLSearchParams(window.location.search);
        if (usp.get('new') === '1') {
            modal.removeClass('hidden');
            resetForm();
            setTimeout(() => { $('#hosts').trigger('focus'); }, 0);
        }
    } catch (e) { /* no-op */ }

    // Edit route handler
    $(document).on('click', '.btnEditRoute', async function(){
        const idx = parseInt($(this).data('idx'));
        if (Number.isNaN(idx)) return;
        const hosts = $(this).data('hosts') || '';
        const dial = $(this).data('dial') || '';
        const insecure = String($(this).data('insecure') || '0') === '1';
        $('#hosts').val(hosts);
        $('#dial').val(dial);
        $('#insecure').prop('checked', insecure);
        editIndex = idx;
        $('#modalTitle').text('Edit Route · ' + serverName);
        modal.removeClass('hidden');
        setTimeout(() => { $('#hosts').trigger('focus'); }, 0);
    });
    }
    // Modal logic
    const modal = $('#addRouteModal');
    let editIndex = null; // null = add, number = edit
    const serverName = '<?= addslashes($serverName) ?>';
    function resetForm(){
        $('#hosts').val('');
        $('#dial').val('');
        $('#insecure').prop('checked', false);
        editIndex = null;
        $('#modalTitle').text('Add Route · ' + serverName);
        $('#formAction').val('save_route');
        $('#editIndex').val('');
    }

    $('#btnOpenAddRoute').on('click', function(){
        modal.removeClass('hidden');
        resetForm();
        setTimeout(() => { $('#hosts').trigger('focus'); }, 0);
    });
    $('#btnCloseAddRoute, #btnCloseAddRoute2, #addRouteBackdrop').on('click', function(){ modal.addClass('hidden'); resetForm(); });
    // When submitting, set hidden edit index and let the browser POST
    $('#formAddRoute').on('submit', function(){
        $('#formAction').val('save_route');
        $('#editIndex').val(editIndex === null ? '' : String(editIndex));
        return true;
    });

    // Keyboard shortcuts while modal is open
    $(document).on('keydown', function(e){
        if (modal.hasClass('hidden')) return;
        if (e.key === 'Escape') {
            modal.addClass('hidden');
        } else if (e.key === 'Enter' && !$(e.target).is('textarea')) {
            $('#formAddRoute').trigger('submit');
        }
    });

    // SweetAlert2: show status alerts from query params
    (async function(){
        try {
            const usp = new URLSearchParams(window.location.search);
            const status = usp.get('status');
            const msg = usp.get('msg') || '';
            if (status) {
                await Swal.fire({
                    icon: status === 'ok' ? 'success' : 'error',
                    title: status === 'ok' ? 'Success' : 'Error',
                    text: msg
                });
                usp.delete('status');
                usp.delete('msg');
                const newUrl = window.location.pathname + (usp.toString() ? ('?' + usp.toString()) : '');
                history.replaceState({}, '', newUrl);
            }
        } catch (e) { /* no-op */ }
    })();

    // SweetAlert2: confirm delete route
    $(document).on('submit', '.formDeleteRoute', async function(e){
        e.preventDefault();
        const form = this;
        const idx = $(form).find('input[name="idx"]').val() || '';
        try {
            const res = await Swal.fire({
                icon: 'warning',
                title: 'Delete route',
                text: idx ? ('Delete route #' + (parseInt(idx,10)+1) + ' ?') : 'Delete this route?',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc2626'
            });
            if (res.isConfirmed) form.submit();
        } catch(_) { /* no-op */ }
    });
});
</script>

<!-- Add Route Modal -->
<div id="addRouteModal" class="fixed inset-0 hidden" aria-hidden="true">
    <div id="addRouteBackdrop" class="absolute inset-0 bg-black/50"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-lg bg-theme shadow-xl rounded-lg overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-700 flex items-center justify-between">
                <h3 id="modalTitle" class="text-lg font-semibold text-theme">Add Route · <?= htmlspecialchars($serverName) ?></h3>
                <button id="btnCloseAddRoute" class="px-3 py-1.5 rounded text-theme hover:bg-slate-700/50">✕</button>
            </div>
            <form id="formAddRoute" method="post" class="p-4 space-y-4">
                <input type="hidden" name="action" id="formAction" value="save_route" />
                <input type="hidden" name="edit_index" id="editIndex" value="" />
                <div>
                    <label for="hosts" class="block text-sm mb-1 text-theme">Hosts (comma or newline separated)</label>
                    <textarea id="hosts" name="hosts" class="w-full input-theme rounded-[14px] text-sm h-[90px]" rows="2" placeholder="example.com, api.example.com"></textarea>
                </div>
                <div>
                    <label for="dial" class="block text-sm mb-1 text-theme">Dial (ip[:port])</label>
                    <input id="dial" name="dial" type="text" class="w-full input-theme rounded-[55px] text-center text-[16px] h-[50px]" placeholder="52.63.187.129:3001" />
                </div>
                <div class="flex items-center gap-2">
                    <input id="insecure" name="insecure" value="1" type="checkbox" class="h-4 w-4" />
                    <label for="insecure" class="text-sm text-theme">Insecure TLS (skip verify)</label>
                </div>
                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" id="btnCloseAddRoute2" class="px-5 py-2 rounded-[50px] text-white text-sm bg-slate-600 hover:bg-slate-500">ยกเลิก</button>
                    <button type="submit" class="px-5 py-2 rounded-[50px] bg-[#506BF4] text-white text-sm hover:opacity-90">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>
