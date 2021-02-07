<?php
class SprBaseController {
    public function __construct($spr) {
        $this->spr = $spr;
    }

    protected function LoadCommon($name, $ioc=false, $ioc_params=[]) {
        $file = join([$this->spr["approot"], "/Common/", $name, ".php"]);
        require_once($file);
        if ($ioc === true) {
            $class = new ReflectionClass($name);
            return $class->newInstanceArgs($ioc_params);
        }
        return null;
    }

    protected function View($viewbeg, $controller="", $action="", $layout="Default") {
        $spr = $this->spr;
        $viewpath   = join([$spr["approot"], "/Views"]);
        $layoutfile = join([$viewpath, "/Layouts/", $layout, ".php"]);
        if ($controller == "") { $controller = $spr["mvc"]["controller"]["name"]; }
        if ($action     == "") { $action     = $spr["mvc"]["action"]    ["name"]; }
        $view     = join([$controller, ".", $action]);
        $viewfile = join([$spr["approot"], "/Views/", $view, ".php"]);
        $viewbeg_meta = compact("spr", "viewpath", "controller", "action", "layout", "layoutfile", "view", "viewfile");
        foreach ($viewbeg_meta as $key => $value) {
            $viewbeg["__$key"] = $value;
        }
        SprView::View($viewbeg);
    }
}