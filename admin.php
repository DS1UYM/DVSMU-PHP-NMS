<?php
session_start();
$admin_password = 'admin'; // 사용자 설정 관리자 비밀번호
$users_file = '/var/log/webaccess/dvs_users.json';

// 1. 보안 및 로그아웃
if (isset($_GET['logout'])) { 
    session_destroy(); 
    header("Location: admin.php"); 
    exit; 
}

$error_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_pw'])) {
    if ($_POST['login_pw'] === $admin_password) { 
        $_SESSION['is_admin'] = true; 
        header("Location: admin.php"); 
        exit; 
    } else {
        $error_msg = '비밀번호가 일치하지 않습니다.';
    }
}

// =========================================================================
// 🚀 로그인 화면 레이아웃
// =========================================================================
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>🔒 시스템 관리자 로그인</title>
    <style>
        * { box-sizing: border-box; }
        :root { --bg: #121212; --card: #1e1e1e; --green: #00e676; --red: #ff5252; --blue: #4da6ff; }
        body { background: var(--bg); color: #eee; font-family: 'Malgun Gothic', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; padding: 20px; overflow-x: hidden; }
        .login-box { background: var(--card); padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.8); border-top: 4px solid var(--green); text-align: center; width: 100%; max-width: 350px; }
        .login-box h2 { margin-top: 0; color: #fff; letter-spacing: 1px; margin-bottom: 30px; font-size: 1.4rem; display: flex; align-items: center; justify-content: center; gap: 8px;}
        .pwd-input { width: 100%; padding: 12px; border-radius: 6px; border: 1px solid #444; background: #111; color: #fff; font-size: 1.1rem; text-align: center; letter-spacing: 2px; transition: 0.3s; margin-bottom: 20px; }
        .pwd-input:focus { border-color: var(--green); outline: none; box-shadow: 0 0 10px rgba(0,230,118,0.3); }
        .login-btn { width: 100%; background: var(--green); color: #000; border: none; padding: 12px; border-radius: 6px; font-weight: 900; font-size: 1.1rem; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 10px rgba(0,230,118,0.3); }
        .login-btn:hover { background: #55ff99; transform: translateY(-2px); }
        .error-msg { color: var(--red); font-size: 0.9rem; margin-bottom: 15px; font-weight: bold; }
        .back-link { display: inline-block; margin-top: 25px; padding: 10px 20px; background: #333; color: #fff; text-decoration: none; border-radius: 6px; font-size: 0.95rem; font-weight: bold; transition: 0.3s; border: 1px solid #444;}
    </style>
</head>
<body>
    <div class="login-box">
        <h2>
            <svg viewBox="0 0 24 24" width="24" height="24" fill="var(--green)"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
            시스템 관리자 접속
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

// 🚀 통신 전용 강력한 cURL 함수 (에러 코드 추적 기능 탑재)
function fetch_remote_file($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) DVSwitch Updater'); // 방화벽 우회용
        
        $data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        
        if ($http_code == 200) {
            return ['success' => true, 'data' => $data];
        } else {
            return ['success' => false, 'error' => "HTTP 상태 에러: {$http_code} " . ($err ? "({$err})" : "")];
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) DVSwitch Updater'
            ]
        ]);
        $data = @file_get_contents($url, false, $context);
        if ($data !== false) {
            return ['success' => true, 'data' => $data];
        } else {
            return ['success' => false, 'error' => 'file_get_contents 통신 실패'];
        }
    }
}

// =========================================================================
// 🚀 시스템 자동 업데이트 로직
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'system_update') {
    header('Content-Type: application/json');
    
    $update_base_url = "http://dvsmu.ricedesert.com:8888/update/";
    $local_dir = "/var/www/html/";
    
    $success_count = 0;
    $errors = [];
    $files_to_update = [];
    $success_files = []; 

    // 디렉토리 목록 가져오기
    $dir_fetch = fetch_remote_file($update_base_url);
    
    if (!$dir_fetch['success']) {
        echo json_encode(["success" => false, "message" => "업데이트 서버(폴더)에 접속할 수 없습니다. 원인: " . $dir_fetch['error']]);
        exit;
    }
    $dir_html = $dir_fetch['data'];

    $api_data = @json_decode($dir_html, true);
    if (is_array($api_data) && isset($api_data['files'])) {
        $files_to_update = $api_data['files'];
    } elseif ($dir_html !== false) {
        preg_match_all('/href=(["\'])(.*?)\1/i', $dir_html, $matches);
        foreach ($matches[2] as $link) {
            if (strpos($link, '?') !== false || $link === '../' || $link === '/') continue;
            $filename = basename(urldecode($link));
            if ($filename === '' || $filename === '.' || $filename === '..' || strpos($filename, '.') === 0) continue;
            $files_to_update[] = $filename;
        }
        $files_to_update = array_unique($files_to_update);
    }

    if (empty($files_to_update)) {
        echo json_encode(["success" => false, "message" => "업데이트 서버에서 파일 목록을 읽을 수 없거나 파일이 없습니다."]);
        exit;
    }

    // 파일 개별 다운로드 및 덮어쓰기
    foreach ($files_to_update as $file) {
        $local_filename = $file;
        
        // 💡 .txt로 올라온 파일을 로컬에서는 원래 확장자로 복원
        if (preg_match('/\.php\.txt$/i', $local_filename)) {
            $local_filename = preg_replace('/\.txt$/i', '', $local_filename);
        } elseif (preg_match('/\.js\.txt$/i', $local_filename)) {
            $local_filename = preg_replace('/\.txt$/i', '', $local_filename);
        }

        $remote_url = $update_base_url . $file;
        $local_path = $local_dir . $local_filename;
        $backup_path = $local_dir . $local_filename . ".bak";
        
        $fetch_result = fetch_remote_file($remote_url);
        
        if ($fetch_result['success'] && strlen($fetch_result['data']) > 5) {
            if (file_exists($local_path)) @copy($local_path, $backup_path);
            
            $result = @file_put_contents($local_path, $fetch_result['data']);
            if ($result !== false) {
                $success_count++;
                $success_files[] = $local_filename;
            } else {
                $errors[] = "[쓰기 실패] 권한 문제 (chmod 777 필요) : {$local_filename}";
            }
        } else {
            $fail_reason = $fetch_result['success'] ? "파일 내용이 비어있음" : $fetch_result['error'];
            $errors[] = "[다운로드 실패] {$fail_reason} : {$file}";
        }
    }
    
    if ($success_count > 0) {
        echo json_encode([
            "success" => empty($errors) ? true : false, 
            "count" => $success_count,
            "files" => $success_files,
            "errors" => $errors
        ]);
    } else {
        $err_msg = implode("\n", $errors);
        echo json_encode(["success" => false, "message" => "모든 업데이트가 실패했습니다:\n{$err_msg}"]);
    }
    exit;
}
// =========================================================================

function get_ini_val($content, $key) {
    if (preg_match('/^' . preg_quote($key, '/') . '\s*=\s*(.*)$/im', $content, $m)) return trim($m[1]);
    return '';
}

function sync_from_server_ini() {
    global $users_file;
    $old_data = file_exists($users_file) ? json_decode(file_get_contents($users_file), true) ?: [] : [];
    $old_map = [];
    foreach ($old_data as $u) {
        if (isset($u['bridge'])) $old_map[(int)$u['bridge']] = $u;
    }

    $ini_paths = [];
    $ini_paths[0] = [];
    if (file_exists('/opt/MMDVM_Bridge/MMDVM_Bridge.ini')) $ini_paths[0][] = '/opt/MMDVM_Bridge/MMDVM_Bridge.ini';
    if (file_exists('/opt/MMDVM_Bridge/DVSwitch.ini')) $ini_paths[0][] = '/opt/MMDVM_Bridge/DVSwitch.ini';
    if (file_exists('/opt/Analog_Bridge/Analog_Bridge.ini')) $ini_paths[0][] = '/opt/Analog_Bridge/Analog_Bridge.ini';
    if (empty($ini_paths[0])) unset($ini_paths[0]);

    $dirs = glob('/opt/user*');
    if ($dirs) {
        foreach ($dirs as $d) {
            if (preg_match('/user(\d+)/', $d, $m)) {
                $b_num = (int)$m[1];
                $ini_paths[$b_num] = [];
                if (file_exists("$d/MMDVM_Bridge.ini")) $ini_paths[$b_num][] = "$d/MMDVM_Bridge.ini";
                if (file_exists("$d/Analog_Bridge.ini")) $ini_paths[$b_num][] = "$d/Analog_Bridge.ini";
                if (file_exists("$d/DVSwitch.ini")) $ini_paths[$b_num][] = "$d/DVSwitch.ini";
                if (empty($ini_paths[$b_num])) unset($ini_paths[$b_num]);
            }
        }
    }

    $active_users = [];
    foreach ($ini_paths as $b => $files) {
        $ini_content = "";
        foreach ($files as $f) { $ini_content .= shell_exec("sudo cat " . escapeshellarg($f) . " 2>/dev/null") . "\n"; }

        $call = get_ini_val($ini_content, 'Callsign');
        $raw_id = get_ini_val($ini_content, 'Id');
        $raw_pass = get_ini_val($ini_content, 'Password');
        $ta = get_ini_val($ini_content, 'talkerAlias');
        
        $ctrl_port = '';
        if (preg_match('/\[USRP\].*?rxPort\s*=\s*(\d+)/is', $ini_content, $matches)) { $ctrl_port = $matches[1]; }

        $dmrid = $raw_id; $rpt = '';
        if (strlen($raw_id) >= 9) { $dmrid = substr($raw_id, 0, 7); $rpt = substr($raw_id, 7, 2); }

        $u = $old_map[$b] ?? [];
        if ($call) $u['callsign'] = $call;
        if ($raw_id) { $u['dmrid'] = $dmrid; $u['rpt'] = $rpt; }
        if ($raw_pass) { $u['bmpass'] = $raw_pass; }
        if ($ta) { $u['ta'] = $ta; }
        $u['controlPort'] = $ctrl_port;

        $u['rxfreq'] = get_ini_val($ini_content, 'RXFrequency');
        $u['txfreq'] = get_ini_val($ini_content, 'TXFrequency');
        $u['power'] = get_ini_val($ini_content, 'Power');
        $u['lat'] = get_ini_val($ini_content, 'Latitude');
        $u['lon'] = get_ini_val($ini_content, 'Longitude');
        $u['height'] = get_ini_val($ini_content, 'Height');
        $u['location'] = get_ini_val($ini_content, 'Location');
        $u['desc'] = get_ini_val($ini_content, 'Description');
        $u['url'] = get_ini_val($ini_content, 'URL');
        $u['bridge'] = str_pad($b, 2, "0", STR_PAD_LEFT);
        $u['files'] = $files;
        if (!isset($u['suffix'])) $u['suffix'] = '';

        $active_users[] = $u;
    }
    usort($active_users, function($a, $b) { return (int)$a['bridge'] <=> (int)$b['bridge']; });
    file_put_contents($users_file, json_encode($active_users, JSON_PRETTY_PRINT));
    return $active_users;
}

if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $in = json_decode(file_get_contents('php://input'), true);
        $users = json_decode(file_get_contents($users_file), true) ?: [];

        if ($in['action'] === 'edit') {
            $idx = $in['index']; $u = $in['data'];
            $u['bridge'] = str_pad((int)$u['bridge'], 2, "0", STR_PAD_LEFT);
            $users[$idx] = $u;

            if (!empty($u['files']) && is_array($u['files'])) {
                $full_id = $u['dmrid'] . $u['rpt'];
                $cmds = [
                    "s|^Callsign\s*=.*|Callsign={$u['callsign']}|I",
                    "s|^Id\s*=.*|Id={$full_id}|I",
                    "s|^Password\s*=.*|Password={$u['bmpass']}|I",
                    "s|^talkerAlias\s*=.*|talkerAlias={$u['ta']}|I",
                    "s|^RXFrequency\s*=.*|RXFrequency={$u['rxfreq']}|I",
                    "s|^TXFrequency\s*=.*|TXFrequency={$u['txfreq']}|I",
                    "s|^Power\s*=.*|Power={$u['power']}|I",
                    "s|^Latitude\s*=.*|Latitude={$u['lat']}|I",
                    "s|^Longitude\s*=.*|Longitude={$u['lon']}|I",
                    "s|^Height\s*=.*|Height={$u['height']}|I",
                    "s|^Location\s*=.*|Location={$u['location']}|I",
                    "s|^Description\s*=.*|Description={$u['desc']}|I",
                    "s|^URL\s*=.*|URL={$u['url']}|I",
                    "/^\\[USRP\\]/,/^\\[/ s|^txPort\\s*=.*|txPort={$u['controlPort']}|I",
                    "/^\\[USRP\\]/,/^\\[/ s|^rxPort\\s*=.*|rxPort={$u['controlPort']}|I"
                ];

                foreach ($u['files'] as $f) {
                    if (file_exists($f)) {
                        $esc_f = escapeshellarg($f);
                        foreach ($cmds as $cmd) shell_exec("sudo sed -i " . escapeshellarg($cmd) . " $esc_f");
                    }
                }
            }
            usort($users, function($a, $b) { return (int)$a['bridge'] <=> (int)$b['bridge']; });
            file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));
            echo json_encode(['status' => 'ok']);
        }
        exit;
    }
    echo json_encode(sync_from_server_ini());
    exit;
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NMS User Admin</title>
    <style>
        :root { --bg: #121212; --card: #1e1e1e; --green: #00e676; --blue: #4da6ff; --red: #ff5252; --warn: #ffb142; }
        body { background: var(--bg); color: #eee; font-family: 'Malgun Gothic', sans-serif; margin: 0; padding: 15px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header-container { max-width: 1000px; margin: 0 auto 20px; border-bottom: 2px solid #333; padding-bottom: 15px; display: flex; justify-content: space-between; align-items: center; width: 100%; gap: 20px;}
        .left-btns { display: flex; gap: 8px; flex-shrink: 0; }
        .header-title { display: flex; justify-content: center; align-items: center; gap: 10px; margin: 0; padding: 0; font-size: 1.6rem; color: var(--green); letter-spacing: 1px; flex-grow: 1; text-align: center; white-space: nowrap;}
        .back-btn, .logout-btn, .update-btn { background-color: #333; color: #fff; text-decoration: none; padding: 10px 16px; border-radius: 8px; font-size: 0.95rem; font-weight: bold; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; border: 1px solid #444; cursor: pointer; white-space: nowrap;}
        .logout-btn { color: var(--red); flex-shrink: 0; }
        .logout-btn:hover { background-color: var(--red); color: #fff; }
        .back-btn:hover { transform: translateY(-2px); }
        .update-btn { background: transparent; border-color: var(--blue); color: var(--blue); }
        .update-btn:hover { background-color: var(--blue); color: #000; box-shadow: 0 0 10px rgba(77, 166, 255, 0.4); transform: translateY(-2px); }
        .status-text { margin: 0; font-size: 0.85rem; color: var(--blue); font-weight: bold; text-align: center; margin-bottom: 20px;}
        .grid { display: grid; gap: 12px; grid-template-columns: 1fr; }
        @media(min-width: 600px) { .grid { grid-template-columns: repeat(2, 1fr); } }
        .card { background: var(--card); padding: 15px; border-radius: 8px; border-left: 4px solid var(--blue); position: relative; }
        .info strong { display: block; font-size: 1.25rem; margin-bottom: 5px; color: #fff; }
        .info .suffix { color: var(--warn); font-size: 1.1rem; }
        .info span { display: inline-block; background:#333; padding:2px 6px; border-radius:4px; font-size: 0.8rem; color: #ddd; margin: 0 3px 5px 0; }
        .pass-tag { color: var(--green) !important; border: 1px solid var(--green); }
        .pass-none { color: var(--warn) !important; border: 1px solid var(--warn); }
        .file-path { font-size: 0.7rem; color: #666; font-family: monospace; word-break: break-all; margin-top: 8px; border-top: 1px dashed #444; padding-top: 8px; line-height: 1.4; }
        .card-actions { position: absolute; top: 15px; right: 15px; display: flex; gap: 5px; }
        .btn-edit { background: var(--warn); color: #000; padding: 6px 12px; font-size: 0.85rem; border-radius: 6px; border: none; font-weight: bold; cursor: pointer; }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center; }
        .modal-box { background: var(--card); width: 95%; max-width: 420px; padding: 25px; border-radius: 12px; border-top: 4px solid var(--warn); box-shadow: 0 10px 30px rgba(0,0,0,0.5); max-height: 90vh; overflow-y: auto; }
        .modal-box h3 { margin: 0 0 15px 0; color: #fff; border-bottom: 1px solid #333; padding-bottom: 10px; }
        .modal-box label { display: block; font-size: 0.85rem; color: #aaa; margin-bottom: 4px; font-weight:bold;}
        .modal-box input { width: 100%; padding: 10px; margin-bottom: 12px; box-sizing: border-box; background: #111; border: 1px solid #444; color: #fff; border-radius: 5px; font-size: 0.95rem; }
        .modal-box input:focus { border-color: var(--warn); outline: none; }
        .modal-box input[readonly] { background: #222; color: #888; cursor: not-allowed; border-color: #333; }
        .modal-box .input-group { display: flex; gap: 10px; }
        .modal-box .input-group > div { flex: 1; }
        .adv-settings { background: #181818; padding: 12px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #333; }
        .adv-settings summary { font-weight: bold; cursor: pointer; color: var(--blue); outline: none; margin-bottom: 5px; font-size: 0.95rem; }
        .adv-settings summary::-webkit-details-marker { color: var(--blue); }
        .adv-content { margin-top: 10px; border-top: 1px dashed #333; padding-top: 10px; }
        .modal-warn { font-size: 0.8rem; color: var(--warn); margin-bottom: 15px; display: none; line-height: 1.4; padding: 8px; background: rgba(255, 177, 66, 0.1); border-radius: 6px; border: 1px solid rgba(255,177,66,0.3);}
        .modal-buttons { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; }
        .btn-cancel { background: #333; color: #eee; border: 1px solid #555; padding: 8px 12px; border-radius: 6px; cursor: pointer;}
        #updateModal .modal-box { border-top-color: var(--blue); text-align: center; }
        .spinner { border: 4px solid rgba(255, 255, 255, 0.1); border-left-color: var(--blue); border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 20px; }
        .file-list-box { background: #111; padding: 12px; border-radius: 8px; border: 1px solid #333; text-align: left; max-height: 160px; overflow-y: auto; margin-top: 15px; font-size: 0.85rem; font-family: monospace; color: #ccc; line-height: 1.5; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @media (max-width: 768px) {
            .header-container { flex-direction: column; align-items: stretch; border-bottom: none; padding-bottom: 0; gap: 10px; }
            .left-btns { display: flex; width: 100%; gap: 10px; }
            .back-btn { flex: 1; margin: 0; }
            .logout-btn { flex: 1; margin: 0; }
            .update-btn { width: 100%; margin: 0; }
            .header-title { font-size: 1.3rem; margin-top: 10px; margin-bottom: 5px; text-align: center; width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-container">
            <div class="left-btns">
                <a href="dashboard.php" class="back-btn">⬅️ 대시보드 복귀</a>
                <a href="?logout=1" class="logout-btn" id="mobile-logout">🔓 로그아웃</a>
            </div>
            <h1 class="header-title">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                시스템 통합 설정 관리
            </h1>
            <button class="update-btn" onclick="runSystemUpdate()">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" style="margin-right:5px;"><path d="M19 8l-4 4h3c0 3.31-2.69 6-6 6-1.01 0-1.97-.25-2.8-.7l-1.46 1.46C8.97 19.54 10.43 20 12 20c4.42 0 8-3.58 8-8h3l-4-4zM6 12c0-3.31 2.69-6 6-6 1.01 0 1.97.25 2.8.7l1.46-1.46C15.03 4.46 13.57 4 12 4c-4.42 0-8 3.58-8 8H1l4 4 4-4H6z"/></svg>
                시스템 업데이트
            </button>
            <a href="?logout=1" class="logout-btn" id="pc-logout" style="display: none;">🔓 로그아웃</a>
        </div>
        <p class="status-text">🔄 원격 서버의 폴더를 스캔하여 최신 파일을 가져옵니다. (파일명 뒤에 .txt 권장)</p>
        <div id="user-list" class="grid">
            <div style="text-align:center; padding:30px; color:#888;">서버 데이터를 읽어오는 중입니다...</div>
        </div>
    </div>

    <div id="editModal" class="modal-overlay">
        <div class="modal-box">
            <h3 id="modalTitle">사용자 정보 상세 수정</h3>
            <input type="hidden" id="modalIndex">
            <input type="hidden" id="modalFiles">
            <div class="input-group">
                <div style="flex: 2;"><label>Callsign</label><input type="text" id="modalCall" placeholder="DS1UYM" style="text-transform: uppercase;"></div>
                <div style="flex: 1;"><label>Suffix</label><input type="text" id="modalSuffix" placeholder="/M, /PC"></div>
            </div>
            <div class="input-group">
                <div><label>Bridge</label><input type="text" id="modalBridge" readonly></div>
                <div><label>RPT (2자리)</label><input type="text" id="modalRpt" placeholder="01" maxlength="2"></div>
            </div>
            <div class="input-group">
                <div style="flex:1.5;"><label>DMR ID</label><input type="text" id="modalId" placeholder="4501953" maxlength="7"></div>
                <div style="flex:2;"><label>BM Password</label><input type="text" id="modalBmPass" placeholder="BM 연결 암호"></div>
            </div>
            <label>Talker Alias (송출 닉네임)</label><input type="text" id="modalTA">
            <div class="input-group" style="margin-top:10px;">
                <div style="flex:1;"><label>제어 포트 (USRP txPort/rxPort)</label><input type="number" id="modalControlPort" placeholder="50001"></div>
            </div>
            <details class="adv-settings">
                <summary>⚙️ 상세 설정 (주파수/위치/장비 정보)</summary>
                <div class="adv-content">
                    <div class="input-group"><div><label>RX Freq</label><input type="text" id="modalRx"></div><div><label>TX Freq</label><input type="text" id="modalTx"></div></div>
                    <div class="input-group"><div><label>Latitude</label><input type="text" id="modalLat"></div><div><label>Longitude</label><input type="text" id="modalLon"></div></div>
                    <div class="input-group"><div><label>Height</label><input type="text" id="modalHeight"></div><div><label>Power</label><input type="text" id="modalPower"></div></div>
                    <div class="input-group"><div><label>Location</label><input type="text" id="modalLoc"></div><div><label>Desc</label><input type="text" id="modalDesc"></div></div>
                    <label>URL</label><input type="text" id="modalUrl">
                </div>
            </details>
            <div id="modalWarn" class="modal-warn">⚠️ 저장 시 관련된 INI 파일이 즉시 수정됩니다.</div>
            <div class="modal-buttons">
                <button class="btn-cancel" onclick="closeModal()">취소</button>
                <button class="btn-edit" style="background:var(--green);" onclick="saveUser()">저장하기</button>
            </div>
        </div>
    </div>

    <div id="updateModal" class="modal-overlay">
        <div class="modal-box">
            <div id="updateSpinner" class="spinner"></div>
            <h3 id="updateTitle" style="color: var(--blue); border-bottom: none; margin-bottom: 5px;">시스템 업데이트 중...</h3>
            <div id="updateMessage" style="color: #aaa; font-size: 0.95rem; line-height: 1.5;">
                서버의 모든 파일을 스캔하고 다운로드 중입니다.<br>잠시만 기다려 주세요.
            </div>
            <div class="modal-buttons" style="justify-content: center; margin-top: 20px;">
                <button id="updateCloseBtn" style="background:var(--green); color:#000; display:none; width: 100%; border:none; padding:12px; border-radius:6px; font-weight:bold; font-size:1rem; cursor:pointer;" onclick="closeUpdateModal()">확인 (새로고침)</button>
            </div>
        </div>
    </div>

    <script>
        function adjustLogoutButton() {
            if (window.innerWidth <= 768) {
                document.getElementById('mobile-logout').style.display = 'flex';
                document.getElementById('pc-logout').style.display = 'none';
            } else {
                document.getElementById('mobile-logout').style.display = 'none';
                document.getElementById('pc-logout').style.display = 'flex';
            }
        }
        window.addEventListener('resize', adjustLogoutButton);
        adjustLogoutButton();

        let currentData = [];

        function loadUsers() {
            fetch('?api=1').then(r => r.json()).then(data => {
                currentData = data;
                const list = document.getElementById('user-list');
                if (data.length === 0) {
                    list.innerHTML = '<div style="text-align:center; color:#ff5252; padding:30px;">DVSMU 사용자가 없습니다.</div>';
                    return;
                }
                let html = '';
                data.forEach((u, i) => {
                    const bridgeName = u.bridge === '00' ? 'MAIN (00)' : `USER (${u.bridge})`;
                    const passStatus = u.bmpass ? `<span class="pass-tag">🔑 암호설정됨</span>` : `<span class="pass-none">⚠️ 암호미설정</span>`;
                    const suffixText = u.suffix ? `<span class="suffix">${u.suffix}</span>` : '';
                    let fileText = '⚠️ 웹 전용';
                    if (u.files && u.files.length > 0) { fileText = '📁 ' + u.files.join('<br>📁 '); }

                    html += `
                        <div class="card">
                            <div class="card-actions"><button class="btn-edit" onclick="openModal(${i})">상세 설정</button></div>
                            <div class="info">
                                <strong>${u.callsign || 'N/A'}${suffixText}</strong>
                                <div>
                                    <span>🌐 BR: ${bridgeName}</span><span>📡 RPT: ${u.rpt || '--'}</span><span>🆔 ID: ${u.dmrid || '미설정'}</span>
                                    <span style="background:#0d47a1; color:#82b1ff;">🔌 USRP: ${u.controlPort || '미설정'}</span>
                                    <span style="background:#442200; color:#ffb142;">🗣️ TA: ${u.ta || '미설정'}</span>
                                </div>
                                <div style="margin-top:5px;">${passStatus}</div>
                            </div>
                            <div class="file-path">${fileText}</div>
                        </div>`;
                });
                list.innerHTML = html;
            });
        }

        function openModal(index) {
            document.getElementById('editModal').style.display = 'flex';
            const u = currentData[index];
            document.getElementById('modalIndex').value = index;
            document.getElementById('modalCall').value = u.callsign || '';
            document.getElementById('modalSuffix').value = u.suffix || '';
            document.getElementById('modalBridge').value = u.bridge || '00';
            document.getElementById('modalRpt').value = u.rpt || '';
            document.getElementById('modalId').value = u.dmrid || '';
            document.getElementById('modalBmPass').value = u.bmpass || '';
            document.getElementById('modalTA').value = u.ta || '';
            document.getElementById('modalControlPort').value = u.controlPort || '';
            document.getElementById('modalRx').value = u.rxfreq || '';
            document.getElementById('modalTx').value = u.txfreq || '';
            document.getElementById('modalLat').value = u.lat || '';
            document.getElementById('modalLon').value = u.lon || '';
            document.getElementById('modalHeight').value = u.height || '';
            document.getElementById('modalPower').value = u.power || '';
            document.getElementById('modalLoc').value = u.location || '';
            document.getElementById('modalDesc').value = u.desc || '';
            document.getElementById('modalUrl').value = u.url || '';
            document.getElementById('modalFiles').value = JSON.stringify(u.files || []);
            document.getElementById('modalWarn').style.display = (u.files && u.files.length > 0) ? 'block' : 'none';
        }

        function closeModal() { document.getElementById('editModal').style.display = 'none'; }

        function saveUser() {
            const call = document.getElementById('modalCall').value.toUpperCase();
            if (!call) { alert("Callsign을 입력해주세요."); return; }
            const payload = {
                action: 'edit', index: parseInt(document.getElementById('modalIndex').value),
                data: {
                    callsign: call, suffix: document.getElementById('modalSuffix').value.toUpperCase(),
                    bridge: document.getElementById('modalBridge').value, rpt: document.getElementById('modalRpt').value,
                    dmrid: document.getElementById('modalId').value, bmpass: document.getElementById('modalBmPass').value,
                    ta: document.getElementById('modalTA').value, controlPort: document.getElementById('modalControlPort').value,
                    rxfreq: document.getElementById('modalRx').value, txfreq: document.getElementById('modalTx').value,
                    lat: document.getElementById('modalLat').value, lon: document.getElementById('modalLon').value,
                    height: document.getElementById('modalHeight').value, power: document.getElementById('modalPower').value,
                    location: document.getElementById('modalLoc').value, desc: document.getElementById('modalDesc').value,
                    url: document.getElementById('modalUrl').value,
                    files: JSON.parse(document.getElementById('modalFiles').value || '[]')
                }
            };
            fetch('?api=1', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
                .then(() => { closeModal(); loadUsers(); });
        }

        function runSystemUpdate() {
            if (!confirm("원격 서버에서 파일을 다운로드하여 덮어씌웁니다. 진행하시겠습니까?")) return;

            const modal = document.getElementById('updateModal');
            const title = document.getElementById('updateTitle');
            const msg = document.getElementById('updateMessage');
            const spinner = document.getElementById('updateSpinner');
            const closeBtn = document.getElementById('updateCloseBtn');

            modal.style.display = 'flex';
            title.innerText = '시스템 업데이트 중...'; 
            title.style.color = 'var(--blue)';
            msg.innerHTML = '<span style="color:#aaa;">다운로드를 진행하고 있습니다.<br>잠시만 기다려 주세요.</span>';
            spinner.style.display = 'block'; 
            closeBtn.style.display = 'none';

            const formData = new FormData(); 
            formData.append('action', 'system_update');

            fetch('', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                spinner.style.display = 'none'; 
                closeBtn.style.display = 'block';
                
                if(data.count > 0) { 
                    title.innerText = data.success ? '✅ 업데이트 완료!' : '⚠️ 부분 업데이트 완료'; 
                    title.style.color = data.success ? 'var(--green)' : 'var(--warn)';
                    
                    let fileListHtml = '<div class="file-list-box">';
                    data.files.forEach(file => { fileListHtml += `<span style="color:var(--green)">✓</span> ${file}<br>`; });
                    fileListHtml += '</div>';
                    
                    let errorHtml = '';
                    if (!data.success && data.errors && data.errors.length > 0) {
                        errorHtml = `<div style="color:var(--red); margin-top:10px; font-size:0.8rem; border:1px solid var(--red); padding:8px; border-radius:5px;"><b>[실패 사유 확인]</b><br>${data.errors.join('<br>')}</div>`;
                    }
                    
                    msg.innerHTML = `<strong>총 ${data.count}개의 파일</strong>이 덮어씌워졌습니다.${fileListHtml}${errorHtml}`;
                } else {
                    title.innerText = '❌ 업데이트 실패'; 
                    title.style.color = 'var(--red)';
                    msg.innerHTML = `<span style="color:var(--warn);">${data.message || '알 수 없는 오류가 발생했습니다.'}</span><br><br><span style="color:#888; font-size:0.8rem;">${data.errors ? data.errors.join('<br>') : ''}</span>`;
                }
            }).catch(error => {
                spinner.style.display = 'none'; 
                closeBtn.style.display = 'block';
                title.innerText = '❌ 통신 오류'; 
                title.style.color = 'var(--red)';
                msg.innerHTML = '<span style="color:var(--warn);">서버 통신 에러가 발생했습니다. (포트 8888 또는 PHP 설정 확인 필요)</span>';
            });
        }

        function closeUpdateModal() { document.getElementById('updateModal').style.display = 'none'; window.location.reload(); }

        loadUsers();
    </script>
</body>
</html>
