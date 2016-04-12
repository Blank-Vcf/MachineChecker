<?php
include ('../../../inc/includes.php');

Html::header(PluginMachinecheckerInput::getTypeName(2) , '', "tools", "PluginMachinecheckerInputs", "machinechecker");
$command = new PluginMachinecheckerInput();

// empty database if fist use or reset form has been send

if (isset($_POST["reset"]) || !isset($_SESSION['PluginMachinecheckerInputs'])) {
   $command->ClearDatabase();
}

if (isset($_POST["sendform"])) {
   $command->DoTheJob($_POST["computer_list"]);
   
   Html::back();
}

$command->DisplayInputsForm();

if (isset($_POST["computer_list"]) || isset($_GET["itemtype"]) || $_SESSION['PluginMachinecheckerInputs']==1) {
   Search::show("PluginMachinecheckerInput");
}

Html::footer();
?>