# install.sh By @minjun1177(6K2LZB) 2026-04-12

#!/bin/bash

set -Eeuo pipefail

trap 'echo "Error: failed at line $LINENO: $BASH_COMMAND"; exit 1' ERR

# Check for root permissions
if [ "$EUID" -ne 0 ]; then 
  echo "Error: This script must be run with sudo privileges."
  exit 1
fi

printf "Changing GRUB settings . . ."

if ! sed -i 's/GRUB_TIMEOUT=[0-9]*/GRUB_TIMEOUT=0/' /etc/default/grub; then
  echo "Error: failed to update GRUB_TIMEOUT"
  exit 1
fi


if grep -q "GRUB_TIMEOUT_STYLE=" /etc/default/grub; then
    sed -i 's/GRUB_TIMEOUT_STYLE=.*/GRUB_TIMEOUT_STYLE=hidden/' /etc/default/grub
else
    echo "GRUB_TIMEOUT_STYLE=hidden" >> /etc/default/grub
fi

# Apply changes
printf "Changing GRUB settings . . ."
if ! update-grub; then
  echo "Error: update-grub failed"
  exit 1
fi
echo "OK"

# step1
printf "Checking for updates . . ."
if ! apt-get update -y; then
  echo "Error: apt-get update failed"
  exit 1
fi
if ! apt-get upgrade -y; then
  echo "Error: apt-get upgrade failed"
  exit 1
fi
echo "OK"

cd /tmp

printf "Installing DVS . . ."
if ! wget http://dvswitch.org/bookworm; then
  echo "Error: failed to download dvswitch installer"
  exit 1
fi
if ! chmod +x bookworm; then
  echo "Error: failed to set execute permission on bookworm"
  exit 1
fi
if ! ./bookworm; then
  echo "Error: bookworm installer failed"
  exit 1
fi
if ! apt-get update; then
  echo "Error: apt-get update failed after bookworm installer"
  exit 1
fi
if ! apt-get install dvswitch-server -y; then
  echo "Error: failed to install dvswitch-server"
  exit 1
fi

alias dvs='sudo /usr/local/dvs/dvs'
echo "OK"
echo "You can run 'dvs' to start the DVS server."

# step2
printf "Installing dependencies for DVS . . ."
if ! wget -O setup https://raw.githubusercontent.com/hl5ky/dvsmu/main/setup; then # I will make to download code from github. Because I am not familiar with the license for hl5ky/dvsmu. So I will not upload the setup file to my pull request. @minjun1177(6K2LZB) 2026-04-12
  echo "Error: failed to download DVS setup script"
  exit 1
fi
if ! chmod +x setup; then
  echo "Error: failed to set execute permission for DVS setup script"
  exit 1
fi
if ! ./setup; then
  echo "Error: DVS setup execution failed"
  exit 1
fi
echo "OK"

# step3
printf "Installing NMS Package . . ."
if ! wget -O setup https://raw.githubusercontent.com/hl5ky/dvsmu/main/setup_nms; then # .... It is so long code. @minjun1177(6K2LZB) 2026-04-12
  echo "Error: failed to download setup script"
  exit 1
fi
if ! chmod +x setup; then
  echo "Error: failed to set execute permission for setup"
  exit 1
fi
if ! ./setup; then
  echo "Error: setup execution failed"
  exit 1
fi
echo "OK"

echo "Installation completed! Please reboot your system to apply all changes."