<?php
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}
class PluginMachinecheckerInputs extends CommonDBTM
{
   static $rightname = "plugin_machinechecker";
   
   static function getTypeName($nb = 0)
   {
      return "Machine Checker";
   }
   
   static function canView()
   {
      return Session::haveRight(self::$rightname, READ);
   }
   
   static function DisplayInputsForm()
   {
      global $DB;
      // $UserID=Session::getLoginUserID();
      // echo "USer: ".$UserID;
      echo "<div align='center'><table class='tab_cadre_fixe' cellpadding='5'>";
      echo "<table class='tab_cadre'>";
      echo "<tr><th colspan='2'>Machine checker</th></tr>";
      echo "<tr class='tab_bg_1'><td align='center'><table><tr>";
      echo "<FORM ACTION=\"" . $_SERVER['PHP_SELF'] . "\" METHOD=\"POST\">";
      echo "<td width=\"300px\"><div id=\"debug\"><h2>Entrer la liste de machine a rechercher:</h2></div></td>";
      echo "<td><TEXTAREA NAME=\"computer_list\" id=\"computer_list\" ROWS=20 COLS=50 type=\"text\">";
      $query = "select computers_name
				from glpi_plugin_machinechecker_inputs";
      $result = $DB->query($query) or die("Query failed:" . $DB->error());
      while ($row = $result->fetch_assoc())
         if (isset($row)) {
            echo htmlentities($row['computers_name']) . "\r\n";
         }
      echo "</TEXTAREA></td></tr></table>";
      echo "<br />";
      echo "<INPUT TYPE=\"hidden\" name=\"showresult\" VALUE=\"1\">";
      echo "<INPUT class=\"submit\" TYPE=\"submit\" VALUE=\"Effacer\" name=\"reset\" align=\"center\">&nbsp";
      echo "<INPUT class=\"submit\" TYPE=\"submit\" VALUE=\"Rechercher\" name=\"sendform\" align=\"center\">";
      Html::closeForm();
      echo "</td></tr>";
      echo "</table>";
      echo "<br />";
   }
   
   
   static function ClearDatabase()
   {
      global $DB;
      $cleanup_query="truncate table glpi_plugin_machinechecker_inputs";
      $DB->query($cleanup_query) or die("Query failed:". $DB->error());   
   }
   
   
   
   
   function getSpecificMassiveActions($checkitem = NULL)
   {
      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);
      if ($isadmin) {
         $actions['Computer_Item' . MassiveAction::CLASS_ACTION_SEPARATOR . 'add']            = _x('button', 'Connect');
         $actions['Computer_SoftwareVersion' . MassiveAction::CLASS_ACTION_SEPARATOR . 'add'] = _x('button', 'Install');
      }
      if ($isadmin) {
         MassiveAction::getAddTransferList($actions);
      }
      return $actions;
   }
   static function showMassiveActionsSubForm(MassiveAction $ma)
   {
      switch ($ma->getAction()) {
         case 'plugin_certificates_add_item':
            self::dropdownCertificate(array());
            echo "&nbsp;" . Html::submit(_x('button', 'Post'), array(
               'name' => 'massiveaction'
            ));
            return true;
         case "install":
            Dropdown::showAllItems("item_item", 0, 0, -1, self::getTypes(true), false, false, 'typeitem');
            echo Html::submit(_x('button', 'Post'), array(
               'name' => 'massiveaction'
            ));
            return true;
            break;
         case "uninstall":
            Dropdown::showAllItems("item_item", 0, 0, -1, self::getTypes(true), false, false, 'typeitem');
            echo Html::submit(_x('button', 'Post'), array(
               'name' => 'massiveaction'
            ));
            return true;
            break;
         case "transfer":
            Dropdown::show('Entity');
            echo Html::submit(_x('button', 'Post'), array(
               'name' => 'massiveaction'
            ));
            return true;
            break;
      }
      return parent::showMassiveActionsSubForm($ma);
   }
   function getSearchOptions()
   {
      //$UserID=Session::getLoginUserID();
      $tab                    = array();
      $tab['common']          = 'computers';
      $tab[1]['table']        = $this->gettable();
      $tab[1]['field']        = 'computers_name';
      $tab[1]['name']         = 'Ordinateur'; 
      $tab[2]['table']        = $this->gettable();
      $tab[2]['field']        = 'computertypes_name';
      $tab[2]['name']         = 'Type';
      $tab[3]['table']        = $this->gettable();
      $tab[3]['field']        = 'computers_contact';
      $tab[3]['name']         = 'Contacts';
      $tab[4]['table']        = $this->gettable();
      $tab[4]['field']        = 'users_name';
      $tab[4]['name']         = 'Username';
      $tab[5]['table']        = $this->gettable();
      $tab[5]['field']        = 'locations_completename';
      $tab[5]['name']         = 'Lieu';
      $tab[6]['table']        = $this->gettable();
      $tab[6]['field']        = 'states_name';
      $tab[6]['name']         = 'Status';
      return $tab;
   }
   
   function DoTheJob($ComputerList)
   {
      global $DB;
	  $_SESSION['PluginMachinecheckerInputs'] = '1';
	  
	  //clean db before add data
	  self::ClearDatabase();
      echo "<div class='center'>";
      echo "<table class='tab_cadrehov'><tr><th>".__('Work in progress...')."</th></tr>";
      echo "<tr class='tab_bg_2'><td>";
      Html::createProgressBar(__('Work in progress...'));
      echo "</td></tr></table></div>\n";
	  $i = 0;
	  $nb = substr_count( $ComputerList, '\r\n' );
      $ComputerList = explode('\r\n', $ComputerList);
      foreach ($ComputerList as $value):
         if (strlen($value) != 0) {
            $query = "select 
               glpi_computers.id as computers_id,
               glpi_computers.name as computers_name,
               glpi_computertypes.name as computertypes_name,
               glpi_computers.contact as computers_contact,
               glpi_users.name as users_name,
               glpi_locations.completename as locations_completename,
               glpi_states.name as states_name
               from glpi_computers
               left join glpi_computertypes on glpi_computers.computertypes_id=glpi_computertypes.id
               left join glpi_locations ON glpi_computers.locations_id=glpi_locations.id
               left join glpi_states ON glpi_computers.states_id=glpi_states.id
               left join glpi_users ON glpi_computers.users_id=glpi_users.id
               where glpi_computers.name='" . $value . "'";
            $result = $DB->query($query) or die("Query failed:" . $DB->error());
            if ($DB->numrows($result) == 0) {
               $insert_query = " insert into glpi_plugin_machinechecker_inputs
               (id,computers_id,computers_name,computertypes_name,computers_contact,users_name,locations_completename,states_name)
               values
               (NULL,'','" . $value . "','','','','','Absent de glpi')";
               $DB->query($insert_query) or die("Query failed:" . $DB->error());
               $i++;
               Html::changeProgressBarPosition($i, $nb+1 ,"$i / $nb");
            }
            while ($row = $DB->fetch_array($result)) {
               $insert_query = " insert into glpi_plugin_machinechecker_inputs
               (id,computers_id,computers_name,computertypes_name,computers_contact,users_name,locations_completename,states_name)
               values
               (NULL,'" . $row['computers_id'] . "','" . $row['computers_name'] . "','" . $row['computertypes_name'] . "','" . $row['computers_contact'] . "','" . $row['users_name']. "','" .htmlspecialchars($row['locations_completename']). "','" . $row['states_name']."')";
               $DB->query($insert_query) or die("Query failed:" . $DB->error());
               $i++;
               Html::changeProgressBarPosition($i, $nb+1 ,"$i / $nb");
            }
         }
      endforeach;
   }
}
?>
