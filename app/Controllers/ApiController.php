<?php
class ApiController extends SprApiController {
    public function __construct($spr) {
        parent::__construct($spr);
        // if you need database connection
        // $this->sql = $this->LoadCommon("SprDatabase", ioc: true, ioc_params: [$spr]);
        // $this->sql->connect();
    }

    public function HelloWorld() {
        $this->Success(self::HttpOk, array(
            "code" => 0,
            "message" => "OK",
            "data" => "Hello, World!"
        ));
    }
}
