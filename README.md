# DVSMU-PHP-NMS
This is an NMS system for administrators of the DV Switch multi-user version.

OS Debian 12 Bookworm 설치 권장

-- SSH,Apache,Default 선택 (패키지 옵션 선택 중 가장 아래 3개만 선택)

-- ROM 8GB, RAM 4GB 이상 권장, CPU core는 많을수록 좋음

-- ROOT 패스워드는 설정하지 않고 건들지 않음




### Step0. Grub 대기 시간 0초로 변경

cd /tmp

wget -O setup https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/Step0_grubzero.sh

sudo chmod +x ./Step0_grubzero.sh



### Step1. DVS-Server 설치

cd /tmp

wget -O setup https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/Step1_DVS_Setup.sh

chmod +x setup

sudo chmod +x ./Step1_DVS_Setup.sh

--> Main User(관리자) 설정 후 다음 단계 진행



### Step2. DVMU 설치

cd /tmp

wget -O setup https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/Step2_DVSMU_Setup.sh

chmod +x setup

sudo chmod +x ./Step2_DVSMU_Setup.sh



### Step3. NMS 패키지 설치

cd /tmp

wget -O setup https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/Step3_NMS_Setup.run

chmod +x setup

sudo chmod +x ./Step3_NMS_Setup.run



### Step4. NMS+TG알림봇(텔레그램) 설치

텔레그램 'BotFather' 에서 '/newbot' 입력하고 나만의 챗봇 생성

API토큰과 챗ID를 받으면 'TG알리미'에서 정보 2가지 정보 입력.



### 업데이트 공지시 '접속자관리' 페이지에서 '자동업데이트' 클릭 하세요.

### 다음 업데이트는 방화벽 DVS에서 사용하는 포트만 골라 자동으로 설치가 추가될 예정입니다.



