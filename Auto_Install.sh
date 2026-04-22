{
  clear
  cat << 'EOF'
===========================================================
 _____  __      __  _____   _   _   __  __   _____ 
|  __ \ \ \    / / / ____| | \ | | |  \/  | / ____|
| |  | | \ \  / / | (___   |  \| | | \  / || (___  
| |  | |  \ \/ /   \___ \  | . ` | | |\/| | \___ \ 
| |__| |   \  /    ____) | | |\  | | |  | | ____) |
|_____/     \/    |_____/  |_| \_| |_|  |_||_____/ 

          dvsNMS All-in-One Package Installation
          Copyright (c) DS1UYM Sejung Kim
===========================================================
EOF

  echo -e "\n설치를 시작합니다. 잠시만 기다려주세요...\n"
  sleep 3

  cd /tmp && \
  wget -O step0_setup https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/Step0_grubzero.sh && \
  sudo chmod +x ./step0_setup && \
  sudo ./step0_setup && \
  sudo apt update -y && \
  sudo apt upgrade -y && \
  sudo wget http://dvswitch.org/trixie && \
  sudo chmod +x trixie && \
  sudo ./trixie && \
  sudo apt-get update && \
  sudo apt-get install dvswitch-server -y && \
  echo "alias dvs='sudo /usr/local/dvs/dvs'" >> ~/.bashrc && \
  wget -O step2_setup https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/Step2_NMS_Setup.run && \
  sudo chmod +x ./step2_setup && \
  sudo ./step2_setup && \
  {
    # 1. 파일 시스템 동기화 및 프로세스 완전 종료 대기
    sync
    sleep 3
    
    # 2. 설치 중 입력된 불필요한 키보드 버퍼 비우기 (타임아웃 스킵 방지용)
    read -t 1 -n 10000 discard < /dev/tty 2>/dev/null || true
        
    # 3. 10초 대기 및 재부팅 (키보드 직접 입력 강제)
    read -t 10 -n 1 -s -r -p "10초 후 자동으로 시스템을 재부팅합니다. 즉시 재부팅하려면 아무 키나 누르세요..." < /dev/tty
    echo -e "\n\n시스템을 재부팅합니다..."
    sudo reboot
  }
}
