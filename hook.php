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


function plugin_looztick_install() {
    global $DB;

    //get default values for fields 
    if (!$DB->tableExists("glpi_plugin_looztick_config")) {        
        $createQuery = <<<SQL
            CREATE TABLE glpi_plugin_looztick_config (
                id int(11) NOT NULL AUTO_INCREMENT,
                api_key varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                firstname varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                lastname varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                mobile varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                friendmobile varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                countrycode varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                email varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        SQL;
        $insertQuery = <<<SQL
            INSERT INTO glpi_plugin_looztick_config (id, api_key) VALUES (1, '');
        SQL;

        $DB->queryOrDie($createQuery, $DB->error());
        $DB->queryOrDie($insertQuery, $DB->error());
    }
    if (!$DB->tableExists("glpi_plugin_looztick_loozticks")) {        
        $createQuery = <<<SQL
            CREATE TABLE glpi_plugin_looztick_loozticks (
                id int(11) NOT NULL AUTO_INCREMENT,
                qrcode varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                itemtype varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                itemid varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        SQL;
        $DB->queryOrDie($createQuery, $DB->error());

        for ($i = 2; $i <= 5; $i++) {
            $DB->query("REPLACE INTO glpi_displaypreferences VALUES
               (NULL, 'PluginLooztickLooztick', $i, ".($i-1).", 0)");
         }

    }
    return true;
}

function plugin_looztick_uninstall() {
    global $DB;

    // Drop tables
    if($DB->tableExists('glpi_plugin_looztick_config')) {
        $DB->queryOrDie("DROP TABLE `glpi_plugin_looztick_config`",$DB->error());
    }
    if($DB->tableExists('glpi_plugin_looztick_loozticks')) {
        $DB->queryOrDie("DROP TABLE `glpi_plugin_looztick_loozticks`",$DB->error());
        $DB->queryOrDie("DELETE FROM `glpi_displaypreferences` WHERE `itemtype` = 'PluginLooztickLooztick'",$DB->error());
    }



    return true;
}
