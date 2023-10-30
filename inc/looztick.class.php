
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

    static protected function sendQuery(string $method = 'GET', string $uri = '/', array $data = []) {
        $apiKey = self::getApiKey();
        $content = $data + ['key' => $apiKey];
        $url = self::LOOZTIK_ENDPOINT . $uri;
        $opts = [
            'http' => [
                'method' => $method,
                'header' => 'Content-Type: application/json',
                'content' => json_encode($content)
            ]
        ];
        $context = stream_context_create($opts);
        $result = file_get_contents($url, false, $context);
        return json_decode($result, true);
    }

    function testApiConnection(): bool
    {
        $response = $this::sendQuery("POST");
        return $response['control'] == "ok";
    }

    function showForm() : void
    {
        if ($this->testApiConnection()) {
            echo "Connected to : ". $this::LOOZTIK_ENDPOINT . "<br>";
            echo var_dump($this->sendQuery('POST', '/qrcodes/'));
        }
    }
}
