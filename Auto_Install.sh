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
      # 이 아래 부분이 변경되었습니다.
      
      echo -e "\n\n==========================================================="
      echo -e "           모든 설치가 성공적으로 완료되었습니다!           "
      echo -e "===========================================================\n"
      
      echo -e "안전한 재부팅을 위해 10초 대기 후 자동으로 시스템을 재부팅합니다.\n"
      
      # 키보드 입력을 완전히 무시하고 무조건 1초씩 10번 대기
      for i in {10..1}; do
          echo -ne "재부팅 대기 중... $i 초 남았습니다. (강제 대기)\r"
          sleep 1
      done
      
      echo -e "\n\n시스템을 재부팅합니다..."
      sudo reboot
  else
      echo -e "\n\n[오류] 설치 중 문제가 발생하여 재부팅을 진행하지 않습니다."
  fi
}
