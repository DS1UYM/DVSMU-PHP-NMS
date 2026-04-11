# DVSMU-PHP-NMS
This is an NMS system for administrators of the DV Switch multi-user version.

OS Debian 12 Bookworm 설치

-- SSH,Apache,Default 선택


sudo apt update -y && sudo apt upgrade -y

sudo apt install net-tools -y

sudo apt install ntpsec -y


sudo nano /etc/ntpsec/ntp.conf 

-->  server time.bora.net


cd /tmp

wget http://dvswitch.org/bookworm

chmod +x bookworm

sudo ./bookworm

sudo apt-get update

sudo apt-get install dvswitch-server -y


sudo nano ~/.bashrc

(맨 밑 줄에 아래와 같이 추가합니다)

-->  alias dvs='sudo /usr/local/dvs/dvs'


cd /tmp

wget -O setup https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/setup.run

chmod +x setup

./setup.run


cd /tmp

wget -O setup https://raw.githubusercontent.com/hl5ky/dvsmu/main/setup

chmod +x setup

./setup




#########################################################################

기존에 DVSMU가 모두 설치되어 있는 경우 다음의 NMS패키지만 설치합니다.

cd /tmp

wget -O setup https://raw.githubusercontent.com/ds1uym/DVSMU-PHP-NMS/main/setup.run

chmod +x setup

./setup.run

#########################################################################


