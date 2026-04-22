# dvsNMS (DVSwitch Multi-User NMS)

[![OS](https://img.shields.io/badge/OS-Debian%2013%20Trixie-A80030.svg?logo=debian&logoColor=white)](#)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](#)

**dvsNMS**는 DV Switch 멀티유저 버전 관리자를 위한 강력한 웹 기반 네트워크 관리 시스템(NMS)입니다. 시스템 모니터링, 멀티유저 관리, 텔레그램 연동 알림 등 서버 관리에 필요한 핵심 기능들을 통합 제공합니다.

> [!CAUTION]
> 설치 과정 중 원치 않는 단계는 건너뛸 수 있으나, 필수 패키지가 누락되어 발생하는 시스템 오류는 해결되지 않을 수 있습니다. 가급적 자동 설치나 순차적인 수동 설치를 권장합니다.

<div align="center">
  <img src="https://github.com/DS1UYM/DVSMU-PHP-NMS/blob/main/NMS_cap_20260417.png" alt="dvsNMS Dashboard Screenshot" width="800">
</div>

---

## 📢 최근 업데이트 내역 (4월 20일 10:30 기준)

> [!NOTE]
> **기존 사용자 안내:** 전체를 다시 설치할 필요 없이 **[수동 설치 - Step2]**만 재설치하시면 최신 버전이 적용됩니다.

* **통합 및 편의성 강화**
  * 관리자 페이지 패스워드 관리 기능 통합
  * '시스템 자동 재부팅' 예약 관리 기능 추가
  * 개발자 '공지사항' 및 '뉴스' 플립창 추가, BM Hoseline 바로가기 제공
* **실시간 모니터링 및 통계**
  * **[통계]** 페이지 추가: 사용자별 이용량 확인 가능
  * Websocket 방식 적용으로 실시간 대시보드 반응 속도 대폭 향상 (⚠️ **TCP 3000 포트 개방 필수**)
  * *참고: 기존의 오디오 모니터 기능은 제거되었습니다.*
* **스마트 알림 (Telegram)**
  * 일일 리포트 텔레그램 자동 발송 기능 추가
  * 텔레그램 메시지 수신 '방해 금지 시간' 설정 기능 지원
* **멀티유저 무한 버전**
  * `dvsMU` 패키지 설치 없이도 웹상에서 멀티유저 추가/삭제/편집 완벽 지원
  * 멀티유저 생성 수 제한 해제

---

## ⚙️ 시스템 권장 사양 및 OS 설정

**지원 OS:** Debian 13 (Trixie)

> [!TIP]
> * **OS 설치 시 주의사항:** 패키지 선택 화면에서 가장 아래 **3개 항목(SSH server, standard system utilities 등)**만 선택하세요.
> * **권장 하드웨어:** ROM 8GB 이상, RAM 4GB 이상 (CPU 코어는 많을수록 원활합니다.)
> * **계정 설정:** `root` 패스워드는 별도로 설정하지 않고 기본값을 유지하는 것을 권장합니다.

---

## 🚀 설치 안내

OS(Debian) 설치가 막 끝난 초기 상태에서 실행하는 것을 권장합니다.

### 💡 [방법 A] 자동 설치 (권장)
단일 스크립트로 필요한 모든 구성을 자동으로 진행합니다.

```bash
cd /tmp
wget -O setup [https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/Auto_Install.sh](https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/Auto_Install.sh)
sudo chmod +x ./setup
sudo ./setup
```

### 🛠 [방법 B] 수동 설치
필요한 단계만 선택하여 개별적으로 설치할 수 있습니다.

Step 0. GRUB 대기 시간 0초 설정

```
cd /tmp
wget -O setup [https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/Step0_grubzero.sh](https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/Step0_grubzero.sh)
sudo chmod +x ./setup
sudo ./setup
```
<BR>

Step 1. DVS-Server 설치
```
cd /tmp
wget -O setup [https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/Step1_DVS_Setup.sh](https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/Step1_DVS_Setup.sh)
sudo chmod +x ./setup
sudo ./setup
```
<BR>

Step 2. NMS 패키지 설치
```
Bash
cd /tmp
wget -O setup [https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/Step2_NMS_Setup.run](https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/Step2_NMS_Setup.run)
sudo chmod +x ./setup
sudo ./setup
```
<BR>

## 💻 초기 설정 및 사용 방법
### 1. 사용자 구성
Step 2까지 설치를 마친 후, 웹 브라우저를 통해 http://[서버-IP-주소]로 접속합니다.

초기 패스워드: admin (해당 페이지 소스 파일 최상단에서 직접 수정 가능)

로그인 후 '접속자 관리' 페이지에서 메인 유저와 멀티 유저를 반드시 설정해 주세요.

### 2. Step 4. NMS+TG 알림봇(텔레그램) 연동

> [!NOTE]
> 텔레그램 검색창에서 BotFather를 검색하여 실행합니다.
>
> /newbot 명령어를 입력하여 나만의 챗봇을 생성합니다.
>
> 발급받은 API 토큰과 Chat ID를 NMS 웹페이지의 'TG알리미' 설정 메뉴에 입력합니다.

### 3. 시스템 업데이트
향후 새로운 업데이트 공지가 있을 경우, 웹 대시보드의 '접속자 관리' 페이지에서 '자동업데이트' 버튼을 클릭하여 간편하게 최신 버전을 유지할 수 있습니다.

## 🗓 다음 업데이트 예정 사항
방화벽 자동 구성: DVS에서 실제 사용하는 포트만 선별하여 방화벽(UFW/iptables)을 자동으로 설정하는 기능이 추가될 예정입니다.
