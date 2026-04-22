# dvsNMS
This is an NMS system for administrators of the DV Switch multi-user version.
> [!CAUTION]
> 원치 않는 단계는 패스할 수 있으나 시스템 오류는 해결되지 않습니다.  


## OS Debian 13 trixie Upgrade & 멀티유저 무한버전
> [!TIP]
> SSH,Apache,standard utility 선택 (패키지 옵션 선택 중 가장 아래 3개만 선택)  
> ROM 8GB, RAM 4GB 이상 권장, CPU core는 많을수록 좋음  
> ROOT 패스워드는 설정하지 않고 건들지 않음  

> [!NOTE]
> 4월 20일 10시 30분 업데이트가 있으니 기존 사용자분은 Step2만 재설치 해주세요.<BR>
> 관리자페이지 패스워드 관리가 통합되었습니다.<BR>
> '시스템 자동재부팅' 예약 관리가 가능합니다.<BR>
> [통계] 페이지가 추가 되었습니다. 사용자별 이용량 확인이 가능합니다.<BR>
> 일일리포트를 텔레그램으로 자동 발송 합니다.<BR>
> 텔레그램 메세지 방해금지시간 설정이 가능합니다.<BR>
> 개발자의 '공지사항' 및 '뉴스' 플립창이 추가 되었습니다.<BR>
> Websoket 방식으로 실시간대시보드가 더욱 빨라졌습니다.<BR>
> TCP3000 오픈 되어 있지 않으면 업데이트 전보다 실시간 확인이 느리게 확인됩니다.
> 오디오모니터 기능은 제거 되었습니다.<BR>
> BM Hoseline 바로가기가 추가되었습니다.<BR>
> dvsMU 패키지 설치 없이 웹상에서 멀티 유저 추가 및 삭제, 편집이 가능합니다.
> 멀티 유저수 제한이 없습니다.
  
<BR><BR><BR>
<img src=https://github.com/DS1UYM/DVSMU-PHP-NMS/blob/main/NMS_cap_20260417.png>
<BR><BR><BR>


> [!NOTE]
> Step2까지 모두 설치 하신 후 웹페이지에서 서버 주소로 접속하고 '접속자관리' 페이지에서 메인유저와 멀티유저를 설정하세요

<BR><BR><BR>
 
  
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
  
  
## Step2. NMS 패키지 설치
```
cd /tmp

wget -O setup https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/Step2_NMS_Setup.run

sudo chmod +x ./setup

sudo ./setup
```

<BR><BR><BR>

## Step4. NMS+TG알림봇(텔레그램) 설치  
> [!NOTE]
> 텔레그램 'BotFather' 에서 '/newbot' 입력하고 나만의 챗봇 생성  
> API토큰과 챗ID를 받으면 'TG알리미'에서 정보 2가지 정보 입력  
    
 

<BR><BR><BR><BR><BR>
   
### 업데이트 공지시 '접속자관리' 페이지에서 '자동업데이트' 클릭 하세요.
  
### 초기 패스워드는 "admin" 입니다. 해당페이지 소스 파일 최상단에서 직접 수정이 가능합니다.
  
### 다음 업데이트는 방화벽 DVS에서 사용하는 포트만 골라 자동으로 설치가 추가될 예정입니다.
  
   


