#!/bin/bash

LANGUAGES="de_DE.UTF8"

for LANGUAGE in ${LANGUAGES}
do
   mkdir -p ${LANGUAGE}/LC_MESSAGES

   if [ -e ${LANGUAGE}/LC_MESSAGES/messages.po ]
   then
      echo "Language ${LANGUAGE}, joining..."
      xgettext -L PHP ../*.php -o ${LANGUAGE}/LC_MESSAGES/messages.po -k=_  -j --copyright-holder="Andreas Unterkircher <unki@netshadow.at>" -s --no-wrap
   else
      echo "Language ${LANGUAGE}, creating new po file..."
      xgettext -L PHP ../*.php -o ${LANGUAGE}/LC_MESSAGES/messages.po -k=_ --copyright-holder="Andreas Unterkircher <unki@netshadow.at>" -s --no-wrap
   fi
done

