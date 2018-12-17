<?php
function plugin_init_machinechecker()
{
   global $PLUGIN_HOOKS, $CFG_GLPI;
   $PLUGIN_HOOKS['csrf_compliant']['machinechecker'] = true;
   if (Session::getLoginUserID()) {
      Plugin::registerClass('PluginMachinecheckerProfile', array(
         'addtabon' => 'Profile'
      ));
      if (Session::haveRight("plugin_machinechecker", READ)) {
         $PLUGIN_HOOKS['menu_toadd']['machinechecker'] = array(
            'tools' => 'PluginMachinecheckerInput'
         );
      }
      $PLUGIN_HOOKS['post_init']['machinechecker'] = 'plugin_machinechecker_postinit';
   }
}

function plugin_version_machinechecker()
{
   return ['name'           => "Machine Checker",
           'version'        => '0.4.0',
           'author'         => 'Blank @ Vcf',
           'license'        => 'GPLv2+',
           'homepage'       => 'https://github.com/Blank-Vcf/MachineChecker',
           'minGlpiVersion' => '9.3'];
}

function plugin_machinechecker_check_prerequisites()
{

   if (version_compare(GLPI_VERSION, '9.3', 'lt') || version_compare(GLPI_VERSION, '9.4', 'ge')) {
      echo "This plugin requires GLPI >= 9.3";
      return false;
   }
   return true;
}

function plugin_machinechecker_check_config() {
   return true;
}

?>