<?php
$_SPR = array();

$_SPR['mime'] = array(
    'html' => 'text/html',
    'css'  => 'text/css',
    'js'   => 'application/javascript',
    'txt'  => 'text/css',
    'json' => 'application/json',
    'xml'  => 'text/xml',
    '*'    => 'application/octet-stream'
);

$_SPR['preg'] = array(
    'uint'   => '([0-9]+)',
    'int'    => '(-?[0-9]+)',
    'float'  => '(-?[0-9]+\.[0-9]+)',
    'guid'   => '([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})',
    'date'   => '([0-9]{4}-[0-9]{2}-[0-9]{2})',
    'word'   => '([a-z0-9_\-]+)',
    'uword'  => '([a-z0-9_]+)',
    'dword'  => '([a-z0-9\-]+)',
    'alpha'  => '([a-z]+)',
);

$_SPR["extensions"] = [];

function spr_run(){
    global $_SPR;
    $_SPR['uri'] = $_SERVER['REQUEST_URI'];
    if (strpos($_SPR['uri'], '?') !== false){
        $_SPR['uri'] = substr($_SPR['uri'], 0, strpos($_SPR['uri'], '?'));
    }
    if (($_SPR['uri'][strlen($_SPR['uri']) - 1] === '/') && ($_SPR['uri'] !== '/')) {
        $_SPR['uri'] = substr($_SPR['uri'], 0, strlen($_SPR['uri']) - 1);
    }

    foreach ($_SPR["extensions"] as $extension) {
        $extension["onload"]($_SPR);
    }

    foreach($_SPR['routes'] as $route => $action){
        $route = preg_quote($route, '/');
        $params_count = 0;
        foreach($_SPR['preg'] as $preg_type => $preg_expr){
            $route = str_replace('\\{'.$preg_type.'\\}', $preg_expr, $route, $_replace_count);
            $params_count += $_replace_count;
        }
        $route = '/^' . $route . '$/i';
        $_SPR['url_matches'] = [];
        $preg_result = preg_match($route, $_SPR['uri'], $_SPR['url_matches']);
        if ($preg_result === 0){ continue; }
        $_SPR['params'] = [];
        for($i=1; $i <= $params_count; $i++) {
            $_SPR['params'] []= $_SPR['url_matches'][$i];
        }
        $action();
        exit();
    }
    http_response_code(404);
    exit($_SPR['error_404']);
}

function spr_get() {
    global $_SPR;
    return $_SPR;
}

function spr_static($path, $mime=null, $encoding='utf-8', $cache=false){
    global $_SPR;
    if (is_null($mime)){
        $fileinfo = explode('.', $path);
        $file_ext = $fileinfo[count($fileinfo) - 1];
        if (array_key_exists($file_ext, $_SPR['mime'])){
            $mime = $_SPR['mime'][$file_ext];
        }else{
            $mime = $_SPR['mime']['*'];
        }
    }

    return (function () use ($wwwpath, $mime, $encoding, $cache) {
        $path = $_SPR['wwwroot'] . $wwwpath;
        if ($cache === 'etag') {
            if (!is_null($_SERVER['HTTP_IF_NONE_MATCH'])) {
                $client_version = $_SERVER['HTTP_IF_NONE_MATCH'];
            } else {
                $client_version = '""';
            }
            $server_version = '"' . sha1_file($path) . '"';
            header("ETag: $server_version");

            if ($server_version === $client_version) {
                http_response_code(304);
                exit();
            }
        } else if ($cache === 'time') {
            if (!is_null($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
                $client_version = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
            } else {
                $client_version = 0;
            }
            $server_version = filemtime($path);
            $server_version_str = gmdate('D, d M Y H:i:s', $server_version);
            header("Last-Modified: $server_version_str GMT");

            if ($server_version === $client_version) {
                http_response_code(304);
                exit();
            }
        } else {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header("Expires: 0"); 
        }

        header("Content-type: $mime; charset=$encoding");
        exit(file_get_contents($path));
    });
}

function spr_redirect($url, $code=302){
    return (function () use ($url, $code){
        http_response_code($code);
        header("Location: $url");
        exit();
    });
}

function spr_app($path){
    return (function ()  use ($path) {
        $_SPR = spr_get();
        $_SPR["script"] = join([$_SPR['approot'], $path]);
        unset($path);
        include $_SPR["script"];
        exit();
    });
}
