# Firefox over VNC
#

FROM ubuntu:16.04
# make sure the package repository is up to date

ENV HOME /root

#Disable terminal switching
ADD	files/15-no-vt.conf /etc/X11/xorg.conf.d/15-no-vt.conf

#Updates packages database
ADD 	files/sources.list /etc/apt/sources.list
RUN	apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 40976EAF437D05B5
RUN     apt-get update

# Install vnc, xvfb in order to create a 'fake' display and firefox
RUN     apt-get install -y x11vnc xvfb firefox

#Installs wm
RUN     apt-get install -y matchbox-window-manager

ADD	files/viewer.sh    /usr/bin/viewer.sh

RUN	chmod +x /usr/bin/viewer.sh


#Configure vnc
RUN     mkdir ~/.vnc

RUN 	apt-get clean && rm -rf /var/lib/apt/lists/*  /var/tmp/

## UNI SECTION
#Needed for autologin
ADD		files/index.html /var/www/
ADD		files/myScript.js /var/www/
#Install acroread
RUN		dpkg --add-architecture i386
RUN		apt-get update
RUN		apt-get install -y libgtk2.0-0:i386 libnss3-1d:i386 libnspr4-0d:i386 libnss-mdns libxml2:i386 libxslt1.1:i386 libstdc++6:i386 wget
RUN		wget -O /root/AdobeReader_ita-8.1.7-1.i386.deb ftp.adobe.com/pub/adobe/reader/unix/8.x/8.1.7/ita/AdobeReader_ita-8.1.7-1.i386.deb
RUN		dpkg -i /root/AdobeReader_ita-8.1.7-1.i386.deb
RUN		rm /root/AdobeReader_ita-8.1.7-1.i386.deb
#Adds FileOpenInstaller
ADD		files/FileOpenInstaller/commandline_installer.sh /opt/
RUN		/opt/commandline_installer.sh
#Needed to allow user choose acroread
ADD		files/.mailcap /root/
#Adds Firefox and Adobe folders already configured
ADD		files/.mozilla/ /root/.mozilla/
ADD		files/.adobe    /root/.adobe/
#Adds extension to change content-disposition to inline so firefox automatically open PDFs
ADD		files/{b134ae49-6ada-41e0-9141-9f8d00373d93}.xpi /usr/lib/firefox-addons/extensions/

## END

