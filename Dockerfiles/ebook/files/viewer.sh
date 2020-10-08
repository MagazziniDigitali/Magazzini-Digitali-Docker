#!/bin/bash
chown -R lowpriv:lowpriv /home/lowpriv
sudo -u lowpriv matchbox-window-manager&
while true;
do
    sudo -u lowpriv firefox $FFURL
done
