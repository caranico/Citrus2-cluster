<?php

namespace Citrus\Cluster\Loader;

class DbLoader implements Loader {
    protected $content;

    static public function get( $config, $exist = array() ) {
        $inst = new JsonLoader( $config );
        return array_merge( $exist, $inst->getContent() );
    }

    public function __construct( $config ) {

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
