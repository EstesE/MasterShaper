<?php

/***************************************************************************
 *
 * Copyright (c) by Andreas Unterkircher, unki@netshadow.at
 * All rights reserved
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 ***************************************************************************/

define("UNIDIRECTIONAL", 1);
define("BIDIRECTIONAL", 2);

class Ruleset_Interface {

   private $initialized;
   private $rules;
   private $if_id;
   private $db;
   private $parent;

   /****
    * Just to record the positions of IP packet header fields
    * when within an GRE-encapsulated tunnel:
    *
    * Pos (Byte)     Lenght (Bytes)    What
    * 25             1                 Type of Service (TOS, DSCP)
    * 33             1                 Protocol
    * 36             4                 Source IP address
    * 40             4                 Destination IP address
    *
    ****/

   /**
    * Ruleset_Interface constructor
    *
    * Initialize the Ruleset_Interface class
    */
   public function __construct($if_id, $if_gre)
   {
      global $ms;

      $this->initialized = false;
      $this->rules       = Array();

      $this->current_chain  = 0;
      $this->current_class  = NULL;
      $this->current_filter = NULL;
      $this->current_pipe   = NULL;

      if(!$if = $this->getInterfaceDetails($if_id))
         $ms->throwError("Something is really wrong now.");

      $this->if_id           = $if_id;
      $this->if_name         = $if->if_name;
      $this->if_speed        = $if->if_speed;
      $this->if_fallback_idx = $if->if_fallback_idx;
      $this->if_active       = $if->if_active;
      $this->if_gre          = $if_gre;

   } // __construct()

   /**
    * set the status of the interface
    *
    * this function set a "initialized" flag to indicate whether
    * the interface has been already initialized or not.
    *
    * @param bool new status
    */ 
   private function setStatus($status) 
   {
      if($status == true or $status == false) 
         $this->initialized = $status;      

   } // setStatus()

   /**
    * return the current status of the interface
    *
    * this function return the current state of the "initialized flag to
    * indicate whether the interface has been already initialized or not.
    *
    * @return bool
    */
   public function getStatus() 
   {
      return $this->initialized;

   } // getStatus()

   /**
    * return ruleset
    *
    * this function will return the buffer in which all
    * the generated rules for this interface are stored.
    *
    * @return string
    */
   public function getRules()
   {
      return $this->rules;

   } // getRules()

   /**
    * check if interface is active
    *
    * will return, if the interface assigned to this
    * class is enabled or disabled in MasterShaper
    * config.
    *
    * @return bool
    */
   public function isActive()
   {
      return $this->if_active;

   } // isActive()

   /* return interface speed in kbit/s
    *
    * @return int
    */
   private function getSpeed()
   {
      global $ms;

      return $ms->getKbit($this->if_speed);

   } // getSpeed()

   /**
    * return interface id
    *
    * return the unique primary database key as interface id.
    *
    * @return int
    */
   private function getId()
   {
      return $this->if_id;

   } // getId()

   /**
    * return interface name
    *
    * returns the current interface name (ipsec0, eth0, ...)
    *
    * @return string
    */
   private function getName()
   {
      return $this->if_name;

   } // getName()

   /**
    * is matching inside GRE tunnel
    *
    * @param bool
    */
   private function isGRE()
   {
      if($this->if_gre == 'Y')
         return true;

      return false;

   } // isGRE()

   private function getInterfaceDetails($if_idx)
   {
      $if = new Network_Interface($if_idx);

      return $if;

   } // getInterfaceDetails()

   /**
    * add comment-line to ruleset
    *
    * @param string $text
    */
   private function addRuleComment($text)
   {
      $this->addRule("######### ". $text);

   } // addRuleComment()

   /**
    * add rule-lint to ruleset
    *
    * @param string $cmd
    */
   private function addRule($cmd)
   {
      array_push($this->rules, $cmd);

   } // addRule()

   private function addRootQdisc($id)
   {
      global $ms;

      switch($ms->getOption("classifier")) {

         default:
         case 'HTB':
	         $this->addRule(TC_BIN ." qdisc add dev ". $this->getName() ." handle ". $id ." root htb default 1");
            break;

         case 'HFSC':
            $this->addRule(TC_BIN ." qdisc add dev ". $this->getName() ." handle ". $id ." root hfsc default 1");
            break;

         case 'CBQ':
            $this->addRule(TC_BIN ." qdisc add dev ". $this->getName() ." handle ". $id ." root cbq avpkt 1000 bandwidth ". $this->getSpeed() ."Kbit cell 8");
            break;

      }

   } // addRootQdisc()

   private function addInitClass($parent, $classid)
   {
      global $ms;

      $bw = $this->getSpeed();

      if(!$bw)
         die("Unknown bandwidth for interface ". $this->if_id);

      switch($ms->getOption("classifier")) {

         default:
         case 'HTB':
            $this->addRule(TC_BIN ." class add dev ". $this->getName() ." parent ". $parent ." classid ". $classid ." htb rate ". $bw ."Kbit");
            break;

         case 'HFSC':
            $this->addRule(TC_BIN ." class add dev ". $this->getName() ." parent ". $parent ." classid ". $classid ." hfsc sc rate ". $bw ."Kbit ul rate ". $bw ."Kbit");
            break;

         case 'CBQ':
            $this->addRule(TC_BIN ." class add dev ". $this->getName() ." parent ". $parent ." classid ". $classid ." cbq bandwidth ". $bw ."Kbit rate ". $bw ."Kbit allot 1000 prio 3 bounded");
            break;

      }

   } // addInitClass()

   /**
    * adds the top level filter which brings traffic into the initClass
    */
   private function addInitFilter($parent)
   {
      $this->addRule(TC_BIN ." filter add dev ". $this->getName() ." parent ". $parent ." protocol all u32 match u32 0 0 classid 1:1");

   } // addInitFilter()

   /* Adds a class definition for a inbound chain */
   private function addClass($parent, $classid, $sl, $direction = "in", $parent_sl = null)
   {
      global $ms;

      $string = TC_BIN ." class add dev ". $this->getName() ." parent ". $parent ." classid ". $classid;

      switch($direction) {

         case 'in':

            switch($ms->getOption("classifier")) {

               default:
               case 'HTB':

                  $string.= " htb ";
                  if($sl->sl_htb_bw_in_rate != "" && $sl->sl_htb_bw_in_rate > 0) {
                     $string.= " rate ". $sl->sl_htb_bw_in_rate ."Kbit ";
                     if($sl->sl_htb_bw_in_ceil != "" && $sl->sl_htb_bw_in_ceil > 0)
                        $string.= "ceil ". $sl->sl_htb_bw_in_ceil ."Kbit ";
                     if($sl->sl_htb_bw_in_burst != "" && $sl->sl_htb_bw_in_burst > 0)
                        $string.= "burst ". $sl->sl_htb_bw_in_burst ."Kbit ";
                     if($sl->sl_htb_priority > 0) 
                        $string.= "prio ". $sl->sl_htb_priority;
                  }	
                  else {
                     if(isset($parent_sl)) {
                        $string.= " rate ". $parent_sl->sl_htb_bw_in_rate ."Kbit ";
                        if(!empty($parent_sl->sl_htb_bw_in_ceil))
                           $string.= " ceil ". $parent_sl->sl_htb_bw_in_ceil ."Kbit ";
                     }
                     else
                        $string.= " rate 1Kbit ceil ". $this->getSpeed() ."Kbit ";

                     if($sl->sl_htb_priority > 0)
                        $string.= "prio ". $sl->sl_htb_priority;
                  }
                  /* this value remains hardcoded here.
                     *******
                     It might be good time to touch concept of quantums
                     now. In fact when more classes want to borrow
                     bandwidth they are each given some number of bytes
                     before serving other competing class. This number
                     is called quantum. You should see that if several
                     classes are competing for parent's bandwidth then
                     they get it in proportion of their quantums. It is
                     important to know that for precise operation
                     quantums need to be as small as possible and
                     larger than MTU.
                     *******
                  */
                  $string.= " quantum 1532";
                  break;
				      
               case 'HFSC':

                  $string.= " hfsc sc ";
                  if(isset($sl->sl_hfsc_in_umax) && $sl->sl_hfsc_in_umax != "" && $sl->sl_hfsc_in_umax > 0) 
                     $string.= " umax ". $sl->sl_hfsc_in_umax ."b ";
                  if(isset($sl->sl_hfsc_in_dmax) && $sl->sl_hfsc_in_dmax != "" && $sl->sl_hfsc_in_dmax > 0)
                     $string.= " dmax ". $sl->sl_hfsc_in_dmax ."ms ";
                  if(isset($sl->sl_hfsc_in_rate) && $sl->sl_hfsc_in_rate != "" && $sl->sl_hfsc_in_rate > 0)
                     $string.= " rate ". $sl->sl_hfsc_in_rate ."Kbit ";
                  if(isset($sl->sl_hfsc_in_ulrate) && $sl->sl_hfsc_in_ulrate != "" && $sl->sl_hfsc_in_ulrate > 0)
                     $string.= " ul rate ". $sl->sl_hfsc_in_ulrate ."Kbit";

                  $string.= " rt ";

                  if(isset($sl->sl_hfsc_in_umax) && $sl->sl_hfsc_in_umax != "" && $sl->sl_hfsc_in_umax > 0) 
                     $string.= " umax ". $sl->sl_hfsc_in_umax ."b ";
                  if(isset($sl->sl_hfsc_in_dmax) && $sl->sl_hfsc_in_dmax != "" && $sl->sl_hfsc_in_dmax > 0)
                     $string.= " dmax ". $sl->sl_hfsc_in_dmax ."ms ";
                  if(isset($sl->sl_hfsc_in_rate) && $sl->sl_hfsc_in_rate != "" && $sl->sl_hfsc_in_rate > 0)
                     $string.= " rate ". $sl->sl_hfsc_in_rate ."Kbit ";
                  if(isset($sl->sl_hfsc_in_ulrate) && $sl->sl_hfsc_in_ulrate != "" && $sl->sl_hfsc_in_ulrate > 0)
                     $string.= " ul rate ". $sl->sl_hfsc_in_ulrate ."Kbit";
                  break;

               case 'CBQ':

                  $string.= " cbq bandwidth ". $this->inbound ."Kbit rate ". $sl->sl_cbq_in_rate ."Kbit allot 1500 prio ". $sl->sl_cbq_in_priority ." avpkt 1000";
                  if($sl->sl_cbq_bounded == "Y")
                     $string.= " bounded";
                  break;

            }
            break;

         case 'out':

            switch($ms->getOption("classifier")) {

               default:
               case 'HTB':

                  $string.= " htb ";

                  if($sl->sl_htb_bw_out_rate != "" && $sl->sl_htb_bw_out_rate > 0) {
                     $string.= " rate ". $sl->sl_htb_bw_out_rate ."Kbit ";
                     if($sl->sl_htb_bw_out_ceil != "" && $sl->sl_htb_bw_out_ceil > 0)
                        $string.= "ceil ". $sl->sl_htb_bw_out_ceil ."Kbit ";
                     if($sl->sl_htb_bw_out_burst != "" && $sl->sl_htb_bw_out_burst > 0)
                        $string.= "burst ". $sl->sl_htb_bw_out_burst ."Kbit ";
                     if($sl->sl_htb_priority > 0) 
                        $string.= "prio ". $sl->sl_htb_priority;
                  }	
                  else {
                     if(isset($parent_sl)) {
                        $string.= " rate ". $parent_sl->sl_htb_bw_out_rate ."Kbit ";
                        if(!empty($parent_sl->sl_htb_bw_out_ceil))
                           $string.= " ceil ". $parent_sl->sl_htb_bw_out_ceil ."Kbit ";
                     }
                     else
                        $string.= " rate 1Kbit ceil ". $this->getSpeed() ."Kbit ";

                     if($sl->sl_htb_priority > 0)
                        $string.= "prio ". $sl->sl_htb_priority;
                  }
                  if(isset($sl->sl_sfq_quantum) && is_numeric($sl->sl_sfq_quantum))
                     $string.= " quantum ". $sl->sl_sfq_quantum;
                  break;

               case 'HFSC':

                  $string.= " hfsc sc ";

                  if(isset($sl->sl_hfsc_out_umax) && $sl->sl_hfsc_out_umax != "" && $sl->sl_hfsc_out_umax > 0) 
                     $string.= " umax ". $sl->sl_hfsc_out_umax ."b ";
                  if(isset($sl->sl_hfsc_out_dmax) && $sl->sl_hfsc_out_dmax != "" && $sl->sl_hfsc_out_dmax > 0)
                     $string.= " dmax ". $sl->sl_hfsc_out_dmax ."ms ";
                  if(isset($sl->sl_hfsc_out_rate) && $sl->sl_hfsc_out_rate != "" && $sl->sl_hfsc_out_rate > 0)
                     $string.= " rate ". $sl->sl_hfsc_out_rate ."Kbit ";
                  if(isset($sl->sl_hfsc_out_ulrate) && $sl->sl_hfsc_out_ulrate != "" && $sl->sl_hfsc_out_ulrate > 0)
                     $string.= " ul rate ". $sl->sl_hfsc_out_ulrate ."Kbit";
                  $string.= " rt ";
                  if(isset($sl->sl_hfsc_out_umax) && $sl->sl_hfsc_out_umax != "" && $sl->sl_hfsc_out_umax > 0) 
                     $string.= " umax ". $sl->sl_hfsc_out_umax ."b ";
                  if(isset($sl->sl_hfsc_out_dmax) && $sl->sl_hfsc_out_dmax != "" && $sl->sl_hfsc_out_dmax > 0)
                     $string.= " dmax ". $sl->sl_hfsc_out_dmax ."ms ";
                  if(isset($sl->sl_hfsc_out_rate) && $sl->sl_hfsc_out_rate != "" && $sl->sl_hfsc_out_rate > 0)
                     $string.= " rate ". $sl->sl_hfsc_out_rate ."Kbit ";
                  if(isset($sl->sl_hfsc_out_ulrate) && $sl->sl_hfsc_out_ulrate != "" && $sl->sl_hfsc_out_ulrate > 0)
                     $string.= " ul rate ". $sl->sl_hfsc_out_ulrate ."Kbit";
                  break;

               case 'CBQ':

                  $string.= " cbq bandwidth ifspeedKbit rate ". $sl->sl_cbq_out_rate ."Kbit allot 1500 prio ". $sl->sl_cbq_out_priority ." avpkt 1000";
                  if($sl->sl_cbq_bounded == "Y")
                     $string.= " bounded";
                  break;

            }
            break;
      }

      $this->addRule($string);

   } // addClass()

   /* Adds qdisc at the end of class for final queuing mechanism */
   private function addSubQdisc($child, $parent, $sl)
   {
      global $ms;

      $string = TC_BIN ." qdisc add dev ". $this->getName() ." handle ". $child ." parent ". $parent ." ";

      switch($sl->sl_qdisc) {

         default:
         case 'SFQ':
            $string.="sfq";
            if(isset($sl->sl_sfq_perturb) && is_numeric($sl->sl_sfq_perturb))
               $string.= " perturb ". $sl->sl_sfq_perturb;
            if(isset($sl->sl_sfq_quantum) && is_numeric($sl->sl_sfq_quantum))
               $string.= " quantum ". $sl->sl_sfq_quantum;
            break;

         case 'ESFQ':
            $string.= "esfq ". $this->get_esfq_params($sl);
            break;

         case 'HFSC':
            $string.= "hfsc";
            break;

         case 'NETEM':
            $string.= "netem ". $this->get_netem_params($sl);
            break;

      }

      $this->addRule($string);

   } // addSubQdisc()

   private function addAckFilter($parent, $option, $id = "")
   {
      global $ms;

      switch($ms->getOption("filter")) {

         default:
         case 'tc':

            if($this->isGRE()) {
               $this->addRule(TC_BIN ." filter add dev ". $this->getName() ." parent ". $parent ." protocol ip prio 1 u32 match u8 0x06 0xff at 33 match u8 0x05 0x0f at 24 match u16 0x0000 0xffc0 at 26 match u8 0x10 0xff at 57 flowid ". $id);
            }
            else {
               $this->addRule(TC_BIN ." filter add dev ". $this->getName() ." parent ". $parent ." protocol ip prio 1 u32 match ip protocol 6 0xff match u8 0x05 0x0f at 0 match u16 0x0000 0xffc0 at 2 match u8 0x10 0xff at 33 flowid ". $id);
               //$this->addRule(TC_BIN ." filter add dev ". $this->getName() ." parent ". $parent ." protocol ip prio 1 u32 match ip protocol 6 0xff match u8 0x10 0xff at nexthdr+13 match u16 0x0000 0xffc0 at 2 flowid ". $id);
            }

            break;

         case 'ipt':

            $this->addRule(IPT_BIN ." -t mangle -A ms-postrouting -p tcp -m length --length :64 -j CLASSIFY --set-class ". $id);
            $this->addRule(IPT_BIN ." -t mangle -A ms-postrouting -p tcp -m length --length :64 -j RETURN");
            break;

      }

   } // addAckFilter()
	
   /* create IP/host matching filters */
   private function addHostFilter($parent, $option, $params1 = "", $params2 = "", $chain_direction = "")
   {
      global $ms;

      switch($ms->getOption("filter")) {
	 
         default:
         case 'tc':

            if($chain_direction == "out") {
               $tmp = $params1->chain_src_target;
               $params1->chain_src_target = $params1->chain_dst_target;
               $params1->chain_dst_target = $tmp;
            }

            // matching on source address, but not on destination
            if($params1->chain_src_target != 0 && $params1->chain_dst_target == 0) {

               $hosts = $this->getTargetHosts($params1->chain_src_target);

               foreach($hosts as $host) {
                  if(!$this->check_if_mac($host)) {
                     if($this->isGRE()) {
                        $hex_host = $this->convertIpToHex($host);
                        switch($params1->chain_direction) {
                           case UNIDIRECTIONAL:
                              $this->addRule(TC_BIN ." filter add dev ". $this->getName() ." parent ". $parent ." protocol all prio 2 u32 match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 36 flowid ". $params2);
                              break;
                           case BIDIRECTIONAL:
                              $this->addRule(TC_BIN ." filter add dev ". $this->getName() ." parent ". $parent ." protocol all prio 2 u32 match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 36 flowid ". $params2);
                              $this->addRule(TC_BIN ." filter add dev ". $this->getName() ." parent ". $parent ." protocol all prio 2 u32 match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 40 flowid ". $params2);
                              break;
                        }
                     }
                     else {
                        switch($params1->chain_direction) {
                           case UNIDIRECTIONAL:
                              $this->addRule(TC_BIN ." filter add dev ". $this->getName() ." parent ". $parent ." protocol all prio 2 u32 match ip src ". $host ." flowid ". $params2);
                              break;
                           case BIDIRECTIONAL:
                              $this->addRule(TC_BIN ." filter add dev ". $this->getName() ." parent ". $parent ." protocol all prio 2 u32 match ip src ". $host ." flowid ". $params2);
                              $this->addRule(TC_BIN ." filter add dev ". $this->getName() ." parent ". $parent ." protocol all prio 2 u32 match ip dst ". $host ." flowid ". $params2);
                              break;
                        }
                     }
                  }
                  else {
                     if(preg_match("/(.*):(.*):(.*):(.*):(.*):(.*)/", $host))
                        list($m1, $m2, $m3, $m4, $m5, $m6) = preg_split("/:/", $host);
                     else
                        list($m1, $m2, $m3, $m4, $m5, $m6) = preg_split("/-/", $host);

                     $this->addRule(TC_BIN ." filter add dev ". $this->getName() ." parent ". $parent ." protocol all prio 2 u32 match u16 0x0800 0xffff at -2 match u16 0x". $m5 . $m6 ." 0xffff at -4 match u32 0x". $m1 . $m2 . $m3 . $m4 ."  0xffffffff at -8 flowid ". $params2);
                  }
               }
            }
            // matching on destination address, but not on source
            elseif($params1->chain_src_target == 0 && $params1->chain_dst_target != 0) {

               $hosts = $this->getTargetHosts($params1->chain_dst_target);

               foreach($hosts as $host) {
                  if(!$this->check_if_mac($host)) {
                     if($this->isGRE()) {
                        $hex_host = $this->convertIpToHex($host);
                        switch($params1->chain_direction) {
                           case UNIDIRECTIONAL:
                              $this->addRule(TC_BIN ." filter add dev ". $this->getName() ." parent ". $parent ." protocol all prio 2 u32 match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 40 flowid ". $params2);
                              break;
                           case BIDIRECTIONAL:
                              $this->addRule(TC_BIN ." filter add dev ". $this->getName() ." parent ". $parent ." protocol all prio 2 u32 match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 36 flowid ". $params2);
                              $this->addRule(TC_BIN ." filter add dev ". $this->getName() ." parent ". $parent ." protocol all prio 2 u32 match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 40 flowid ". $params2);
                              break;
                        }
                     }
                     else {
                        switch($params1->chain_direction) {
                           case UNIDIRECTIONAL:
                              $this->addRule(TC_BIN ." filter add dev ". $this->getName() ." parent ". $parent ." protocol all prio 2 u32 match ip dst ". $host ." flowid ". $params2);
                              break;
                           case BIDIRECTIONAL:
                              $this->addRule(TC_BIN ." filter add dev ". $this->getName() ." parent ". $parent ." protocol all prio 2 u32 match ip src ". $host ." flowid ". $params2);
                              $this->addRule(TC_BIN ." filter add dev ". $this->getName() ." parent ". $parent ." protocol all prio 2 u32 match ip dst ". $host ." flowid ". $params2);
                              break;
                        }
                     }
                  }
                  else {
                     if(preg_match("/(.*):(.*):(.*):(.*):(.*):(.*)/", $host))
                        list($m1, $m2, $m3, $m4, $m5, $m6) = preg_split("/:/", $host);
                     else
                        list($m1, $m2, $m3, $m4, $m5, $m6) = preg_split("/-/", $host);

                     $this->addRule(TC_BIN ." filter add dev ". $this->getName() ." parent ". $parent ." protocol all prio 2 u32 match u16 0x0800 0xffff at -2 match u32 0x". $m3 . $m4 . $m5 .$m6 ." 0xffffffff at -12 match u16 0x". $m1 . $m2 ." 0xffff at -14 flowid ". $params2);
                  }
               }
            }
            // matching on both, source and destination address
            elseif($params1->chain_src_target != 0 && $params1->chain_dst_target != 0) {

               $src_hosts = $this->getTargetHosts($params1->chain_src_target);

               foreach($src_hosts as $src_host) {

                  if(!$this->check_if_mac($src_host)) {

                     if($this->isGRE()) {
                        $hex_host = $this->convertIpToHex($src_host);
                        $string = TC_BIN ." filter add dev ". $this->getName() ." parent ". $parent ." protocol all prio 2 u32 match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 36 ";
                     }
                     else {
                        $string = TC_BIN ." filter add dev ". $this->getName() ." parent ". $parent ." protocol all prio 2 u32 match ip src ". $src_host ." ";
                     }

                  }
                  else {

                     if(preg_match("/(.*):(.*):(.*):(.*):(.*):(.*)/", $src_host))
                        list($m1, $m2, $m3, $m4, $m5, $m6) = preg_split("/:/", $src_host);
                     else
                        list($m1, $m2, $m3, $m4, $m5, $m6) = preg_split("/-/", $src_host);
                     $string = TC_BIN ." filter add dev ". $this->getName() ." parent ". $parent ." protocol all prio 2 u32 match u16 0x0800 0xffff at -2 match u16 0x". $m5 . $m6 ." 0xffff at -4 match u32 0x". $m1 . $m2 . $m3 . $m4 ." 0xffffffff at -8 ";
                  }

                  $dst_hosts = $this->getTargetHosts($params1->chain_dst_target);

                  $strings = Array();
                  foreach($dst_hosts as $dst_host) {

                     $tmp_string = $string;

                     if(!$this->check_if_mac($dst_host)) {
                        if($this->isGRE()) {
                           $hex_host = $this->convertIpToHex($dst_host);
                           $tmp_string.= "match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 40 flowid ". $params2;
                        }
                        else {
                           $tmp_string.= "match ip dst ". $dst_host ." flowid ". $params2;
                        }
                     }
                     else {
                        if(preg_match("/(.*):(.*):(.*):(.*):(.*):(.*)/", $dst_host))
                           list($m1, $m2, $m3, $m4, $m5, $m6) = preg_split("/:/", $dst_host);
                        else
                           list($m1, $m2, $m3, $m4, $m5, $m6) = preg_split("/-/", $dst_host);

                        $tmp_string.= "match u16 0x0800 0xffff at -2 match u32 0x". $m3 . $m4 . $m5 .$m6 ." 0xffffffff at -12 match u16 0x". $m1 . $m2 ." 0xffff at -14 flowid ". $params2;
                     }

                     array_push($strings, $tmp_string);
                  }

                  foreach($strings as $string) {
                     // unidirectional or bidirectional matches
                     switch($params1->chain_direction) {
                        case UNIDIRECTIONAL:
                           $this->addRule($string);
                           break;
                        case BIDIRECTIONAL:
                           $this->addRule($string);
                           if(!$this->isGRE()) {
                              // now swap src and dst in the filter string
                              $string = str_replace("src", "JUSTforAmoment", $string);
                              $string = str_replace("dst", "src", $string);
                              $string = str_replace("JUSTforAmoment", "dst", $string);
                           }
                           else {
                              // now swap src and dst in the filter string
                              $string = str_replace("at 36", "JUSTforAmoment", $string);
                              $string = str_replace("at 40", "at 36", $string);
                              $string = str_replace("JUSTforAmoment", "at 40", $string);
                           }
                           $this->addRule($string);
                           break;
                     }
                  }
               }
            }
            break;

         case 'ipt':

            if($ms->getOption("msmode") == "router") 
               $string = IPT_BIN ." -t mangle -A ms-forward -o ". $this->getName();
            elseif($ms->getOption("msmode") == "bridge") 
               $string = IPT_BIN ." -t mangle -A ms-forward -m physdev --physdev-in ". $params5;

            if($chain_direction == "out") {
               $tmp = $params1->chain_src_target;
               $params1->chain_src_target = $params1->chain_dst_target;
               $params1->chain_dst_target = $tmp;
            }

            if($params1->chain_src_target != 0 && $params1->chain_dst_target == 0) {

               $hosts = $this->getTargetHosts($params1->chain_src_target);

               foreach($hosts as $host) {
                  if($this->check_if_mac($host)) {
                     $this->addRule($string ." -m mac --mac-source ". $host ." -j MARK --set-mark ". $ms->getConnmarkId($this->getId(), $params2));
                     $this->addRule($string ." -m mac --mac-source ". $host ." -j RETURN");
                  }
                  else{
                     if(strstr($host, "-") === false) {
                        $this->addRule($string ." -s ". $host ." -j MARK --set-mark ". $ms->getConnmarkId($this->getId(), $params2));
                        $this->addRule($string ." -s ". $host ." -j RETURN");
                     }
                     else {
                        $this->addRule($string ." -m iprange --src-range ". $host ." -j MARK --set-mark ". $ms->getConnmarkId($this->getId(), $params2));
                        $this->addRule($string ." -m iprange --src-range ". $host ." -j RETURN");
                     }
                  }
               }
            }
            elseif($params1->chain_src_target == 0 && $params1->chain_dst_target != 0) {

               $hosts = $this->getTargetHosts($params1->chain_dst_target);

               foreach($hosts as $host) {
                  if($this->check_if_mac($host)) {
                     $this->addRule($string ." -m mac --mac-source ". $host ." -j MARK --set-mark ". $ms->getConnmarkId($this->getId(), $params2));
                     $this->addRule($string ." -m mac --mac-source ". $host ." -j RETURN");
                  }
                  else {
                     if(strstr($host, "-") === false) {
                        $this->addRule($string ." -d ". $host ." -j MARK --set-mark ". $ms->getConnmarkId($this->getId(), $params2));
                        $this->addRule($string ." -d ". $host ." -j RETURN");
                     }
                     else {
                        $this->addRule($string ." -m iprange --dst-range ". $host ." -j MARK --set-mark ". $ms->getConnmarkId($this->getId(), $params2));
                        $this->addRule($string ." -m iprange --dst-range ". $host ." -j RETURN");
                     }
                  }
               }
            }
            elseif($params1->chain_src_target != 0 && $params1->chain_dst_target != 0) {

               $src_hosts = $this->getTargetHosts($params1->chain_src_target);
               $dst_hosts = $this->getTargetHosts($params1->chain_dst_target);

               foreach($src_hosts as $src_host) {
                  if(!$this->check_if_mac($src_host)) {
                     foreach($dst_hosts as $dst_host) {
                        if($this->check_if_mac($dst_host)) {
                           $this->addRule($string ." -m mac --mac-source ". $src_host ." -j MARK --set-mark ". $ms->getConnmarkId($this->getId(), $params2));
                           $this->addRule($string ." -m mac --mac-source ". $dst_host ." -j RETURN");
                        }
                        else {
                           if(strstr($host, "-") === false) {
                              $this->addRule($string ." -s ". $src_host ." -d ". $dst_host ." -j MARK --set-mark ". $ms->getConnmarkId($this->getId(), $params2));
                              $this->addRule($string ." -s ". $src_host ." -d ". $dst_host ." -j RETURN");
                           }
                           else {
                              $this->addRule($string ." -m iprange --src-range ". $src_host ." --dst-range ". $dst_host ." -j MARK --set-mark ". $ms->getConnmarkId($this->getId(), $params2));
                              $this->addRule($string ." -m iprange --src-range ". $src_host ." --dst-range ". $dst_host ." -j RETURN");
                           }
                        }
                     }
                  }
               }
            }
            break;
      }

   } // addHostFilter()

   /**
    * return all host addresses
    *
    * this function returns a array of host addresses for a target definition
    */
   private function getTargetHosts($target_idx)
   {
      global $ms, $db;

      $row = new Target($target_idx);

      $targets = array();

      switch($row->target_match) {

         case 'IP':

            /* for tc-filter we need to need to resolve a IP range
               iptables will use the IPRANGE match for this            
            */
            if($ms->getOption("filter") == "tc") {

               if(strstr($row->target_ip, "-") !== false) {
                  list($host1, $host2) = preg_split("/-/", $row->target_ip);
                  $host1 = ip2long($host1);
                  $host2 = ip2long($host2);

                  for($i = $host1; $i <= $host2; $i++) 
                     array_push($targets, long2ip($i));
               }
               else 
                  array_push($targets, $row->target_ip);
            }
            else 
               array_push($targets, $row->target_ip);

            break;

         case 'MAC':

            $row->target_mac = str_replace("-", ":", $row->target_mac);
            list($one, $two, $three, $four, $five, $six) = preg_split("/:/", $row->target_mac);
            $row->target_mac = sprintf("%02s:%02s:%02s:%02s:%02s:%02s", $one, $two, $three, $four, $five, $six);
            array_push($targets, $row->target_mac);
            break;

         case 'GROUP':

            $sth = $db->db_prepare("
               SELECT
                  atg_target_idx
               FROM
                  ". MYSQL_PREFIX ."assign_target_groups
               WHERE
                  atg_group_idx LIKE ?
            ");

            $result = $db->db_execute($sth, array(
               $target_idx
            ));

            while($target = $result->fetchRow()) {
               $members = $this->getTargetHosts($target->atg_target_idx);
               foreach($members as $member) {
                  array_push($targets, $member);
               }
            }

            $db->db_sth_free($sth);
            break;

      }

      return $targets;

   } // getTargetHosts()

   /* set the actually tc handle ID for a chain */
   private function setChainID($chain_idx, $chain_tc_id)
   {
      global $db;

      $sth = $db->db_prepare("
         INSERT INTO ". MYSQL_PREFIX ."tc_ids (
            id_pipe_idx,
            id_chain_idx,
            id_if,
            id_tc_id
         ) VALUES (
            '0',
            ?,
            ?,
            ?
         )
      ");

      $db->db_execute($sth, array(
         $chain_idx,
         $this->getName(),
         $chain_tc_id
      ));

      $db->db_sth_free($sth);

   } // setChainID()

   /* set the actually tc handle ID for a pipe */ 
   private function setPipeID($pipe_idx, $chain_tc_id, $pipe_tc_id)
   {
      global $db;

      $sth = $db->db_prepare("
         INSERT INTO ". MYSQL_PREFIX ."tc_ids (
            id_pipe_idx,
            id_chain_idx,
            id_if,
            id_tc_id
         ) VALUES (
            ?,
            ?,
            ?,
            ?
         )
      ");

      $db->db_execute($sth, array(
         $pipe_idx,
         $chain_tc_id,
         $this->getName(),
         $pipe_tc_id
      ));

      $db->db_sth_free($sth);

   } // setPipeID()

   /**
     * Generate code to add a pipe filter
     *
     * This function generates the tc/iptables code to filter traffic into a pipe
     * @param string $parent
     * @param string $option
     * @param Filter $filter
     * @param string $my_id
     * @param Pipe $pipe
     * @param string $chain_direction
     */
   private function addPipeFilter($parent, $option, $filter, $my_id, $pipe, $chain_direction)
   {
      global $ms;

      /* If this filter matches bidirectional, src & dst target has to be swapped */
      if($pipe->pipe_direction == BIDIRECTIONAL && $chain_direction == "out") {
         $pipe->swap_targets();
      }

      $tmp_str   = "";
      $tmp_array = array();

      switch($ms->getOption("filter")) {

         default:
         case 'tc':

            $string = TC_BIN ." filter add dev ". $this->getName() ." parent ". $parent ." protocol all prio 1 [HOST_DEFS] ";

            if(isset($filter)) {
               if($this->isGRE()) {
                  if($filter->filter_tos > 0)
                     $string.= "match u8 ". sprintf("%02x", $filter->filter_tos) ." 0xff at 25 ";
                  if(!empty($filter->filter_dscp) && $filter->filter_dscp != -1)
                     $string.= "match u8 0x". $this->get_dscp_hex_value($filter->filter_dscp) ." 0xfc at 25 ";
               }
               else {
                  if($filter->filter_tos > 0)
                     $string.= "match ip tos ". $filter->filter_tos ." 0xff ";
                  if(!empty($filter->filter_dscp) && $filter->filter_dscp != -1)
                     $string.= "match u8 0x". $this->get_dscp_hex_value($filter->filter_dscp) ." 0xfc at 1 ";
               }
            }

            /* filter matches a specific network protocol */
            if(isset($filter) && !empty($filter) && $filter->filter_protocol_id >= 0) {

               switch($ms->getProtocolNumberById($filter->filter_protocol_id)) {

                  /* TCP */
                  case 6:
                  /* UDP */
                  case 17:
                  /* IP */
                  case 4:

                     if($ports = $ms->getPorts($filter->filter_idx)) {

                        if($this->isGRE()) {
                           $string.= "match u16 ";
                        }
                        else {
                           $string.= "match ip ";
                        }
                        $str_ports = "";
                        $cnt_ports = 0;

                        foreach($ports as $port) {
                           $dst_ports = $ms->extractPorts($port['number']);
                           if($dst_ports == 0)
                              continue;
                           foreach($dst_ports as $dst_port) {
                              if($this->isGRE()) {
                                 $port_hex = $this->convertPortToHex($dst_port);
                                 $tmp_str = $string ." 0x". $port_hex ." 0xffff at [DIRECTION]";

                                 switch($pipe->pipe_direction) {
                                    case UNIDIRECTIONAL:
                                       array_push($tmp_array, str_replace("[DIRECTION]", "46", $tmp_str));
                                       break;
                                    case BIDIRECTIONAL:
                                       array_push($tmp_array, str_replace("[DIRECTION]", "46", $tmp_str));
                                       array_push($tmp_array, str_replace("[DIRECTION]", "44", $tmp_str));
                                       break;
                                 }
                              }
                              else {
                                 $tmp_str = $string ." [DIRECTION] ". $dst_port ." 0xffff ";

                                 switch($pipe->pipe_direction) {
                                    case UNIDIRECTIONAL:
                                       array_push($tmp_array, str_replace("[DIRECTION]", "dport", $tmp_str));
                                       break;
                                    case BIDIRECTIONAL:
                                       array_push($tmp_array, str_replace("[DIRECTION]", "dport", $tmp_str));
                                       array_push($tmp_array, str_replace("[DIRECTION]", "sport", $tmp_str));
                                       break;
                                 }
                              }
                           }
                        }
                        // we break here if there where ports selected.
                        // otherwise we go to the default: clause
                        // to match on IP, TCP and UDP protocols only.
                        break;
                     }
                     // there is no break; here for IP, TCP and UDP withouts ports. we use
                     // the default clause now!

                  default:

                     if($proto = $ms->getProtocolNumberById($filter->filter_protocol_id)) {

                        if($this->isGRE()) {
                           $proto_hex = $this->convertProtoToHex($proto);
                           $string.= "match u8 0x". $proto_hex ." 0xff at 33";
                        }
                        else {
                           $string.= "match ip protocol ". $proto ." 0xff ";
                        }
                        array_push($tmp_array, $string);

                     }
                     break;
               }
            }
            else
               array_push($tmp_array, $string);

            if($pipe->pipe_src_target != 0 && $pipe->pipe_dst_target == 0) {

               $hosts = $this->getTargetHosts($pipe->pipe_src_target);
               foreach($hosts as $host) {
                  if(!$this->check_if_mac($host)) {
                     if($this->isGRE()) {
                        foreach($tmp_array as $tmp_arr) {
                           $hex_host = $this->convertIpToHex($host);
                           switch($pipe->pipe_direction) {
                              case UNIDIRECTIONAL:
                                 $this->addRule(str_replace("[HOST_DEFS]", "u32 match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 36", $tmp_arr) ." flowid ". $my_id);
                                 break;
                              case BIDIRECTIONAL:
                                 $this->addRule(str_replace("[HOST_DEFS]", "u32 match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 36", $tmp_arr) ." flowid ". $my_id);
                                 $this->addRule(str_replace("[HOST_DEFS]", "u32 match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 40", $tmp_arr) ." flowid ". $my_id);
                                 break;
                           }
                        }
                     }
                     else {

                        foreach($tmp_array as $tmp_arr) {
                           switch($pipe->pipe_direction) {
                              case UNIDIRECTIONAL:
                                 $this->addRule(str_replace("[HOST_DEFS]", "u32 match ip src ". $host, $tmp_arr) ." flowid ". $my_id);
                                 break;
                              case BIDIRECTIONAL:
                                 $this->addRule(str_replace("[HOST_DEFS]", "u32 match ip src ". $host, $tmp_arr) ." flowid ". $my_id);
                                 $this->addRule(str_replace("[HOST_DEFS]", "u32 match ip dst ". $host, $tmp_arr) ." flowid ". $my_id);
                                 break;
                           }
                        }
                     }  
                  }		 
                  else {
                     foreach($tmp_array as $tmp_arr) {

                        if(preg_match("/(.*):(.*):(.*):(.*):(.*):(.*)/", $host))
                           list($m1, $m2, $m3, $m4, $m5, $m6) = preg_split("/:/", $host);
                        else
                           list($m1, $m2, $m3, $m4, $m5, $m6) = preg_split("/-/", $host);

                        switch($pipe->pipe_direction) {
                           case UNIDIRECTIONAL:
                              $this->addRule(str_replace("[HOST_DEFS]", "u32 match u16 0x0800 0xffff at -2 match u16 0x". $m5 . $m6 ." 0xffff at -4 match u32 0x". $m1 . $m2 . $m3 . $m4 ." 0xffffffff at -8 ", $tmp_arr) ." flowid ". $my_id);
                              break;
                           case BIDIRECTIONAL:
                              $this->addRule(str_replace("[HOST_DEFS]", "u32 match u16 0x0800 0xffff at -2 match u16 0x". $m5 . $m6 ." 0xffff at -4 match u32 0x". $m1 . $m2 . $m3 . $m4 ." 0xffffffff at -8 ", $tmp_arr) ." flowid ". $my_id);
                              $this->addRule(str_replace("[HOST_DEFS]", "u32 match u16 0x0800 0xffff at -2 match u32 0x". $m3 . $m4 . $m5 .$m6 ." 0xffffffff at -12 match u16 0x". $m1 . $m2 ." 0xffff at -14 ", $tmp_arr) ." flowid ". $my_id);
                              break;
			               }
                     }
                  }
               }
            }
            elseif($pipe->pipe_src_target == 0 && $pipe->pipe_dst_target != 0) {

               $hosts = $this->getTargetHosts($pipe->pipe_dst_target);
               foreach($hosts as $host) {
                  if(!$this->check_if_mac($host)) {
                     if($this->isGRE()) {
                        foreach($tmp_array as $tmp_arr) {
                           $hex_host = $this->convertIpToHex($host);
                           switch($pipe->pipe_direction) {
                              case UNIDIRECTIONAL:
                                 $this->addRule(str_replace("[HOST_DEFS]", "u32 match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 40", $tmp_arr) ." flowid ". $my_id);
                                 break;
                              case BIDIRECTIONAL:
                                 $this->addRule(str_replace("[HOST_DEFS]", "u32 match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 40", $tmp_arr) ." flowid ". $my_id);
                                 $this->addRule(str_replace("[HOST_DEFS]", "u32 match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at 36", $tmp_arr) ." flowid ". $my_id);
                                 break;
                           }
                        }
                     }
                     else {
                        foreach($tmp_array as $tmp_arr) {

                           switch($pipe->pipe_direction) {
                              case UNIDIRECTIONAL:
                                 $this->addRule(str_replace("[HOST_DEFS]", "u32 match ip dst ". $host, $tmp_arr) ." flowid ". $my_id);
                                 break;
                              case BIDIRECTIONAL:
                                 $this->addRule(str_replace("[HOST_DEFS]", "u32 match ip dst ". $host, $tmp_arr) ." flowid ". $my_id);
                                 $this->addRule(str_replace("[HOST_DEFS]", "u32 match ip src ". $host, $tmp_arr) ." flowid ". $my_id);
                                 break;
                           }
                        }
                     }
                  }
                  else {

                     foreach($tmp_array as $tmp_arr) {

                        if(preg_match("/(.*):(.*):(.*):(.*):(.*):(.*)/", $host))
                           list($m1, $m2, $m3, $m4, $m5, $m6) = preg_split("/:/", $host);
                        else
                           list($m1, $m2, $m3, $m4, $m5, $m6) = preg_split("/-/", $host);

                        switch($pipe->pipe_direction) {
                           case UNIDIRECTIONAL:
                              $this->addRule(str_replace("[HOST_DEFS]", "u32 match u16 0x0800 0xffff at -2 match u32 0x". $m3 . $m4 . $m5 .$m6 ." 0xffffffff at -12 match u16 0x". $m1 . $m2 ." 0xffff at -14 ", $tmp_arr) ." flowid ". $my_id);
                              break;
                           case BIDIRECTIONAL:
                              $this->addRule(str_replace("[HOST_DEFS]", "u32 match u16 0x0800 0xffff at -2 match u32 0x". $m3 . $m4 . $m5 .$m6 ." 0xffffffff at -12 match u16 0x". $m1 . $m2 ." 0xffff at -14 ", $tmp_arr) ." flowid ". $my_id);
                              $this->addRule(str_replace("[HOST_DEFS]", "u32 match u16 0x0800 0xffff at -2 match u16 0x". $m5 . $m6 ." 0xffff at -4 match u32 0x". $m1 . $m2 . $m3 . $m4 ." 0xffffffff at -8 ", $tmp_arr) ." flowid ". $my_id);
                              break;
                        }
                     }
                  }  
               }
            }
            elseif($pipe->pipe_src_target != 0 && $pipe->pipe_dst_target != 0) {

               $src_hosts = $this->getTargetHosts($pipe->pipe_src_target);

               foreach($src_hosts as $src_host) {
                  if(!$this->check_if_mac($src_host)) {
                     if($this->isGRE()) {
                        $hex_host = $this->convertIpToHex($src_host);
                        $tmp_str = "u32 match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at [DIR1] ";
                     }
                     else {
                        $tmp_str = "u32 match ip [DIR1] ". $src_host ." ";
                     }
                  }
                  else {
                     if(preg_match("/(.*):(.*):(.*):(.*):(.*):(.*)/", $src_host))
                        list($sm1, $sm2, $sm3, $sm4, $sm5, $sm6) = preg_split("/:/", $src_host);
                     else
                        list($sm1, $sm2, $sm3, $sm4, $sm5, $sm6) = preg_split("/-/", $src_host);
 
                     $tmp_str = "u32 [DIR1] [DIR2]";
                  }

                  $dst_hosts = $this->getTargetHosts($pipe->pipe_dst_target);
                  foreach($dst_hosts as $dst_host) {

                     if(!$this->check_if_mac($dst_host)) {

                        if($this->isGRE()) {
                           foreach($tmp_array as $tmp_arr) {
                              $hex_host = $this->convertIpToHex($dst_host);
                              switch($pipe->pipe_direction) {

                                 case UNIDIRECTIONAL:
                                    $string = str_replace("[HOST_DEFS]", $tmp_str . "match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at [DIR2] ", $tmp_arr);
                                    $string = str_replace("[DIR1]", "36", $string);
                                    $string = str_replace("[DIR2]", "40", $string);
                                    $this->addRule($string ." flowid ". $my_id);
                                    break;

                                 case BIDIRECTIONAL:
                                    $string = str_replace("[HOST_DEFS]", $tmp_str . "match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at [DIR2] ", $tmp_arr);
                                    $string = str_replace("[DIR1]", "36", $string);
                                    $string = str_replace("[DIR2]", "40", $string);
                                    $this->addRule($string ." flowid ". $my_id);
                                    $string = str_replace("[HOST_DEFS]", $tmp_str . "match u32 0x". $hex_host['ip'] ." ". $hex_host['netmask'] ." at [DIR2] ", $tmp_arr);
                                    $string = str_replace("[DIR1]", "40", $string);
                                    $string = str_replace("[DIR2]", "36", $string);
                                    $this->addRule($string ." flowid ". $my_id);
                                    break;
                              }
                           }
                        }
                        else {

                           foreach($tmp_array as $tmp_arr) {

                              switch($pipe->pipe_direction) {

                                 case UNIDIRECTIONAL:
                                    $string = str_replace("[HOST_DEFS]", $tmp_str . "match ip [DIR2] ". $dst_host, $tmp_arr);
                                    $string = str_replace("[DIR1]", "src", $string);
                                    $string = str_replace("[DIR2]", "dst", $string);
                                    $this->addRule($string ." flowid ". $my_id);
                                    break;

                                 case BIDIRECTIONAL:
                                    $string = str_replace("[HOST_DEFS]", $tmp_str . "match ip [DIR2] ". $dst_host, $tmp_arr);
                                    $string = str_replace("[DIR1]", "src", $string);
                                    $string = str_replace("[DIR2]", "dst", $string);
                                    $this->addRule($string ." flowid ". $my_id);
                                    $string = str_replace("[HOST_DEFS]", $tmp_str . "match ip [DIR2] ". $dst_host, $tmp_arr);
                                    $string = str_replace("[DIR1]", "dst", $string);
                                    $string = str_replace("[DIR2]", "src", $string);
                                    $this->addRule($string ." flowid ". $my_id);
                                    break;
                              }
                           }
                        }
                     }
                     else {

                        if(preg_match("/(.*):(.*):(.*):(.*):(.*):(.*)/", $dst_host))
                           list($dm1, $dm2, $dm3, $dm4, $dm5, $dm6) = preg_split("/:/", $dst_host);
                        else
                           list($dm1, $dm2, $dm3, $dm4, $dm5, $dm6) = preg_split("/-/", $dst_host);

                        foreach($tmp_array as $tmp_arr) {

                           switch($pipe->pipe_direction) {

                              case UNIDIRECTIONAL:
                                 $string = str_replace("[HOST_DEFS]", $tmp_str . "match ip [DIR2] ". $dst_host, $tmp_arr);
                                 $string = str_replace("[DIR1]", "src", $string);
                                 $string = str_replace("[DIR2]", "dst", $string);
                                 $this->addRule($string ." flowid ". $my_id);
                                 break;

                              case BIDIRECTIONAL:
                                 $string = str_replace("[HOST_DEFS]", $tmp_str, $tmp_arr);
                                 $string = str_replace("[DIR1]", "match u16 0x0800 0xffff at -2 match u16 0x". $sm5 . $sm6 ." 0xffff at -4 match u32 0x". $sm1 . $sm2 . $sm3 . $sm4 ." 0xffffffff at -8", $string);
                                 $string = str_replace("[DIR2]", "match u16 0x0800 0xffff at -2 match u32 0x". $dm3 . $dm4 . $dm5 .$dm6 ." 0xffffffff at -12 match u16 0x". $dm1 . $dm2 ." 0xffff at -14", $string);
                                 $this->addRule($string ." flowid ". $my_id);
                                 $string = str_replace("[HOST_DEFS]", $tmp_str, $tmp_arr);
                                 $string = str_replace("[DIR1]", "match u16 0x0800 0xffff at -2 match u32 0x". $sm3 . $sm4 . $sm5 .$sm6 ." 0xffffffff at -12 match u16 0x". $sm1 . $sm2 ." 0xffff at -14", $string);
                                 $string = str_replace("[DIR2]", "match u16 0x0800 0xffff at -2 match u16 0x". $dm5 . $dm6 ." 0xffff at -4 match u32 0x". $dm1 . $dm2 . $dm3 . $dm4 ." 0xffffffff at -8", $string);
                                 $this->addRule($string ." flowid ". $my_id);
                                 break;

                           }
                        }
                     }
                  }
               }
            }
            else {

               foreach($tmp_array as $tmp_arr)
                  $this->addRule(str_replace("[HOST_DEFS]", "u32", $tmp_arr) ." flowid ". $my_id);

            }
	    
            break;

         case 'ipt':

            $match_str = "";
            $cnt= 0;
            $str_p2p   = "";
            $match_ary = Array();
            $proto_ary = Array();

	         // Construct a string with all used ipt matches
 
            /* If this filter should match on ftp data connections add the rules here */
            if($filter->filter_match_ftp_data == "Y") {
               $this->addRule(IPT_BIN ." -t mangle -A ms-chain-". $this->getName() ."-". $parent ." --match conntrack --ctproto tcp --ctstate RELATED,ESTABLISHED --match helper --helper ftp -j CLASSIFY --set-class ". $my_id);
               $this->addRule(IPT_BIN ." -t mangle -A ms-chain-". $this->getName() ."-". $parent ." --match conntrack --ctproto tcp --ctstate RELATED,ESTABLISHED --match helper --helper ftp -j RETURN");
            }

            /* If this filter should match on SIP data streans (RTP / RTCP) add the rules here */
            if($filter->filter_match_sip == "Y") {
               $this->addRule(IPT_BIN ." -t mangle -A ms-chain-". $this->getName() ."-". $parent ." --match conntrack --ctproto udp --ctstate RELATED,ESTABLISHED --match helper --helper sip -j CLASSIFY --set-class ". $my_id);
               $this->addRule(IPT_BIN ." -t mangle -A ms-chain-". $this->getName() ."-". $parent ." --match conntrack --ctproto udp --ctstate RELATED,ESTABLISHED --match helper --helper sip -j RETURN");

            }

            // filter matches on protocols 
            if($filter->filter_protocol_id >= 0) {

               switch($ms->getProtocolNumberById($filter->filter_protocol_id)) {
		  
                  /* IP */
                  case 4:
                     array_push($proto_ary, " -p 6");
                     array_push($proto_ary, " -p 17");
                     break;
                  default:
                     array_push($proto_ary, " -p ". $ms->getProtocolNumberById($filter->filter_protocol_id));
                     break;
               }

               // Select for TCP flags (only valid for TCP protocol)
               if($ms->getProtocolNumberById($filter->filter_protocol_id) == 6) {

                  $str_tcpflags = "";

                  if($filter->filter_tcpflag_syn == "Y")
                     $str_tcpflags.= "SYN,";
                  if($filter->filter_tcpflag_ack == "Y")
                     $str_tcpflags.= "ACK,";
                  if($filter->filter_tcpflag_fin == "Y")
                     $str_tcpflags.= "FIN,";
                  if($filter->filter_tcpflag_rst == "Y")
                     $str_tcpflags.= "RST,";
                  if($filter->filter_tcpflag_urg == "Y")
                     $str_tcpflags.= "URG,";
                  if($filter->filter_tcpflag_psh == "Y")
                     $str_tcpflags.= "PSH,";

                  if($str_tcpflags != "")
                     $match_str.= " --tcp-flags ". substr($str_tcpflags, 0, strlen($str_tcpflags)-1) ." ". substr($str_tcpflags, 0, strlen($str_tcpflags)-1);

               }

               // Get all the used ports for IP, TCP or UDP 
               switch($ms->getProtocolNumberById($filter->filter_protocol_id)) {

                  case 4:  // IP
                  case 6:  // TCP
                  case 17: // UDP
                     $all_ports = array();
                     $cnt_ports = 0;

                     // Which ports are selected for this filter 
                     if($ports = $ms->getPorts($filter->filter_idx)) {

                        foreach($ports as $port) {
                           // If this port is definied as range or list get all the single ports 
                           $dst_ports = $ms->extractPorts($port['number']);
                           if($dst_ports != 0) {
                              foreach($dst_ports as $dst_port) {
                                 array_push($all_ports, $dst_port);
                                 $cnt_ports++;
                              }
                           }
                        }
                     }
                     break;
               }
            }
            else
               array_push($proto_ary, "");

            // Layer7 protocol matching 
            if($l7protocols = $ms->getL7Protocols($filter->filter_idx)) {
		  
               $l7_cnt = 0;
               $l7_protos = array();

               while($l7proto = $l7protocols->fetchRow()) {
                  array_push($l7_protos, $l7proto->l7proto_name);
                  $l7_cnt++;
               }
            }

            // TOS flags matching 
            if($filter->filter_tos > 0)
               $match_str.= " -m tos --tos ". $filter->filter_tos;

            // DSCP flags matching
            if($filter->filter_dscp != -1)
               $match_str.= " -m dscp --dscp-class ". $filter->filter_dscp;

            // packet length matching 
            if($filter->filter_packet_length > 0)
               $match_str.= " -m length --length ". $filter->filter_packet_length;

            // time range matching 
            if($filter->filter_time_use_range == "Y") {
               $start = strftime("%Y:%m:%d:%H:%M:00", $filter->filter_time_start);
               $stop = strftime("%Y:%m:%d:%H:%M:00", $filter->filter_time_stop);
               $match_str.= " -m time --datestart ". $start ." --datestop ". $stop;
            }
            else {
               $str_days = "";
               if($filter->filter_time_day_mon == "Y")
                  $str_days.= "Mon,";
               if($filter->filter_time_day_tue == "Y")
                  $str_days.= "Tue,";
               if($filter->filter_time_day_wed == "Y")
                  $str_days.= "Wed,";
               if($filter->filter_time_day_thu == "Y")
                  $str_days.= "Thu,";
               if($filter->filter_time_day_fri == "Y")
                  $str_days.= "Fri,";
               if($filter->filter_time_day_sat == "Y")
                  $str_days.= "Sat,";
               if($filter->filter_time_day_sun == "Y")
                  $str_days.= "Sun,";

               if($str_days != "")
                  $match_str.= " -m time --days ". substr($str_days, 0, strlen($str_days)-1);
            }

            // IPP2P matching 
            if($filter->filter_p2p_edk == "Y")
               $str_p2p.= "--edk ";
            if($filter->filter_p2p_kazaa == "Y")
               $str_p2p.= "--kazaa ";
            if($filter->filter_p2p_dc == "Y")
               $str_p2p.= "--dc ";
            if($filter->filter_p2p_gnu == "Y")
               $str_p2p.= "--gnu ";
            if($filter->filter_p2p_bit == "Y")
               $str_p2p.= "--bit ";
            if($filter->filter_p2p_apple == "Y")
               $str_p2p.= "--apple ";
            if($filter->filter_p2p_soul == "Y")
               $str_p2p.= "--soul ";
            if($filter->filter_p2p_winmx == "Y")
               $str_p2p.= "--winmx ";
            if($filter->filter_p2p_ares == "Y")
               $str_p2p.= "--ares ";

            if($str_p2p != "")
               $match_str.= " -m ipp2p ". substr($str_p2p, 0, strlen($str_p2p)-1);

            // End of match string
	 
            /* All port matches will be matched with the iptables multiport */
            /* (advantage is that src&dst matches can be done with a simple */
            /* --port */

            switch($ms->getProtocolNumberById($filter->filter_protocol_id)) {

               /* TCP, UDP or IP */
               case 4:
               case 6:
               case 17:
		  
                  if($cnt_ports > 0) {
                     switch($pipe->pipe_direction) {
                        /* 1 = incoming, 3 = both */
                        case UNIDIRECTIONAL:
                           $match_str.= " -m multiport --dport ";
                           break;
                        case BIDIRECTIONAL:
                           $match_str.= " -m multiport --port ";
                           break;
                     }

                     $j = 0;
                     for($i = 0; $i <= $cnt_ports; $i++) {
                        if($j == 0)
                           $tmp_ports = "";

                        if(isset($all_ports[$i]))
                           $tmp_ports.= $all_ports[$i] .",";

                        // with one multiport match iptables can max. match 14 single ports 
                        if($j == 14 || $i == $cnt_ports-1) {
                           $tmp_str = $match_str . substr($tmp_ports, 0, strlen($tmp_ports)-1); 
                           array_push($match_ary, $tmp_str);
                           $j = 0;
                        }
                        else 
                           $j++;
                     }
                  }
                  break;

               default:

                  // is there any l7 filter protocol we have to attach to the filter? 
                  if(isset($l7_cnt) && $l7_cnt > 0) {
                     foreach($l7_protos as $l7_proto) {
                        array_push($match_ary, $match_str ." -m layer7 --l7proto ". $l7_proto);
                     }
                  }
                  else 
                     array_push($match_ary, $match_str); 
                  break;
            }

            foreach($match_ary as $match_str) {

               $ipt_tmpl = IPT_BIN ." -t mangle -A ms-chain-". $this->getName() ."-". $parent;

               if($pipe->pipe_src_target != 0 && $pipe->pipe_dst_target == 0) {
                  $src_hosts = $this->getTargetHosts($pipe->pipe_src_target);
                  foreach($src_hosts as $src_host) {
                     foreach($proto_ary as $proto_str) {
                        if(strstr("-", $src_host) === false) {
                           $this->addRule($ipt_tmpl ." -s ". $src_host ." ". $proto_str ." ". $match_str ." -j CLASSIFY --set-class ". $my_id);
                           $this->addRule($ipt_tmpl ." -s ". $src_host ." ". $proto_str ." ". $match_str ." -j RETURN");
                        }
                        else {
                           $this->addRule($ipt_tmpl ." -m iprange --src-range ". $src_host ." ". $proto_str ." ". $match_str ." -j CLASSIFY --set-class ". $my_id);
                           $this->addRule($ipt_tmpl ." -m iprange --src-range ". $src_host ." ". $proto_str ." ". $match_str ." -j RETURN");
                        }
                     }
                  }
               }
               elseif($pipe->pipe_src_target == 0 && $pipe->pipe_dst_target != 0) {
                  $dst_hosts = $this->getTargetHosts($pipe->pipe_dst_target);
                  foreach($dst_hosts as $dst_host) {
                     foreach($proto_ary as $proto_str) {
                        if(strstr("-", $dst_host) === false) {
                           $this->addRule($ipt_tmpl ." -d ". $dst_host ." ". $proto_str ." ". $match_str ." -j CLASSIFY --set-class ". $my_id);
                           $this->addRule($ipt_tmpl ." -d ". $dst_host ." ". $proto_str ." ". $match_str ." -j RETURN");
                        }
                        else {
                           $this->addRule($ipt_tmpl ." -m iprange --dst-range ". $dst_host ." ". $proto_str ." ". $match_str ." -j CLASSIFY --set-class ". $my_id);
                           $this->addRule($ipt_tmpl ." -m iprange --dst-range ". $dst_host ." ". $proto_str ." ". $match_str ." -j RETURN");
                        }
                     }
                  }
               }
               elseif($pipe->pipe_src_target != 0 && $pipe->pipe_dst_target != 0) {
                  $src_hosts = $this->getTargetHosts($pipe->pipe_src_target);
                  $dst_hosts = $this->getTargetHosts($pipe->pipe_dst_target);
                  foreach($src_hosts as $src_host) {
                     foreach($dst_hosts as $dst_host) {
                        foreach($proto_ary as $proto_str) {
                           if(strstr("-", $dst_host) === false) {
                              $this->addRule($ipt_tmpl ." -s ". $src_host ." -d ". $dst_host ." ". $proto_str ." ". $match_str ." -j CLASSIFY --set-class ". $my_id);
                              $this->addRule($ipt_tmpl ." -s ". $src_host ." -d ". $dst_host ." ". $proto_str ." ". $match_str ." -j RETURN");
                           }
                           else {
                              $this->addRule($ipt_tmpl ." -m iprange --src-range ". $src_host ." --dst-range ". $dst_host ." ". $proto_str ." ". $match_str ." -j CLASSIFY --set-class ". $my_id);
                              $this->addRule($ipt_tmpl ." -m iprange --src-range ". $src_host ." --dst-range ". $dst_host ." ". $proto_str ." ". $match_str ." -j RETURN");

                           }
                        }
                     }
                  }
               }
               elseif($pipe->pipe_src_target == 0 && $pipe->pipe_dst_target == 0) {
                  foreach($proto_ary as $proto_str) {
                     $this->addRule($ipt_tmpl ." ". $proto_str ." ". $match_str ." -j CLASSIFY --set-class ". $my_id);
                     $this->addRule($ipt_tmpl ." ". $proto_str ." ". $match_str ." -j RETURN");
                  }
               }
            }
            break;

      }

   } // addPipeFilter()

   private function addFallbackFilter($parent, $filter)
   {
      global $ms;

      switch($ms->getOption("filter")) {
         default:
         case 'tc':
            $this->addRule(TC_BIN ." filter add dev ". $this->getName() ." parent ". $parent ." protocol all prio 5 u32 match u32 0 0 flowid ". $filter);
            break;
         case 'ipt':
            $this->addRule(IPT_BIN ." -t mangle -A ms-chain-". $this->getName() ."-". $parent ." -j CLASSIFY --set-class ". $filter);
            $this->addRule(IPT_BIN ." -t mangle -A ms-chain-". $this->getName() ."-". $parent ." -j RETURN");
            break;
      }

   } // addFallbackFilter()

   private function addMatchallFilter($parent, $filter = "")
   {
      global $ms;

      switch($ms->getOption("filter")) {
         case 'tc':
            $this->addRule(TC_BIN ." filter add dev ". $this->getName() ." parent ". $parent ." protocol all prio 2 u32 match u32 0 0 classid ". $filter);
            break;

         case 'ipt':
            if($ms->getOption("msmode") == "router") {
               //$this->addRule(IPT_BIN ." -t mangle -A ms-forward -o ". $this->getName() ." -j ms-chain-". $this->getName() ."-". $filter);
               $this->addRule(IPT_BIN ." -t mangle -A ms-forward -o ". $this->getName() ." -j MARK --set-mark ". $ms->getConnmarkId($this->getId(), $filter));
               $this->addRule(IPT_BIN ." -t mangle -A ms-forward -o ". $this->getName() ." -j RETURN");
            }
            elseif($ms->getOption("msmode") == "bridge") {
               $this->addRule(IPT_BIN ." -t mangle -A ms-forward -m physdev --physdev-in ". $this->getName() ." -j MARK --set-mark ". $ms->getConnmarkId($this->getId(), $filter));
               $this->addRule(IPT_BIN ." -t mangle -A ms-forward -m physdev --physdev-in ". $this->getName() ." -j RETURN");
            }
            break;

      }

   } // addMatchallFilter()

   /**
    * build chain-ruleset
    *
    * this function will build up the chain-ruleset necessary
    * for the provided network path and direction.
    */
   public function buildChains($netpath_idx, $direction)
   {
      global $ms;

      $this->addRuleComment("Rules for interface ". $this->getName());
      $chains = $this->getChains($netpath_idx);

      while($chain = $chains->fetchRow()) {

         // prepare class identifiers for the now to-be-handled chain
         $this->current_chain += 1;
         $this->current_class  = 1;
         $this->current_pipe   = 1;
         $this->current_filter = 1;

         $this->addRuleComment("chain ". $chain->chain_name ."");
         /* chain doesn't ignore QoS? */
         if($chain->chain_sl_idx != 0)
            $this->addClass("1:1", "1:". $this->get_current_chain() . $this->get_current_class(), $ms->get_service_level($chain->chain_sl_idx), $direction);

         /* remember the assigned chain id */
         $this->setChainID($chain->chain_idx, "1:". $this->get_current_chain() . $this->get_current_class(), "dst", "src");

         if($ms->getOption("filter") == "ipt") {
            $this->addRule(IPT_BIN ." -t mangle -N ms-chain-". $this->getName() ."-1:". $this->get_current_chain() . $this->get_current_filter());
            $this->addRule(IPT_BIN ." -t mangle -A ms-postrouting -m connmark --mark ". $ms->getConnmarkId($this->getId(), "1:". $this->get_current_chain() . $this->get_current_filter()) ." -j ms-chain-". $this->getName() ."-1:". $this->get_current_chain() . $this->get_current_filter());
         }

         /*if($chain->chain_sl_idx == 0)
            $filter_flow_target = "1:1";
         else*/
         $filter_flow_target = "1:". $this->get_current_chain() . $this->get_current_filter();
		   
         /* setup the filter definition to match traffic which should go into this chain */
         if($chain->chain_src_target != 0 || $chain->chain_dst_target != 0) {
            $this->addHostFilter("1:1", "host", $chain, $filter_flow_target, $direction);
         } else {
            $this->addMatchallFilter("1:1", $filter_flow_target);
         }

         /* chain does ignore QoS? then skip further processing */
         if($chain->chain_sl_idx == 0)
            continue;

         /* chain uses fallback service level? if no, add a qdisc
            and skip further processing of this chain
         */
         if($chain->chain_fallback_idx == 0) {
            $this->addRuleComment("chain without fallback service level");
            $this->addSubQdisc($this->get_current_chain() . $this->get_current_class() .":", "1:". $this->get_current_chain() . $this->get_current_class(), $ms->get_service_level($chain->chain_sl_idx));
            continue;
         }

         $this->addRuleComment("generating pipes for ". $chain->chain_name ."");
         $this->buildPipes($chain->chain_idx, "1:". $this->get_current_chain() . $this->get_current_class(), $direction);

         // Fallback
         $this->addRuleComment("fallback pipe");
         $this->addClass("1:". $this->get_current_chain() . $this->get_current_class(), "1:". $this->get_current_chain() ."00", $ms->get_service_level($chain->chain_fallback_idx), $direction, $ms->get_service_level($chain->chain_sl_idx));
         $this->addSubQdisc($this->get_current_chain() ."00:", "1:". $this->get_current_chain() ."00", $ms->get_service_level($chain->chain_fallback_idx));
         $this->addFallbackFilter("1:". $this->get_current_chain() . $this->get_current_class(), "1:". $this->get_current_chain() ."00");
         $this->setPipeID(-1, $chain->chain_idx, "1:". $this->get_current_chain() ."00");

      }

   } // buildChains()

   private function getChains($netpath_idx)
   {
      global $ms, $db;

      $sth = $db->db_prepare("
         SELECT
            *
         FROM
            ". MYSQL_PREFIX ."chains
         WHERE
            chain_active='Y'
         AND
            chain_netpath_idx LIKE ?
         AND
            chain_host_idx LIKE ?
         ORDER BY
            chain_position ASC
      ");

      $result = $db->db_execute($sth, array(
         $netpath_idx,
         $ms->get_current_host_profile(),
      ));

      $db->db_sth_free($sth);
      return $result;

   } // getChains()

   /* build ruleset for incoming pipes */
   private function buildPipes($chain_idx, $my_parent, $chain_direction)
   {
      global $ms, $db;

      /* get all active pipes for this chain */
      $sth = $db->db_prepare("
         SELECT
            p.pipe_idx,
            p.pipe_active,
            apc.apc_sl_idx,
            apc.apc_pipe_active
         FROM
            ". MYSQL_PREFIX ."pipes p
         INNER JOIN
            ". MYSQL_PREFIX ."assign_pipes_to_chains apc
         ON
            p.pipe_idx=apc.apc_pipe_idx
         WHERE
            p.pipe_active='Y'
         AND
            apc.apc_chain_idx LIKE ?
         ORDER BY
            apc.apc_pipe_pos ASC"
      );

      $active_pipes = $db->db_execute($sth, array(
         $chain_idx
      ));

      $db->db_sth_free($sth);

      while($active_pipe = $active_pipes->fetchRow()) {

         // if pipe has been locally (for this chain) disabled, we can skip it.
         if($active_pipe->apc_pipe_active != 'Y')
            continue;

         // now load Pipe as object
         $pipe = new Pipe($active_pipe->pipe_idx);

         $this->current_pipe+= 0x1;

         $my_id = "1:". $this->get_current_chain() . $this->get_current_pipe();
         $this->addRuleComment("pipe ". $pipe->pipe_name ."");

         // check if pipes original service level has been overruled locally
         // for this chain. if so, we proceed with the local service level.
         if(isset($active_pipe->apc_sl_idx) && !empty($active_pipe->apc_sl_idx)) {
            $pipe->pipe_sl_idx = $active_pipe->apc_sl_idx;
         }

         $sl = $ms->get_service_level($pipe->pipe_sl_idx);

         /* add a new class for this pipe */
         $this->addClass($my_parent, $my_id, $sl, $chain_direction);
         $this->addSubQdisc($this->get_current_chain() . $this->get_current_pipe() .":", $my_id, $sl);
         $this->setPipeID($pipe->pipe_idx, $chain_idx, "1:". $this->get_current_chain() . $this->get_current_pipe());

         /* get the nescassary parameters */
         $filters = $ms->getFilters($pipe->pipe_idx);

         /* no filter selected */
         if($filters->numRows() <= 0) {
            $this->addPipeFilter($my_parent, "pipe_filter", NULL, $my_id, $pipe, $chain_direction);
            continue;
         }

         while($filter = $filters->fetchRow()) {
            $detail = new Filter($filter->apf_filter_idx);
            $this->addPipeFilter($my_parent, "pipe_filter", $detail, $my_id, $pipe, $chain_direction);
         }
      }

   } // buildPipes()

   private function iptInitRulesIf() 
   {
      global $ms;

      if($ms->getOption("msmode") == "router") {
         $this->addRule(IPT_BIN ." -t mangle -A FORWARD -o ". $this->getName() ." -j ms-forward");
         $this->addRule(IPT_BIN ." -t mangle -A OUTPUT -o ". $this->getName() ." -j ms-forward");
         $this->addRule(IPT_BIN ." -t mangle -A POSTROUTING -o ". $this->getName() ." -j ms-postrouting");
      }
      else {
         $this->addRule(IPT_BIN ." -t mangle -A POSTROUTING -m physdev --physdev-out ". $this->getName() ." -j ms-postrouting");
      }

   } // iptInitRulesIf()

   /**
    * initialize the current interface
    *
    * this function which initialize the current interface, which means
    * to prepare all the necessary tc-rules and add them to the buffer
    * to be executed later when loading the rules.
    */
   public function Initialize($direction)
   {
      global $ms;

      $ack_sl = $ms->getOption("ack_sl");

      $this->addRuleComment("Initialize Interface ". $this->getName());

      $this->addRootQdisc("1:");

      /* Initial iptables rules */
      if($ms->getOption("filter") == "ipt") 
         $this->iptInitRulesIf();

      $this->addInitClass("1:", "1:1");
      $this->addInitFilter("1:0");

      /* ACK options */
      if($ack_sl != 0) {

         $this->addRuleComment("boost ACK packets");
         $this->addClass("1:1", "1:2", $ms->get_service_level($ack_sl), $direction);
         $this->addSubQdisc("2:", "1:2", $ms->get_service_level($ack_sl));
         $this->addAckFilter("1:1", "ack", "1:2", "1");

      }

      $this->setStatus(true);

   } // Initialize()

   /**
    * check if MAC address
    *
    * check if specified host consists a MAC address.
    * @return true, false
    */
   private function check_if_mac($host)
   {
      if(preg_match("/(.*):(.*):(.*):(.*):(.*):(.*)/", $host) ||
         preg_match("/(.*)-(.*)-(.*)-(.*)-(.*)-(.*)/", $host))
         return true;

      return false;

   } // check_if_mac()

   /**
    * convert an IP address into a hex value
    *
    * @param string $IP
    * @return string
    */
   private function convertIpToHex($host)
   {
      global $ms;

      $ipv4 = new Net_IPv4;
      $parsed = $ipv4->parseAddress($host);

      // if CIDR contains no netmask or was unparsable, we assume /32
      if(empty($parsed->netmask))
         $parsed->netmask = "255.255.255.255";

      if(!$ipv4->validateIP($parsed->ip))
         $ms->throwError(_("Incorrect IP address! Can not convert it to hex!"));

      if(!$ipv4->validateNetmask($parsed->netmask))
         $ms->throwError(_("Incorrect Netmask! Can not convert it to hex!"));

      if(($hex_host = $ipv4->atoh($parsed->ip)) == false)
         $ms->throwError(_("Failed to convert ". $parsed->ip ." to hex!"));

      if(($hex_subnet = $ipv4->atoh($parsed->netmask)) == false)
         $ms->throwError(_("Failed to convert ". $parsed->netmask ." to hex!"));

      return array('ip' => $hex_host, 'netmask' => $hex_subnet);

   } // convertIpToHex

   /**
    * convert an Protocol ID number into a hex value
    *
    * @param int $ProtocolId
    * @return string
    */
   private function convertProtoToHex($ProtocolId)
   {
      return sprintf("%02x", $ProtocolId);

   } // convertProtoToHex

   /**
    * convert an port number into a hex value
    *
    * @param int $PortNumber
    * @return string
    */
   private function convertPortToHex($PortNumber)
   {
      return sprintf("%04x", $PortNumber);

   } // convertPortToHex

   /* get current chain ID in hex format
    *
    * @return string
    */
   private function get_current_chain()
   {
      return sprintf("%02x", 0xff - $this->current_chain);

   } // get_current_chain()

   /* get current pipe ID in hex format
    *
    * @return string
    */
   private function get_current_pipe()
   {
      return sprintf("%02x", 0xff - $this->current_pipe);

   } // get_current_pipe()
 
   /* get current class ID in hex format
    *
    * @return string
    */
   private function get_current_class()
   {
      return sprintf("%02x", 0xff - $this->current_class);

   } // get_current_class()

   private function get_current_filter()
   {
      return sprintf("%02x", 0xff - $this->current_filter);

   } // get_current_filter()

   private function get_dscp_hex_value($dscp_class)
   {
      /* below we have to shift into 6-bit DSCP class value
         two further bits so we have the actual value we can
         match in the 8-bit long TOS field.
       */
      switch($dscp_class) {
         // AF11 = 0x0a
         case 'AF11': $dscp = 10 << 2; break;
         // AF12 = 0x0c
         case 'AF12': $dscp = 12 << 2; break;
         // AF13 = 0x0e
         case 'AF13': $dscp = 14 << 2; break;
         // AF21 = 0x12
         case 'AF21': $dscp = 18 << 2; break;
         // AF22 = 0x14
         case 'AF22': $dscp = 20 << 2; break;
         // AF23 = 0x16
         case 'AF23': $dscp = 22 << 2; break;
         // AF31 = 0x1a
         case 'AF31': $dscp = 26 << 2; break;
         // AF32 = 0x1c
         case 'AF32': $dscp = 28 << 2; break;
         // AF33 = 0x1e
         case 'AF33': $dscp = 30 << 2; break;
         // AF41 = 0x22
         case 'AF41': $dscp = 34 << 2; break;
         // AF42 = 0x24
         case 'AF42': $dscp = 36 << 2; break;
         // AF43 = 0x26
         case 'AF43': $dscp = 38 << 2; break;
         // EF = 0x2e
         case 'EF':   $dscp = 46 << 2; break;
         default:     $dscp = 0 << 2; break;
      }

      return sprintf("%02x", $dscp);

   } // get_dscp_hex_value

   /**
    * all the pending jobs for that interface
    *
    * will be called from Ruleset class.
    */
   public function finish()
   {
      if(isset($this->if_fallback_idx) && !empty($this->if_fallback_idx))
         $this->add_interface_fallback();

   } // finish()

   /**
    * add a fallback service level class to the
    * actual interface.
    */
   private function add_interface_fallback()
   {
      global $ms;

      $this->current_chain += 1;

      $this->addRuleComment("interface fallback");
      $this->addClass("1:1", "1:". $this->get_current_chain() . $this->get_current_class(), $ms->get_service_level($this->if_fallback_idx));
      $this->addSubQdisc($this->get_current_chain() . $this->get_current_class() .":", "1:". $this->get_current_chain() . $this->get_current_class(), $ms->get_service_level($this->if_fallback_idx));
      $this->addFallbackFilter("1:1", "1:". $this->get_current_chain() . $this->get_current_class());

   } // add_interface_fallback()

   /*
    * get NETEM parameter string
    *
    * this function returns the parameter string for the NETEM qdisc
    *
    * @param mixed $sl
    * @return string
    */
   function get_netem_params($sl)
   {
      if(!empty($sl->sl_netem_delay) && is_numeric($sl->sl_netem_delay)) {
         $params.= "delay ". $sl->sl_netem_delay ."ms ";

         if(!empty($sl->sl_netem_jitter) && is_numeric($sl->sl_netem_jitter)) {
            $params.= $sl->sl_netem_jitter ."ms ";

            if(!empty($sl->sl_netem_random) && is_numeric($sl->sl_netem_random)) {
               $params.= $sl->sl_netem_random ."% ";
            }
         }

         if($sl->sl_netem_distribution != "ignore") {
            $params.= "distribution ". $sl->sl_netem_distribution ." ";
         }
      }

      if(!empty($sl->sl_netem_loss) && is_numeric($sl->sl_netem_loss)) {
         $params.= "loss ". $sl->sl_netem_loss ."% ";
      }

      if(empty($sl->sl_netem_duplication) && is_numeric($sl->sl_netem_duplication)) {
         $params.= "duplicate ". $sl->sl_netem_duplication ."% ";
      }

      if(empty($sl->sl_netem_gap) && is_numeric($sl->sl_netem_gap)) {
         $params.= "gap ". $sl->sl_netem_gap ." ";
      }

      if(!empty($sl->sl_netem_reorder_percentage) && is_numeric($sl->sl_netem_reorder_percentage)) {
         $params.= "reorder ". $sl->sl_netem_reorder_percentage ."% ";
         if(!empty($sl->sl_netem_reorder_correlation) && is_numeric($sl->sl_netem_reorder_correlation )) {
            $params.= $sl->sl_netem_reorder_correlation ."% ";
         }
      }

      return $params;

   } // get_netem_params()

   /**
    * get ESFQ parameter string
    *
    * this function returns the parameter string for the ESFQ qdisc
    *
    * @param mixed $sl
    * @return string
    */
   function get_esfq_params($sl)
   {
      $params = "";

      if(!empty($sl->sl_esfq_perturb) && is_numeric($sl->sl_esfq_perturb))
         $params.= "perturb ". $sl->sl_esfq_perturb ." ";

      if(!empty($sl->sl_esfq_limit) && is_numeric($sl->sl_esfq_limit))
         $params.= "limit ". $sl->sl_esfq_limit ." ";

      if(!empty($sl->sl_esfq_depth) && is_numeric($sl->sl_esfq_depth))
         $params.= "depth ". $sl->sl_esfq_depth ." ";

      if(!empty($sl->sl_esfq_divisor) && is_numeric($sl->sl_esfq_divisor))
         $params.= "divisor ". $sl->sl_esfq_divisor ." ";

      if(!empty($sl->sl_esfq_hash))
         $params.= "hash ". $sl->sl_esfq_hash;

      return $params;

   } // get_esfq_params()

} // class Ruleset_Interface

?>
