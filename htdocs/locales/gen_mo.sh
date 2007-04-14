#!/bin/bash

LANGUAGES="de_DE.UTF8"

for LANGUAGE in ${LANGUAGES}
do
   mkdir -p ${LANGUAGE}/LC_MESSAGES

   if [ -e ${LANGUAGE}/LC_MESSAGES/messages.po ]
   then
      echo "Language ${LANGUAGE}, creating mo file..."
      msgfmt ${LANGUAGE}/LC_MESSAGES/messages.po --output-file=${LANGUAGE}/LC_MESSAGES/messages.mo
   fi
done

