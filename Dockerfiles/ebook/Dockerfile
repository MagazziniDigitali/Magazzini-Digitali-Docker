# Firefox over VNC
#

FROM ubuntu:16.04
# make sure the package repository is up to date

#Disable terminal switching
COPY    files/15-no-vt.conf /etc/X11/xorg.conf.d/15-no-vt.conf

#Updates packages database
COPY    files/sources.list /etc/apt/sources.list
RUN     apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 40976EAF437D05B5
RUN     apt-get update

# Install vnc, xvfb in order to create a 'fake' display and firefox
RUN     apt-get install -y \
        x11vnc \
        xvfb \
        firefox \
        firefox-locale-it

#Installs wm
RUN     apt-get install -y matchbox-window-manager sudo

ENV     HOME /home/lowpriv
#Adds an unprivileged user and configures novnc
RUN     useradd -d /home/lowpriv -m lowpriv && \
        mkdir ~/.vnc

#Localizes firefox in italian
COPY    files/custom-settings.js /usr/lib/firefox/defaults/pref/

#Copies the startup program
COPY    files/viewer.sh    /usr/bin/viewer.sh

#Install content-disposition and ebookreader
COPY    files/reader_1.5.0.12.xpi /usr/lib/firefox-addons/extensions/{5384767E-00D9-40E9-B72F-9CC39D655D6F}.xpi

#Makes startup program executable
RUN     chmod +x /usr/bin/viewer.sh

#Saves space removing apt cache
RUN     apt-get clean && rm -rf /var/lib/apt/lists/*  /var/tmp/
