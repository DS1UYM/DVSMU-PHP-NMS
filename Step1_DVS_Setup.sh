sudo apt update -y && sudo apt upgrade -y

cd /tmp

sudo wget http://dvswitch.org/trixie

sudo chmod +x trixie

sudo ./trixie

sudo apt-get update

sudo apt-get install dvswitch-server -y

grep -qxF "alias dvs='sudo /usr/local/dvs/dvs'" ~/.bashrc || echo "alias dvs='sudo /usr/local/dvs/dvs'" >> ~/.bashrc

