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

/**
 * @param $form
 * @param $additionnalHtml
 * 
 * @return string
 */
function renderForm($form, $additionnalHtml = "", $colAmount = 2)
{
    $options = ['actions', 'after', 'before', 'hooks'];
    $token = Session::getNewCSRFToken();
    $action = $form['action'];
    echo <<<HTML
        <form action="$action" method="POST" class="container">
    HTML;
    foreach ($form['content'] as $title => $content) {
        echo <<<HTML
            <div class="container mb-3">
                <h2>$title</h2>
                <div class="row row-cols-2">
        HTML;
        foreach ($content['inputs'] as $label => $input) {
            echo <<<HTML
                    <div class="col col-lg-4 col-md-6 col-12 text-start">
                        <label for="{$input['name']}">$label</label>
                        <input type="{$input['type']}" name="{$input['name']}" value="{$input['value']}" class="form-control">
                    </div>
            HTML;
        }
        echo <<<HTML
                </div>
            </div>
        HTML;
    }
    echo <<<HTML
        <input type="hidden" name="_glpi_csrf_token" value="$token"/>
        <button class="btn btn-warning">{$form['submit']}</button>
        </form>
    HTML;
    echo "<script>";
    foreach ($form['content'] as $content) {
        foreach ($content['inputs'] as $input) {
            if (isset($input['hooks'])) {
                foreach($input['hooks'] as $hook => $script) {
                    echo <<<HTML
                        {$input['id']}.on({$hook}, function() {
                            {$script}
                        })
                    HTML;
                }
            }
        }
    }
    echo "</script>";
}