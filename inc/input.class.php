<?php
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}
class PluginMachinecheckerInput extends CommonDBTM
{
   static $rightname                   = 'computer';   
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
      
      echo "<div align='center'><table class='tab_cadre_fixe' cellpadding='5'>";
      echo "<table class='tab_cadre'>";
      echo "<tr><th colspan='2'>Machine checker</th></tr>";
      echo "<tr class='tab_bg_1'><td align='center'><table><tr>";
      echo "<FORM ACTION=\"" . $_SERVER['PHP_SELF'] . "\" METHOD=\"POST\">";
      echo "<td width=\"300px\"><div id=\"debug\"><h2>".__('SearchList','machinechecker')."</h2></div>";
      echo "<td><TEXTAREA NAME=\"computer_list\" id=\"computer_list\" ROWS=20 COLS=50 type=\"text\">";
      $query = "select name
                from glpi_plugin_machinechecker_inputs";
      $result = $DB->query($query) or die("Query failed:" . $DB->error());
      while ($row = $result->fetch_assoc()) {
         if (isset($row)) {
            echo htmlentities($row['name']) . "\r\n";
         }
      }
      echo "</TEXTAREA></td></tr></table>";
      echo "<br />";
      echo "<INPUT TYPE=\"hidden\" name=\"showresult\" VALUE=\"1\">";
      echo "<INPUT class=\"submit\" TYPE=\"submit\" VALUE=\"".__('Clear')."\" name=\"reset\" align=\"center\">&nbsp";
      echo "<INPUT class=\"submit\" TYPE=\"submit\" VALUE=\"".__('Search')."\" name=\"sendform\" align=\"center\">";
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
   
   
   function getSearchOptions()
   {
      global $DB;
   
      $tab                    = array();
      $tab['common']             = __('Characteristics');
   
      $tab[2]['table']          = $this->gettable();
      $tab[2]['field']          = 'name';
      $tab[2]['name']           = __('Name');
      $tab[2]['datatype']       = 'itemlink';
      $tab[2]['massiveaction']  = true;
      
      $tab[3]['table']          = 'glpi_computertypes';
      $tab[3]['field']          = 'name';
      $tab[3]['name']           = __('Type');
      $tab[3]['datatype']       = 'dropdown';
      
      $tab[4]['table']          = $this->gettable();
      $tab[4]['field']          = 'computers_contact';
      $tab[4]['name']           = __('Alternate username');
      $tab[4]['datatype']       = 'dropdown';
      
      $tab[5]['table']          = 'glpi_users';
      $tab[5]['field']          = 'name';
      $tab[5]['name']           = __('User');
      $tab[3]['datatype']       = 'dropdown';
      
      $tab[6]['table']          = 'glpi_locations';
      $tab[6]['field']          = 'completename';
      $tab[6]['name']           = __('Location');
      $tab[6]['datatype']       = 'dropdown';
      
      $items_device_joinparams  = array('jointype'          => 'itemtype_item',
                                        'specific_itemtype' => 'Computer');

      $tab[7]['table']          = 'glpi_devicenetworkcards';
      $tab[7]['field']          = 'designation';
      $tab[7]['name']           = _n('Network interface', 'Network interfaces', 1);
      $tab[7]['forcegroupby']   = true;
      $tab[7]['massiveaction']  = false;
      $tab[7]['datatype']       = 'string';
      $tab[7]['joinparams']     = array('beforejoin'
                                          => array('table'      => 'glpi_items_devicenetworkcards',
                                                   'joinparams' => $items_device_joinparams));

      $tab[8]['table']          = 'glpi_items_devicenetworkcards';
      $tab[8]['field']          = 'mac';
      $tab[8]['name']           = __('MAC address');
      $tab[8]['forcegroupby']   = true;
      $tab[8]['massiveaction']  = false;
      $tab[8]['datatype']       = 'string';
      $tab[8]['joinparams']     = $items_device_joinparams;
      
      $tab[9]['table']          = 'glpi_states';
      $tab[9]['field']          = 'completename';
      $tab[9]['name']           = __('Status');
      $tab[9]['datatype']       = 'dropdown';
      
      //Check for OcsNg PluginMachinecheckerInputs
      $query = "SELECT state
                FROM glpi_plugins
                where directory='ocsinventoryng'";
      $result = $DB->query($query) or die("Query failed:" . $DB->error());
      while ($row = $result->fetch_assoc()) {
         if (isset($row)) {
            if ($row['state']==1) {
               $tab[10]['table']          = $this->gettable();
               $tab[10]['field']          = 'last_ocs_update';
               $tab[10]['name']           = __('Last OCSNG inventory date', 'ocsinventoryng');
               $tab[10]['datatype']       = 'datetime';
            }
         }
      }
//Ajout tab pour le multi entité
      $tab[] = [
         'id'                 => '11',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'datatype'           => 'dropdown'
      ];

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
      $MissingStatusIdquery = "SELECT id FROM glpi_states where name = '".__('MissingStatus','machinechecker')."'";
      $result = $DB->query($MissingStatusIdquery) or die($DB->error());
      while ($row = $result->fetch_assoc()) {
      if (isset($row)) {
         $MissingStatusId = $row['id'];
      }
      $nb = substr_count( $ComputerList, '\r\n' );
      $ComputerList = explode('\r\n', $ComputerList);
      foreach ($ComputerList as $value):
         if (strlen($value) != 0) {
            $ExpValue = explode(".", $value, 2);
            $value = $ExpValue[0];
            $query = "SELECT
               glpi_computers.id as computers_id,
               glpi_computers.name as computers_name,
               glpi_computertypes.id as computertypes_id,
               glpi_computers.contact as computers_contact,
               glpi_users.id as users_id,
               glpi_locations.id as locations_id,
               glpi_states.id as states_id,
               glpi_entities.id as entities_id
               from glpi_computers
               left join glpi_computertypes on glpi_computers.computertypes_id=glpi_computertypes.id
               left join glpi_locations ON glpi_computers.locations_id=glpi_locations.id
               left join glpi_states ON glpi_computers.states_id=glpi_states.id
               left join glpi_users ON glpi_computers.users_id=glpi_users.id
               left join glpi_entities ON glpi_computers.entities_id=glpi_entities.id
               where glpi_computers.name='" . $value . "'";
            $result = $DB->query($query) or die("Query failed:" . $DB->error());
            if ($DB->numrows($result) == 0) {
               $insert_query = "insert into glpi_plugin_machinechecker_inputs
               (id,name,computertypes_id,computers_contact,users_id,locations_id,states_id,last_ocs_update,entities_id)
               values
               ('0','" . $value . "',NULL,NULL,NULL,NULL,'".$MissingStatusId."',NULL,NULL)";
               $DB->query($insert_query) or die("Query failed:" . $DB->error());
               $i++;
               Html::changeProgressBarPosition($i, $nb+1 ,"$i / $nb");
               continue;
            }
            while ($row = $DB->fetch_array($result)) {
               //Get ocsng last_ocs_update if available
               $OcsState = "SELECT state
               FROM glpi_plugins
               where directory='ocsinventoryng'";
               $resultOcsState = $DB->query($OcsState) or die("Query failed:" . $DB->error());
               while ($rowOcsState = $resultOcsState->fetch_assoc()) {
                  if (isset($rowOcsState)) {
                     if ($rowOcsState['state']==1) {
                        $OcsQuery = "SELECT last_ocs_update
                        from glpi_plugin_ocsinventoryng_ocslinks
                        where glpi_plugin_ocsinventoryng_ocslinks.computers_id = '".$row['computers_id']."'";
                        $resultOCS = $DB->query($OcsQuery) or die("Query failed:" . $DB->error());
                        if ($DB->numrows($resultOCS) == 0) { 
                           $last_ocs_update="NULL";
                        }
                        while ($rowOCS = $DB->fetch_array($resultOCS)) {
                           $last_ocs_update=$rowOCS['last_ocs_update'];
                        }
                     }
                  }
               }
               //Check for empty string
               if (empty($row['users_id'])) {
                  $users_id="NULL";
               } else {
                  $users_id=$row['users_id'];
               }
               if (empty($row['computertypes_id'])) {
                  $computertypes_id="NULL";
               } else {
                  $computertypes_id=$row['computertypes_id'];
               }
               if (empty($row['locations_id'])) {
                  $locations_id="NULL";
               } else {
                  $locations_id=$row['locations_id'];
               }
               if (empty($last_ocs_update)) {
                  $last_ocs_update="NULL";
               }
               if (empty($row['entities_id'])) {
                  $entities_id="NULL";
               } else {
                  $entities_id=$row['entities_id'];
               }
               $insert_query = "insert into glpi_plugin_machinechecker_inputs
               (id,name,computertypes_id,computers_contact,users_id,locations_id,states_id,last_ocs_update,entities_id)
               values
               ('" . $row['computers_id'] . "','" . $row['computers_name'] . "',$computertypes_id,'" . $row['computers_contact'] . "',$users_id,$locations_id,'" . $row['states_id']."','$last_ocs_update','$entities_id')";
               $DB->query($insert_query) or die("Query failed:" . $DB->error());
               $i++;
               Html::changeProgressBarPosition($i, $nb+1 ,"$i / $nb");
            }
         }
      endforeach;
   }
}
}
?>