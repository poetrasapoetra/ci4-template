<?php

function getResponse(bool $success, string $message, int $code = 200): array
{
    return array("success" => $success, "message" => $message, "code" => $code);
}

function getResponseWithData(bool $success, string $message, $data, int $code = 200): array
{
    $d = getResponse($success, $message, $code);
    $d["data"] = $data;
    return $d;
}

function getResponseWithRedirect(bool $success, string $message, string $redirect, int $code = 200): array
{
    $d = getResponse($success, $message, $code);
    $d["redirect"] = $redirect;
    return $d;
}

function throw404(?string $message = null)
{
    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound($message);
}
