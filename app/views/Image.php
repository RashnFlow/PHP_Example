<?php

header("Content-type: image/" . $Parameters["Type"]);

$Image = imagecreatefromstring($Parameters["Image"]);

switch($Parameters["Type"])
{
    case "jpg":
    case "jpeg":
        imagejpeg($Image);
        break;

    case "png":
        imagepng($Image);
        break;

    case "gif":
        imagegif($Image);
    break;

    default:
        throw new Exception($Parameters["Type"] . " image is not supported");
    break;
}