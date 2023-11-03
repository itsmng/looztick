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
class PluginLooztickConfig extends CommonDBTM {
    /**
     * Displays the configuration page for the plugin
     * 
     * @return void
     */
    public function showConfigForm() {
        global $DB;

        $api_key_label = __("API Key");
        $form_action = Plugin::getWebDir("looztick")."/front/config.form.php";
        
        $config = ($DB->request("SELECT * FROM glpi_plugin_looztick_config WHERE id=1"))->next();
        
        $defaultValues = [
            'First name' => 'firstname',
            'Last name' => 'lastname',
            'Mobile' => 'mobile',
            'Second mobile' => 'friendmobile',
            'Country code' => 'countrycode',
            'Email' => 'email',
        ];

        $form = [
            'action' => $form_action,
            'submit' => __('Save'),
            'content' => [
                'Configuration' => [
                    'visible' => true,
                    'inputs' => [
                        'Api Keys (separated by ",")' => [
                            'type' => 'text',
                            'value' => $config['api_key'],
                            'name' => 'api_key',
                        ],
                    ]
                    ],
                    'Default values' => [
                        'visible' => true,
                        'inputs' => []
                    ],
            ]
        ];
        foreach ($defaultValues as $label => $name) {
            $form['content']['Default values']['inputs'] += [ $label => [
                'type' => 'text',
                'value' => $config[$name],
                'name' => $name,
            ]];
        }
        include_once PLUGIN::getPhpDir('looztick') . "/inc/form.utils.php";
        renderForm($form);
    }

    public function updateConfig() {
        global $DB;

        $api_key = $_POST["api_key"];
        $DB->request("UPDATE glpi_plugin_whitelabel_config SET api_key='$api_key' WHERE id=1");
    }
}
