<?php

function plugin_machinechecker_install()
{
   global $DB;
   include_once (GLPI_ROOT . "/plugins/machinechecker/inc/profile.class.php");

   PluginMachinecheckerProfile::initProfile();
   PluginMachinecheckerProfile::createfirstAccess($_SESSION['glpiactiveprofile']['id']);

   //create plugin table
   //Shoulb be nothing in it so drop it and recreate it

   if (TableExists("glpi_plugin_machinechecker_inputs")) {
      $DeleteTable_query ="DROP TABLE `glpi`.`glpi_plugin_machinechecker_inputs`";
      $DB->query($DeleteTable_query) or die($DB->error());
   }
   $query ="CREATE TABLE `glpi_plugin_machinechecker_inputs` (
           `id` int(11) DEFAULT '0',
           `name` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
           `computertypes_id` int(11) DEFAULT NULL,
           `computers_contact` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
           `users_id` int(11) DEFAULT NULL,
           `locations_id` int(11) DEFAULT NULL,
           `states_id` int(11) DEFAULT NULL
           ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
   $DB->query($query) or die($DB->error());

   //clean and add default plugin table view

   $DeleteViewquery = "DELETE FROM `glpi_displaypreferences`  where itemtype ='PluginMachinecheckerInputs'";
   $DB->query($DeleteViewquery) or die($DB->error());
   $query = "INSERT INTO `glpi_displaypreferences`
            (id,itemtype,num,rank,users_id)
            VALUES
            (NULL,'PluginMachinecheckerInputs', '6', '7', '0'),
            (NULL,'PluginMachinecheckerInputs', '3', '2', '0'),
            (NULL,'PluginMachinecheckerInputs', '4', '3', '0'),
            (NULL,'PluginMachinecheckerInputs', '2', '1', '0'),
            (NULL,'PluginMachinecheckerInputs', '5', '4', '0'),
            (NULL,'PluginMachinecheckerInputs', '7', '6', '0'),
            (NULL,'PluginMachinecheckerInputs', '11', '12', '0'),
            (NULL,'PluginMachinecheckerInputs', '20', '10', '0'),
            (NULL,'PluginMachinecheckerInputs', '31', '13', '0'),
            (NULL,'PluginMachinecheckerInputs', '30', '9', '0'),
            (NULL,'PluginMachinecheckerInputs', '21', '14', '0'),
            (NULL,'PluginMachinecheckerInputs', '51', '11', '0')";
   $DB->query($query) or die($DB->error());
   return true;
}

function plugin_machinechecker_uninstall()
{
   global $DB;
   $DeleteViewquery = "DELETE FROM `glpi_displaypreferences`  where itemtype ='PluginMachinecheckerInputs'";
   $DB->query($DeleteViewquery) or die($DB->error());
   $DB->query("DROP TABLE IF EXISTS glpi_plugin_machinechecker_inputs");
   
   return true;
}

?>