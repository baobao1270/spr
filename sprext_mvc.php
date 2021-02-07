<?php
$_SPR["extensions"][] = array(
    "name" => "mvc",
    "onload" => function($spr) {
        ini_set("display_errors", false);
        ini_set("error_log", join([$spr["approot"], "/sprext_mvc_errors.log"]));
        ini_set("error_reporting", E_ALL);
        ini_set("log_errors", true);
        ini_set("track_errors", true);
        require_once(join([$spr["approot"], "/SprBaseController.php"]));
        require_once(join([$spr["approot"], "/SprApiController.php"]));
        require_once(join([$spr["approot"], "/SprView.php"]));
    }
);

function spr_mvc($controller="Home", $action="Index", $content_type="text/html; charset=utf8") {
    return (function ()  use ($controller, $action, $content_type) {
        header("Content-Type: $content_type");
        unset($content_type);
        $_SPR = spr_get();
        $_SPR["mvc"] = [];
        $_SPR["mvc"]["controller"] = [];
        $_SPR["mvc"]["action"]     = [];
        $_SPR["mvc"]["controller"]["name"] = $controller; unset($controller);
        $_SPR["mvc"]["action"]    ["name"] = $action;     unset($action);
        $_SPR["script"] = join([
            $_SPR['approot'],
            "/Controllers/",
            $_SPR["mvc"]["controller"]["name"],
            "Controller.php"]);
        require_once($_SPR["script"]);
        $_SPR["mvc"]["controller"]["class_name"] = join([$_SPR["mvc"]["controller"]["name"], "Controller"]);
        $_SPR["mvc"]["controller"]["class"] = new $_SPR["mvc"]["controller"]["class_name"]($_SPR);
        $_SPR["mvc"]["action"]["reflection"] =
            new ReflectionMethod($_SPR["mvc"]["controller"]["class"], $_SPR["mvc"]["action"]["name"]);
        $_SPR["mvc"]["action"]["reflection"]->invokeArgs($_SPR["mvc"]["controller"]["class"], $_SPR["params"]);
        exit();
    });
}

function spr_api($controller="ApiHome", $action="Index", $type="json") {
    $_SPR = spr_get();
    return spr_mvc($controller, $action, join([$_SPR['mime'][$type], "; charset=utf8"]));
}
