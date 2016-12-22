<?php

function plugin_machinechecker_install()
{
   global $DB;
   include_once (GLPI_ROOT . "/plugins/machinechecker/inc/profile.class.php");
   PluginMachinecheckerProfile::initProfile();
   PluginMachinecheckerProfile::createfirstAccess($_SESSION['glpiactiveprofile']['id']);

   //create plugin table
   //Shoulb be nothing in it so drop it and recreate it
   echo "Create plugin database<br>";
   if (TableExists("glpi_plugin_machinechecker_inputs")) {
      $DeleteTable_query ="DROP TABLE `glpi_plugin_machinechecker_inputs`";
      $DB->query($DeleteTable_query) or die($DB->error());
   }
   $query ="CREATE TABLE `glpi_plugin_machinechecker_inputs` (
           `id` int(11) DEFAULT '0',
           `name` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
           `computertypes_id` int(11) DEFAULT NULL,
           `computers_contact` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
           `users_id` int(11) DEFAULT NULL,
           `locations_id` int(11) DEFAULT NULL,
           `states_id` int(11) DEFAULT NULL,
           `last_ocs_update` datetime DEFAULT NULL
           ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
   $DB->query($query) or die($DB->error());

   //clean and add default plugin table view
   echo "Adding default view<br>";
   $DeleteViewquery = "DELETE FROM `glpi_displaypreferences`  where itemtype ='PluginMachinecheckerInput'";
   $DB->query($DeleteViewquery) or die($DB->error());
   $query = "INSERT INTO `glpi_displaypreferences`
            (itemtype,num,rank,users_id)
            VALUES
            ('PluginMachinecheckerInput', '10', '9', '0'),
            ('PluginMachinecheckerInput', '9', '8', '0'),
            ('PluginMachinecheckerInput', '8', '7', '0'),
            ('PluginMachinecheckerInput', '7', '6', '0'),
            ('PluginMachinecheckerInput', '6', '5', '0'),
            ('PluginMachinecheckerInput', '5', '4', '0'),
            ('PluginMachinecheckerInput', '4', '3', '0'),
            ('PluginMachinecheckerInput', '3', '2', '0'),
            ('PluginMachinecheckerInput', '2', '1', '0')";
   $DB->query($query) or die($DB->error());
   
   //Add new status for missing computer in glpi table glpi_states

   $MachinecheckerStatusquery= "SELECT name FROM glpi_states where name = '".__('MissingStatus','machinechecker')."'";
   $result = $DB->query($MachinecheckerStatusquery) or die($DB->error());
   if (mysqli_num_rows($result)==0) {
   while ($row = $result->fetch_assoc())   
   echo "Inserting new status<br>";
   $MachinecheckerStatusInsertquery= "INSERT INTO glpi_states
                                     (`name`, `entities_id`, `is_recursive`, `states_id`, `completename`, `level`, `is_visible_computer`, `is_visible_monitor`, `is_visible_networkequipment`, `is_visible_peripheral`, `is_visible_phone`, `is_visible_printer`, `is_visible_softwareversion`)
                                     VALUES
                                     ('".__('MissingStatus','machinechecker')."', '0', '1', '0', '".__('MissingStatus','machinechecker')."', '1', '1', '0', '0', '0', '0', '0', '0')";
   $DB->query($MachinecheckerStatusInsertquery) or die($DB->error());
   }
   return true;
}

function plugin_machinechecker_uninstall()
{
   global $DB;
   $DeleteViewquery = "DELETE FROM `glpi_displaypreferences`  where itemtype ='PluginMachinecheckerInput'";
   $DB->query($DeleteViewquery) or die($DB->error());
   $DB->query("DROP TABLE IF EXISTS glpi_plugin_machinechecker_inputs");
   
   return true;
}

function plugin_machinechecker_MassiveActions($type) {
   return array (
      'PluginShellcommandsShellcommand'.MassiveAction::CLASS_ACTION_SEPARATOR."generate" => __('Command launch','shellcommands'),
      'PluginShellcommandsCommandGroup'.MassiveAction::CLASS_ACTION_SEPARATOR."generate" =>  __('Command group launch','shellcommands')
   );

}
?>