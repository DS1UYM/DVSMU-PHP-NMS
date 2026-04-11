sudo apt update -y && sudo apt upgrade -y

cd /tmp

sudo wget http://dvswitch.org/bookworm

sudo chmod +x bookworm

sudo ./bookworm

sudo apt-get update

sudo apt-get install dvswitch-server -y

alias dvs='sudo /usr/local/dvs/dvs'

