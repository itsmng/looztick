<?php
/**
 * ---------------------------------------------------------------------
 * ITSM-NG
 * Copyright (C) 2022 ITSM-NG and contributors.
 *
 * https://www.itsm-ng.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of ITSM-NG.
 *
 * ITSM-NG is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * ITSM-NG is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with ITSM-NG. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */
include("../../../inc/includes.php");
require_once("../inc/config.class.php");

$plugin = new Plugin();

if($plugin->isActivated("looztick")) {
    $config = new PluginLooztickConfig();
    if(isset($_POST["api_key"])) {
        Session::checkRight("config", UPDATE);
        
        global $DB;
        $api_key = Toolbox::sodiumEncrypt($_POST["api_key"]);
        $query = <<<SQL
        UPDATE glpi_plugin_looztick_config SET
            api_key = '{$api_key}',
            firstname = '{$_POST["firstname"]}',
            lastname = '{$_POST["lastname"]}',
            mobile = '{$_POST["mobile"]}',
            friendmobile = '{$_POST["friendmobile"]}',
            countrycode = '{$_POST["countrycode"]}',
            email = '{$_POST["email"]}',
            comment = '{$_POST["comment"]}'
        WHERE id = 1
        SQL;
        $DB->request($query);
        
        PluginLooztickLooztick::updateQrCodes();
        if (!PluginLooztickLooztick::testApiConnection()) {
            Session::addMessageAfterRedirect("API key is invalid", false, ERROR);
            Html::back();
        } else {
            Session::addMessageAfterRedirect("Configuration updated");
            Html::redirect($_SERVER["PHP_SELF"]);
        }
    }

    if (isset($_GET['sync'])) {
        PluginLooztickLooztick::updateQrCodes();
        Session::addMessageAfterRedirect("sync status : ". $_GET['sync']);
        Html::redirect($_SERVER["PHP_SELF"]);
    }
    Html::header("Looztick", $_SERVER["PHP_SELF"], "config", PluginLooztickLooztick::class);
    $config->showConfigForm();
} else {
    
    Html::header("settings", '', "config", "plugins");
    echo "<div class='center'><br><br><img src=\"".$CFG_GLPI["root_doc"]."/pics/warning.png\" alt='warning'><br><br>";
    echo "<b>Please enable the plugin before configuring it</b></div>";
    Html::footer();
}

Html::footer();
