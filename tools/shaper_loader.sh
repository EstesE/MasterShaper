#!/bin/bash

#/***************************************************************************
# *
# * Copyright (c) by Andreas Unterkircher, unki@netshadow.at
# * All rights reserved
# *
# *  This program is free software; you can redistribute it and/or modify
# *  it under the terms of the GNU General Public License as published by
# *  the Free Software Foundation; either version 2 of the License, or
# *  (at your option) any later version.
# *
# *  This program is distributed in the hope that it will be useful,
# *  but WITHOUT ANY WARRANTY; without even the implied warranty of
# *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# *  GNU General Public License for more details.
# *
# *  You should have received a copy of the GNU General Public License
# *  along with this program; if not, write to the Free Software
# *  Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
# *
# ***************************************************************************/

IPT_BIN=`xmlstarlet select -T -t -v '//ipt_bin' config.dat`

if [ ! -e "${IPT_BIN}" ]
then
   echo "iptables binary can't be found under: ${IPT_BIN}"
   exit 1;
fi

case "$1" in

   # Cleanup existing residues from previous loaded rules
   cleanup)

      # Remove the ms-postrouting entries from POSTROUTING chain
      for PR_RULES in `${IPT_BIN} -t mangle -L POSTROUTING -v -n --line-numbers | grep -i ms-postrouting | grep -iv ^chain | awk '{ print $1 }' | sort -r`; do
         ${IPT_BIN} -t mangle -D POSTROUTING ${PR_RULES}
      done

      # Remove the ms-postrouting entries from OUTPUT chain
      for PR_RULES in `${IPT_BIN} -t mangle -L OUTPUT -v -n --line-numbers | grep -i ms-forward | grep -iv ^chain | awk '{ print $1 }' | sort -r`; do
         ${IPT_BIN} -t mangle -D OUTPUT ${PR_RULES}
      done


      # Remove the ms-forward entries from FORWARD chain
      for FWD_RULES in `${IPT_BIN} -t mangle -L FORWARD -v -n --line-numbers |grep -i ms-forward | grep -iv ^chain | awk '{ print $1 }' | sort -r`; do
         ${IPT_BIN} -t mangle -D FORWARD ${FWD_RULES}
      done

      # Remove MasterShapers own prerouting chain
      ${IPT_BIN} -t mangle -L ms-prerouting -n >/dev/null 2>&1

      if [ $? == 0 ]
      then
         ${IPT_BIN} -t mangle -F ms-prerouting
         ${IPT_BIN} -t mangle -D PREROUTING -j ms-prerouting
         ${IPT_BIN} -t mangle -X ms-prerouting
      fi

      # Get all available MasterShaper chains and remove them from ms-postrouting
      MS_CHAINS=`${IPT_BIN} -t mangle -L ms-postrouting -n 2>/dev/null | grep -i ^ms-chain | awk '{ print $1 }'`

      for PR_RULES in `${IPT_BIN} -t mangle -L ms-postrouting -v -n --line-numbers 2>/dev/null | grep -i ^ms-chain | awk '{ print $1 }' | sort -r `; do
         ${IPT_BIN} -t mangle -D ms-postrouting ${PR_RULES}
      done

      # Empty all MasterShaper chains
      for MS_CHAIN in ${MS_CHAINS}; do
         ${IPT_BIN} -t mangle -F ${MS_CHAIN}
      done

      # Remove MasterShaper traffic chains
      ${IPT_BIN} -t mangle -L ms-forward -n >/dev/null 2>&1

      if [ $? == 0 ]; then
         ${IPT_BIN} -t mangle -F ms-forward
         ${IPT_BIN} -t mangle -X ms-forward >/dev/null 2>&1
      fi

      ${IPT_BIN} -t mangle -L ms-postrouting -n > /dev/null 2>&1

      if [ $? == 0 ]; then
         ${IPT_BIN} -t mangle -F ms-postrouting
         ${IPT_BIN} -t mangle -X ms-postrouting >/dev/null 2>&1
      fi

      # Remove any still existing chain rule
      for CHAIN in `${IPT_BIN} -t mangle -L -n 2>&1 |grep -i ms- | grep -i ^Chain | awk '{ print $2 }' | sort -r`; do
         ${IPT_BIN} -t mangle -F ${CHAIN}
         ${IPT_BIN} -t mangle -X ${CHAIN}
      done

      ;;

   tc)

      OUTPUT=`$2 2>&1`;

      if [ $? != 0 ]; then
         echo "Error: ${OUTPUT}" >&2
         exit 1;
      else
         echo "OK"
      fi
      ;;

   iptables)

      while read line; do
         OUTPUT=`${line} 2>&1`;
         if [ $? != 0 ]; then
            echo "Error: ${OUTPUT}" >&2
            exit 1;
         fi
      done < $2
      echo "OK"

      ;;
   *)
      echo
      echo "MasterShaper rules loader."
      echo "   $0 (cleanup)"
      echo
      ;;

esac

exit 0;
