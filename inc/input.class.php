<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
   
   
   function rawSearchOptions() {
      global $CFG_GLPI,$DB;

      $tab = [];
      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];
   
      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'itemlink'
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => 'glpi_computertypes',
         'field'              => 'name',
         'name'               => __('Type'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => 'glpi_plugin_machinechecker_inputs',
         'field'              => 'name',
         'name'               => __('Alternate username'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'computers_contact',
         'name'               => __('Alternate username'),
         'datatype'           => 'dropdown',
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'name'               => __('User'),
         'datatype'           => 'dropdown',
         'right'              => 'all'
      ];

      //Check for OcsNg PluginMachinecheckerInputs
      $query = $DB->request([
         'SELECT' => ['state'],
         'FROM'   => 'glpi_plugins',
         'WHERE'  => ['directory' => 'ocsinventoryng']
      ]);
      while ($row = $query->next()) {
         if ($row['state']==1) {
            $tab[] = [
            'id'                 => '10',
            'table'              => $this->getTable(),
            'field'              => 'last_ocs_update',
            'name'               => __('Last OCSNG inventory date', 'ocsinventoryng'),
            'datatype'           => 'datetime'
            ];
         }
      }

      $tab = array_merge($tab, Location::rawSearchOptionsToAdd());
      
      $items_device_joinparams   = ['jointype'          => 'itemtype_item',
                                    'specific_itemtype' => 'Computer'];

      $tab[] = [
         'id'                 => '7',
         'table'              => 'glpi_devicenetworkcards',
         'field'              => 'designation',
         'name'               => __('Network interface'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'string',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_items_devicenetworkcards',
               'joinparams'         => $items_device_joinparams
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => 'glpi_items_devicenetworkcards',
         'field'              => 'mac',
         'name'               => __('MAC address'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'string',
         'joinparams'         => $items_device_joinparams
      ];
      
      $tab[] = [
         'id'                 => '9',
         'table'              => 'glpi_states',
         'field'              => 'completename',
         'name'               => __('Status'),
         'datatype'           => 'dropdown',
         'condition'          => '`is_visible_computer`'
      ];
      
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
               $random_computers_id="SELECT random_num
               FROM (
               SELECT FLOOR(RAND() * 99999) AS random_num
               UNION
               SELECT FLOOR(RAND() * 99999) AS random_num
               ) AS numbers_mst_plus_1
               WHERE `random_num` NOT IN (SELECT id FROM glpi_computers)
               AND `random_num` NOT IN (SELECT id FROM glpi_plugin_machinechecker_inputs)
               LIMIT 1";
            $resultRnd = $DB->query($random_computers_id) or die("Query failed:" . $DB->error());
               while ($rowRnd = $DB->fetch_array($resultRnd)) {
                  $RndID=$rowRnd['random_num'];
               }
               $insert_query = "insert into glpi_plugin_machinechecker_inputs
               (id,name,computertypes_id,computers_contact,users_id,locations_id,states_id,last_ocs_update,entities_id)
               values
               (".$RndID.",'" . $value . "',NULL,NULL,NULL,NULL,'".$MissingStatusId."',NULL,NULL)";
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
               //Check for double input of same computer
               $query = $DB->request([
                  'SELECT' => ['id'],
                   'FROM'   => $this->getTable(),
                  'WHERE'  => ['id' =>  $row['computers_id']]
               ]);
               if (count($query)) {
                  continue;
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