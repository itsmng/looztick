
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

class PluginLooztickLooztick extends CommonDBTM
{
    const LOOZTIK_ENDPOINT = "https://looztick.fr/api";
    static $rightname = "plugin_looztick_looztick";

    static function getMenuContent(): array
    {
        $menu = [
            'title' => 'Looztick',
            'page' => Plugin::getPhpDir('looztick', false) . '/front/looztick.form.php',
            'icon' => 'fas fa-qrcode'
        ];

        return $menu;
    }

    static function getConfig(): array
    {
        global $DB;

        $query = "SELECT * FROM glpi_plugin_looztick_config WHERE id = 1";
        $result = $DB->query($query);
        $config = iterator_to_array($result)[0];
        return $config;
    }

    static function sendQuery(string $method = 'GET', string $uri = '/', array $data = [])
    {
        $apiKey = Toolbox::sodiumDecrypt(self::getConfig()['api_key'] ?? '');
        $result = [];
        foreach (explode(',', $apiKey) as $key) {
            $content = $data + ['key' => $key];
            $url = self::LOOZTIK_ENDPOINT . $uri;
            $opts = [
                'http' => [
                    'method' => $method,
                    'header' => 'Content-Type: application/json',
                    'content' => json_encode($content)
                ]
            ];
            $context = stream_context_create($opts);
            $result = json_decode(file_get_contents($url, false, $context), true);
            if ($result['control'] != "ok") {
                throw new Exception($result['reason']);
            }
        }

        return $result;
    }

    static function updateQrCodes()
    {
        global $DB;
        $qrcodes_api = self::sendQuery('GET', '/qrcodes/');
        $qrcodes_local = self::getQrCodes();
        $table = self::getTable();
        if (!isset($qrcodes_api['qrcodes']) || count($qrcodes_api['qrcodes']) == 0) {
            return;
        }

        // update and insert any not linked and non-existing qrcode
        $query = "REPLACE INTO `$table` 
                  (id, item, firstname, lastname, mobile, friendmobile, countrycode, email, activated) 
                  VALUES ";
    
        $values = array();
    
        foreach ($qrcodes_api['qrcodes'] as $qrcode) {
            if (in_array($qrcode['id'], array_column($qrcodes_local, 'id')) && $qrcodes_local[$qrcode['id']]['item'] != '') {
                $current_local = $qrcodes_local[$qrcode['id']];
                self::sendQuery('POST', '/update/', [
                    'qrcode' => $current_local['id'],
                    'activate' => $current_local['activated'],
                    'firstname' => $current_local['firstname'],
                    'lastname' => $current_local['lastname'],
                    'mobile' => $current_local['mobile'],
                    'friendmobile' => $current_local['friendmobile'],
                    'countrycode' => $current_local['countrycode'],
                    'email' => $current_local['email'],
                    'id_client' => $current_local['item'],
                ]);
            } else {
                $values[] = "('{$qrcode['id']}', '{$qrcode['id_client']}', '{$qrcode['firstname']}', '{$qrcode['lastname']}', '{$qrcode['mobile']}', '{$qrcode['friendmobile']}', '{$qrcode['countrycode']}', '{$qrcode['email']}', '{$qrcode['activated']}')";
            }
        }
    
        $query .= implode(', ', $values) . ";";
    
        $DB->query($query);
    }
    

    static function testApiConnection(): bool
    {
        $response = PluginLooztickLooztick::sendQuery("POST");
        return $response['control'] == "ok";
    }

    static function getQrCodes($conditions = []): array
    {
        global $DB;
        $result = $DB->request([
            'SELECT' => '*',
            'FROM' => self::getTable(),
            'WHERE' => $conditions
        ]);
        return iterator_to_array($result);
    }

    static function unlink(): bool
    {
        global $DB;
        $query = "UPDATE glpi_plugin_looztick_loozticks SET item = '', activated = 0 WHERE id = {$_POST['id']}";
        $DB->query($query);
        self::sendQuery('POST', '/update/', ['qrcode' => $_POST['id'], 'activated' => 0]);
        return true;
    }

    function showForm()
    {
        global $DB;

        $api_key_label = __("API Key");
        $form_action = Plugin::getWebDir("looztick")."/front/looztick.form.php?id=".$this->fields["id"];
        
        $defaultValues = [
            'First name' => 'firstname',
            'Last name' => 'lastname',
            'Mobile' => 'mobile',
            'Second mobile' => 'friendmobile',
            'Country code' => 'countrycode',
            'Email' => 'email',
        ];

        $item = explode('_', $this->fields['item']);
        $itemUrl = method_exists($item[0], 'getFormURL') ? $item[0]::getFormURL()."?id=".$item[1] : '#';
        $activatedLabel = __('Activated');
        $link = <<<HTML
        {$activatedLabel} : <a href={$itemUrl}>{$item[0]}</a>
        HTML;

        $form = [
            'action' => $form_action,
            'submit' => __('Save'),
            'content' => [
                'Looztick QR Code' => [
                    'visible' => true,
                    'inputs' => [
                        'action' => [
                            'name' => 'action',
                            'type' => 'hidden',
                            'value' => 'update',
                        ],
                        $link => [
                            'type' => 'checkbox',
                            'value' => $this->fields['activated'],
                            $this->fields['activated'] == 1 ? 'checked' : '' => true,
                            'name' => 'activated',
                            'disabled' => true,
                        ],
                        'Code' => [
                            'type' => 'text',
                            'value' => $this->fields['id'],
                            'name' => 'api_key',
                            'disabled' => true,
                        ],
                        'activated' => [
                            'type' => 'hidden',
                            'value' => $this->fields['activated'],
                            'name' => 'activated',
                        ],
                        'item' => [
                            'type' => 'hidden',
                            'value' => $this->fields['item'],
                            'name' => 'item',
                        ],
                    ]
                ] 
            ]
        ];
        foreach ($defaultValues as $label => $name) {
            $form['content']['Looztick QR Code']['inputs'] += [ $label => [
                'type' => 'text',
                'value' => $this->fields[$name],
                'name' => $name,
            ]];
        }
        $form['content']['Looztick QR Code']['inputs'] += [__("Comment") => [
            'name' => 'comment',
            'type' => 'textarea',
            'value' => $this->fields['comment'] ?? null,
            'rows' => 5,
            'col' => 12,
            'col_md' => 12,
            'col_lg' => 12,
        ]];

        include_once GLPI_ROOT . '/ng/form.utils.php';
        renderTwigForm($form);
    }

    function rawSearchOptions()
    {
        $tab = [];
        $tab[] = [
            'id' => 1,
            'table' => self::getTable(),
            'name' => __("QR Code"),
            'field' => 'id',
            'datatype' => 'itemlink'
        ];
        $tab[] = [
            'id' => 2,
            'table' => self::getTable(),
            'name' => __("First name"),
            'field' => 'firstname',
        ];
        $tab[] = [
            'id' => 3,
            'table' => self::getTable(),
            'name' => __("Last name"),
            'field' => 'lastname',
        ];
        $tab[] = [
            'id' => 4,
            'table' => self::getTable(),
            'name' => __("Mobile"),
            'field' => 'mobile',
        ];
        $tab[] = [
            'id' => 5,
            'table' => self::getTable(),
            'name' => __("Friend mobile"),
            'field' => 'friendmobile',
        ];
        $tab[] = [
            'id' => 6,
            'table' => self::getTable(),
            'name' => __("Country code"),
            'field' => 'countrycode',
        ];
        $tab[] = [
            'id' => 7,
            'table' => self::getTable(),
            'name' => __("Email"),
            'field' => 'email',
        ];
        $tab[] = [
            'id' => 8,
            'table' => self::getTable(),
            'name' => __("Activated"),
            'field' => 'activated',
            'massiveaction' => false,
        ];
        $tab[] = [
            'id' => 9,
            'table' => self::getTable(),
            'name' => __("Comment"),
            'field' => 'comment',
            'massiveaction' => false,
        ];
        return $tab;
    }

    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        return "Looztick";
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        $qrcodes = self::getQrCodes(['OR' => [
            ['item' => $item->getType(). '_' .$item->getID()],
            ['item' => ''],
        ]]);
        $currentQrcode = array_filter($qrcodes, function ($qrcode) use ($item) {
            return $qrcode['item'] == $item->getType(). '_' .$item->getID();
        });
        $qrcodeAjaxEndpoint = Plugin::getWebDir('looztick') . '/ajax/qrcode.php';
        $updateInputs = <<<JS
            $.ajax({
                url: '{$qrcodeAjaxEndpoint}',
                method: 'POST',
                data: {
                    id: $('#looztick_qrcode_dropdown').val()
                },
                success: function(data) {
                    $('#looztick_firstname').val(data.firstname);
                    $('#looztick_lastname').val(data.lastname);
                    $('#looztick_mobile').val(data.mobile);
                    $('#looztick_friendmobile').val(data.friendmobile);
                    $('#looztick_countrycode').val(data.countrycode);
                    $('#looztick_email').val(data.email);
                }
            });
        JS;

        $countryCodes = array_map('str_getcsv', file(Plugin::getPhpDir('looztick') . '/datas/countrycode.csv'));
        $alpha2Idx = array_search('alpha-2', $countryCodes[0]);
        $nameIdx = array_search('name', $countryCodes[0]);
        unset($countryCodes[0]);
        $alpha2CountryCodes = array_column($countryCodes, $alpha2Idx);
        $nameCountryCodes = array_column($countryCodes, $nameIdx);
        $countryCodes = array_combine($alpha2CountryCodes, $nameCountryCodes);

        $form = [
            'action' => Plugin::getWebDir('looztick') . '/front/looztick.form.php',
            'content' => [
                '' => [
                    'visible' => true,
                    'inputs' => [
                        "QR code" => [
                            'name' => 'qrcode',
                            'id' => 'looztick_qrcode_dropdown',
                            'type' => 'select',
                            'values' => array_column($qrcodes, 'id', 'id'),
                            'value' => array_values($currentQrcode)[0]['id'] ?? null,
                            count($currentQrcode) != 0 ? 'disabled' : '' => true,
                            'hooks' => [
                                'change' => $updateInputs,
                            ],
                            'init' => $updateInputs,
                            'actions' => count($currentQrcode) != 0 ? ['unlink' => [
                                    'icon' => 'fas fa-unlink',
                                    'onClick' => <<<JS
                                        $.ajax({
                                            url: '{$qrcodeAjaxEndpoint}',
                                            method: 'POST',
                                            data: {
                                                action: 'unlink',
                                                id: $('#looztick_qrcode_dropdown').val(),
                                            },
                                        });
                                        window.location.reload();
                                    JS,
                                ]
                            ] : []
                        ],
                        "First name" => [
                            'name' => 'firstname',
                            'id' => 'looztick_firstname',
                            'type' => 'text',
                            'value' => array_values($currentQrcode)[0]['firstname'] ?? null,
                        ],
                        "Last name" => [
                            'name' => 'lastname',
                            'id' => 'looztick_lastname',
                            'type' => 'text',
                            'value' => array_values($currentQrcode)[0]['lastname'] ?? null,
                        ],
                        "Mobile" => [
                            'name' => 'mobile',
                            'id' => 'looztick_mobile',
                            'type' => 'text',
                            'value' => array_values($currentQrcode)[0]['mobile'] ?? null,
                        ],
                        "Friend mobile" => [
                            'name' => 'friendmobile',
                            'id' => 'looztick_friendmobile',
                            'type' => 'text',
                            'value' => array_values($currentQrcode)[0]['friendmobile'] ?? null,
                        ],
                        __('Country') => [
                            'type' => 'select',
                            'id' => 'countryCodeDropdown',
                            'searchable' => true,
                            'name' => 'countrycode',
                            'value' => array_values($currentQrcode)[0]['countrycode'] ?? null,
                            'values' => $countryCodes,
                         ],
                        __("Email") => [
                            'name' => 'email',
                            'id' => 'looztick_email',
                            'type' => 'text',
                            'value' => array_values($currentQrcode)[0]['email'] ?? null,
                        ],
                        __("Comment") => [
                            'name' => 'comment',
                            'type' => 'textarea',
                            'value' => array_values($currentQrcode)[0]['comment'] ?? null,
                            'rows' => 5
                        ],
                        'action' => [
                            'name' => 'action',
                            'type' => 'hidden',
                            'value' => 'update',
                        ],
                        'item' => [
                            'name' => 'item',
                            'type' => 'hidden',
                            'value' => $item->getType(). '_' . $item->getID(),
                        ],
                        'activated' => [
                            'name' => 'activated',
                            'type' => 'hidden',
                            'value' => 1,
                        ],
                    ]
                ]
            ]
        ];
        renderTwigForm($form);
        return true;
    }
}
