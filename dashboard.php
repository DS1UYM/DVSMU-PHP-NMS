<?php
// 💡 서버의 기본 시간을 한국 표준시(KST)로 강제 고정합니다.
date_default_timezone_set('Asia/Seoul');

// 세션 시작 (로그인 상태 유지용)
session_start();

// =========================================================================
// 🚨 1. 스마트 네트워크 감지 및 외부망 접속자 인증 로직
// =========================================================================
$users_file = '/var/log/webaccess/dvs_users.json';

// 클라이언트 IP 추출
$client_ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
$client_ip = trim(explode(',', $client_ip)[0]);

// 내부망 판별
$is_local = false;
if (in_array($client_ip, ['127.0.0.1', '::1']) ||
    strpos($client_ip, '192.168.') === 0 ||
    strpos($client_ip, '10.') === 0 ||
    preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $client_ip)) {
    $is_local = true;
}

if (isset($_GET['dash_logout'])) {
    unset($_SESSION['is_dashboard_auth']);
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// 외부망일 경우 인증 절차 수행
if (!$is_local) {
    if (!isset($_SESSION['is_dashboard_auth']) || $_SESSION['is_dashboard_auth'] !== true) {
        $login_error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_call']) && isset($_POST['login_pass'])) {
            $input_call = strtoupper(trim($_POST['login_call']));
            $input_pass = trim($_POST['login_pass']);

            $users_data = file_exists($users_file) ? json_decode(file_get_contents($users_file), true) : [];
            $auth_success = false;

            if (is_array($users_data)) {
                foreach ($users_data as $u) {
                    if ((strtoupper($u['callsign'] ?? '') === $input_call)
                        && ($u['bmpass'] ?? '') === $input_pass && !empty($u['bmpass'])) {
                        $auth_success = true;
                        break;
                    }
                }
            }

            if ($auth_success) {
                $_SESSION['is_dashboard_auth'] = true;
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $login_error = '콜사인 또는 BM 비밀번호가 일치하지 않습니다.';
            }
        }
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔒 대시보드 보안 접속</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        :root { --bg: #121212; --card: #1e1e1e; --green: #00e676; --red: #ff5252; --warn: #ffb142; }
        body { background: var(--bg); color: #eee; font-family: 'Segoe UI', Tahoma, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: var(--card); padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.8); border-top: 4px solid var(--green); text-align: center; width: 100%; max-width: 350px; }
        .login-box h2 { margin-top: 0; color: #fff; letter-spacing: 1px; margin-bottom: 10px; font-size: 1.4rem; }
        .login-desc { font-size: 0.85rem; color: #aaa; margin-bottom: 25px; line-height: 1.5; }
        .pwd-input { width: 100%; padding: 12px; border-radius: 6px; border: 1px solid #444; background: #111; color: #fff; font-size: 1.1rem; box-sizing: border-box; text-align: center; letter-spacing: 1px; transition: 0.3s; margin-bottom: 15px; }
        .pwd-input:focus { border-color: var(--green); outline: none; box-shadow: 0 0 10px rgba(0,230,118,0.3); }
        .login-btn { width: 100%; background: var(--green); color: #000; border: none; padding: 12px; border-radius: 6px; font-weight: 900; font-size: 1.1rem; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 10px rgba(0,230,118,0.3); margin-top: 10px;}
        .login-btn:hover { background: #55ff99; transform: translateY(-2px); }
        .error-msg { color: var(--red); font-size: 0.9rem; margin-bottom: 15px; font-weight: bold; }
        .ip-badge { display: inline-block; background: rgba(255, 177, 66, 0.15); color: var(--warn); padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; margin-top: 25px; border: 1px solid var(--warn);}
    </style>
</head>
<body>
    <div class="login-box">
        <h2>📻 DVSwitch 대시보드</h2>
        <div class="login-desc">외부망 접속이 감지되었습니다.<br>등록된 콜사인과 BM 비밀번호로 인증해주세요.</div>
        <?php if($login_error): ?><div class="error-msg">❌ <?= $login_error ?></div><?php endif; ?>
        <form method="POST">
            <input type="text" name="login_call" class="pwd-input" placeholder="콜사인 (Callsign)" required autofocus style="text-transform: uppercase;">
            <input type="password" name="login_pass" class="pwd-input" placeholder="BM 비밀번호" required>
            <button type="submit" class="login-btn">대시보드 접속</button>
        </form>
        <div class="ip-badge">접속 IP: <?= htmlspecialchars($client_ip) ?> (외부망)</div>
    </div>
</body>
</html>
<?php
        exit;
    }
}
// =========================================================================

// --- [1] DVSMU 메인 콜사인 자동 추출 ---
$sys_callsign = 'DVSwitch';
$ini_path = '/opt/MMDVM_Bridge/MMDVM_Bridge.ini';
if (file_exists($ini_path)) {
    $parsed_call = shell_exec("grep -i '^Callsign' " . escapeshellarg($ini_path) . " | cut -d'=' -f2 | head -n 1");
    if ($parsed_call && trim($parsed_call) !== '') {
        $sys_callsign = trim($parsed_call);
    }
}

// --- [2] 백엔드 (대시보드 실시간 렌더링 로직 API) ---
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');

    $cpu_val = shell_exec("top -bn1 | grep 'Cpu(s)' | awk '{print $2 + $4}'");
    $cpu_usage = $cpu_val ? round((float)trim($cpu_val), 1) : 0;

    $load = sys_getloadavg();
    $load1 = $load[0];
    $load5 = $load[1];

    $mem_info = shell_exec("free -m | awk 'NR==2{print $3, $2}'");
    list($mem_used, $mem_total) = explode(" ", trim($mem_info));
    $mem_usage = ($mem_total > 0) ? round(($mem_used / $mem_total) * 100, 1) : 0;

    $max_kbps = 10000;
    $net_rx_kbps = 0; $net_tx_kbps = 0; $net_percent = 0;

    $iface = trim(shell_exec("ip route get 8.8.8.8 | awk 'NR==1 {print $5}' 2>/dev/null")) ?: "eth0";
    $net_str = trim(shell_exec("awk -v dev=\"$iface:\" '$1 == dev {print $2, $10}' /proc/net/dev 2>/dev/null"));
    if ($net_str) {
        list($rx_bytes, $tx_bytes) = explode(" ", $net_str);
        $now_time = microtime(true);
        $tmp_file = '/tmp/dvs_net_stat.json';
        if (file_exists($tmp_file)) {
            $old = json_decode(file_get_contents($tmp_file), true);
            $time_diff = $now_time - $old['time'];
            if ($time_diff > 0) {
                $net_rx_kbps = round(((($rx_bytes - $old['rx']) / $time_diff) * 8) / 1000, 1);
                $net_tx_kbps = round(((($tx_bytes - $old['tx']) / $time_diff) * 8) / 1000, 1);
                $net_rx_kbps = max(0, $net_rx_kbps);
                $net_tx_kbps = max(0, $net_tx_kbps);
                $net_percent = min(100, round((($net_rx_kbps + $net_tx_kbps) / $max_kbps) * 100, 1));
            }
        }
        @file_put_contents($tmp_file, json_encode(['rx' => $rx_bytes, 'tx' => $tx_bytes, 'time' => $now_time]));
    }

    $uptime_str = @file_get_contents('/proc/uptime');
    if ($uptime_str) {
        list($uptime_sec) = explode(' ', $uptime_str);
        $uptime_sec = (int)$uptime_sec;
        $d = floor($uptime_sec / 86400);
        $h = floor(($uptime_sec % 86400) / 3600);
        $m = floor(($uptime_sec % 3600) / 60);
        $uptime_formatted = ($d > 0 ? $d."일 " : "") . sprintf("%02d시간 %02d분", $h, $m);
    } else {
        $uptime_formatted = "N/A";
    }

    // BM 서버 Ping 테스트 및 품질 상태 로직
    $target_host = $_GET['bm_host'] ?? '4501.master.brandmeister.network';
    $bm_cache_file = '/tmp/bm_status_' . md5($target_host) . '.json';
    $bm_check_interval = 10; 

    $bm_status = ['status' => 'WAIT...', 'color' => '#aaa', 'ping' => '-', 'quality' => 'unknown', 'time' => '--:--:--'];

    if (file_exists($bm_cache_file)) {
        $bm_cache = json_decode(file_get_contents($bm_cache_file), true);
        if (time() - $bm_cache['ts'] < $bm_check_interval) {
            $bm_status = $bm_cache['data'];
        } else {
            $needs_bm_check = true;
        }
    } else {
        $needs_bm_check = true;
    }

    if (isset($needs_bm_check) && $needs_bm_check) {
        $port = 443;
        $start_time = microtime(true);
        $connection = @fsockopen($target_host, $port, $errno, $errstr, 2);
        $end_time = microtime(true);

        if (is_resource($connection)) {
            fclose($connection);
            $ping_ms = round(($end_time - $start_time) * 1000); 
            
            if ($ping_ms < 150) {
                $quality = 'good';
                $color = '#00e676';
                $status_text = '최적 (Good)';
            } elseif ($ping_ms < 300) {
                $quality = 'fair';
                $color = '#ffb142';
                $status_text = '보통 (Fair)';
            } else {
                $quality = 'poor';
                $color = '#ff5252';
                $status_text = '지연 (Poor)';
            }

            $bm_status = [
                'status' => $status_text, 
                'color' => $color, 
                'ping' => $ping_ms,
                'quality' => $quality,
                'time' => date('H:i:s')
            ];
        } else {
            $bm_status = [
                'status' => '연결 실패', 
                'color' => '#ff5252', 
                'ping' => 'Timeout',
                'quality' => 'offline',
                'time' => date('H:i:s')
            ];
        }
        @file_put_contents($bm_cache_file, json_encode(['ts' => time(), 'data' => $bm_status]));
    }

    // --- 사용자 로깅 분석 로직 ---
    $users = json_decode(@file_get_contents($users_file), true) ?: [];
    $timeout_seconds = 900;
    $now_utc = time();

    $cache_file = '/var/log/webaccess/dvs_user_history.json';
    $cache_data = file_exists($cache_file) ? json_decode(file_get_contents($cache_file), true) : [];
    if (!is_array($cache_data)) $cache_data = [];

    $today_kst = date('Y-m-d');

    foreach ($users as &$user) {
        $ukey = ($user['callsign'] ?? '') . ($user['suffix'] ?? '');

        if (!isset($cache_data[$ukey])) {
            $cache_data[$ukey] = ['history' => [], 'last_tg' => ''];
        }

        $history_arr = &$cache_data[$ukey]['history'];
        $last_tg_saved = &$cache_data[$ukey]['last_tg'];

        $last_seen_ts = !empty($history_arr) ? $history_arr[0] : 0;
        $status = 'offline';
        $active_tg = '';
        $is_ptt = false;

        $bridge_num = (int)($user['bridge'] ?? 0);
        $log_path = ($bridge_num === 0)
                    ? "/var/log/dvswitch/Analog_Bridge.log"
                    : "/var/log/dvswitch/user" . str_pad($bridge_num, 2, "0", STR_PAD_LEFT) . "/Analog_Bridge.log";

        if (file_exists($log_path) && is_readable($log_path)) {
            $tail = shell_exec("tail -n 150 " . escapeshellarg($log_path) . " 2>/dev/null");
            if ($tail) {
                $lines = array_reverse(explode("\n", trim($tail)));
                $found_status = false; $found_tg = false; $found_ptt = false;

                foreach ($lines as $line) {
                    if (strpos($line, "I:") !== 0 && strpos($line, "M:") !== 0) continue;
                    if (!preg_match('/^[IM]:\s+(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})/', $line, $time_matches)) continue;

                    $dt_log = new DateTime($time_matches[1], new DateTimeZone('UTC'));
                    $log_time = $dt_log->getTimestamp();
                    $time_diff = $now_utc - $log_time;
                    $is_recent = ($time_diff >= -60 && $time_diff <= $timeout_seconds);

                    if (!$found_ptt && preg_match('/PTT (on|off)/', $line, $m)) {
                        if ($is_recent && $m[1] === 'on') { $is_ptt = true; }
                        $found_ptt = true;
                        if ($log_time > $last_seen_ts) $last_seen_ts = $log_time;
                        if (!$found_status) { $status = $is_recent ? 'online' : 'offline'; $found_status = true; }
                    }

                    if (!$found_tg && preg_match('/txTg=:\s*(\d+)/i', $line, $m)) {
                        if ($is_recent) { $active_tg = $m[1]; }
                        $found_tg = true;
                        if ($log_time > $last_seen_ts) $last_seen_ts = $log_time;
                        if (!$found_status) { $status = $is_recent ? 'online' : 'offline'; $found_status = true; }
                    }

                    if (!$found_status) {
                        if (strpos($line, 'USRP unregister client') !== false) {
                            $status = 'offline';
                            $found_status = true;
                            if ($log_time > $last_seen_ts) $last_seen_ts = $log_time;
                        }
                        else {
                            $log_call_match = $user['callsign'] ?? '';
                            $log_id_match = ($user['dmrid'] ?? '') . ($user['rpt'] ?? '');

                            if (preg_match('/USRP_TYPE_TEXT \(' . preg_quote($log_call_match, '/') . '\) -> ' . preg_quote($log_id_match, '/') . '/i', $line)) {
                                $status = $is_recent ? 'online' : 'offline';
                                $found_status = true;
                                if ($log_time > $last_seen_ts) $last_seen_ts = $log_time;
                            }
                        }
                    }
                    if ($found_status && $found_tg && $found_ptt) break;
                }
            }
        } elseif (file_exists($log_path) && !is_readable($log_path)) {
            $status = 'permission_error';
        }

        if ($status === 'online') {
            if ($active_tg !== '') { $last_tg_saved = $active_tg; }
            else { $active_tg = $last_tg_saved; }
        } else {
            $active_tg = '';
        }

        if ($last_seen_ts > 0) {
            if (empty($history_arr)) {
                array_unshift($history_arr, $last_seen_ts);
            } else {
                $time_diff_log = $last_seen_ts - $history_arr[0];
                if ($time_diff_log > 3600) {
                    array_unshift($history_arr, $last_seen_ts);
                    if (count($history_arr) > 10) { array_pop($history_arr); }
                } else if ($time_diff_log > 0) {
                    $history_arr[0] = $last_seen_ts;
                }
            }

            $dt = new DateTime();
            $dt->setTimestamp($history_arr[0]);
            $dt->setTimezone(new DateTimeZone('Asia/Seoul'));

            if ($today_kst == $dt->format('Y-m-d')) { $last_seen = $dt->format('H:i:s'); }
            else { $last_seen = $dt->format('m-d H:i'); }
        } else {
            $last_seen = '기록 없음';
        }

        $formatted_history = [];
        if (!empty($history_arr)) {
            foreach ($history_arr as $ts) {
                if ($ts > 0) {
                    $dt = new DateTime();
                    $dt->setTimestamp($ts);
                    $dt->setTimezone(new DateTimeZone('Asia/Seoul'));
                    $formatted_history[] = $dt->format('Y-m-d H:i:s');
                }
            }
        }

        $user['full_call'] = $ukey;
        $user['status'] = $status;
        $user['last_seen'] = $last_seen;
        $user['last_seen_ts'] = !empty($history_arr) ? $history_arr[0] : 0;
        $user['active_tg'] = $active_tg;
        $user['is_ptt'] = $is_ptt;
        $user['history'] = $formatted_history;
    }

    @file_put_contents($cache_file, json_encode($cache_data));
    unset($user);

    echo json_encode([
        'stats' => [
            'cpu' => $cpu_usage, 'load1' => $load1, 'load5' => $load5,
            'mem' => $mem_usage, 'mem_used' => $mem_used, 'mem_total' => $mem_total,
            'net_pct' => $net_percent, 'net_rx' => $net_rx_kbps, 'net_tx' => $net_tx_kbps,
            'uptime' => $uptime_formatted
        ],
        'users' => $users,
        'bm_status' => $bm_status
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📻 <?php echo htmlspecialchars($sys_callsign); ?> DVS 실시간 대시보드</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        :root { --bg: #121212; --card: #1e1e1e; --text: #eee; --sub: #aaa; --green: #00e676; --red: #ff5252; --warn: #ffb142; --blue: #4da6ff;}
        body { background: var(--bg); color: var(--text); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 15px; box-sizing: border-box;}

        .header-container { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; max-width: 96%; margin: 0 auto 15px; border-bottom: 2px solid #333; padding-bottom: 10px; gap: 15px; }
        .header-container h1 { margin: 0; padding: 0; font-size: 1.9rem; color: var(--text); letter-spacing: 1px;}
        .header-buttons { display: flex; gap: 10px; flex-wrap: wrap; }

        .control-btn { background-color: #333; color: #fff; text-decoration: none; padding: 8px 16px; border-radius: 8px; font-size: 0.9rem; font-weight: bold; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px rgba(0,0,0,0.4); border: 1px solid #444; cursor: pointer; white-space: nowrap; }
        .btn-sound { border-color: var(--warn); color: var(--warn); }
        .btn-sound:hover { background-color: var(--warn) !important; color: #000 !important; border-color: var(--warn) !important; box-shadow: 0 0 10px rgba(255, 177, 66, 0.4) !important; transform: translateY(-2px); }
        .btn-admin { border-color: var(--green); color: var(--green); }
        .btn-admin:hover { background-color: var(--green) !important; color: #000 !important; border-color: var(--green) !important; box-shadow: 0 0 10px rgba(0, 230, 118, 0.4) !important; transform: translateY(-2px); }
        .btn-tg { border-color: #4da6ff; color: #4da6ff; }
        .btn-tg:hover { background-color: #4da6ff !important; color: #000 !important; border-color: #4da6ff !important; box-shadow: 0 0 10px rgba(77, 166, 255, 0.4) !important; transform: translateY(-2px); }
        .btn-info { border-color: #b388ff; color: #b388ff; }
        .btn-info:hover { background-color: #b388ff !important; color: #000 !important; border-color: #b388ff !important; box-shadow: 0 0 10px rgba(179, 136, 255, 0.4) !important; transform: translateY(-2px); }
        .btn-logout { border-color: var(--red); color: var(--red); }
        .btn-logout:hover { background-color: var(--red) !important; color: #fff !important; border-color: var(--red) !important; box-shadow: 0 0 10px rgba(255, 82, 82, 0.4) !important; transform: translateY(-2px); }

        .main-wrapper { display: flex; gap: 20px; max-width: 96%; margin: 0 auto; align-items: stretch; }
        .left-column { flex: 1.15; display: flex; flex-direction: column; gap: 15px; }
        .right-column { flex: 1; display: flex; flex-direction: column; gap: 15px;}

        .sys-row { display: flex; gap: 12px; flex-wrap: wrap; }
        .sys-card { flex: 1; min-width: 140px; background: #222; padding: 15px; border-radius: 12px; border: 1px solid #333; box-shadow: 0 4px 8px rgba(0,0,0,0.4); display: flex; flex-direction: column; justify-content: center;}
        .sys-header { display: flex; justify-content: space-between; align-items: center; font-weight: bold; margin-bottom: 8px; font-size: 0.95rem;}
        .sys-val { font-size: 1.35rem; color: #fff; font-family: monospace; font-weight: bold; text-shadow: 0 0 5px rgba(255,255,255,0.3);}
        .bar-bg { background: #111; height: 14px; border-radius: 7px; border: 1px inset #000; position: relative; overflow: visible; box-shadow: 0 1px 3px rgba(255,255,255,0.1); margin-top: 5px;}
        .bar-fill { height: 100%; width: 0%; background: var(--green); border-radius: 7px; transition: width 0.3s cubic-bezier(0.22, 1, 0.36, 1), background-color 0.4s; box-shadow: inset 0 -2px 4px rgba(0,0,0,0.3); }
        .peak-mark { position: absolute; top: -3px; bottom: -3px; width: 4px; background: #fff; box-shadow: 0 0 8px #fff, 0 0 4px var(--warn); border-radius: 2px; transition: left 1.5s ease-out; z-index: 2; left: 0%; }
        .sys-details { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; font-size: 0.8rem; color: var(--sub); font-weight: bold; letter-spacing: 0.5px; }
        .sys-details span { color: #fff; font-family: monospace; font-size: 0.95rem; margin-left: 4px; margin-right: 2px;}

        .clock-container { display: flex; gap: 12px; }
        .clock-box { flex: 1; background: #1a1a1a; border-radius: 12px; padding: 15px; text-align: center; border: 1px solid #333; box-shadow: inset 0 0 20px rgba(0,0,0,0.8), 0 4px 10px rgba(0,0,0,0.5); }
        .clock-label { color: var(--warn); font-size: 0.85rem; font-weight: bold; letter-spacing: 1.5px; margin-bottom: 5px; text-transform: uppercase;}
        .clock-time { font-size: 2.4rem; font-weight: bold; color: #fff; font-family: 'Courier New', Courier, monospace; letter-spacing: 3px; text-shadow: 0 0 15px rgba(255,255,255,0.3); }

        .user-container { display: flex; flex-direction: column; gap: 15px; }
        .user-section { background: var(--card); padding: 15px 20px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.5); }
        .user-section h2 { margin-top: 0; font-size: 1.3rem; border-bottom: 1px solid #333; padding-bottom: 8px; display: flex; gap: 10px;}

        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px; margin-top: 15px; }

        .card { background: #2a2a2a; padding: 12px 10px; border-radius: 8px; text-align: center; border-top: 4px solid #444; transition: all 0.3s ease; position: relative; cursor: pointer; overflow: hidden; }
        .card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.4); }
        .card.online { border-color: var(--green); }
        .card.offline { border-color: var(--red); opacity: 0.6; }

        @keyframes neonGlow { 0% { box-shadow: 0 0 10px rgba(0,230,118,0.3); border-color: #00e676; } 100% { box-shadow: 0 0 25px rgba(0,230,118,0.8), 0 0 15px rgba(0,230,118,0.5); border-color: #55ff99; } }
        .card.glowing { animation: neonGlow 1.2s infinite alternate; z-index: 10; }
        @keyframes neonGlowRed { 0% { box-shadow: 0 0 10px rgba(255,82,82,0.3); border-color: #ff5252; } 100% { box-shadow: 0 0 25px rgba(255,82,82,0.9), 0 0 15px rgba(255,82,82,0.6); border-color: #ff8a80; } }
        .card.glowing-red { animation: neonGlowRed 0.8s infinite alternate; z-index: 10; }

        /* 🚀 2. 완벽한 고정형 레이아웃 교체 (가짜 VU 메터 vs 접속시간) */
        .dynamic-info { height: 16px; margin-top: 8px; display: flex; align-items: center; justify-content: center; width: 100%; }
        
        .volume-meter { width: 90%; height: 6px; background: #111; border-radius: 3px; overflow: hidden; display: none; box-shadow: inset 0 1px 3px rgba(0,0,0,0.8); border: 1px solid #333; margin: 0; }
        .volume-bar { height: 100%; width: 0%; background: linear-gradient(90deg, var(--green) 0%, var(--warn) 70%, var(--red) 100%); border-radius: 3px;}
        
        .time-tag { font-size: 0.7rem; color: #888; font-style: italic; margin: 0; display: block;}

        @keyframes fakeVuAnim {
            0% { width: 15%; } 15% { width: 60%; } 30% { width: 35%; } 45% { width: 85%; } 
            60% { width: 40%; } 75% { width: 95%; } 90% { width: 50%; } 100% { width: 75%; }
        }
        
        /* TX 상태(glowing-red)일 때 접속시간 숨기고 메터 표시 (높이 변화 0px) */
        .glowing-red .volume-meter { display: block; }
        .glowing-red .time-tag { display: none; }
        .glowing-red .volume-bar { animation: fakeVuAnim 0.7s infinite alternate ease-in-out; }

        .c-tag { font-weight: bold; font-size: 1.1rem; letter-spacing: 0px; margin-bottom: 5px; white-space: nowrap;}
        .d-tag { font-size: 0.8rem; color: var(--sub); }
        .status-badge { display: inline-block; margin-top: 6px; padding: 3px 8px; border-radius: 8px; font-size: 0.7em; font-weight: bold; }
        .online .status-badge { background: rgba(0, 230, 118, 0.15); color: var(--green); }
        .offline .status-badge { background: rgba(255, 82, 82, 0.15); color: var(--red); }

        .tg-badge { display: inline-block; padding: 3px 6px; border-radius: 5px; font-weight: 900; font-size: 0.85em; letter-spacing: 0.5px; white-space: nowrap; }

        .toast-popup { position: fixed; top: 20px; right: 20px; background-color: var(--green); color: #000; padding: 15px 25px; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.5); font-weight: bold; font-size: 1.1rem; transform: translateX(150%); transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); z-index: 9999;}
        .toast-popup.show { transform: translateX(0); }

        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(5px); display: flex; justify-content: center; align-items: center; z-index: 10000; }
        .modal-content { background: var(--card); padding: 25px; border-radius: 12px; width: 90%; max-width: 400px; border-top: 4px solid var(--green); box-shadow: 0 10px 30px rgba(0,0,0,0.8); }
        .modal-content h3 { margin-top: 0; color: var(--text); border-bottom: 1px solid #333; padding-bottom: 10px; text-align: center;}
        .history-list { list-style: none; padding: 0; margin: 20px 0; max-height: 300px; overflow-y: auto;}
        .history-list li { padding: 10px; border-bottom: 1px solid #333; display: flex; align-items: center; font-size: 0.95rem;}
        .history-list li:last-child { border-bottom: none; }
        .hist-num { display: inline-block; background: #333; color: var(--green); width: 24px; height: 24px; text-align: center; line-height: 24px; border-radius: 50%; font-weight: bold; margin-right: 15px; font-size: 0.8rem;}
        .close-btn { background: #ff5252; color: white; border: none; padding: 10px; width: 100%; border-radius: 6px; font-weight: bold; font-size: 1rem; cursor: pointer; transition: 0.3s;}
        .close-btn:hover { background: #ff8a80; }

        .iframe-box { background: var(--card); padding: 15px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.5); box-sizing: border-box; display: flex; flex-direction: column; flex: 1;}
        .iframe-box h2 { margin-top: 0; font-size: 1.3rem; border-bottom: 1px solid #333; padding-bottom: 8px; margin-bottom: 15px;}
        .iframe-wrap { position: relative; width: 100%; flex: 1; min-height: 500px; overflow: hidden; border-radius: 8px; background: #fff; }
        .iframe-wrap iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; }

        /* 🚀 BM 상태 카드 UI 스타일 */
        .bm-card { background: var(--card); padding: 20px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.5); border: 1px solid #333; display: flex; flex-direction: column; align-items: stretch; gap: 15px; }
        .bm-card-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; padding-bottom: 15px; }
        .bm-card-title { margin: 0; color: var(--green); font-size: 1.2rem; display: flex; align-items: center; gap: 8px; }
        .server-select { background: #111; color: #fff; border: 1px solid #555; padding: 8px 12px; border-radius: 6px; outline: none; font-size: 0.95rem; cursor: pointer; font-family: 'Segoe UI', sans-serif;}
        .server-select:focus { border-color: var(--blue); }
        
        .bm-status-row { display: flex; justify-content: space-between; align-items: center; width: 100%; }
        .traffic-light { width: 20px; height: 20px; border-radius: 50%; background-color: #333; box-shadow: inset 0 0 5px rgba(0,0,0,0.8); border: 2px solid #555; transition: all 0.3s ease; }
        .ping-value { font-family: monospace; font-size: 1.4rem; color: #fff; font-weight: bold; text-shadow: 0 0 5px rgba(255,255,255,0.3); }
        .bm-ping-label { font-size: 0.75rem; font-weight: bold; letter-spacing: 1px; color: var(--sub); }
        .bm-ping-time { font-family: monospace; font-size: 1.05rem; background: #111; padding: 5px 12px; border-radius: 6px; border: 1px solid #444; color: #fff; display: inline-block; margin-top: 4px;}

        .footer-container { margin-top: 20px; padding-top: 15px; border-top: 1px dashed #333; text-align: center; color: #888; font-size: 0.85rem; line-height: 1.6; padding-bottom: 10px; }
        .footer-container strong { color: #ccc; letter-spacing: 1px; }
        .footer-contact { display: inline-block; background: rgba(255, 177, 66, 0.1); border: 1px solid rgba(255, 177, 66, 0.3); color: var(--warn); padding: 4px 12px; border-radius: 20px; font-weight: bold; margin-top: 6px; font-size: 0.8rem; letter-spacing: 0.5px;}
        .footer-credit { margin-top: 10px; font-size: 0.7rem; color: #555; }

        @media (max-width: 1200px) {
            .main-wrapper { flex-direction: column; }
            .iframe-wrap { height: 500px; min-height: 500px; }
            .iframe-wrap iframe { width: 125%; height: 125%; transform: scale(0.8); transform-origin: 0 0; }
        }

        /* 🚀 모바일 반응형 완벽 보완 */
        @media (max-width: 768px) {
            .header-container { flex-direction: column; align-items: center; padding-bottom: 15px;}
            .header-container h1 { font-size: 1.35rem; width: 100%; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 5px;}
            .header-buttons { justify-content: center; width: 100%; gap: 8px;}
            .control-btn { flex: 1; min-width: 45%; font-size: 0.85rem; padding: 10px 5px;}
            .clock-container { flex-direction: column; }
            .clock-time { font-size: 2.2rem; }
            .iframe-wrap { height: 400px; min-height: 400px; }
            .iframe-wrap iframe { width: 200%; height: 200%; transform: scale(0.5); transform-origin: 0 0; }
            
            .bm-card-header { flex-direction: column; gap: 12px; align-items: flex-start; }
            .server-select { width: 100%; }
            
            /* 💡 핵심: 모바일에서도 가로 나란히(Row) 유지 및 글자 크기 축소 */
            .bm-status-row { flex-direction: row !important; justify-content: space-between !important; align-items: center !important; gap: 5px; }
            #bm-status-text { font-size: 0.95rem !important; }
            .ping-value { font-size: 1.1rem !important; }
            .bm-ping-time { font-size: 0.85rem !important; padding: 4px 8px !important; }
            .bm-ping-label { font-size: 0.65rem; margin-bottom: 2px; }
            .traffic-light { width: 16px !important; height: 16px !important; }
            
            .grid { grid-template-columns: repeat(auto-fill, minmax(115px, 1fr)); }
            .c-tag { font-size: 1.05rem; }
            .footer-container { font-size: 0.7rem; padding-bottom: 20px; overflow: hidden;}
            .footer-container div { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .footer-contact { font-size: 0.65rem; padding: 3px 8px; margin-top: 5px; letter-spacing: -0.3px; }
            .footer-credit { font-size: 0.55rem; margin-top: 6px; letter-spacing: -0.5px; }
        }
    </style>
</head>
<body>
    <div class="header-container">
        <h1>📻 <?php echo htmlspecialchars($sys_callsign); ?> DVS 실시간 대시보드</h1>
        <div class="header-buttons">
            <button id="sound-toggle" class="control-btn btn-sound" onclick="toggleSound()">🔕 소리 켜기</button>
            <a href="./admin.php" class="control-btn btn-admin">👥 접속자 관리</a>
            <a href="./TG_notice.php" class="control-btn btn-tg">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" style="margin-right: 6px;">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.18-.08-.05-.19-.02-.27 0-.12.03-1.99 1.25-5.61 3.7-.53.36-1.01.54-1.44.53-.47-.01-1.38-.27-2.06-.49-.83-.27-1.49-.41-1.43-.87.03-.24.34-.49.92-.75 3.6-1.57 6.01-2.61 7.23-3.12 3.44-1.43 4.16-1.68 4.63-1.69.1 0 .32.02.46.13.12.09.15.22.16.33-.02.04-.02.16-.03.22z"/>
                </svg>TG알리미
            </a>
            <a href="./usercontrol.php" class="control-btn btn-info">⚙️ 사용자제어판</a>

            <?php if (!$is_local): ?>
                <a href="?dash_logout=1" class="control-btn btn-logout">🔓 로그아웃</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="main-wrapper">
        <div class="left-column">

            <div class="sys-row">
                <div class="sys-card">
                    <div class="sys-header"><span>🖥️ CPU 사용량</span> <span id="v-cpu" class="sys-val">0%</span></div>
                    <div class="bar-bg">
                        <div id="b-cpu" class="bar-fill"></div>
                        <div id="p-cpu" class="peak-mark"></div>
                    </div>
                    <div class="sys-details">
                        <div style="color: #aaa;">Load 1m:<span id="sys-load1">0.00</span></div>
                        <div style="color: #aaa;">5m:<span id="sys-load5">0.00</span></div>
                    </div>
                </div>
                <div class="sys-card">
                    <div class="sys-header"><span>🧠 메모리 (RAM)</span> <span id="v-mem" class="sys-val">0%</span></div>
                    <div class="bar-bg">
                        <div id="b-mem" class="bar-fill"></div>
                        <div id="p-mem" class="peak-mark"></div>
                    </div>
                    <div class="sys-details">
                        <div style="color: #ffb142;">Used:<span id="sys-mem-used">0</span> MB</div>
                        <div style="color: #00e676;">Total:<span id="sys-mem-total">0</span> MB</div>
                    </div>
                </div>
                <div class="sys-card">
                    <div class="sys-header"><span>🌐 네트워크 (Traffic)</span> <span id="v-net" class="sys-val">0%</span></div>
                    <div class="bar-bg">
                        <div id="b-net" class="bar-fill"></div>
                        <div id="p-net" class="peak-mark"></div>
                    </div>
                    <div class="sys-details">
                        <div style="color: #ffb142;">⬆️ TX:<span id="net-tx">0</span> Kbps</div>
                        <div style="color: #00e676;">⬇️ RX:<span id="net-rx">0</span> Kbps</div>
                    </div>
                </div>
                <div class="sys-card">
                    <div class="sys-header"><span>⏱️ 시스템 가동 (Uptime)</span></div>
                    <div style="display:flex; flex-direction:column; justify-content:center; align-items:center; flex:1; padding-top: 5px;">
                        <div id="v-uptime" class="sys-val" style="color: var(--green); font-size: 1.25rem;">0일 00시간 00분</div>
                        <div style="margin-top: 10px; font-size: 0.85rem; color: var(--sub); font-weight: bold; letter-spacing: 0.5px;">
                            자동 재부팅: <span id="v-reboot" style="color: #fff; font-family: monospace; font-size: 0.95rem; margin-left: 3px;">0일 00:00:00</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="clock-container">
                <div class="clock-box">
                    <div class="clock-label">KST (Seoul)</div>
                    <div class="clock-time" id="time-kst">00:00:00</div>
                </div>
                <div class="clock-box">
                    <div class="clock-label">UTC (Standard)</div>
                    <div class="clock-time" id="time-utc">00:00:00</div>
                </div>
            </div>

            <div class="user-container">
                <div class="user-section">
                    <h2 style="color:var(--green)">🟢 ON-AIR (<span id="count-on">0</span>)</h2>
                    <div id="grid-on" class="grid"></div>
                </div>
                <div class="user-section">
                    <h2 style="color:var(--red)">🔴 OFFLINE (<span id="count-off">0</span>)</h2>
                    <div id="grid-off" class="grid"></div>
                </div>
            </div>

        </div>

        <div class="right-column">
            
            <div class="bm-card">
                <div class="bm-card-header">
                    <h3 class="bm-card-title">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                        </svg>
                        BM Server Ping Test
                    </h3>
                    
                    <select id="bm-server-select" class="server-select" onchange="forcePingUpdate()">
                        <option value="4501.master.brandmeister.network" selected>🇰🇷 한국 (Korea 4501)</option>
                        <option value="4401.master.brandmeister.network">🇬🇧 영국 (UK 4401)</option>
                        <option value="3102.master.brandmeister.network">🇺🇸 미국 동부 (US East 3102)</option>
                        <option value="3103.master.brandmeister.network">🇺🇸 미국 서부 (US West 3103)</option>
                        <option value="5051.master.brandmeister.network">🇦🇺 호주 (Australia 5051)</option>
                        <option value="4408.master.brandmeister.network">🇯🇵 일본 (Japan 4408)</option>
                        <option value="2621.master.brandmeister.network">🇩🇪 독일 (Germany 2621)</option>
                    </select>
                </div>

                <div class="bm-status-row">
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div id="traffic-light" class="traffic-light"></div>
                            <span id="bm-status-text" style="font-weight: bold; font-size: 1.1rem; color: #aaa;">상태 확인 중...</span>
                        </div>
                        <div style="color: var(--sub); font-size: 0.9rem;">
                            지연 시간: <span id="bm-ping-val" class="ping-value">--</span> <span style="font-size: 0.8rem;">ms</span>
                        </div>
                    </div>

                    <div style="text-align: right;">
                        <div class="bm-ping-label">업데이트 시간</div>
                        <div id="bm-last-check" class="bm-ping-time">--:--:--</div>
                    </div>
                </div>
            </div>

            <div class="iframe-box">
                <h2>🌐 BM450 Monitoring</h2>
                <div class="iframe-wrap">
                    <iframe src="http://lhmon.duckdns.org/index.php"></iframe>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-container">
        <div>Copyright &copy; <?php echo date('Y'); ?> <strong><?php echo htmlspecialchars($sys_callsign); ?> (SeJung Kim)</strong>. All rights reserved.</div>
        <div class="footer-contact">🚨 시스템 장애 발생 시 연락처 : 0502-1943-8289</div>
        <div class="footer-credit">Dashboard Version 1.2 (Global Ping Tester added) | Powered by DVSwitch & Analog_Bridge</div>
    </div>

    <div id="historyModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <h3 id="modalTitle">접속 이력</h3>
            <ul id="modalList" class="history-list"></ul>
            <button class="close-btn" onclick="closeModal()">닫기</button>
        </div>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            const kstOptions = { timeZone: 'Asia/Seoul', hour12: false, hour: '2-digit', minute:'2-digit', second:'2-digit' };
            document.getElementById('time-kst').innerText = now.toLocaleTimeString('en-US', kstOptions);
            const utcOptions = { timeZone: 'UTC', hour12: false, hour: '2-digit', minute:'2-digit', second:'2-digit' };
            document.getElementById('time-utc').innerText = now.toLocaleTimeString('en-US', utcOptions);
        }
        setInterval(updateClock, 1000);
        updateClock();

        function updateRebootCountdown() {
            const nowString = new Date().toLocaleString("en-US", {timeZone: "Asia/Seoul"});
            const nowKST = new Date(nowString);
            let nextSunday = new Date(nowKST);
            nextSunday.setDate(nowKST.getDate() + (7 - nowKST.getDay()) % 7);
            nextSunday.setHours(3, 0, 0, 0);

            if (nowKST.getDay() === 0 && nowKST.getHours() >= 3) {
                nextSunday.setDate(nowKST.getDate() + 7);
            } else if (nowKST.getDay() === 0 && nowKST.getHours() < 3) {
                nextSunday.setDate(nowKST.getDate());
            }

            const diff = nextSunday - nowKST;
            const d = Math.floor(diff / (1000 * 60 * 60 * 24));
            const h = Math.floor((diff / (1000 * 60 * 60)) % 24);
            const m = Math.floor((diff / 1000 / 60) % 60);
            const s = Math.floor((diff / 1000) % 60);

            document.getElementById('v-reboot').innerText = `${d}일 ${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        }
        setInterval(updateRebootCountdown, 1000);
        updateRebootCountdown();

        let wakeLock = null;
        async function requestWakeLock() {
            try {
                if ('wakeLock' in navigator) {
                    wakeLock = await navigator.wakeLock.request('screen');
                    wakeLock.addEventListener('release', () => {});
                }
            } catch (err) {}
        }
        document.addEventListener('visibilitychange', async () => {
            if (wakeLock !== null && document.visibilityState === 'visible') { requestWakeLock(); }
        });

        let audioCtx = null;
        let soundEnabled = false;

        function toggleSound() {
            if (!audioCtx) { audioCtx = new (window.AudioContext || window.webkitAudioContext)(); }
            if (audioCtx.state === 'suspended') { audioCtx.resume(); }

            soundEnabled = !soundEnabled;
            const btn = document.getElementById('sound-toggle');

            if (soundEnabled) {
                btn.innerHTML = '🔊 알림 켜짐';
                btn.style.color = '#00e5ff';
                btn.style.backgroundColor = '#111';
                btn.style.borderColor = '#00e5ff';
                btn.style.boxShadow = '0 0 10px rgba(0, 229, 255, 0.4)';
                playDingDong();
            } else {
                btn.innerHTML = '🔕 소리 켜기';
                btn.style.color = 'var(--warn)';
                btn.style.backgroundColor = '#333';
                btn.style.borderColor = 'var(--warn)';
                btn.style.boxShadow = '0 4px 6px rgba(0,0,0,0.4)';
            }
            if (!wakeLock) requestWakeLock();
        }

        function playDingDong() {
            if (!soundEnabled) return;
            try {
                if (!audioCtx) return;
                const osc = audioCtx.createOscillator();
                const gainNode = audioCtx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, audioCtx.currentTime);
                osc.frequency.setValueAtTime(659.25, audioCtx.currentTime + 0.4);
                gainNode.gain.setValueAtTime(0.7, audioCtx.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 1.8);
                osc.connect(gainNode);
                gainNode.connect(audioCtx.destination);
                osc.start();
                osc.stop(audioCtx.currentTime + 1.8);
            } catch(e) {}
        }

        function showToast(msg) {
            const toast = document.createElement('div');
            toast.className = 'toast-popup';
            toast.innerText = '🔔 ' + msg;
            document.body.appendChild(toast);
            setTimeout(() => toast.classList.add('show'), 10);
            setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 500); }, 4000);
        }

        function showModal(callsign, historyArray) {
            document.getElementById('modalTitle').innerText = '📡 ' + callsign + ' 최근 접속 이력';
            const list = document.getElementById('modalList');
            list.innerHTML = '';
            if (!historyArray || historyArray.length === 0) {
                list.innerHTML = '<li style="justify-content:center; color:#888;">기록이 없습니다.</li>';
            } else {
                historyArray.slice(0, 5).forEach((time, index) => {
                    list.innerHTML += `<li><span class="hist-num">${index+1}</span> ${time}</li>`;
                });
            }
            document.getElementById('historyModal').style.display = 'flex';
        }

        function closeModal() { document.getElementById('historyModal').style.display = 'none'; }
        document.getElementById('historyModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });

        let prevStatusMap = {};
        let isFirstLoad = true;
        let peaks = { cpu: 0, mem: 0, net: 0 };
        let tg4000Timers = {};
        
        function forcePingUpdate() {
            document.getElementById('bm-status-text').innerText = '측정 중...';
            document.getElementById('bm-status-text').style.color = '#aaa';
            document.getElementById('traffic-light').style.backgroundColor = '#333';
            document.getElementById('traffic-light').style.borderColor = '#555';
            document.getElementById('traffic-light').style.boxShadow = 'none';
            document.getElementById('bm-ping-val').innerText = '--';
            document.getElementById('bm-ping-val').style.color = '#fff';
            update();
        }

        function updateBar(id, val) {
            document.getElementById('v-'+id).innerText = val + '%';
            const bar = document.getElementById('b-'+id);
            const peakMark = document.getElementById('p-'+id);
            bar.style.width = val + '%';
            bar.style.backgroundColor = val >= 85 ? 'var(--red)' : (val >= 60 ? 'var(--warn)' : 'var(--green)');
            if (val > peaks[id]) { peaks[id] = val; } else { peaks[id] = Math.max(val, peaks[id] - 2.5); }
            if(peakMark) { peakMark.style.left = peaks[id] > 0 ? `calc(${peaks[id]}% - 2px)` : '0%'; }
        }

        function update() {
            const selectedHost = document.getElementById('bm-server-select').value;
            fetch('?ajax=1&bm_host=' + selectedHost).then(r => r.json()).then(data => {
                updateBar('cpu', data.stats.cpu);
                updateBar('mem', data.stats.mem);
                updateBar('net', data.stats.net_pct);

                document.getElementById('sys-load1').innerText = parseFloat(data.stats.load1).toFixed(2);
                document.getElementById('sys-load5').innerText = parseFloat(data.stats.load5).toFixed(2);
                document.getElementById('sys-mem-used').innerText = data.stats.mem_used;
                document.getElementById('sys-mem-total').innerText = data.stats.mem_total;
                document.getElementById('net-rx').innerText = Number(data.stats.net_rx).toLocaleString();
                document.getElementById('net-tx').innerText = Number(data.stats.net_tx).toLocaleString();
                document.getElementById('v-uptime').innerText = data.stats.uptime;

                if (data.bm_status) {
                    const statusText = document.getElementById('bm-status-text');
                    const trafficLight = document.getElementById('traffic-light');
                    const pingVal = document.getElementById('bm-ping-val');
                    
                    statusText.innerText = data.bm_status.status;
                    statusText.style.color = data.bm_status.color;
                    pingVal.innerText = data.bm_status.ping;
                    pingVal.style.color = data.bm_status.color;
                    
                    trafficLight.style.backgroundColor = data.bm_status.color;
                    trafficLight.style.borderColor = data.bm_status.color;
                    
                    if(data.bm_status.quality === 'offline') {
                        trafficLight.style.boxShadow = 'none';
                    } else {
                        trafficLight.style.boxShadow = `0 0 15px ${data.bm_status.color}88`;
                    }
                    
                    document.getElementById('bm-last-check').innerText = data.bm_status.time;
                }

                data.users.forEach(u => {
                    if (u.status === 'online' && u.active_tg === '4000') {
                        if (!tg4000Timers[u.full_call]) {
                            tg4000Timers[u.full_call] = Date.now();
                        } else if (Date.now() - tg4000Timers[u.full_call] >= 300000) { 
                            u.status = 'offline';
                        }
                    } else {
                        delete tg4000Timers[u.full_call];
                    }
                });

                const gOn = document.getElementById('grid-on'), gOff = document.getElementById('grid-off');
                gOn.innerHTML = ''; gOff.innerHTML = '';
                let cOn = 0, cOff = 0;

                data.users.sort((a, b) => b.last_seen_ts - a.last_seen_ts);

                data.users.forEach(u => {
                    if (!isFirstLoad) {
                        if (prevStatusMap[u.full_call] !== 'online' && u.status === 'online') {
                            showToast(u.full_call + " 님이 접속했습니다!");
                            playDingDong();
                        }
                    }
                    prevStatusMap[u.full_call] = u.status;

                    const div = document.createElement('div');
                    let bText = u.status === 'online' ? 'CONNECTED' : 'OFFLINE';
                    let bClass = u.status === 'online' ? 'online' : 'offline';
                    let bStyle = '';
                    let glowClass = '';
                    let badges = [];
                    let badgeArea = '';

                    if (u.status === 'online') {
                        if (u.is_ptt) {
                            glowClass = 'glowing-red';
                            badges.push(`<div class="tg-badge" style="background:var(--red); color:white; box-shadow: 0 0 8px var(--red); margin-top:0; min-width:70px;">🎤 TX</div>`);
                        } else {
                            if (u.active_tg === '4000') {
                                glowClass = '';
                                badges.push(`<div class="tg-badge" style="background:#444; color:#bbb; box-shadow: inset 0 0 5px rgba(0,0,0,0.8); border: 1px solid #666; margin-top:0; min-width:70px;">🔇 MUTE</div>`);
                            } else {
                                if (u.active_tg !== '') glowClass = 'glowing';
                                badges.push(`<div class="tg-badge" style="background:#333; color:#aaa; box-shadow: inset 0 0 5px rgba(0,0,0,0.5); border: 1px solid #555; margin-top:0; min-width:70px;">🎧 RX</div>`);
                            }
                        }

                        if (u.active_tg === '4000') {
                            badges.push(`<div class="tg-badge" style="background:var(--warn); color:#000; box-shadow: 0 0 8px var(--warn); margin-top:0; min-width:70px;">Unlink</div>`);
                        } else if (u.active_tg !== '') {
                            badges.push(`<div class="tg-badge" style="background:var(--green); color:#000; box-shadow: 0 0 8px var(--green); margin-top:0; min-width:70px;">TG ${u.active_tg}</div>`);
                        } else {
                            badges.push(`<div class="tg-badge" style="background:#333; color:#aaa; box-shadow: inset 0 0 5px rgba(0,0,0,0.5); border: 1px solid #555; margin-top:0; min-width:70px;">TG ---</div>`);
                        }

                        badgeArea = `<div style="height: 52px; display:flex; flex-direction:column; gap:4px; align-items:center; margin-top:8px;">` + badges.join('') + `</div>`;
                    } else {
                        badgeArea = ``;
                    }

                    if (u.status === 'permission_error') {
                        bText = 'PERM DENIED';
                        bStyle = 'color:var(--warn); background:rgba(255,177,66,0.15);';
                        div.style.borderColor = 'var(--warn)';
                    }

                    div.className = `card ${bClass} ${glowClass}`;
                    div.addEventListener('click', function() { showModal(u.full_call, u.history || []); });

                    // 🚀 레이아웃 고정: dynamic-info 영역 안에 레벨바와 시간 태그를 같이 배치
                    div.innerHTML = `
                        <div class="c-tag">${u.full_call}</div>
                        <div class="d-tag">ID: ${u.dmrid}</div>
                        ${badgeArea}
                        <div class="dynamic-info">
                            <div class="volume-meter"><div class="volume-bar"></div></div>
                            <div class="time-tag">접속: ${u.last_seen}</div>
                        </div>
                        <div class="status-badge" style="${bStyle}">${bText}</div>
                    `;

                    if(u.status === 'online') { gOn.appendChild(div); cOn++; }
                    else { gOff.appendChild(div); cOff++; }
                });

                document.getElementById('count-on').innerText = cOn;
                document.getElementById('count-off').innerText = cOff;
                isFirstLoad = false;
            }).catch(e => console.error('Data Fetch Error:', e));
        }

        update();
        setInterval(update, 3000);
    </script>
</body>
</html>