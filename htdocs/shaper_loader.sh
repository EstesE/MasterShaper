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

# source mastershaper config
. config.dat

if [ ! -e "${IPT_BIN}" ]
then
   echo "iptables binary can't be found under: ${IPT_BIN}"
   exit 1;
fi

case "$1" in

   # Cleanup existing residues from previous rules
   cleanup)

      # Remove the ms-all-chains entries from POSTROUTING chain
      for PR_RULES in `${IPT_BIN} -t mangle -L POSTROUTING -v -n --line-numbers | grep -i ms-all-chains | grep -iv ^chain | awk '{ print $1 }' | sort -r`; do
         ${IPT_BIN} -t mangle -D POSTROUTING ${PR_RULES}
      done

      # Remove the ms-all entries from FORWARD chain
      for FWD_RULES in `${IPT_BIN} -t mangle -L FORWARD -v -n --line-numbers |grep -i ms-all | grep -iv ^chain | awk '{ print $1 }' | sort -r`; do
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

      # Get all available MasterShaper chains and removed them from ms-all-chains
      MS_CHAINS=`${IPT_BIN} -t mangle -L ms-all-chains -n 2>/dev/null | grep -i ^ms-chain | awk '{ print $1 }'`

      for PR_RULES in `${IPT_BIN} -t mangle -L ms-all-chains -v -n --line-numbers 2>/dev/null | grep -i ^ms-chain | awk '{ print $1 }' | sort -r `; do
         ${IPT_BIN} -t mangle -D ms-all-chains ${PR_RULES}
      done

      # Empty all MasterShaper chains
      for MS_CHAIN in ${MS_CHAINS}; do
         ${IPT_BIN} -t mangle -F ${MS_CHAIN}
      done

      # Remove MasterShaper traffic chains
      ${IPT_BIN} -t mangle -L ms-all -n >/dev/null 2>&1

      if [ $? == 0 ]; then
         ${IPT_BIN} -t mangle -F ms-all
         ${IPT_BIN} -t mangle -X ms-all >/dev/null 2>&1
      fi

      ${IPT_BIN} -t mangle -L ms-all-chains -n > /dev/null 2>&1

      if [ $? == 0 ]; then
         ${IPT_BIN} -t mangle -F ms-all-chains
         ${IPT_BIN} -t mangle -X ms-all-chains >/dev/null 2>&1
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
