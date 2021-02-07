<?php
$_SPR['wwwroot'] = dirname(__FILE__).'/public';
$_SPR['approot'] = dirname(__FILE__).'/app';
$_SPR['error_404'] = "404 Not Found";

$_SPR['routes'] = array(
    '/'    => spr_mvc('Home', 'Index'),
    '/api' => spr_api('Api',  'HelloWorld'),
);
