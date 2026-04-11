#!/bin/bash

# root 권한 체크
if [ "$EUID" -ne 0 ]; then 
  echo "오류: 이 스크립트는 sudo 권한으로 실행해야 합니다."
  exit
fi

echo "GRUB 설정을 변경합니다 (대기 시간 0초)..."

# /etc/default/grub 파일 수정
# GRUB_TIMEOUT 값을 0으로 변경
sed -i 's/GRUB_TIMEOUT=[0-9]*/GRUB_TIMEOUT=0/' /etc/default/grub

# GRUB_TIMEOUT_STYLE이 있으면 hidden으로 변경, 없으면 추가
if grep -q "GRUB_TIMEOUT_STYLE=" /etc/default/grub; then
    sed -i 's/GRUB_TIMEOUT_STYLE=.*/GRUB_TIMEOUT_STYLE=hidden/' /etc/default/grub
else
    echo "GRUB_TIMEOUT_STYLE=hidden" >> /etc/default/grub
fi

# 변경사항 시스템 적용
echo "변경 사항을 적용 중입니다 (update-grub)..."
update-grub

echo "설정이 완료되었습니다! 다음 부팅부터 대기 화면 없이 바로 시작됩니다."
