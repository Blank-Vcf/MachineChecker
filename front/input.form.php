<?php
   include ('../../../inc/includes.php');
   if ($_GET["id"]==0) {
      Html::header(PluginMachinecheckerInput::getTypeName(2) , '', "tools", "PluginMachinecheckerInputs", "machinechecker");
      echo "<div class='center'><br><br>";
      echo "<img src='" . $CFG_GLPI["root_doc"] . "/pics/warning.png' alt='".__s('Warning')."'>";
      echo "<br><br><span class='b'>" . __('Item not found') . "</span></div>";
      Html::footer();
      Html::back();
   }
   header('Location: ../../../front/computer.form.php?id='.$_GET["id"].'');
   exit();
?>