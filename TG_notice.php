<?php
// 💡 서버의 기본 시간을 한국 표준시(KST)로 강제 고정합니다.
date_default_timezone_set('Asia/Seoul');

session_start();

$admin_password = 'admin'; 

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$error_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_pw'])) {
    if ($_POST['login_pw'] === $admin_password) {
        $_SESSION['is_tg_admin'] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error_msg = '비밀번호가 일치하지 않습니다.';
    }
}

if (!isset($_SESSION['is_tg_admin']) || $_SESSION['is_tg_admin'] !== true) {
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>🔒 TG 알리미 로그인</title>
    <style>
        * { box-sizing: border-box; }
        :root { --bg: #121212; --card: #1e1e1e; --green: #00e676; --red: #ff5252; --blue: #4da6ff; }
        body { background: var(--bg); color: #eee; font-family: 'Malgun Gothic', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; padding: 20px; overflow-x: hidden; }
        .login-box { background: var(--card); padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.8); border-top: 4px solid var(--blue); text-align: center; width: 100%; max-width: 350px; }
        .login-box h2 { margin-top: 0; color: #fff; letter-spacing: 1px; margin-bottom: 30px; font-size: 1.4rem; display: flex; align-items: center; justify-content: center; gap: 8px;}
        .pwd-input { width: 100%; padding: 12px; border-radius: 6px; border: 1px solid #444; background: #111; color: #fff; font-size: 1.1rem; text-align: center; letter-spacing: 2px; transition: 0.3s; margin-bottom: 20px; }
        .pwd-input:focus { border-color: var(--blue); outline: none; box-shadow: 0 0 10px rgba(77,166,255,0.3); }
        .login-btn { width: 100%; background: var(--blue); color: #000; border: none; padding: 12px; border-radius: 6px; font-weight: 900; font-size: 1.1rem; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 10px rgba(77,166,255,0.3); }
        .login-btn:hover { background: #338ce6; transform: translateY(-2px); }
        .error-msg { color: var(--red); font-size: 0.9rem; margin-bottom: 15px; font-weight: bold; }
        .back-link { display: inline-block; margin-top: 25px; padding: 10px 20px; background: #333; color: #fff; text-decoration: none; border-radius: 6px; font-size: 0.95rem; font-weight: bold; transition: 0.3s; border: 1px solid #444;}
    </style>
</head>
<body>
    <div class="login-box">
        <h2>
            <svg viewBox="0 0 24 24" width="24" height="24" fill="var(--blue)"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.18-.08-.05-.19-.02-.27 0-.12.03-1.99 1.25-5.61 3.7-.53.36-1.01.54-1.44.53-.47-.01-1.38-.27-2.06-.49-.83-.27-1.49-.41-1.43-.87.03-.24.34-.49.92-.75 3.6-1.57 6.01-2.61 7.23-3.12 3.44-1.43 4.16-1.68 4.63-1.69.1 0 .32.02.46.13.12.09.15.22.16.33-.02.04-.02.16-.03.22z"/></svg>
            TG 알리미 로그인
        </h2>
        <?php if($error_msg): ?><div class="error-msg"><?= $error_msg ?></div><?php endif; ?>
        <form method="POST">
            <input type="password" name="login_pw" class="pwd-input" placeholder="비밀번호 입력" required autofocus>
            <button type="submit" class="login-btn">접속하기</button>
        </form>
        <a href="dashboard.php" class="back-link">⬅️ 대시보드 복귀</a>
    </div>
</body>
</html>
<?php
    exit; 
}
// =========================================================================

$file = '/var/log/webaccess/tg_favorites.json';
$config_file = '/var/log/webaccess/tg_config.json';
$history_file = '/var/log/webaccess/tg_history.json'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'service') {
        $cmd = $_POST['cmd'] ?? '';
        if ($cmd === 'start') { exec("sudo /var/www/html/start_bot.sh"); sleep(1); }
        elseif ($cmd === 'stop') { exec("sudo /var/www/html/stop_bot.sh"); sleep(1); }
        elseif ($cmd === 'restart') { exec("sudo /var/www/html/stop_bot.sh"); sleep(1); exec("sudo /var/www/html/start_bot.sh"); sleep(1); }
        
        $pid = shell_exec("pgrep -f '[b]m_bot.py'");
        echo json_encode(['status' => (trim($pid) !== '' ? 'active' : 'inactive')]);
        exit;
    }
    
    // 🚀 설정 저장 백엔드 (목록 표시 개수 추가됨)
    if ($action === 'config') {
        $mins = (int)($_POST['cooldown_mins'] ?? 90);
        $token = trim($_POST['telegram_token'] ?? '');
        $chat_id = trim($_POST['telegram_chat_id'] ?? '');
        $display_count = (int)($_POST['display_count'] ?? 10);

        $config_data = file_exists($config_file) ? json_decode(file_get_contents($config_file), true) : [];
        if (!is_array($config_data)) $config_data = [];

        $config_data['cooldown'] = $mins * 60;
        if ($token !== '') $config_data['telegram_token'] = $token;
        if ($chat_id !== '') $config_data['telegram_chat_id'] = $chat_id;
        $config_data['display_count'] = $display_count; // 화면 표시 개수 저장

        @file_put_contents($config_file, json_encode($config_data, JSON_UNESCAPED_UNICODE));
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'add' || $action === 'delete') {
        $callsign = strtoupper(trim($_POST['callsign'] ?? ''));
        $favorites = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
        if (!is_array($favorites)) $favorites = [];

        if ($action === 'add' && !in_array($callsign, $favorites) && !empty($callsign)) { $favorites[] = $callsign; }
        elseif ($action === 'delete') { $favorites = array_values(array_filter($favorites, fn($c) => $c !== $callsign)); }
        
        @file_put_contents($file, json_encode($favorites));
        echo json_encode(['success' => true]);
        exit;
    }
}

$favorites = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
$js_favorites = json_encode(is_array($favorites) ? $favorites : []);

// 🚀 설정값 불러오기 (기본값 세팅 포함)
$config = file_exists($config_file) ? json_decode(file_get_contents($config_file), true) : [];
if (!is_array($config)) $config = [];
$current_cooldown_mins = round(($config['cooldown'] ?? 5400) / 60);
$tg_token = $config['telegram_token'] ?? '8732703729:AAH4RG-zX4WX4m6UFq2y808dZMXfhUxCJL8';
$tg_chat_id = $config['telegram_chat_id'] ?? '8615114253';
$display_count = (int)($config['display_count'] ?? 10); // 기본값 10

$history_data = file_exists($history_file) ? json_decode(file_get_contents($history_file), true) : [];
$js_history = json_encode(is_array($history_data) ? $history_data : []);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>📱 BM TG 실시간 알리미</title>
    <script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
    <style>
        * { box-sizing: border-box; } 
        :root { --bg: #1e1e1e; --card: #2a2a2a; --text: #eee; --sub: #aaa; --green: #00e676; --red: #ff5252; --warn: #f39c12; --blue: #4da6ff; }
        
        body { font-family: 'Malgun Gothic', sans-serif; background-color: var(--bg); color: var(--text); margin: 0; padding: 20px; overflow-x: hidden; width: 100%; }
        
        .header-container { position: relative; max-width: 900px; margin: 0 auto 20px; border-bottom: 2px solid #333; padding-bottom: 15px; display: flex; justify-content: center; align-items: center; width: 100%; }
        .header-container h1 { margin: 0; padding: 0; font-size: 1.8rem; color: var(--blue); letter-spacing: 1px; display: flex; align-items: center; justify-content: center; gap: 10px; }
        
        .back-btn, .logout-btn { position: absolute; background-color: #333; color: #fff; text-decoration: none; padding: 10px 18px; border-radius: 8px; font-size: 0.95rem; font-weight: bold; transition: all 0.3s ease; display: flex; align-items: center; border: 1px solid #444; }
        .back-btn { left: 0; }
        .logout-btn { right: 0; color: var(--red); }

        .main-panel { max-width: 900px; margin: 0 auto; background: var(--card); padding: 25px 30px; border-radius: 5px; width: 100%; }
        .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        .info-box { background-color: #333; padding: 20px; border-radius: 5px; display: flex; flex-direction: column; width: 100%; line-height: 1.6; }
        .info-box strong { color: var(--blue); font-size: 1.1rem; display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        
        .favorite-tag { background: #e74c3c; color: white; padding: 5px 10px; border-radius: 15px; font-weight: bold; margin-right: 8px; display: inline-flex; align-items: center; margin-bottom: 5px; font-size: 14px; }
        .delete-btn { background: none; border: none; color: white; margin-left: 8px; cursor: pointer; font-weight: bold; padding: 0; }
        
        .add-form { display: flex; gap: 10px; margin-top: auto; padding-top: 15px; width: 100%; background: #222; padding: 10px; border-radius: 5px; }
        .add-form input { flex: 1; padding: 10px; border-radius: 3px; background: #111; color: white; border: 1px solid #555; text-transform: uppercase; font-size: 1rem; width: 100%; }
        .add-form button { padding: 10px 15px; background: var(--blue); color: #000; border: none; border-radius: 3px; font-weight: bold; cursor: pointer; white-space: nowrap; }
        
        .config-form { display: flex; flex-direction: column; gap: 10px; margin-top: auto; background: #222; padding: 15px; border-radius: 5px; width: 100%; }
        .config-row { display: flex; align-items: center; gap: 10px; width: 100%; }
        .config-row input[type="text"] { flex: 1; padding: 8px; border-radius: 3px; background: #111; color: #ccc; border: 1px solid #555; font-size: 0.9rem; }
        .config-row input[type="number"] { width: 70px; padding: 8px; border-radius: 3px; background: #111; color: var(--warn); border: 1px solid #555; font-size: 1.1rem; text-align: center; font-weight: bold; }
        .config-label { color: #ccc; font-size: 0.85rem; letter-spacing: 0.5px; white-space: nowrap; min-width: 50px; }
        .btn-save { padding: 10px 15px; background: #4da6ff; color: #000; border: none; border-radius: 3px; font-weight: bold; cursor: pointer; white-space: nowrap; font-size: 0.95rem; width: 100%; margin-top: 5px; transition: 0.2s;}
        .btn-save:hover { background: #338ce6; }
        
        .ctrl-btns { display: flex; gap: 8px; margin-bottom: 15px; width: 100%; }
        .btn-svc { flex: 1; padding: 10px 0; border: none; border-radius: 3px; font-weight: bold; cursor: pointer; color: #fff; font-size: 0.9rem; display: flex; justify-content: center; align-items: center; }
        .btn-start { background: var(--green); color: #000; }
        .btn-stop { background: var(--red); }
        .btn-restart { background: var(--warn); color: #000; }
        
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 15px; font-size: 0.85rem; font-weight: bold; }
        .status-active { background: rgba(0, 230, 118, 0.2); color: var(--green); border: 1px solid var(--green); }
        .status-inactive { background: rgba(255, 82, 82, 0.2); color: var(--red); border: 1px solid var(--red); }

        .table-container { width: 100%; background: #2b2b2b; border-radius: 5px; max-height: 600px; overflow-y: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; table-layout: fixed; }
        th, td { padding: 10px 5px; border-bottom: 1px solid #444; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        th { background-color: #111; position: sticky; top: 0; z-index: 10; }
        
        .col-time { width: 28%; }
        .col-call { width: 44%; }
        .col-tg   { width: 28%; }

        .callsign-cell { font-weight: bold; color: #fff; }
        .callsign-container { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; }
        .callsign-text { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        
        .lookup-btn { background: #4da6ff; color: #000; border: none; border-radius: 3px; padding: 4px 6px; font-size: 11px; cursor: pointer; font-weight: bold; flex-shrink: 0; width: 38px; }
        .lookup-btn:hover { background: #2b8ce6; color: white; }

        .tg-cell { color: var(--green); font-weight: bold; }
        
        .highlight { background-color: #8b0000 !important; font-weight: bold; color: #ffeb3b; }
        .highlight .callsign-text { color: #ffeb3b; }

        @media (max-width: 768px) {
            body { padding: 10px; }
            .header-container { flex-direction: row; flex-wrap: wrap; justify-content: space-between; border-bottom: none; padding-bottom: 0; gap: 10px; }
            .header-container h1 { order: 3; width: 100%; font-size: 1.1rem; white-space: nowrap; margin-top: 5px; margin-bottom: 10px; }
            .back-btn, .logout-btn { position: relative; width: 48%; order: 1; justify-content: center; padding: 10px 5px; font-size: 0.85rem; }
            .logout-btn { order: 2; }
            
            .main-panel { padding: 15px 10px; }
            .settings-grid { grid-template-columns: 1fr; gap: 15px; margin-bottom: 15px; }
            .info-box { padding: 15px 12px; }
            
            .add-form { flex-direction: column; background: transparent; padding: 10px 0; }
            .add-form button { width: 100%; padding: 12px; }
            
            .config-row { flex-direction: column; align-items: flex-start; gap: 5px; }
            .config-row input[type="text"], .config-row input[type="number"] { width: 100%; margin: 0; }
            
            .ctrl-btns { gap: 6px; }
            .btn-svc { padding: 12px 2px; font-size: 0.85rem; }

            th, td { padding: 8px 2px; font-size: 13px; }
            .lookup-btn { width: 34px; padding: 3px 4px; font-size: 10px; }
        }
    </style>
</head>
<body>
    <div class="header-container">
        <a href="./dashboard.php" class="back-btn">⬅️ 대시보드 복귀</a>
        <a href="?logout=1" class="logout-btn">🔓 로그아웃</a>
        <h1>
            <svg viewBox="0 0 24 24" width="24" height="24" fill="var(--blue)"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.18-.08-.05-.19-.02-.27 0-.12.03-1.99 1.25-5.61 3.7-.53.36-1.01.54-1.44.53-.47-.01-1.38-.27-2.06-.49-.83-.27-1.49-.41-1.43-.87.03-.24.34-.49.92-.75 3.6-1.57 6.01-2.61 7.23-3.12 3.44-1.43 4.16-1.68 4.63-1.69.1 0 .32.02.46.13.12.09.15.22.16.33-.02.04-.02.16-.03.22z"/></svg>
            BM Korea 실시간 TG 알리미 (Admin)
        </h1>
    </div>

    <div class="main-panel">
        <div class="settings-grid">
            <div class="info-box">
                <strong>🔔 관심 콜사인 목록</strong> 
                <div id="fav-list" style="min-height: 40px; margin-bottom: 10px;"></div>
                <div class="add-form">
                    <input type="text" id="new-callsign" placeholder="콜사인 (ex: DS1UYM)">
                    <button onclick="updateCallsign('add')">➕ 추가</button>
                </div>
            </div>

            <div class="info-box">
                <strong>
                    ⚙️ 봇 서비스 및 설정
                    <span id="svc-status" class="status-badge status-inactive">확인 중...</span>
                </strong>
                <div class="ctrl-btns">
                    <button class="btn-svc btn-start" onclick="controlService('start')">▶️ 시작</button>
                    <button class="btn-svc btn-restart" onclick="controlService('restart')">🔄 재시작</button>
                    <button class="btn-svc btn-stop" onclick="controlService('stop')">⏹️ 정지</button>
                </div>
                
                <div class="config-form">
                    <div class="config-row">
                        <span class="config-label">Bot Token:</span>
                        <input type="text" id="tg-token" value="<?= htmlspecialchars($tg_token) ?>" placeholder="텔레그램 봇 토큰 입력">
                    </div>
                    <div class="config-row">
                        <span class="config-label">Chat ID:</span>
                        <input type="text" id="tg-chat" value="<?= htmlspecialchars($tg_chat_id) ?>" placeholder="알림받을 Chat ID 입력">
                    </div>
                    <div class="config-row" style="margin-top: 5px;">
                        <span class="config-label">알림 쿨타임:</span>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <input type="number" id="cooldown-input" value="<?= $current_cooldown_mins ?>" min="0">
                            <span class="config-label">분 (연속 알림 무시)</span>
                        </div>
                    </div>
                    <div class="config-row" style="margin-top: 5px;">
                        <span class="config-label">수신 목록수:</span>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <input type="number" id="display-count-input" value="<?= $display_count ?>" min="1" max="100">
                            <span class="config-label">개 (기본 10, 최대 100)</span>
                        </div>
                    </div>
                    <button class="btn-save" onclick="saveConfig()">💾 설정 전체 저장</button>
                </div>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th class="col-time">수신 시간</th>
                        <th class="col-call">송신자 (Callsign)</th>
                        <th class="col-tg">목적지 (TG)</th>
                    </tr>
                </thead>
                <tbody id="log-body">
                    <tr id="waiting-row"><td colspan="3" style="padding: 30px; color: #888; white-space: normal;">📡 BrandMeister 네트워크 수신 대기 중...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        let favorites = <?= $js_favorites ?>;
        let rawHistory = <?= $js_history ?>;
        // 🚀 PHP에서 넘겨받은 설정 개수 변수
        let maxDisplayCount = <?= $display_count ?>; 

        function renderFavorites() {
            const favListDiv = document.getElementById('fav-list');
            favListDiv.innerHTML = '';
            if (favorites.length === 0) { favListDiv.innerHTML = '<span style="color:#aaa; font-size:14px;">등록된 콜사인이 없습니다.</span>'; return; }
            favorites.forEach(call => { favListDiv.innerHTML += `<span class="favorite-tag">${call} <button class="delete-btn" onclick="updateCallsign('delete', '${call}')">✕</button></span>`; });
        }

        function updateCallsign(action, call = null) {
            const callsign = call || document.getElementById('new-callsign').value.trim().toUpperCase();
            if (!callsign) return;
            if (action === 'add' && favorites.includes(callsign)) { alert('⚠️ 이미 등록된 콜사인입니다.'); return; }
            const formData = new FormData();
            formData.append('action', action);
            formData.append('callsign', callsign);
            fetch('', { method: 'POST', body: formData }).then(() => {
                if(action === 'add') favorites.push(callsign);
                else favorites = favorites.filter(c => c !== callsign);
                document.getElementById('new-callsign').value = '';
                renderFavorites();
            });
        }

        function saveConfig() {
            const mins = document.getElementById('cooldown-input').value;
            const token = document.getElementById('tg-token').value;
            const chat = document.getElementById('tg-chat').value;
            // 🚀 설정 개수 폼에서 읽어오기
            const displayCnt = document.getElementById('display-count-input').value;
            
            const formData = new FormData();
            formData.append('action', 'config');
            formData.append('cooldown_mins', mins);
            formData.append('telegram_token', token);
            formData.append('telegram_chat_id', chat);
            formData.append('display_count', displayCnt);
            
            fetch('', { method: 'POST', body: formData }).then(() => {
                alert('✅ 설정이 성공적으로 저장되었습니다.\n(바뀐 정보는 즉시 적용됩니다!)');
                
                // 🚀 설정값 저장 즉시, 화면의 개수를 잘라내어 시각적으로 바로 적용
                maxDisplayCount = parseInt(displayCnt, 10);
                const tbody = document.getElementById("log-body");
                while (tbody.children.length > maxDisplayCount) {
                    tbody.removeChild(tbody.lastChild);
                }
            });
        }

        function checkStatus() {
            const formData = new FormData();
            formData.append('action', 'service');
            formData.append('cmd', 'status');
            fetch('', { method: 'POST', body: formData }).then(r => r.json()).then(data => {
                const badge = document.getElementById('svc-status');
                badge.className = 'status-badge ' + (data.status === 'active' ? 'status-active' : 'status-inactive');
                badge.innerText = data.status === 'active' ? '🟢 가동 중' : '🔴 정지됨';
            });
        }

        function controlService(cmd) {
            const badge = document.getElementById('svc-status');
            badge.className = 'status-badge'; badge.innerText = '⏳ 처리 중...'; badge.style.background = '#444';
            const formData = new FormData();
            formData.append('action', 'service');
            formData.append('cmd', cmd);
            fetch('', { method: 'POST', body: formData }).then(r => r.json()).then(() => checkStatus());
        }

        function openQRZPopup(callsign) {
            const url = `https://www.qrz.com/db/${callsign}`;
            const width = 800, height = 700;
            const left = (screen.width / 2) - (width / 2), top = (screen.height / 2) - (height / 2);
            window.open(url, `QRZ_${callsign}`, `width=${width},height=${height},top=${top},left=${left},menubar=no,toolbar=no,location=no,status=no,scrollbars=yes,resizable=yes`);
        }

        function createRowElement(timeString, sourceCall, destination) {
            const tr = document.createElement("tr"); tr.dataset.callsign = sourceCall;
            tr.innerHTML = `<td class="col-time">${timeString}</td><td class="col-call callsign-cell"><div class="callsign-container"><span class="callsign-text">${sourceCall}</span><button class="lookup-btn" onclick="openQRZPopup('${sourceCall}')">QRZ</button></div></td><td class="col-tg tg-cell">TG ${destination}</td>`;
            if (favorites.includes(sourceCall)) tr.classList.add("highlight");
            return tr;
        }

        function loadHistory() {
            const tbody = document.getElementById("log-body");
            if (rawHistory && rawHistory.length > 0) {
                const waitRow = document.getElementById("waiting-row"); 
                if (waitRow) waitRow.remove();
                
                // 🚀 고정 숫자 30 대신 설정값 사용
                const displayCount = Math.min(rawHistory.length, maxDisplayCount);
                for (let i = 0; i < displayCount; i++) {
                    const item = rawHistory[i];
                    tbody.appendChild(createRowElement(item.time, item.call, item.tg));
                }
            }
        }

        renderFavorites(); checkStatus(); loadHistory();
        document.getElementById('new-callsign').addEventListener('keypress', e => { if(e.key === 'Enter') updateCallsign('add'); });

        const socket = io("https://api.brandmeister.network", { path: "/lh/socket.io", transports: ["websocket"], reconnection: true });
        const packetBuffer = {};

        socket.on("mqtt", (data) => {
            try {
                const payload = JSON.parse(data.payload);
                const sourceCall = payload.SourceCall ? payload.SourceCall.trim() : "";
                
                if (!sourceCall || sourceCall === "UNKNOWN") return;
                
                const destination = String(payload.DestinationID || ""); 
                if (destination.startsWith("450") || favorites.includes(sourceCall)) {
                    const packetKey = sourceCall + "_" + destination;
                    if (!packetBuffer[packetKey]) {
                        packetBuffer[packetKey] = true;
                        setTimeout(() => {
                            const waitRow = document.getElementById("waiting-row"); if (waitRow) waitRow.remove();
                            const timeString = new Date().toLocaleTimeString('en-US', { timeZone: 'Asia/Seoul', hour12: false, hour: '2-digit', minute:'2-digit', second:'2-digit' });

                            const existingRow = document.querySelector(`tr[data-callsign="${sourceCall}"]`);
                            if (existingRow) existingRow.remove();

                            const tbody = document.getElementById("log-body");
                            tbody.prepend(createRowElement(timeString, sourceCall, destination)); 
                            
                            // 🚀 고정 숫자 30 대신 설정값으로 목록 자르기
                            while (tbody.children.length > maxDisplayCount) {
                                tbody.removeChild(tbody.lastChild); 
                            }
                            
                            delete packetBuffer[packetKey];
                        }, 1000);
                    }
                }
            } catch (e) {}
        });
    </script>
</body>
</html>
