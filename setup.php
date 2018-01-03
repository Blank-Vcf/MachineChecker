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

         // Menu name

         $PLUGIN_HOOKS['menu_toadd']['machinechecker'] = array(
            'tools' => 'PluginMachinecheckerInput'
         );
      }

      $PLUGIN_HOOKS['post_init']['machinechecker'] = 'plugin_machinechecker_postinit';
      //$PLUGIN_HOOKS['use_massive_action']['machinechecker'] = 1;
   }
}


function plugin_version_machinechecker()
{
   return array(
      'name' => 'Machine Checker',
      'version' => '0.3.6',
      'author' => 'Blank @ Vcf',
      'license' => 'GPLv2+',
      'homepage' => 'http://clermont-ferrand.fr',
      'minGlpiVersion' => '9.1'
   ); // For compatibility / no install in version < 0.80
}


function plugin_machinechecker_check_prerequisites()
{
   if (version_compare(GLPI_VERSION,'9.1','lt') || version_compare(GLPI_VERSION,'9.3', 'ge')) {
      echo "This plugin requires GLPI >= 9.1";
      return false;
   }
   return true;
}

function plugin_machinechecker_check_config($verbose = false)
{
   if (true) {
      return true;
   }
   if ($verbose) {
      _e('Installed / not configured', 'machinechecker');
   }
   return false;
}

?>