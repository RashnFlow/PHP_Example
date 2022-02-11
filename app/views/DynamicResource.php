<?php

use classes\ResourceStream;
use interfaces\IResource;

$Resource = $Parameters["Resource"];
if(!($Resource instanceof IResource))
    throw new Exception('Resource not instanceof IResource');


switch($Parameters["IsDownload"] ? null : $Resource->GetType())
{
    case 'video/mp4':
    case 'audio/mpeg':
        header("Content-type: " . $Resource->GetType());

        $ResourceStream = new ResourceStream($Resource->GetPath(), 'rb');

        $Size = $ResourceStream->GetSize();

        $Begin  = 0;
        $End    = 1000024;

        if(isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $Matches))
        {
            $Begin  = intval($Matches[1]);
            $End    += $Begin;
        }

        if($End > $Size)
            $End = $Size;

        if($End != $Size)
            header('HTTP/1.0 206 Partial Content');
        else
            header('HTTP/1.0 200 OK');

        header('Accept-Ranges: bytes');
        header('Content-Length:'. ($End - $Begin));
        header("Content-Range: bytes $Begin-$End/$Size");

        print $ResourceStream->ReadBytes($Begin, ($End - $Begin));
        break;

    case 'image/jpeg':
    case 'image/png':
        views\View::Print("Image", ["Type" => explode('/', $Resource->GetType())[1], "Image" => $Resource->GetResource()]);
        break;

    default:
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $Resource->GetType());
        header('Content-Disposition: attachment; filename=' . $Resource->GetName());
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . $Resource->GetSize());
        echo $Resource->GetResource();
    break;
}