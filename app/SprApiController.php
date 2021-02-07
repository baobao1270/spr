<?php
class SprApiController extends SprBaseController {
    const HttpOk       = 200;
    const HttpCreated  = 201;
    const HttpNoConent = 204;
    const HttpRedirect = 302;
    const HttpErrorBadRequest = 400;
    const HttpErrorForbidden  = 403;
    const HttpErrorNotFound   = 404;
    const HttpError           = 500;
    const HttpErrorMethodNotAllowed = 405;

    protected function AssertMethod($methods=["GET"]) {
        if (in_array($_SERVER['REQUEST_METHOD'], $methods)) {
            return;
        }
        $this->Failed(self::HttpErrorMethodNotAllowed, join(["Allowed ", join(", ", $methods), " but you requested ", $_SERVER['REQUEST_METHOD'],]));
    }

    protected function FromBody() {
        return file_get_contents("php://input");
    }

    protected function JsonFromBody($class=false) {
        return json_decode($this->FromBody(), !$class);
    }

    protected function Redirect($url) {
        header("Location: $url");
        $this->Failed(self::HttpRedirect, $url);
    }

    public function Failed($status=500, $extra_info="") {
        $messages = array(
            self::HttpErrorBadRequest => "Bad Request",
            self::HttpErrorForbidden  => "Forbidden",
            self::HttpErrorNotFound   => "Not Found",
            self::HttpError           => "Internal Server Error",
            self::HttpRedirect        => "Found",
            self::HttpErrorMethodNotAllowed => "Method Not Allowed"
        );
        $message = $messages[$status];
        $this->Success($status, "$status $message\n$extra_info", "text/plain");
    }

    protected function Success($status=200, $content=null, $ct=null) {
        ob_clean();
        http_response_code($status);
        if (is_null($content)) {
            exit();
        }
        if (is_string($content)) {
            $ct = is_null($ct) ? "text/plain" : $ct;
            header(join(["Content-Type: ", $ct, "; charset=utf-8"]));
            exit($content);
        }
        $ct = is_null($ct) ? "application/json" : $ct;
        header(join(["Content-Type: ", $ct, "; charset=utf-8"]));
        exit(json_encode($content));
    }
}
