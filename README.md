# DVSMU-PHP-NMS
This is an NMS system for administrators of the DV Switch multi-user version.

NMS 시스템까지 단계별 설치 패키지 입니다.
원치 않는 단계는 건너띄워도 되나 시스템 오류는 해결하지 않습니다.

OS Debian 12 Bookworm 설치 권장

-- SSH,Apache,standard utility 선택 (패키지 옵션 선택 중 가장 아래 3개만 선택)

-- ROM 8GB, RAM 4GB 이상 권장, CPU core는 많을수록 좋음

-- ROOT 패스워드는 설정하지 않고 건들지 않음




### Step0. Grub 대기 시간 0초로 변경

cd /tmp

wget -O setup https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/Step0_grubzero.sh

sudo chmod +x ./setup

sudo ./setup


### Step1. DVS-Server 설치

cd /tmp

wget -O setup https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/Step1_DVS_Setup.sh

sudo chmod +x ./setup

sudo ./setup

--> Main User(관리자) 설정 후 다음 단계 진행



### Step2. DVSMU 설치

cd /tmp

wget -O setup https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/Step2_DVSMU_Setup.sh

sudo chmod +x ./setup

sudo ./setup



### Step3. NMS 패키지 설치

cd /tmp

wget -O setup https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/Step3_NMS_Setup.run

sudo chmod +x ./setup

sudo ./setup



### Step4. NMS+TG알림봇(텔레그램) 설치

텔레그램 'BotFather' 에서 '/newbot' 입력하고 나만의 챗봇 생성

API토큰과 챗ID를 받으면 'TG알리미'에서 정보 2가지 정보 입력.



### 업데이트 공지시 '접속자관리' 페이지에서 '자동업데이트' 클릭 하세요.

### 다음 업데이트는 방화벽 DVS에서 사용하는 포트만 골라 자동으로 설치가 추가될 예정입니다.



