# install.sh By @minjun1177(6K2LZB) 2026-04-12
# 

#!/bin/bash

# Check for root permissions
if [ "$EUID" -ne 0 ]; then 
  echo "Error: This script must be run with sudo privileges."
  exit 1
fi

echo "Changing GRUB settings . . ."

sed -i 's/GRUB_TIMEOUT=[0-9]*/GRUB_TIMEOUT=0/' /etc/default/grub


if grep -q "GRUB_TIMEOUT_STYLE=" /etc/default/grub; then
    sed -i 's/GRUB_TIMEOUT_STYLE=.*/GRUB_TIMEOUT_STYLE=hidden/' /etc/default/grub
else
    echo "GRUB_TIMEOUT_STYLE=hidden" >> /etc/default/grub
fi

# Apply changes
echo "Changing GRUB settings . . ."
update-grub
echo "OK"

# step1
echo "Checking for updates . . ."
apt-get update -y && apt-get upgrade -y

cd /tmp

wget http://dvswitch.org/bookworm && chmod +x bookworm && ./bookworm
apt-get update && apt-get install dvswitch-server -y

alias dvs='sudo /usr/local/dvs/dvs'
echo "OK"
echo "You can run 'dvs' to start the DVS server."

# step2
echo "Installing dependencies for DVS . . ."
wget -O setup https://raw.githubusercontent.com/hl5ky/dvsmu/main/setup # I will make to download code from github. Because I am not familiar with the license for hl5ky/dvsmu. So I will not upload the setup file to my pull request. @minjun1177(6K2LZB) 2026-04-12
chmod +x setup && ./setup
echo "OK"

# step3
echo "Installing NMS Package . . ."
wget -O setup https://raw.githubusercontent.com/hl5ky/dvsmu/main/setup_nms
chmod +x setup && ./setup
echo "OK" # Maybe It will working... But I'm not sure. @minjun1177(6K2LZB) 2026-04-12

echo "Installation completed! Please reboot your system to apply all changes."