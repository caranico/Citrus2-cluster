<?php

namespace Yvelines\Citrus\Loader;

class JsonLoader implements Loader {
    protected $content;

    static public function get( $path, $exist = array() ) {
        $inst = new JsonLoader( $path );
        return array_merge( $exist, $inst->getContent() );
    }

    public function __construct($path) {
        $this->path = $path;
        $this->load();
    }

    public function load() {
        $this->content = array();
        if (!file_exists($this->path)) {
            throw new \InvalidArgumentException(sprintf("Routing file '%s' doesn't exist. Check your configuration", $this->path));
        }
        if (!is_readable($this->path)) {
            throw new \InvalidArgumentException(sprintf("Unable to read routing file '%s'.", $this->path));
        }
        $raw = file_get_contents($this->path);
        $this->content = $this->parseContent( $raw );

    }

    private function parseConfigFile() {
        $routes = $this->content;

        foreach ($routes as $name => $params) {
            if (array_key_exists("url", $params) && array_key_exists("target", $params)) {
                $routes->add($name, new Route($params['url'], Array(
                    "_controller" => $params['target']
                )));
            }
        }
        $this->routes = $routes;
        return $this->routes;
    }

    public function getContent() {
        return $this->content;
    }

    private function parseContent($raw) {
        if ($raw === "") {
            return Array();
        }
        $content = json_decode($raw, true);
        if ($content === null) {
            $error = json_last_error();
            switch ($error) {
                case JSON_ERROR_DEPTH:
                    $error_msg = "The maximum stack depth has been exceeded";
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $error_msg = "Invalid or malformed JSON";
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $error_msg = "Control character error, possibly incorrectly encoded";
                    break;
                case JSON_ERROR_SYNTAX:
                    $error_msg = "Syntax error";
                    break;
                case JSON_ERROR_UTF8:
                    $error_msg = "Malformed UTF-8 characters, possibly incorrectly encoded";
                    break;
                default:
                    $error_msg = "unknown JSON error.";
                break;
            }
            throw new \Exception(sprintf("Error while parsing JSON file %s : %s", $this->path, $error_msg));
        }
        return $content;
    }

}
