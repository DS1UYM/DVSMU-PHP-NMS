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
  sudo ./step2_setup
  
  # 설치가 정상적으로 끝났는지 확인
  if [ $? -eq 0 ]; then
      sync
      sleep 2 # 백그라운드 작업이 완전히 끝날 때까지 2초 안전 대기
     
      # [핵심] 복사/붙여넣기 시 남아있는 키보드 입력(엔터 등) 찌꺼기 완벽히 비우기
      while read -r -t 0.1 -s < /dev/tty; do :; done || true
      
      echo -e "10초 후 자동으로 시스템을 재부팅합니다."
      echo -e "즉시 재부팅하려면 아무 키나 누르세요.\n"
      
      # 10초 카운트다운 루프 (1초마다 아무 키나 눌렸는지 확인)
      for i in {10..1}; do
          echo -ne "재부팅 대기 중... $i 초 남았습니다.\r"
          if read -t 1 -n 1 -s < /dev/tty; then
              break # 키 입력이 감지되면 즉시 루프 탈출
          fi
      done
      
      echo -e "\n\n시스템을 재부팅합니다..."
      sudo reboot
  else
      echo -e "\n\n[오류] 설치 중 문제가 발생하여 재부팅을 진행하지 않습니다."
  fi
}
