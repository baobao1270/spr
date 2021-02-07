<?php
class SprView {
    public static function View($__viewbeg) {
        extract($__viewbeg);
        require($__layoutfile);
    }

    public static function InsertBody ($__viewbeg) {
        extract($__viewbeg);
        require($__viewfile);
    }

    public static function InsertPartial ($__viewbeg, $__partial) {
        $__viewbeg["__partialfile"] = join([$__viewbeg["__spr"]["approot"], "/Views/Partials/", $__partial, ".php"]);
        extract($__viewbeg);
        require($__partialfile);
    }
}