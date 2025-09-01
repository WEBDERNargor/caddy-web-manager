<?php
header('Content-Type: text/html; charset=utf-8');
$layout->setLayout('auth');
$config = getConfig();
$setHead(<<<HTML
<title> Home - {$config['web']['name']}</title>
HTML);

$caddyUrl = rtrim($config['web']['caddy_url'] ?? 'http://127.0.0.1:2019', '/');
$url = $caddyUrl . '/config/';

// Handle POST actions (PHP does all API calls)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirect = (defined('BASE_PATH') ? BASE_PATH : '') . '/';
    $status = 'ok'; $msg = '';
    try {
        // Load current config
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

        if (!isset($cfg['apps'])) $cfg['apps'] = [];
        if (!isset($cfg['apps']['http'])) $cfg['apps']['http'] = [];
        if (!isset($cfg['apps']['http']['servers']) || !is_array($cfg['apps']['http']['servers'])) $cfg['apps']['http']['servers'] = [];
        $servers =& $cfg['apps']['http']['servers'];

        if ($action === 'delete_server') {
            $name = (string)($_POST['name'] ?? '');
            if ($name === '' || !isset($servers[$name])) throw new \RuntimeException('Server not found: ' . $name);
            unset($servers[$name]);
        } elseif ($action === 'save_server') {
            $orig = (string)($_POST['original_name'] ?? '');
            $name = trim((string)($_POST['name'] ?? ''));
            $port = trim((string)($_POST['port'] ?? ''));
            if ($name === '') throw new \RuntimeException('Missing server name');
            // derive listen address
            $listen = $port === '' ? ':80' : (strpos($port, ':') !== false ? $port : (':' . $port));
            if ($orig === '') {
                // create new (not exposed in UI, but supported)
                if (isset($servers[$name])) throw new \RuntimeException('Server already exists: ' . $name);
                $servers[$name] = [ 'listen' => [ $listen ], 'routes' => [] ];
            } else {
                if (!isset($servers[$orig])) throw new \RuntimeException('Server not found: ' . $orig);
                // rename if needed
                if ($name !== $orig) {
                    if (isset($servers[$name])) throw new \RuntimeException('Target name already exists: ' . $name);
                    $servers[$name] = $servers[$orig];
                    unset($servers[$orig]);
                }
                if (!isset($servers[$name]['listen']) || !is_array($servers[$name]['listen'])) $servers[$name]['listen'] = [];
                $servers[$name]['listen'] = [ $listen ];
            }
        } else {
            throw new \RuntimeException('Unknown action');
        }

        // Save config
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
        if ($code2 < 200 || $code2 >= 300) { $status = 'error'; $msg = 'Caddy responded ' . $code2 . ' ' . (string)$res2; }
    } catch (\Throwable $e) {
        $status = 'error'; $msg = $e->getMessage();
    }
    $qs = 'status=' . urlencode($status) . ($msg !== '' ? ('&msg=' . urlencode($msg)) : '');
    header('Location: ' . $redirect . '?' . $qs);
    exit;
}

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

$data = json_decode($response, true);
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
                        if (is_string($listen)) {
                            $listen = trim($listen);
                            $colonPos = strrpos($listen, ':');
                            if ($colonPos !== false) {
                                $port = substr($listen, $colonPos + 1);
                            } else {
                                $port = $listen;
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
        <div>
            <button id="btnAddServer" class="px-4 py-2 rounded bg-green-600 text-white text-sm hover:bg-green-700">+ Add Server</button>
        </div>
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
                        <td class="text-theme">
                            <a class="hover:underline" href="<?= defined('BASE_PATH') ? BASE_PATH : '' ?>/route/<?= urlencode($s['name']) ?>">
                                <?= htmlspecialchars($s['name']) ?>
                            </a>
                        </td>
                        <td class="text-theme"><?= htmlspecialchars($s['port']) ?></td>
                        <td class="space-x-1">
                            <a href="<?= defined('BASE_PATH') ? BASE_PATH : '' ?>/route/<?= urlencode($s['name']) ?>"
                               class="px-3 py-1 inline-block rounded bg-blue-600 text-white text-xs hover:bg-blue-700">View</a>
                            <button class="btnEditServer px-2 py-1 rounded bg-amber-500 text-white text-xs hover:bg-amber-600" data-name="<?= htmlspecialchars($s['name']) ?>" data-port="<?= htmlspecialchars($s['port']) ?>">Edit</button>
                            <form method="post" class="inline formDeleteServer">
                                <input type="hidden" name="action" value="delete_server" />
                                <input type="hidden" name="name" value="<?= htmlspecialchars($s['name']) ?>" />
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
   
    $(function () {
        const table = $('#serversTable').DataTable({
            responsive: true,
            pageLength: 5,
            lengthMenu: [5, 10, 25, 50],
            order: [[0, 'asc']],
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
            const $wrap = $('#serversTable').closest('.dataTables_wrapper, .dt-container');
            $wrap.addClass('text-theme');
            $wrap.find('.dataTables_length, .dataTables_filter').addClass('text-theme');
            $wrap.find('.dataTables_length *, .dataTables_filter *').addClass('text-theme');
            $wrap.find('.dt-length, .dt-search').addClass('text-theme');
            $wrap.find('.dt-length *, .dt-search *').addClass('text-theme');
            const $lenSel = $wrap.find('.dataTables_length select, .dt-length select');
            const $searchInp = $wrap.find('.dataTables_filter input, .dt-search input');
            $lenSel.addClass('input-theme border-theme text-theme');
            $searchInp.addClass('input-theme border-theme text-theme');
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
            } catch (e) { }
          
            $wrap.find('.dataTables_info').addClass('text-theme').find('*').addClass('text-theme');
            $wrap.find('.dataTables_paginate').addClass('text-theme');
            $wrap.find('.dataTables_paginate a, .dataTables_paginate span')
                .addClass('text-theme border-theme');
            $wrap.find('.paginate_button, .paginate_button.current, .paginate_button.disabled')
                .addClass('text-theme border-theme');
            $wrap.find('.dt-info').addClass('text-theme').find('*').addClass('text-theme');
            $wrap.find('.dt-paging').addClass('text-theme');
            $wrap.find('.dt-paging button, .dt-paging a, .dt-paging span')
                .addClass('text-theme border-theme');
            $wrap.find('.dt-paging-button').addClass('text-theme border-theme');
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

        applyDTTheme();
        $('#serversTable').on('init.dt', function () {
            applyDTTheme();
        }).on('draw.dt', function () {
            applyDTTheme();
        });
        document.addEventListener('theme:changed', function () {
            applyDTTheme();
        });
        setTimeout(() => {
            table.columns.adjust().draw(false);
            applyDTTheme();
        }, 50);

        let editServerOriginal = null; 

        // Open Add modal (create new)
        $(document).on('click', '#btnAddServer', function(){
            editServerOriginal = null;
            $('#serverModalTitle').text('Add Server');
            $('#serverName').val('');
            $('#serverPort').val('');
            $('#originalName').val(''); // empty = create new
            $('#serverModal').removeClass('hidden');
            setTimeout(() => { $('#serverName').trigger('focus'); }, 0);
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

        // SweetAlert2: confirm delete
        $(document).on('submit', '.formDeleteServer', async function(e){
            e.preventDefault();
            const form = this;
            const name = $(form).find('input[name="name"]').val() || '';
            try {
                const res = await Swal.fire({
                    icon: 'warning',
                    title: 'Delete server',
                    text: name ? ('Delete server "' + name + '" ?') : 'Delete this server?',
                    showCancelButton: true,
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc2626'
                });
                if (res.isConfirmed) form.submit();
            } catch(_) { /* no-op */ }
        });

        // Open Edit modal
        $(document).on('click', '.btnEditServer', function(){
            const name = String($(this).data('name') || '');
            const port = String($(this).data('port') || '');
            editServerOriginal = name;
            $('#serverModalTitle').text('Edit Server');
            $('#serverName').val(name);
            $('#serverPort').val(port);
            $('#originalName').val(name);
            $('#serverModal').removeClass('hidden');
            setTimeout(() => { $('#serverName').trigger('focus'); }, 0);
        });

        // Close modal
        $('#btnCloseServer, #btnCloseServer2, #serverBackdrop').on('click', function(){
            $('#serverModal').addClass('hidden');
            editServerOriginal = null;
            $('#serverName').val('');
            $('#serverPort').val('');
            $('#originalName').val('');
        });

        // Validate form (simple)
        $('#serverForm').on('submit', function(){
            if (!$('#serverName').val()) { Swal.fire({ icon:'warning', title:'กรอกชื่อเซิร์ฟเวอร์' }); return false; }
            return true;
        });
    });
</script>

<!-- Edit Server Modal -->
<div id="serverModal" class="fixed inset-0 hidden" aria-hidden="true">
    <div id="serverBackdrop" class="absolute inset-0 bg-black/50"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-md bg-theme shadow-xl rounded-lg overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-700 flex items-center justify-between">
                <h3 id="serverModalTitle" class="text-lg font-semibold text-theme">Edit Server</h3>
                <button id="btnCloseServer" class="px-3 py-1.5 rounded text-theme hover:bg-slate-700/50">✕</button>
            </div>
            <form id="serverForm" method="post" class="p-4 space-y-4">
                <input type="hidden" name="action" value="save_server" />
                <input type="hidden" id="originalName" name="original_name" value="" />
                <div>
                    <label for="serverName" class="block text-sm mb-1 text-theme">Server Name</label>
                    <input id="serverName" name="name" type="text" class="w-full input-theme rounded-[10px] h-[40px] px-3" placeholder="srv0" />
                </div>
                <div>
                    <label for="serverPort" class="block text-sm mb-1 text-theme">Port</label>
                    <input id="serverPort" name="port" type="text" class="w-full input-theme rounded-[10px] h-[40px] px-3" placeholder=":80" />
                    <p class="text-xs text-theme opacity-70 mt-1">รับค่า ":443", "8080" หรือ "0.0.0.0:8080"</p>
                </div>
                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" id="btnCloseServer2" class="px-5 py-2 rounded-[50px] text-white text-sm bg-slate-600 hover:bg-slate-500">ยกเลิก</button>
                    <button type="submit" class="px-5 py-2 rounded-[50px] bg-[#506BF4] text-white text-sm hover:opacity-90">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>
