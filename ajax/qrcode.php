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

include ("../../../inc/includes.php");

Session::checkLoginUser();

header('Content-Type: application/json; charset=utf-8');
if (isset($_POST['id'])) {
    global $DB;
    $result = $DB->request([
        'SELECT' => [
            'id',
            'firstname',
            'lastname',
            'mobile',
            'friendmobile',
            'countrycode',
            'email'
        ],
        'FROM' => PluginLooztickLooztick::getTable(),
        'WHERE' => [
            'id' => $_POST['id']
        ]
    ]);
    $code = iterator_to_array($result)[$_POST['id']];
    $config = PluginLooztickLooztick::getConfig();
    foreach($code as $key => $value) {
        if ($value == '') {
            $code[$key] = $config[$key];
        };
    }
    echo json_encode($code);
} else {
    echo json_encode([]);
}
