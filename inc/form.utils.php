<?php

/**
 * @param $form
 * @param $additionnalHtml
 * 
 * @return string
 */
function renderTwigForm($form, $additionnalHtml = "", $colAmount = 2)
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
        <button class="btn btn-warning">Submit</button>
        </form>
    HTML;
}