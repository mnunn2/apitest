<?php
/**
 * Created by PhpStorm.
 * User: Mike Nunn
 * Date: 26/04/2018
 * Time: 15:27
 */

use \Psr\Http\Message\ServerRequestInterface as Req;
use \Psr\Http\Message\ResponseInterface as Resp;

$app->add(function (Req $request, Resp $response, $next) {
    $response->getBody()->write("BEFORE\n");
    $response = $next($request, $response);
    $response->getBody()->write("AFTER\n");

    return $response;
});
