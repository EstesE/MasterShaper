<?php

class MASTERSHAPER_PAGE {

   public $rights;

   public function handler()
   {
      global $tmpl, $page, $ms;

      if(isset($this->rights)) {
         /* If authentication is enabled, check permissions */
         if($ms->getOption("authentication") == "Y" && !$ms->checkPermissions($this->rights)) {
            $ms->throwError("<img src=\"". ICON_CHAINS ."\" alt=\"chain icon\" />&nbsp;". _("Manage Chains"), _("You do not have enough permissions to access this module!"));
            return 0;
         }
      }

      switch($page->action) {
         case 'overview':
         case 'chains':
         case 'pipes':
         case 'bandwidth':
         case 'options':
         case 'about':
         case 'tasklist':
         case 'update-iana':
         case 'update-l7':
         case 'list':
            $content = $this->showList();
            break;
         case 'edit':
         case 'new':
            $content = $this->showEdit();
            break;
      }

      if($ms->get_header('Location')) {
         Header('Location: '. $ms->get_header('Location'));
         return false;
      }

      if(isset($content))
         $tmpl->assign('content', $content);

   } // handler()

   /**
    * returns true if storing is requested
    *
    * @return bool
    */
   public function is_storing()
   {
      if(!isset($_POST['action']) || empty($_POST['action']))
         return false;

      if($_POST['action'] == 'store')
         return true;

      return false;

   } // is_storing()

} // MASTERSHAPER_PAGE()
