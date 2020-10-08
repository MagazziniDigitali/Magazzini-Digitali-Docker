# Firefox over VNC
#

FROM ubuntu:16.04
# make sure the package repository is up to date

ENV HOME /root

#Disable terminal switching
COPY	15-no-vt.conf /etc/X11/xorg.conf.d/15-no-vt.conf

#Updates packages database
COPY 	sources.list /etc/apt/sources.list
RUN	    apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 40976EAF437D05B5
RUN     apt-get update

# Install vnc, xvfb in order to create a 'fake' display and firefox
RUN     apt-get install -y \
        x11vnc \
        xvfb \
        firefox \
        firefox-locale-it

#Installs wm
RUN     apt-get install -y matchbox-window-manager sudo

COPY	viewer.sh    /usr/bin/viewer.sh

RUN	    chmod +x /usr/bin/viewer.sh

#Adds an unprivileged user
RUN     useradd -d /home/lowpriv -m lowpriv && \
        mkdir ~/.vnc
COPY    files/custom-settings.js /usr/lib/firefox/defaults/pref/

RUN 	apt-get clean && rm -rf /var/lib/apt/lists/*  /var/tmp/
