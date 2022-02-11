<?php


namespace views;


class PrintJson
{
    private function __construct() {}


    static public function OperationSuccessful(array $Out = [])
    {
        $Out['status'] = 'ok';
        View::Print("JsonResponse", $Out);
    }


    static public function OperationAccessDenied(string $Error)
    {
        View::Print("CodeErrors", ["Code" => ACCESS_DENIED]);
        View::Print("JsonResponse",
            [
                "status"    => "error",
                "error"     => $Error,
                "code"      => ACCESS_DENIED
            ]);
    }


    static public function OperationError(string $Error, int $Code = -1)
    {
        View::Print("CodeErrors", ["Code" => $Code < 0 ? REQUEST_FAILED : $Code]);
        View::Print("JsonResponse",
            [
                "status"    => "error",
                "error"     => $Error,
                "code"      => $Code
            ]);
    }
}