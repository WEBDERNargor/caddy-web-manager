<?php
header('Content-Type: text/html; charset=utf-8');
$layout->setLayout('auth');
$config = getConfig();
$setHead(<<<HTML
<title> Home - {$config['web']['name']}</title>
HTML);

// PHP-only call to /api/config/ that works in dev (php -S) and production
$appUrl = rtrim($config['web']['url'] ?? '', '/');
$base = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '';
$root = $appUrl !== '' ? $appUrl : ($base !== '' ? $base : '');
$url = $root . '/api/config/';

if (PHP_SAPI === 'cli-server') {
    // Avoid deadlock on built-in server by including proxy directly
    $proxy = __DIR__ . '/../api/caddy/index.php';
    if (!defined('CADDY_PROXY_EMBED')) {
        define('CADDY_PROXY_EMBED', true);
    }
    $backup = ['_SERVER' => $_SERVER];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['PATH_INFO'] = '/config/';
    $_SERVER['QUERY_STRING'] = '';
    ob_start();
    include $proxy;
    $response = ob_get_clean();
    $_SERVER = $backup['_SERVER'];
} else {
    // Normal environments → simple cURL
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

$data = json_decode($response, true);
// pre_r($data);

// Extract servers from Caddy config structure: apps -> http -> servers (object map)
$servers = [];
if (is_array($data)) {
    $apps = $data['apps'] ?? null;
    if (is_array($apps)) {
        $http = $apps['http'] ?? null;
        if (is_array($http)) {
            $srvMap = $http['servers'] ?? null;
            if (is_array($srvMap)) {
                foreach ($srvMap as $name => $srv) {
                    $port = '';
                    if (isset($srv['listen']) && is_array($srv['listen']) && count($srv['listen']) > 0) {
                        $listen = $srv['listen'][0];
                        // Examples: ":80", ":443", "0.0.0.0:8080", "[::]:443"
                        if (is_string($listen)) {
                            // strip brackets for IPv6 and extract trailing :port
                            $listen = trim($listen);
                            $colonPos = strrpos($listen, ':');
                            if ($colonPos !== false) {
                                $port = substr($listen, $colonPos + 1);
                            } else {
                                $port = $listen; // fallback
                            }
                        }
                    }
                    $servers[] = [
                        'name' => (string)$name,
                        'port' => (string)$port,
                    ];
                }
            }
        }
    }
}
?>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Servers</h1>
        <button id="btnOpenAddServer" class="px-3 py-1.5 rounded bg-green-600 text-white text-sm hover:bg-green-700">+ Add Server</button>
    </div>
    <div class="bg-theme shadow rounded-lg p-4 overflow-x-auto">
        <table id="serversTable" class="min-w-full display nowrap stripe hover bg-theme" style="width:100%">
        <thead>
            <tr>
                <th class="text-left text-theme">#</th>
                <th class="text-left text-theme">Server</th>
                <th class="text-left text-theme">Port</th>
                <th class="text-left text-theme">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($servers)): ?>
                <?php foreach ($servers as $i => $s): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="text-theme"><?= htmlspecialchars($s['name']) ?></td>
                        <td class="text-theme"><?= htmlspecialchars($s['port']) ?></td>
                        <td class="space-x-1">
                            <a href="<?= defined('BASE_PATH') ? BASE_PATH : '' ?>/route/<?= urlencode($s['name']) ?>"
                               class="px-3 py-1 inline-block rounded bg-blue-600 text-white text-xs hover:bg-blue-700">View</a>
                            <button class="btnEditServer px-2 py-1 rounded bg-amber-500 text-white text-xs hover:bg-amber-600" data-name="<?= htmlspecialchars($s['name']) ?>" data-port="<?= htmlspecialchars($s['port']) ?>">Edit</button>
                            <button class="btnDeleteServer px-2 py-1 rounded bg-red-600 text-white text-xs hover:bg-red-700" data-name="<?= htmlspecialchars($s['name']) ?>">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        </table>
    </div>
</div>

<script>
    // Initialize DataTable with a clean Tailwind look
    $(function () {
        const table = $('#serversTable').DataTable({
            responsive: true,
            pageLength: 5,
            lengthMenu: [5, 10, 25, 50],
            order: [[0, 'asc']],
            // Apply Tailwind text color class to all columns (th/td)
            columnDefs: [
                { targets: '_all', className: 'text-theme' }
            ],
            language: {
                search: 'Search:',
                lengthMenu: 'Show _MENU_',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                emptyTable: 'No servers found.'
            }
        });

        function applyDTTheme() {
            // Support DataTables v1 and v2 DOM simultaneously
            const $wrap = $('#serversTable').closest('.dataTables_wrapper, .dt-container');
            // Wrapper
            $wrap.addClass('text-theme');
            // Top controls (v1)
            $wrap.find('.dataTables_length, .dataTables_filter').addClass('text-theme');
            $wrap.find('.dataTables_length *, .dataTables_filter *').addClass('text-theme');
            // Top controls (v2)
            $wrap.find('.dt-length, .dt-search').addClass('text-theme');
            $wrap.find('.dt-length *, .dt-search *').addClass('text-theme');
            const $lenSel = $wrap.find('.dataTables_length select, .dt-length select');
            const $searchInp = $wrap.find('.dataTables_filter input, .dt-search input');
            $lenSel.addClass('input-theme border-theme text-theme');
            $searchInp.addClass('input-theme border-theme text-theme');
            // Fallback force color (in case external CSS overrides)
            try {
                const theme = (typeof readCookie === 'function') ? readCookie('theme') : null;
                const styles = getComputedStyle(document.body);
                const darkText = styles.getPropertyValue('--dark-text').trim() || '#e5e7eb';
                const lightText = styles.getPropertyValue('--light-text').trim() || '#111827';
                const borderDark = styles.getPropertyValue('--dark-border').trim() || '#374151';
                const borderLight = styles.getPropertyValue('--light-border').trim() || '#e5e7eb';
                const useText = theme === 'dark' ? darkText : lightText;
                const useBorder = theme === 'dark' ? borderDark : borderLight;
                $wrap.find('.dataTables_length label, .dataTables_filter label').css('color', useText);
                $wrap.find('.dataTables_length, .dataTables_filter').css('color', useText);
                $lenSel.css({ color: useText, borderColor: useBorder });
                $searchInp.css({ color: useText, borderColor: useBorder, caretColor: useText });
            } catch (e) { /* no-op */ }
            // Bottom controls v1
            $wrap.find('.dataTables_info').addClass('text-theme').find('*').addClass('text-theme');
            $wrap.find('.dataTables_paginate').addClass('text-theme');
            $wrap.find('.dataTables_paginate a, .dataTables_paginate span')
                .addClass('text-theme border-theme');
            $wrap.find('.paginate_button, .paginate_button.current, .paginate_button.disabled')
                .addClass('text-theme border-theme');
            // Bottom controls v2
            $wrap.find('.dt-info').addClass('text-theme').find('*').addClass('text-theme');
            $wrap.find('.dt-paging').addClass('text-theme');
            $wrap.find('.dt-paging button, .dt-paging a, .dt-paging span')
                .addClass('text-theme border-theme');
            $wrap.find('.dt-paging-button').addClass('text-theme border-theme');
            // Explicit: arrow buttons (first/prev/next/last)
            const $arrows = $wrap.find('.paginate_button.previous, .paginate_button.next, .paginate_button.first, .paginate_button.last, .dt-paging-button.previous, .dt-paging-button.next, .dt-paging-button.first, .dt-paging-button.last');
            $arrows.addClass('text-theme border-theme');
            try {
                const theme = (typeof readCookie === 'function') ? readCookie('theme') : null;
                const styles = getComputedStyle(document.body);
                const darkText = styles.getPropertyValue('--dark-text').trim() || '#e5e7eb';
                const lightText = styles.getPropertyValue('--light-text').trim() || '#111827';
                const useText = theme === 'dark' ? darkText : lightText;
                $wrap.find('.dataTables_info, .dataTables_paginate, .dataTables_paginate *').css('color', useText);
                $wrap.find('.dt-info, .dt-paging, .dt-paging *').css('color', useText);
                $wrap.find('.paginate_button.previous, .paginate_button.next, .paginate_button.first, .paginate_button.last, .dt-paging-button.previous, .dt-paging-button.next, .dt-paging-button.first, .dt-paging-button.last').css('color', useText);
            } catch (e) { /* no-op */ }
            if (typeof loadtheme === 'function') loadtheme();
        }

        // Apply immediately (post-initialization) and re-apply on events
        applyDTTheme();
        // Re-apply theme after DataTables is ready and on each redraw
        $('#serversTable').on('init.dt', function () {
            applyDTTheme();
        }).on('draw.dt', function () {
            applyDTTheme();
        });

        // When global theme changes from navbar toggle
        document.addEventListener('theme:changed', function () {
            applyDTTheme();
        });

        // Adjust on tab/layout changes if any
        setTimeout(() => {
            table.columns.adjust().draw(false);
            applyDTTheme();
        }, 50);

        // ===== Server CRUD via proxy (/api/caddy/) =====
        const base = '<?= defined('BASE_PATH') ? addslashes(BASE_PATH) : '' ?>';
        let editServerOriginal = null; // null = add, string = original name when editing

        function formatListen(port) {
            const p = String(port || '').trim();
            if (p === '') return ':80';
            // if contains ':' (e.g., 0.0.0.0:8080 or :443), keep
            if (p.includes(':')) return p;
            return ':' + p;
        }

        function openServerModal(title, name, port){
            $('#serverModalTitle').text(title);
            $('#serverName').val(name || '');
            $('#serverPort').val(port || '');
            $('#serverModal').removeClass('hidden');
            setTimeout(() => { $('#serverName').trigger('focus'); }, 0);
        }
        function closeServerModal(){
            $('#serverModal').addClass('hidden');
            editServerOriginal = null;
            $('#serverName').val('');
            $('#serverPort').val('');
        }

        // Open Add
        $('#btnOpenAddServer').on('click', function(){
            editServerOriginal = null;
            openServerModal('Add Server', '', '');
        });
        // Close
        $('#btnCloseServer, #btnCloseServer2, #serverBackdrop').on('click', function(){ closeServerModal(); });

        // Edit existing
        $(document).on('click', '.btnEditServer', function(){
            const name = $(this).data('name');
            const port = $(this).data('port');
            editServerOriginal = name;
            openServerModal('Edit Server', name, port);
        });

        // Delete server
        $(document).on('click', '.btnDeleteServer', async function(){
            const name = String($(this).data('name') || '');
            if (!name) return;
            const conf = await Swal.fire({
                icon: 'warning',
                title: 'Delete server',
                text: 'Delete server "' + name + '" ? This action cannot be undone.',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc2626'
            });
            if (!conf.isConfirmed) return;
            try {
                const getRes = await fetch(base + '/api/caddy/config/', { headers: { 'Accept': 'application/json' } });
                let cfg = null; let t = '';
                try { cfg = await getRes.json(); } catch { try { t = await getRes.text(); } catch { t=''; } }
                if (!getRes.ok || !cfg || typeof cfg !== 'object') throw new Error(t || 'Failed to load config');
                if (!cfg.apps || !cfg.apps.http || !cfg.apps.http.servers || !cfg.apps.http.servers[name]) throw new Error('Server not found: ' + name);
                delete cfg.apps.http.servers[name];
                const putRes = await fetch(base + '/api/caddy/load', {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(cfg)
                });
                let pt = ''; let pd = null; try { pd = await putRes.json(); } catch { try { pt = await putRes.text(); } catch { pt=''; } }
                if (!putRes.ok) throw new Error((pd && (pd.error || pd.body)) || pt || 'Delete failed');
                location.reload();
            } catch (err) { Swal.fire({ icon: 'error', title: 'Delete server failed', text: (err && err.message ? err.message : String(err)) }); }
        });

        // Submit add/edit server
        $('#serverForm').on('submit', async function(e){
            e.preventDefault();
            const name = String($('#serverName').val() || '').trim();
            const port = String($('#serverPort').val() || '').trim();
            if (!name) { await Swal.fire({ icon:'warning', title:'Missing name', text:'Please enter server name' }); return; }
            try {
                const getRes = await fetch(base + '/api/caddy/config/', { headers: { 'Accept': 'application/json' } });
                let cfg = null; let t='';
                try { cfg = await getRes.json(); } catch { try { t = await getRes.text(); } catch { t=''; } }
                if (!getRes.ok || !cfg || typeof cfg !== 'object') throw new Error(t || 'Failed to load config');
                if (!cfg.apps) cfg.apps = {};
                if (!cfg.apps.http) cfg.apps.http = {};
                if (!cfg.apps.http.servers) cfg.apps.http.servers = {};

                const servers = cfg.apps.http.servers;
                if (editServerOriginal === null) {
                    // Add new
                    if (servers[name]) throw new Error('Server already exists: ' + name);
                    servers[name] = { listen: [ formatListen(port || ':80') ], routes: [] };
                } else {
                    // Edit existing (rename allowed)
                    const orig = editServerOriginal;
                    if (!servers[orig]) throw new Error('Server not found: ' + orig);
                    // Move if name changed
                    if (name !== orig) {
                        if (servers[name]) throw new Error('Target name already exists: ' + name);
                        servers[name] = servers[orig];
                        delete servers[orig];
                    }
                    // Update listen
                    if (!servers[name].listen || !Array.isArray(servers[name].listen)) servers[name].listen = [];
                    servers[name].listen = [ formatListen(port || ':80') ];
                }

                const putRes = await fetch(base + '/api/caddy/load', {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(cfg)
                });
                let pt=''; let pd=null; try { pd = await putRes.json(); } catch { try { pt = await putRes.text(); } catch { pt=''; } }
                if (!putRes.ok) throw new Error((pd && (pd.error || pd.body)) || pt || 'Save failed');
                location.reload();
            } catch (err) {
                Swal.fire({ icon:'error', title:'Save server failed', text:(err && err.message ? err.message : String(err)) });
            } finally {
                closeServerModal();
            }
        });
    });
</script>

<!-- Add/Edit Server Modal -->
<div id="serverModal" class="fixed inset-0 hidden" aria-hidden="true">
    <div id="serverBackdrop" class="absolute inset-0 bg-black/50"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-md bg-theme shadow-xl rounded-lg overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-700 flex items-center justify-between">
                <h3 id="serverModalTitle" class="text-lg font-semibold text-theme">Add Server</h3>
                <button id="btnCloseServer" class="px-3 py-1.5 rounded text-theme hover:bg-slate-700/50">✕</button>
            </div>
            <form id="serverForm" class="p-4 space-y-4">
                <div>
                    <label for="serverName" class="block text-sm mb-1 text-theme">Server Name</label>
                    <input id="serverName" type="text" class="w-full input-theme rounded-[10px] h-[40px] px-3" placeholder="srv0" />
                </div>
                <div>
                    <label for="serverPort" class="block text-sm mb-1 text-theme">Port</label>
                    <input id="serverPort" type="text" class="w-full input-theme rounded-[10px] h-[40px] px-3" placeholder=":80" />
                    <p class="text-xs text-theme opacity-70 mt-1">Accepts ":443", "8080", or "0.0.0.0:8080"</p>
                </div>
                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" id="btnCloseServer2" class="px-5 py-2 rounded-[50px] text-white text-sm bg-slate-600 hover:bg-slate-500">ยกเลิก</button>
                    <button type="submit" class="px-5 py-2 rounded-[50px] bg-[#506BF4] text-white text-sm hover:opacity-90">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
    
</div>