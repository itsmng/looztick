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
    $looztick = new PluginLooztickLooztick();
    if (isset($_GET['id'])) {
        $_POST['qrcode'] = $_GET['id'];
    }
    if (isset($_POST['action']) && isset($_POST['qrcode'])) {
        global $DB;
        $table = PluginLooztickLooztick::getTable();
    
        $item = $_POST['item'] ?? '';
        $activated = $_POST['activated'] ?? '0';
        try {
            PluginLooztickLooztick::sendQuery('POST', '/update/', [
                'qrcode' => $_POST['qrcode'],
                'firstname' => $_POST['firstname'],
                'lastname' => $_POST['lastname'],
                'mobile' => $_POST['mobile'],
                'friendmobile' => $_POST['friendmobile'],
                'countrycode' => $_POST['countrycode'],
                'email' => $_POST['email'],
                'id_client' => $item,
                'activate' => $activated,
                'describe' => $_POST["comment"],
            ]);
        } catch (Exception $e) {
            Session::addMessageAfterRedirect($e->getMessage(), false, ERROR);
            Html::back();
            return;
        }
        $query = <<<SQL
        UPDATE $table SET
            item = "{$item}",
            firstname = "{$_POST['firstname']}",
            lastname = "{$_POST['lastname']}",
            mobile = "{$_POST['mobile']}",
            friendmobile = "{$_POST['friendmobile']}",
            countrycode = "{$_POST['countrycode']}",
            email = "{$_POST['email']}",
            activated = "{$activated}",
            comment = '{$_POST["comment"]}'
        WHERE id = "{$_POST['qrcode']}"
        SQL;
        $DB->request($query);
        Session::addMessageAfterRedirect(__('Successful update'), true, INFO);
        Html::back();
        return;
    }
    
    if ($looztick->testApiConnection()) {
        Html::header("Looztick", $_SERVER["PHP_SELF"], "tools", PluginLooztickLooztick::class);
        if (isset($_GET["id"])) {
            $looztick->display([
                'id'           => $_GET["id"],
                'withtemplate' => '',
            ]);
        } else {
            Search::show("PluginLooztickLooztick");
        }
    } else {
        Html::header("settings", '', "config", "plugins");
        echo "<div class='center'><br><br><img src=\"".$CFG_GLPI["root_doc"]."/pics/warning.png\" alt='warning'><br><br>";
        echo "<b>Could not connect to Looztick API</b></div>";
    }
    Html::footer();

} else {
    Html::header("settings", '', "config", "plugins");
    echo "<div class='center'><br><br><img src=\"".$CFG_GLPI["root_doc"]."/pics/warning.png\" alt='warning'><br><br>";
    echo "<b>Please enable the plugin before configuring it</b></div>";
    Html::footer();
}