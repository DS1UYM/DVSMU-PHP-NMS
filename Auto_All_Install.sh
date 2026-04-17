#!/bin/bash
# DVSMU-PHP-NMS Auto Installer
# By @DS1UYM

RED='\e[0;31m'
NOC='\e[0m'

# 에러 발생 시 즉시 중단 및 위치 출력
set -Eeuo pipefail
trap 'echo -e "${RED}Error${NOC}: failed at line $LINENO: $BASH_COMMAND"; exit 1' ERR

# 관리자(root) 권한 확인
if [ "$EUID" -ne 0 ]; then
  echo -e "\n${RED}Error${NOC}: 이 스크립트는 sudo 권한으로 실행해야 합니다."
  exit 1
fi

WORK_DIR="/tmp"
cd "$WORK_DIR" || exit 1

# =====================================================================
# 사용자님의 깃허브 저장소 raw 파일 기본 경로 (main 브랜치 기준)
GITHUB_RAW_BASE="https://raw.githubusercontent.com/DS1UYM/DVSMU-PHP-NMS/main"
# =====================================================================

echo "================================================="
echo " DVSMU & PHP-NMS Automated Installer (DS1UYM) "
echo "================================================="

# ---------------------------------------------------------------------
# [사전 작업] 재부팅 후 실행될 Step 3 (NMS 설치) 예약 등록
# ---------------------------------------------------------------------
if [ ! -f "/root/.dvsmu_step3_done" ]; then
    echo "Configuring auto-resume for Step 3 after reboot..."

    cat << 'EOF' > /root/resume_step3.sh
#!/bin/bash
exec > /root/step3_install.log 2>&1

echo "--- Step 3 Installation Log: $(date) ---"

# 1. 네트워크 연결 대기 로직 (최대 30초)
echo "Waiting for internet connection..."
MAX_RETRIES=15
RETRY_COUNT=0
until ping -c 1 -W 2 google.com > /dev/null 2>&1 || [ $RETRY_COUNT -eq $MAX_RETRIES ]; do
    echo "Network not ready. Retrying in 2 seconds... ($((RETRY_COUNT+1))/$MAX_RETRIES)"
    sleep 2
    RETRY_COUNT=$((RETRY_COUNT+1))
done

if [ $RETRY_COUNT -eq $MAX_RETRIES ]; then
    echo "Error: Network connection timed out. Step 3 failed."
    exit 1
fi

echo "Network is up! Starting Step 3..."
cd /tmp

# 2. 파일 다운로드 재시도 로직
MAX_WGET_RETRIES=5
WGET_SUCCESS=0

for i in $(seq 1 $MAX_WGET_RETRIES); do
    echo "Downloading Step3_NMS_Setup.run (Attempt $i/$MAX_WGET_RETRIES)..."
    if wget -q -O setup_nms "https://raw.githubusercontent.com/DS1UYM/DVSMU-PHP-NMS/main/Step3_NMS_Setup.run"; then
        WGET_SUCCESS=1
        break
    fi
    sleep 3
done

if [ $WGET_SUCCESS -eq 1 ]; then
    chmod +x setup_nms
    ./setup_nms
    echo "Step 3 Installation completed successfully!"
    touch /root/.dvsmu_step3_done
    
    # 성공했을 때만 서비스 삭제
    systemctl disable resume_step3.service
    rm /etc/systemd/system/resume_step3.service
    rm /root/resume_step3.sh
else
    echo "Error: Failed to download setup_nms after $MAX_WGET_RETRIES attempts."
    exit 1
fi
EOF

    chmod +x /root/resume_step3.sh

    # systemd 서비스 등록 (Restart 옵션 추가로 안정성 확보)
    cat << 'EOF' > /etc/systemd/system/resume_step3.service
[Unit]
Description=Resume DVS Install Step 3 after reboot
After=network-online.target
Wants=network-online.target

[Service]
Type=oneshot
ExecStart=/root/resume_step3.sh
RemainAfterExit=yes

[Install]
WantedBy=multi-user.target
EOF

    systemctl enable resume_step3.service
fi

# ---------------------------------------------------------------------
# [Step 1] DVSwitch Server 설치 및 기본 세팅
# ---------------------------------------------------------------------
if [ ! -f "/root/.cache/.dvs_step1_done" ]; then
    printf "Step 1: Installing DVSwitch Server . . .\n"

    # GRUB 타임아웃 숨김 처리
    sed -i 's/GRUB_TIMEOUT=[0-9]*/GRUB_TIMEOUT=0/' /etc/default/grub
    if grep -q "GRUB_TIMEOUT_STYLE=" /etc/default/grub; then
      sed -i 's/GRUB_TIMEOUT_STYLE=.*/GRUB_TIMEOUT_STYLE=hidden/' /etc/default/grub
    else
      echo "GRUB_TIMEOUT_STYLE=hidden" >> /etc/default/grub
    fi
    update-grub > /dev/null 2>&1

    # 시스템 패키지 업데이트
    apt-get update -y > /dev/null 2>&1
    apt-get upgrade -y > /dev/null 2>&1

    # DVSwitch 리포지토리 및 서버 설치
    wget -q http://dvswitch.org/bookworm
    chmod +x bookworm
    ./bookworm > /dev/null 2>&1
    apt-get update > /dev/null 2>&1
    apt-get install dvswitch-server -y > /dev/null 2>&1

    # alias 환경 변수 영구 등록
    if ! grep -q "alias dvs=" /root/.bashrc; then
        echo "alias dvs='sudo /usr/local/dvs/dvs'" >> /root/.bashrc
    fi
    if [ -n "${SUDO_USER:-}" ]; then
        if ! grep -q "alias dvs=" "/home/$SUDO_USER/.bashrc"; then
            echo "alias dvs='sudo /usr/local/dvs/dvs'" >> "/home/$SUDO_USER/.bashrc"
        fi
    fi

    mkdir -p /root/.cache
    touch /root/.cache/.dvs_step1_done
    echo "Step 1 완료!"
fi

# ---------------------------------------------------------------------
# [Step 2] DVSMU Dependencies 다운로드 및 실행 
# ---------------------------------------------------------------------
if [ ! -f "/root/.cache/.dvs_step2_done" ]; then
    printf "\nStep 2: Installing dependencies for DVSMU (System will reboot) . . .\n"

    # 1. /root 폴더로 이동하여 작업 (권한 문제 원천 차단)
    cd /root || exit 1

    # 2. 만약 이전에 다운받다 실패한 잔여 파일이 있다면 삭제
    rm -f /root/setup

    # 3. 파일 다운로드 (에러 내용 확인을 위해 -q 옵션 제외)
    if wget -O /root/setup "https://raw.githubusercontent.com/hl5ky/dvsmu/main/setup"; then
        
        # 4. 파일 소유자를 root로 변경하고 실행 권한 부여
        chown root:root /root/setup
        chmod 755 /root/setup

        echo "setup 스크립트를 실행합니다. 곧 시스템이 재부팅됩니다..."
        
        # ★ 핵심: setup 스크립트가 재부팅을 시키기 전에 미리 완료 표시를 해둡니다.
        mkdir -p /root/.cache
        touch /root/.cache/.dvs_step2_done
        
        # 5. 절대 경로로 파일 실행 (이 명령어가 완료되면 자동으로 재부팅됨)
        /root/setup show

        # 만약 setup 명령어가 스스로 재부팅을 시키지 않을 경우를 대비한 강제 재부팅
        echo "Step 2 완료. 시스템을 재부팅합니다..."
        reboot
    else
        echo -e "\n${RED}Error${NOC}: hl5ky 리포지토리에서 setup 파일을 다운로드하지 못했습니다."
        exit 1
    fi
fi
