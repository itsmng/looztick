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
        $form_ajax = Plugin::getWebDir('looztick')."/ajax/qrcode.php";
        
        $config = ($DB->request("SELECT * FROM glpi_plugin_looztick_config WHERE id=1"))->next();
        
        $defaultValues = [
            'First name' => 'firstname',
            'Last name' => 'lastname',
            'Mobile' => 'mobile',
            'Second mobile' => 'friendmobile',
            'Country code' => 'countrycode',
            'Email' => 'email',
        ];

        $image = Plugin::getWebDir('looztick').'/img/looztick.png';

        $form = [
            'action' => $form_action,
            'buttons' => [
                [
                    'type' => 'submit',
                    'name' => 'update_config',
                    'value' => __('Update'),
                    'class' => 'submit-button btn btn-warning',
                ],
                [
                    'type' => 'button',
                    'name' => 'update_config',
                    'value' => __('Synchronize'),
                    'class' => 'submit-button btn btn-primary',
                    'icon' => 'fas fa-sync',
                    'onClick' => <<<JS
                    $.ajax({
                        url: '{$form_ajax}',
                        type: 'POST',
                        data: {
                            action: 'sync'
                        },
                        success: function (data) {
                            if (data.status == 'success') {
                                document.location = '{$form_action}?sync=success';
                            } else {
                                document.location = '{$form_action}?sync=error';
                            }
                        }
                    });
                    JS,
                ],
            ],
            'content' => [
                '' => [
                    'visible' => true,
                    'inputs' => [
                        '' => [
                            'content' => <<<HTML
                            <img src="{$image}" alt="Looztick logo" class="mx-auto" style="max-height: 6rem;width: auto"/>
                            HTML,
                        ]
                    ]
                ],
                'Configuration' => [
                    'visible' => true,
                    'inputs' => [
                        'Api Keys (separated by ",")' => [
                            'type' => 'text',
                            'value' => Toolbox::sodiumDecrypt($config['api_key']),
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
        include_once GLPI_ROOT . "/ng/form.utils.php";
        renderTwigForm($form);
    }

    public function updateConfig() {
        global $DB;

        $api_key = $_POST["api_key"];
        $DB->request("UPDATE glpi_plugin_whitelabel_config SET api_key='$api_key' WHERE id=1");
    }
}
