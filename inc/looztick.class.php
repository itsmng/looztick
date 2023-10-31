
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

    static function getMenuContent(): array
    {
        $menu = [
            'title' => 'Looztick',
            'page' => Plugin::getPhpDir('looztick', false) . '/front/looztick.form.php',
            'icon' => 'fas fa-qrcode'
        ];

        return $menu;
    }

    static protected function getApiKey(): string
    {
        global $DB;

        $query = "SELECT api_key FROM glpi_plugin_looztick_config WHERE id = 1";
        $result = $DB->query($query);
        $config = iterator_to_array($result)[0];
        return $config["api_key"];
    }

    static protected function sendQuery(string $method = 'GET', string $uri = '/', array $data = [])
    {
        $apiKey = self::getApiKey();
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
            $result = array_merge($result, json_decode(file_get_contents($url, false, $context), true));
            if ($result['control'] != "ok") {
                break;
            }
        }

        return $result;
    }

    static function updateQrCodes()
    {
        global $DB;
        $qrcodes = self::sendQuery('GET', '/qrcodes/');
        $table = self::getTable();
        if (!isset($qrcodes['qrcodes']) || count($qrcodes['qrcodes']) == 0) {
            return;
        }

        $query = "INSERT IGNORE INTO `$table` 
                  (id, itemtype, itemid, firstname, lastname, mobile, friendmobile, countrycode, email) 
                  VALUES ";

        $values = array();

        foreach ($qrcodes['qrcodes'] as $qrcode) {
            $values[] = "('{$qrcode['id']}', '', '', '{$qrcode['firstname']}', '{$qrcode['lastname']}', '{$qrcode['mobile']}', '{$qrcode['friendmobile']}', '{$qrcode['countrycode']}', '{$qrcode['email']}')";
        }

        $query .= implode(', ', $values) . ";";
        $DB->query($query);
    }


    static function testApiConnection(): bool
    {
        $response = PluginLooztickLooztick::sendQuery("POST");
        return $response['control'] == "ok";
    }

    static function getQrCodes(): array
    {
        global $DB;
        $query = "SELECT * FROM glpi_plugin_looztick_loozticks";
        $result = $DB->query($query);
        return iterator_to_array($result);
    }

    function rawSearchOptions()
    {


        $options = [
            __("QR Code") => 'id',
            __("Item type") => 'itemtype',
            __("Item Id") => 'itemid',
            __("First name") => 'firstname',
            __("Last name") => 'lastname',
            __("Mobile") => 'mobile',
            __("Friend mobile") => 'friendmobile',
            __("Country code") => 'countrycode',
            __("Email") => 'email',
        ];

        $tab = [];
        $i = 1;
        foreach ($options as $key => $value) {
            $tab[] = [
                'id' => $i,
                'table' => self::getTable(),
                'name' => $key,
                'field' => $value,
            ];
            $i++;
        }
        return $tab;
    }

    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        return "Looztick";
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        $qrcodes = self::getQrCodes();
        $currentQrcode = array_filter($qrcodes, function ($qrcode) use ($item) {
            return $qrcode['itemtype'] == $item->getType() && $qrcode['itemid'] == $item->getID();
        });
        $qrcodeAjaxEndpoint = Plugin::getWebDir('looztick') . '/ajax/qrcode.php';

        $form = [
            'action' => 'looztick.form.php',
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
                            'hooks' => [
                                'change' => <<<JS
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
                                JS,
                            ]
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
                        "Country code" => [
                            'name' => 'countrycode',
                            'id' => 'looztick_countrycode',
                            'type' => 'text',
                            'value' => array_values($currentQrcode)[0]['countrycode'] ?? null,
                        ],
                        "Email" => [
                            'name' => 'email',
                            'id' => 'looztick_email',
                            'type' => 'text',
                            'value' => array_values($currentQrcode)[0]['email'] ?? null,
                        ],
                    ]
                ]
            ]
        ];
        renderTwigForm($form);
        return true;
    }
}
