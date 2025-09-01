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

// Build API URL (base-path aware)
$appUrl = rtrim($config['web']['url'] ?? '', '/');
$base = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '';
$root = $appUrl !== '' ? $appUrl : ($base !== '' ? $base : '');
$url = $root . '/api/config/';

// Fetch config
if (PHP_SAPI === 'cli-server') {
    $proxy = __DIR__ . '/../../api/caddy/index.php';
    if (!defined('CADDY_PROXY_EMBED')) define('CADDY_PROXY_EMBED', true);
    $backup = ['_SERVER' => $_SERVER];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['PATH_INFO'] = '/config/';
    $_SERVER['QUERY_STRING'] = '';
    ob_start();
    include $proxy;
    $response = ob_get_clean();
    $_SERVER = $backup['_SERVER'];
} else {
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
}

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
                                <button class="btnEditRoute px-2 py-1 rounded bg-amber-500 text-white text-xs hover:bg-amber-600" data-idx="<?= $i ?>">Edit</button>
                                <button class="btnDeleteRoute px-2 py-1 rounded bg-red-600 text-white text-xs hover:bg-red-700" data-idx="<?= $i ?>">Delete</button>
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
        const base = '<?= defined('BASE_PATH') ? addslashes(BASE_PATH) : '' ?>';
        try {
            const getRes = await fetch(base + '/api/caddy/config/', { headers: { 'Accept': 'application/json' } });
            let cfg = null; let getText = '';
            try { cfg = await getRes.json(); } catch { try { getText = await getRes.text(); } catch { getText=''; } }
            if (!getRes.ok || !cfg || typeof cfg !== 'object') throw new Error(getText || 'Failed to load config');
            const srv = cfg.apps && cfg.apps.http && cfg.apps.http.servers ? cfg.apps.http.servers[serverName] : null;
            if (!srv || !Array.isArray(srv.routes) || !srv.routes[idx]) throw new Error('Route not found');
            const r = srv.routes[idx];
            // Prefill hosts
            let hs = [];
            if (Array.isArray(r.match)) {
                r.match.forEach(m => { if (m && m.host) hs.push(...[].concat(m.host)); });
            }
            // Prefill dial from reverse_proxy
            let d = '';
            if (Array.isArray(r.handle)) {
                const rp = r.handle.find(h => h && h.handler === 'reverse_proxy');
                if (rp && Array.isArray(rp.upstreams) && rp.upstreams[0] && rp.upstreams[0].dial) d = rp.upstreams[0].dial;
            }
            // Prefill insecure
            let ins = false;
            if (Array.isArray(r.handle)) {
                const rp = r.handle.find(h => h && h.handler === 'reverse_proxy');
                if (rp && rp.transport && rp.transport.tls && rp.transport.tls.insecure_skip_verify) ins = true;
            }

            $('#hosts').val(hs.join(', '));
            $('#dial').val(d);
            $('#insecure').prop('checked', !!ins);
            editIndex = idx;
            $('#modalTitle').text('Edit Route · ' + serverName);
            modal.removeClass('hidden');
            setTimeout(() => { $('#hosts').trigger('focus'); }, 0);
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Load route failed', text: (err && err.message ? err.message : String(err)) });
        }
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
    }

    $('#btnOpenAddRoute').on('click', function(){
        modal.removeClass('hidden');
        resetForm();
        setTimeout(() => { $('#hosts').trigger('focus'); }, 0);
    });
    $('#btnCloseAddRoute, #btnCloseAddRoute2, #addRouteBackdrop').on('click', function(){ modal.addClass('hidden'); resetForm(); });
    $('#formAddRoute').on('submit', async function(e){
        e.preventDefault();
        const server = serverName;
        const hostsRaw = $('#hosts').val() || '';
        let hosts = hostsRaw.split(/[,\n\s]+/).map(s => s.trim()).filter(Boolean);
        const dial = ($('#dial').val() || '').trim();
        const insecure = $('#insecure').is(':checked');
        if (!dial) { await Swal.fire({ icon:'warning', title:'Missing dial', text:'Please enter dial' }); return; }
        const base = '<?= defined('BASE_PATH') ? addslashes(BASE_PATH) : '' ?>';
        try {
            // 1) GET current config via proxy
            const getRes = await fetch(base + '/api/caddy/config/', { headers: { 'Accept': 'application/json' } });
            let cfg = null; let getText = '';
            try { cfg = await getRes.json(); } catch { try { getText = await getRes.text(); } catch { getText=''; } }
            if (!getRes.ok || !cfg || typeof cfg !== 'object') {
                throw new Error(getText || 'Failed to load config');
            }

            // 2) Build or update route
            let route = { handle: [ { handler: 'reverse_proxy', upstreams: [ { dial } ] } ] };
            if (insecure) {
                route.handle[0].transport = { protocol: 'http', tls: { insecure_skip_verify: true } };
            }
            if (hosts && hosts.length) {
                route.match = [ { host: hosts } ];
            }

            // 3) Append or update to selected server
            if (!cfg.apps || !cfg.apps.http || !cfg.apps.http.servers || !cfg.apps.http.servers[server]) {
                throw new Error('Server not found in config: ' + server);
            }
            const srv = cfg.apps.http.servers[server];
            if (!Array.isArray(srv.routes)) srv.routes = [];
            if (editIndex === null) {
                // add
                srv.routes.push(route);
            } else {
                // edit existing route at index
                const existing = srv.routes[editIndex] || {};
                // Preserve other handlers but ensure reverse_proxy exists and set
                let handles = Array.isArray(existing.handle) ? existing.handle : [];
                let rp = handles.find(h => h && h.handler === 'reverse_proxy');
                if (!rp) { rp = { handler: 'reverse_proxy' }; handles.push(rp); }
                rp.upstreams = [ { dial } ];
                if (insecure) {
                    rp.transport = { protocol: 'http', tls: { insecure_skip_verify: true } };
                } else {
                    if (rp.transport) delete rp.transport;
                }
                // Match hosts
                if (hosts && hosts.length) {
                    existing.match = [ { host: hosts } ];
                } else {
                    if (existing.match) delete existing.match;
                }
                existing.handle = handles;
                srv.routes[editIndex] = existing;
            }

            // 4) POST full config back via proxy to /load (preferred for full replace)
            const putRes = await fetch(base + '/api/caddy/load', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(cfg)
            });
            let putData = null; let putText = '';
            try { putData = await putRes.json(); } catch { try { putText = await putRes.text(); } catch { putText=''; } }
            if (!putRes.ok) {
                const msg = (putData && (putData.error || putData.body)) || putText || 'Update failed';
                throw new Error(msg);
            }
            location.reload();
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Add route failed', text: (err && err.message ? err.message : String(err)) });
        }
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

    // Delete route handler (via proxy only)
    $(document).on('click', '.btnDeleteRoute', async function(){
        const idx = parseInt($(this).data('idx'));
        if (Number.isNaN(idx)) return;
        const conf = await Swal.fire({
            icon: 'warning',
            title: 'Delete route #' + (idx + 1) + '?',
            text: 'This action cannot be undone.',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc2626'
        });
        if (!conf.isConfirmed) return;
        const server = '<?= addslashes($serverName) ?>';
        const base = '<?= defined('BASE_PATH') ? addslashes(BASE_PATH) : '' ?>';
        try {
            // 1) GET current config
            const getRes = await fetch(base + '/api/caddy/config/', { headers: { 'Accept': 'application/json' } });
            let cfg = null; let getText = '';
            try { cfg = await getRes.json(); } catch { try { getText = await getRes.text(); } catch { getText=''; } }
            if (!getRes.ok || !cfg || typeof cfg !== 'object') throw new Error(getText || 'Failed to load config');

            // 2) Remove route by index
            if (!cfg.apps || !cfg.apps.http || !cfg.apps.http.servers || !cfg.apps.http.servers[server]) throw new Error('Server not found: ' + server);
            const srv = cfg.apps.http.servers[server];
            if (!Array.isArray(srv.routes)) throw new Error('Routes list not found');
            if (idx < 0 || idx >= srv.routes.length) throw new Error('Route index out of range');
            srv.routes.splice(idx, 1);

            // 3) POST full config to /load
            const putRes = await fetch(base + '/api/caddy/load', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(cfg)
            });
            let putData = null; let putText = '';
            try { putData = await putRes.json(); } catch { try { putText = await putRes.text(); } catch { putText=''; } }
            if (!putRes.ok) {
                const msg = (putData && (putData.error || putData.body)) || putText || 'Delete failed';
                throw new Error(msg);
            }
            location.reload();
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Delete route failed', text: (err && err.message ? err.message : String(err)) });
        }
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
            <form id="formAddRoute" class="p-4 space-y-4">
                <div>
                    <label for="hosts" class="block text-sm mb-1 text-theme">Hosts (comma or newline separated)</label>
                    <textarea id="hosts" class="w-full input-theme rounded-[14px] text-sm h-[90px]" rows="2" placeholder="example.com, api.example.com"></textarea>
                </div>
                <div>
                    <label for="dial" class="block text-sm mb-1 text-theme">Dial (ip[:port])</label>
                    <input id="dial" type="text" class="w-full input-theme rounded-[55px] text-center text-[16px] h-[50px]" placeholder="52.63.187.129:3001" />
                </div>
                <div class="flex items-center gap-2">
                    <input id="insecure" type="checkbox" class="h-4 w-4" />
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
