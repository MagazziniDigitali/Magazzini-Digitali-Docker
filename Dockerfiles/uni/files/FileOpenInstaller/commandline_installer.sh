#!/bin/bash

echo ""
echo "*************** FileOpen Plug-in Installer ***************"
echo ""
echo "Removing any old installation files..."

rm -rf /tmp/FileOpenInstaller*

if [ ! -d ~/plug_ins ]
mkdir ~/plug_ins
then
echo "Removing any previously installed plug-ins..."
rm -f ~/plug_ins/*.api
fi

echo "Trying to read Acrobat Reader version..."

pathtoexec=$(which acroread)
printf "Path to executable : $pathtoexec\n"

case $pathtoexec in
	/*) 	versionno=$($pathtoexec -version)
		printf "Acrobat Reader version $versionno found.\n"
		case $versionno in
			7*) PLUGIN='FileOpen.AR7.api'
			;;
			8*) PLUGIN='FileOpen.AR8.api'
			;;
			*) printf 'Incorrect Acrobat Reader version "%s" found. Exiting installer.\n' "$versionno"
			exit 2
			;;
			?) printf 'No Acrobat Reader version found. Exiting installer.\n'
			exit 2
			;;
		esac
		;;
	?) 	echo "No Acrobat Reader Found"
		exit 2
		;;
esac

COMPLETEPATH="./$PLUGIN"

printf "Checking if $COMPLETEPATH exists..."

if [ -r $COMPLETEPATH ]
then
	printf "yes\n"
	echo "Using FileOpen plug-in from current directory..."
else
	printf "No\n"
	echo "Downloading the latest FileOpen plug-in..."
	wget -P /tmp/ http://plugin.fileopen.com/current/FileOpenInstaller.tar.gz
	
	cd /tmp
	
	echo "Expanding the package..."
	tar -xvf FileOpenInstaller.tar.gz
	COMPLETEPATH="FileOpenInstaller/$PLUGIN"
fi

printf "Checking if downloaded $COMPLETEPATH exists..."

if [ -r $COMPLETEPATH ]
then
	printf "yes\n"
	echo ""
	echo "Installing plug-in for current user..."
	cp $COMPLETEPATH ~/plug_ins/$PLUGIN
	
	echo "Correcting permissions..."
	chmod 755 ~/plug_ins/$PLUGIN && echo "Installation was successful." && exit 0
	
	echo "Installation failed."
	
else
	printf "No\n"
	echo "Error while downloading and expanding installation package."
fi
