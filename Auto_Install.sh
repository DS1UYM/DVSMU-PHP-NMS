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
sudo ./step2_setup && \
source ~/.bashrc
