<?php

namespace Yvelines\Citrus\View;

interface TemplateEngineInterface {

    public function render($args);

    public function loadTemplate($name);

    // public function setTemplate();

}
