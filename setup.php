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

 define('LOOZTICKPLUGIN_VERSION', '0.1.0');
 define('LOOZTICKPLUGIN_AUTHOR', 'ITSM Dev Team, AntoineLemarchand');
 define('LOOZTICKPLUGIN_HOMEPAGE', 'https://github.com/AntoineLemarchand/looztick');

function plugin_version_looztick() {
    return array(
        'name'           => "Looztick",
        'version'        => LOOZTICKPLUGIN_VERSION,
        'author'         => LOOZTICKPLUGIN_AUTHOR,
        'license'        => 'GPLv3+',
        'homepage'       => LOOZTICKPLUGIN_HOMEPAGE,
        'requirements'   => [
            'glpi'   => [
               'min' => '9.5'
            ],
            'php'    => [
                'min' => '8.0'
            ]
        ]
    );
}

function plugin_looztick_check_prerequisites() {
    if (version_compare(ITSM_VERSION, '2.0', 'lt')) {
        echo "This plugin requires ITSM >= 2.0";
        return false;
    }
    return true;
}

function plugin_looztick_check_config() {
    return true;
}


function plugin_init_looztick() {
    global $PLUGIN_HOOKS;

    Plugin::registerClass('PluginLooztickProfile', ['addtabon' => ['Profile']]);
    Plugin::registerClass('PluginLooztickLooztick', [
        'addtabon' => [
            'Computer',
            'Monitor',
            'Peripheral',
            'Phone',
            'NetworkEquipment',
            'Printer',
            'Rack',
            'Enclosure',
            'PDU',
            'PassiveDCEquipment'
        ]
    ]);
    
    $PLUGIN_HOOKS['csrf_compliant']['looztick'] = true;
    $PLUGIN_HOOKS['change_profile']['looztick'] = ['PluginLooztickProfile','initProfile'];
    if (Session::haveRight("plugin_looztick_looztick", READ)) {
        $PLUGIN_HOOKS['menu_toadd']['looztick'] = array('tools' => 'PluginLooztickLooztick');
    }
    if (Session::haveRight("profile", UPDATE)) {
        $PLUGIN_HOOKS['config_page']['looztick'] = 'front/config.form.php';
    }
}
