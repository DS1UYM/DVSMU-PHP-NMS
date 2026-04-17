# DVSMU-PHP-NMS
This is an NMS system for administrators of the DV Switch multi-user version.
> [!CAUTION]
> 원치 않는 단계는 패스할 수 있으나 시스템 오류는 해결되지 않습니다.  


## OS Debian 12 Bookworm 설치 권장    
> [!TIP]
> SSH,Apache,standard utility 선택 (패키지 옵션 선택 중 가장 아래 3개만 선택)  
> ROM 8GB, RAM 4GB 이상 권장, CPU core는 많을수록 좋음  
> ROOT 패스워드는 설정하지 않고 건들지 않음  
  
<img src=https://github.com/DS1UYM/DVSMU-PHP-NMS/blob/main/NMS_cap_20260404.png>
<BR><BR><BR><BR><BR>
 
# [자동설치] 한번에 모두 설치하는 Auto Install  
> [!CAUTION]
> Auto Install은 시스템에 따라 심각한 손상을 초래할 수 있습니다.  

> [!NOTE]
> Step2_DVSMU까지 설치 후 재부팅이 실시되고 이후 Step3_NMS 설치가 자동 진행됩니다.
> 재부팅 후 터미널에서 DVS를 실행하여 초기 설정을 하시기 바랍니다.  

```
cd /tmp

wget -O setup https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/Auto_All_Install.sh

sudo chmod +x ./setup

sudo ./setup
```
<BR>
설치 완료 후 다음 명령으로 설치가 완료 되었는지 확인해 보시기 바랍니다.  

```
sudo cat /root/step3_install.log
```  
<BR><BR><BR><BR><BR><BR>
 
  
# [수동설치] 순서입니다.  
  
## Step0. Grub 대기 시간 0초로 변경  

```
cd /tmp

wget -O setup https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/Step0_grubzero.sh

sudo chmod +x ./setup

sudo ./setup
```
  
## Step1. DVS-Server 설치
```
cd /tmp

wget -O setup https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/Step1_DVS_Setup.sh

sudo chmod +x ./setup

sudo ./setup
```
  
## Step2. DVSMU 설치
```
cd /tmp

wget -O setup https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/Step2_DVSMU_Setup.sh

sudo chmod +x ./setup

sudo ./setup
```
--> 재부팅 후  
```
dvs
```
--> Main User(관리자) 설정 후 다음 단계 진행  
```
dvsmu
```
--> Multi User 설정 후 다음 단계 진행  
  
## Step3. NMS 패키지 설치
```
cd /tmp

wget -O setup https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/Step3_NMS_Setup.run

sudo chmod +x ./setup

sudo ./setup
```
  
## Step4. NMS+TG알림봇(텔레그램) 설치  
> [!NOTE]
> 텔레그램 'BotFather' 에서 '/newbot' 입력하고 나만의 챗봇 생성  
> API토큰과 챗ID를 받으면 'TG알리미'에서 정보 2가지 정보 입력  
    


<BR><BR><BR><BR><BR>
  
## 2026 4월 13일 업데이트
  
@minjun1177 의견으로 한방 설치 스크립트가 추가 되었습니다. 올리신 코드에 버그가 많아 새로 작성하였습니다.  
올인원 스크립트 실행시 한번의 재부팅 후 NMS설치가 이어집니다.  
설치 완료 후 다음 명령으로 설치가 완료 되었는지 확인해 보시기 바랍니다.  
```
sudo cat /root/step3_install.log
```
<BR><BR><BR>

> [!NOTE]
> 4월 17일 기준 업데이트가 있으니 기존 사용자분은 Step3만 재설치 해주세요.
> 관리자페이지 패스워드 관리가 통합되었습니다.
> [통계] 페이지가 추가 되었습니다. 사용자별 이용량 확인이 가능합니다.
> 개발자의 '공지사항' 및 '뉴스' 플립창이 추가 되었습니다.
> Websoket 방식으로 실시간대시보드가 더욱 빨라졌습니다.
> 사용자별 UDP포트 외에 TCP3000, TCP2222, TCP2001~2099을 열어주셔야 음성 모니터링이 가능합니다.
  
<BR><BR><BR><BR><BR>
   
### 업데이트 공지시 '접속자관리' 페이지에서 '자동업데이트' 클릭 하세요.
  
### 초기 패스워드는 "admin" 입니다. 해당페이지 소스 파일 최상단에서 직접 수정이 가능합니다.
  
### 다음 업데이트는 방화벽 DVS에서 사용하는 포트만 골라 자동으로 설치가 추가될 예정입니다.
  
   


