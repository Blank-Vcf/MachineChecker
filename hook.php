<?php

function plugin_machinechecker_install()
{
   global $DB;
   include_once (GLPI_ROOT . "/plugins/machinechecker/inc/profile.class.php");

   PluginMachinecheckerProfile::initProfile();
   PluginMachinecheckerProfile::createfirstAccess($_SESSION['glpiactiveprofile']['id']);

   // create plugin table

   if (!TableExists("glpi_plugin_machinechecker_inputs")) {
      $query = "CREATE TABLE `glpi_plugin_machinechecker_inputs` (
               `id` int(11) NOT NULL AUTO_INCREMENT,
               `computers_id` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
               `computers_name` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
               `computertypes_name` varchar(45) CHARACTER SET latin1 DEFAULT NULL,
               `computers_contact` varchar(45) CHARACTER SET latin1 DEFAULT NULL,
               `users_name` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
               `locations_completename` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
               `states_name` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
               PRIMARY KEY (`id`)
               ) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->query($query) or die($DB->error());
   }

   // add default plugin table view

   $query_ = "SELECT *
              FROM glpi_displaypreferences
              WHERE itemtype='PluginMachinecheckerInputs'";
   if ($result = $DB->query($query_)) {
      if ($DB->numrows($result_) != 0) {
         return true;
      } else {
               $query = " INSERT INTO `glpi_displaypreferences`
               (id,itemtype,num,rank,users_id)
               VALUES
               (NULL,'PluginMachinecheckerInputs','5','4','0'),
               (NULL,'PluginMachinecheckerInputs','2','1','0'),
               (NULL,'PluginMachinecheckerInputs','4','3','0'),
               (NULL,'PluginMachinecheckerInputs','3','2','0'),
               (NULL,'PluginMachinecheckerInputs','6','5','0')";
               $DB->query($query) or die($DB->error());
               return true;
             }
   }
}

function plugin_machinechecker_uninstall()
{
   global $DB;
   
   $DB->query("DROP TABLE IF EXISTS glpi_plugin_machinechecker_inputs;");
   
   return true;
}

?>