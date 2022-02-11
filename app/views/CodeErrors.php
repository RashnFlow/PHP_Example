<?php

switch($Parameters["Code"])
{
    case NOT_FOUND:
        header("HTTP/1.0 404 Not Found");
        break;

    case ACCESS_DENIED:
        header("HTTP/1.0 403 Forbidden");
        break;

    case USER_NOT_AUTH:
        header("HTTP/1.0 401 Unauthorized");
        break;

    case SERVER_ERROR:
        header("HTTP/1.0 500 Internal Server Error");
        break;

    case REQUEST_FAILED:
    default:
        header("HTTP/1.0 400 Bad Request");
    break;
}