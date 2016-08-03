#!/bin/bash
dirs="client/configs client/files/dokumenty client/files/epodatelna client/sessions client/temp log"
echo Nastavení oprávnění pro adresáře spisovky
if [[ "$(id -u)" != "0" ]]; then
	echo 'Skript je nutné spustit s oprávněním superuživatele (např. pomocí sudo).'
	exit
fi
echo -n Zadejte skupinu, pod kterou běží webový 'server: '
read www_group
getent group $www_group > /dev/null
if [[ $? -gt 0 ]] ; then 
	echo Skupina \"$www_group\" neexistuje!
	exit
fi
echo Nastavuji oprávnění...
for file in $dirs ; do
	echo "- $file"
	chgrp $www_group $file
	chmod g+w,o= $file
done