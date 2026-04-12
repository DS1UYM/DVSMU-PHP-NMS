<?php
// 💡 서버 기본 시간 한국 표준시(KST) 고정
date_default_timezone_set('Asia/Seoul');
session_start();

$admin_password = 'admin'; // 사용자 설정 관리자 비밀번호
$users_file = '/var/log/webaccess/dvs_users.json';

// 1. 보안 및 로그아웃
if (isset($_GET['logout'])) { 
    session_destroy(); 
    header("Location: usercontrol.php"); 
    exit; 
}

$error_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_pw'])) {
    if ($_POST['login_pw'] === $admin_password) { 
        $_SESSION['is_admin'] = true; 
        header("Location: usercontrol.php"); 
        exit; 
    } else {
        $error_msg = '비밀번호가 일치하지 않습니다.';
    }
}

// =========================================================================
// 🚀 로그인 화면 레이아웃 (테마 통일)
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

// =========================================================================
// 🚀 2. 백엔드 API: TG 튜닝 명령 실행 로직 (dvswitch.sh 활용)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tune') {
    header('Content-Type: application/json');
    $bridge_num = (int)$_POST['bridge'];
    $target_tg = preg_replace('/[^0-9]/', '', $_POST['tg']); // 숫자만 추출하여 보안 강화

    if ($target_tg !== '') {
        // 브릿지 번호에 따른 경로 매핑 (0 = MMDVM_Bridge, 1 = user01 ...)
        if ($bridge_num === 0) {
            $script_path = '/opt/MMDVM_Bridge/dvswitch.sh';
        } else {
            $script_path = '/opt/user' . str_pad($bridge_num, 2, "0", STR_PAD_LEFT) . '/dvswitch.sh';
        }

        if (file_exists($script_path)) {
            $cmd = escapeshellarg($script_path) . " tune " . escapeshellarg($target_tg);
            shell_exec($cmd);
            echo json_encode(['success' => true, 'message' => "TG {$target_tg} 변경 명령 전송 완료"]);
        } else {
            echo json_encode(['success' => false, 'message' => "제어 스크립트를 찾을 수 없습니다: {$script_path}"]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => "유효하지 않은 TG 번호입니다."]);
    }
    exit;
}

// =========================================================================
// 🚀 3. 백엔드 API: 실시간 상태 모니터링 데이터 제공
// =========================================================================
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    $users = json_decode(@file_get_contents($users_file), true) ?: [];
    $timeout_seconds = 900;
    $now_utc = time();

    foreach ($users as &$user) {
        $ukey = ($user['callsign'] ?? '') . ($user['suffix'] ?? '');
        $status = 'offline';
        $active_tg = '';
        $is_ptt = false;

        $bridge_num = (int)($user['bridge'] ?? 0);
        $log_path = ($bridge_num === 0)
                    ? "/var/log/dvswitch/Analog_Bridge.log"
                    : "/var/log/dvswitch/user" . str_pad($bridge_num, 2, "0", STR_PAD_LEFT) . "/Analog_Bridge.log";

        // pcmPort 추출 (Audio 모니터링 용도)
        $ini_path = ($bridge_num === 0) 
                    ? "/opt/Analog_Bridge/Analog_Bridge.ini" 
                    : "/opt/user" . str_pad($bridge_num, 2, "0", STR_PAD_LEFT) . "/Analog_Bridge.ini";
        
        $pcm_port = 2222; // 기본값
        if (file_exists($ini_path)) {
            $ini_content = file_get_contents($ini_path);
            if (preg_match('/^pcmPort\s*=\s*(\d+)/im', $ini_content, $m)) {
                $pcm_port = trim($m[1]);
            }
        }

        if (file_exists($log_path) && is_readable($log_path)) {
            $tail = shell_exec("tail -n 150 " . escapeshellarg($log_path) . " 2>/dev/null");
            if ($tail) {
                $lines = array_reverse(explode("\n", trim($tail)));
                $found_status = false; $found_tg = false; $found_ptt = false;

                foreach ($lines as $line) {
                    if (strpos($line, "I:") !== 0 && strpos($line, "M:") !== 0) continue;
                    if (!preg_match('/^[IM]:\s+(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})/', $line, $time_matches)) continue;

                    $dt_log = new DateTime($time_matches[1], new DateTimeZone('UTC'));
                    $time_diff = $now_utc - $dt_log->getTimestamp();
                    $is_recent = ($time_diff >= -60 && $time_diff <= $timeout_seconds);

                    if (!$found_ptt && preg_match('/PTT (on|off)/', $line, $m)) {
                        if ($is_recent && $m[1] === 'on') { $is_ptt = true; }
                        $found_ptt = true;
                        if (!$found_status) { $status = $is_recent ? 'online' : 'offline'; $found_status = true; }
                    }

                    if (!$found_tg && preg_match('/txTg=:\s*(\d+)/i', $line, $m)) {
                        if ($is_recent) { $active_tg = $m[1]; }
                        $found_tg = true;
                        if (!$found_status) { $status = $is_recent ? 'online' : 'offline'; $found_status = true; }
                    }

                    if (!$found_status) {
                        if (strpos($line, 'USRP unregister client') !== false) {
                            $status = 'offline';
                            $found_status = true;
                        } else {
                            $log_call_match = $user['callsign'] ?? '';
                            $log_id_match = ($user['dmrid'] ?? '') . ($user['rpt'] ?? '');
                            if (preg_match('/USRP_TYPE_TEXT \(' . preg_quote($log_call_match, '/') . '\) -> ' . preg_quote($log_id_match, '/') . '/i', $line)) {
                                $status = $is_recent ? 'online' : 'offline';
                                $found_status = true;
                            }
                        }
                    }
                    if ($found_status && $found_tg && $found_ptt) break;
                }
            }
        }

        $user['full_call'] = $ukey;
        $user['status'] = $status;
        $user['active_tg'] = $status === 'online' ? $active_tg : '';
        $user['is_ptt'] = $is_ptt;
        $user['pcm_port'] = $pcm_port; // 오디오 모니터링 포트
    }
    
    echo json_encode(['users' => $users]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>🎛️ 사용자 제어 센터</title>
    
    <script type="text/javascript" src="scripts/jquery.min.js"></script>
    <script type="text/javascript" src="scripts/functions.js"></script>
    <script type="text/javascript" src="scripts/pcm-player.min.js"></script>

    <style>
        * { box-sizing: border-box; }
        :root { --bg: #121212; --card: #1e1e1e; --text: #eee; --sub: #aaa; --green: #00e676; --red: #ff5252; --warn: #ffb142; --blue: #4da6ff;}
        body { background: var(--bg); color: var(--text); font-family: 'Malgun Gothic', Tahoma, sans-serif; margin: 0; padding: 15px; overflow-x: hidden;}

        .header-container { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto 20px; border-bottom: 2px solid #333; padding-bottom: 15px; }
        .header-container h1 { margin: 0; font-size: 1.6rem; color: var(--blue); display: flex; align-items: center; gap: 10px; }
        
        .left-btns { display: flex; gap: 8px; flex-shrink: 0; }
        .back-btn, .logout-btn { background-color: #333; color: #fff; text-decoration: none; padding: 10px 16px; border-radius: 8px; font-size: 0.95rem; font-weight: bold; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; border: 1px solid #444; white-space: nowrap;}
        .logout-btn { color: var(--red); }
        .logout-btn:hover { background-color: var(--red); color: #fff; }
        .back-btn:hover { transform: translateY(-2px); }

        .list-container { max-width: 1200px; margin: 0 auto; display: flex; flex-direction: column; gap: 12px; }

        .user-row { background: #2a2a2a; border-radius: 10px; padding: 15px 20px; display: flex; align-items: center; justify-content: space-between; border-left: 5px solid #444; transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(0,0,0,0.3); flex-wrap: wrap; gap: 15px;}
        .user-row.online { border-color: var(--green); }
        .user-row.offline { border-color: var(--red); opacity: 0.7; }

        @keyframes neonGlow { 0% { box-shadow: 0 0 10px rgba(0,230,118,0.2); border-color: #00e676; } 100% { box-shadow: 0 0 20px rgba(0,230,118,0.6); border-color: #55ff99; } }
        .user-row.glowing { animation: neonGlow 1.2s infinite alternate; }
        
        @keyframes neonGlowRed { 0% { box-shadow: 0 0 10px rgba(255,82,82,0.2); border-color: #ff5252; } 100% { box-shadow: 0 0 20px rgba(255,82,82,0.6); border-color: #ff8a80; } }
        .user-row.glowing-red { animation: neonGlowRed 0.8s infinite alternate; }

        .info-col { display: flex; flex-direction: column; gap: 5px; min-width: 200px; }
        .callsign { font-size: 1.4rem; font-weight: bold; color: #fff; letter-spacing: 1px;}
        .meta-info { font-size: 0.85rem; color: var(--sub); display: flex; gap: 10px;}
        .meta-info span { background: #111; padding: 2px 8px; border-radius: 4px; border: 1px solid #333;}

        .badge-col { display: flex; align-items: center; justify-content: center; min-width: 140px; }
        .tg-badge { padding: 6px 15px; border-radius: 20px; font-weight: 900; font-size: 1rem; letter-spacing: 0.5px; box-shadow: inset 0 0 5px rgba(0,0,0,0.5); text-align: center; width: 100%; white-space: nowrap;}
        .bg-rx { background: #333; color: #aaa; border: 1px solid #555; }
        .bg-tg { background: var(--green); color: #000; box-shadow: 0 0 10px var(--green); }
        .bg-tx { background: var(--red); color: #fff; box-shadow: 0 0 10px var(--red); }
        .bg-unlink { background: var(--warn); color: #000; box-shadow: 0 0 10px var(--warn); }

        .control-col { display: flex; align-items: center; gap: 8px; flex-wrap: wrap;}
        
        .tg-input { background: #111; border: 1px solid #555; color: #fff; padding: 10px; border-radius: 6px; font-size: 1.05rem; width: 110px; text-align: center; outline: none;}
        .tg-input:focus { border-color: var(--blue); box-shadow: 0 0 8px rgba(77,166,255,0.4);}
        
        .btn { padding: 10px 15px; border-radius: 6px; font-weight: bold; cursor: pointer; border: none; font-size: 0.95rem; transition: 0.2s; color: #000; display: flex; align-items: center; justify-content: center; gap: 6px;}
        .btn:hover { transform: translateY(-2px); }
        .btn-tune { background: var(--blue); color: #fff; }
        .btn-tune:hover { box-shadow: 0 0 10px rgba(77,166,255,0.5); }
        .btn-unlink { background: var(--warn); }
        .btn-unlink:hover { box-shadow: 0 0 10px rgba(255,177,66,0.5); }
        .btn-monitor { background: #b388ff; }
        .btn-monitor:hover { box-shadow: 0 0 10px rgba(179,136,255,0.5); }

        .toast { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background: var(--green); color: #000; padding: 12px 25px; border-radius: 30px; font-weight: bold; box-shadow: 0 5px 15px rgba(0,0,0,0.5); opacity: 0; transition: 0.3s; z-index: 9999; pointer-events: none;}

        /* 🚀 모바일 전용 반응형 그리드 레이아웃 */
        @media (max-width: 768px) {
            .header-container { flex-direction: column; gap: 15px; border-bottom: none; padding-bottom: 0; }
            .left-btns { width: 100%; display: flex; gap: 10px; }
            .back-btn, .logout-btn { flex: 1; margin: 0; }
            .header-container h1 { font-size: 1.4rem; text-align: center; width: 100%; justify-content: center; margin-bottom: 10px;}
            
            .user-row { flex-direction: column; align-items: stretch; gap: 12px; padding: 15px;}
            .info-col { flex-direction: row; justify-content: space-between; align-items: center;}
            .meta-info { flex-direction: column; align-items: flex-end; gap: 4px; font-size: 0.75rem;}
            .badge-col { width: 100%; }
            
            /* 모바일 버튼 꽉 차는 그리드 시스템 */
            .control-col { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; width: 100%; }
            .tg-input { grid-column: 1 / 3; width: 100%; font-size: 1.1rem; padding: 12px;} /* 윗줄 통째로 텍스트 입력창 */
            .btn-tune { grid-column: 1 / 2; padding: 12px;}
            .btn-unlink { grid-column: 2 / 3; padding: 12px;}
            .btn-monitor { grid-column: 1 / 3; padding: 14px; font-size: 1.05rem;} /* 아랫줄 통째로 모니터링 버튼 */
        }
    </style>
</head>
<body>

    <div class="header-container">
        <div class="left-btns">
            <a href="dashboard.php" class="back-btn">⬅️ 대시보드 복귀</a>
            <a href="?logout=1" class="logout-btn">🔓 로그아웃</a>
        </div>
        <h1>
            <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
            사용자 통신 제어 센터
        </h1>
    </div>

    <div id="user-list" class="list-container">
        </div>

    <div id="toast" class="toast">✅ 명령이 전송되었습니다.</div>

    <script>
        function showToast(msg, isError = false) {
            const toast = document.getElementById('toast');
            toast.innerText = msg;
            toast.style.background = isError ? 'var(--red)' : 'var(--green)';
            toast.style.color = isError ? '#fff' : '#000';
            toast.style.opacity = '1';
            setTimeout(() => { toast.style.opacity = '0'; }, 3000);
        }

        // 🚀 TG 튜닝 명령 전송 (AJAX)
        function sendTuneCommand(bridgeNum, tgValue) {
            if (!tgValue || isNaN(tgValue)) {
                showToast('❌ 유효한 TG 번호를 입력하세요.', true);
                return;
            }

            const formData = new FormData();
            formData.append('action', 'tune');
            formData.append('bridge', bridgeNum);
            formData.append('tg', tgValue);

            fetch('', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast(`📡 브릿지[${bridgeNum}] -> TG ${tgValue} 전송 완료`);
                        updateStatus(); // 즉시 상태 갱신
                        
                        // 입력창 비우기
                        const inputField = document.getElementById(`tg_input_${bridgeNum}`);
                        if(inputField) inputField.value = '';
                    } else {
                        showToast(`❌ 오류: ${data.message}`, true);
                    }
                })
                .catch(e => showToast('❌ 서버 통신 오류', true));
        }

        let isFirstLoad = true;

        // 🚀 부분 업데이트 (Partial DOM Update) 로직 적용
        function updateStatus() {
            fetch('?ajax=1').then(r => r.json()).then(data => {
                const container = document.getElementById('user-list');

                data.users.sort((a, b) => parseInt(a.bridge) - parseInt(b.bridge));

                data.users.forEach(u => {
                    let glowClass = '';
                    let badgeHtml = '';

                    // 상태 및 애니메이션 배지 처리
                    if (u.status === 'online') {
                        if (u.is_ptt) {
                            glowClass = 'glowing-red';
                            badgeHtml = `<div class="tg-badge bg-tx">🎤 TX ON</div>`;
                        } else {
                            if (u.active_tg === '4000') {
                                badgeHtml = `<div class="tg-badge bg-unlink">🔇 Unlink (4000)</div>`;
                            } else if (u.active_tg !== '') {
                                glowClass = 'glowing';
                                badgeHtml = `<div class="tg-badge bg-tg">🎧 TG ${u.active_tg}</div>`;
                            } else {
                                badgeHtml = `<div class="tg-badge bg-rx">📡 대기중</div>`;
                            }
                        }
                    } else {
                        badgeHtml = `<div class="tg-badge bg-rx" style="opacity:0.5;">💤 OFFLINE</div>`;
                    }

                    const rowId = `user-row-${u.bridge}`;
                    const badgeId = `badge-col-${u.bridge}`;
                    const inputId = `tg_input_${u.bridge}`;
                    
                    let rowElem = document.getElementById(rowId);

                    // 💡 [핵심] 해당 유저 카드가 없으면 최초 1회만 그리기 (입력칸 초기화 방지)
                    if (!rowElem) {
                        const bridgeName = u.bridge === "00" ? "MAIN (0)" : `BR: ${parseInt(u.bridge)}`;
                        
                        let html = `
                            <div id="${rowId}" class="user-row ${u.status} ${glowClass}">
                                <div class="info-col">
                                    <div class="callsign">${u.full_call || 'N/A'}</div>
                                    <div class="meta-info">
                                        <span>${bridgeName}</span>
                                        <span>ID: ${u.dmrid || '없음'}</span>
                                    </div>
                                </div>
                                
                                <div id="${badgeId}" class="badge-col">
                                    ${badgeHtml}
                                </div>
                                
                                <div class="control-col">
                                    <input type="number" id="${inputId}" class="tg-input" placeholder="TG 번호">
                                    
                                    <button class="btn btn-tune" onclick="sendTuneCommand('${u.bridge}', document.getElementById('${inputId}').value)">
                                        🔄 이동
                                    </button>
                                    
                                    <button class="btn btn-unlink" onclick="sendTuneCommand('${u.bridge}', '4000')">
                                        ✂️ Unlink
                                    </button>
                                    
                                    <button class="btn btn-monitor" onclick="playAudioToggle(${u.pcm_port}, this)">
                                        🔈 RX 듣기
                                    </button>
                                </div>
                            </div>
                        `;
                        container.insertAdjacentHTML('beforeend', html);
                    } else {
                        // 💡 이미 카드가 존재하면 전체를 다시 그리지 않고 '테두리 색상'과 '뱃지 텍스트'만 교체
                        rowElem.className = `user-row ${u.status} ${glowClass}`;
                        document.getElementById(badgeId).innerHTML = badgeHtml;
                    }
                });

                // 처음 로딩 시 들어있던 '서버 상태를 불러오는 중입니다...' 텍스트 제거
                if (isFirstLoad) {
                    const loadingText = container.querySelector('div[style*="text-align:center"]');
                    if (loadingText) loadingText.remove();
                    isFirstLoad = false;
                }

            }).catch(e => console.error('Status Fetch Error:', e));
        }

        // 초기 로드 및 3초 주기 자동 갱신
        updateStatus();
        setInterval(updateStatus, 3000);
    </script>
</body>
</html>
