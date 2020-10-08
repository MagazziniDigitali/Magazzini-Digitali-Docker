# Firefox over VNC
#

FROM debian:9-slim
# make sure the package repository is up to date

ENV HOME /root

#Disable terminal switching
COPY	15-no-vt.conf /etc/X11/xorg.conf.d/15-no-vt.conf

#Updates packages database
RUN     apt-get update

# Install vnc, xvfb in order to create a 'fake' display and firefox
RUN     apt-get install -y --no-install-recommends \
        x11vnc \
        xvfb \
        chromium \
        chromium-l10n
#Installs wm
RUN     apt-get install -y matchbox-window-manager

COPY	viewer.sh    /usr/bin/viewer.sh

RUN	    chmod +x /usr/bin/viewer.sh

#Adds an unprivileged user
RUN     useradd -d /home/lowpriv -m lowpriv && \
        mkdir ~/.vnc
COPY    files/custom-settings.js /usr/lib/firefox/defaults/pref/

RUN 	apt-get clean && rm -rf /var/lib/apt/lists/*  /var/tmp/
