<?php


namespace classes;


use Exception;


class Image
{
    private $Image;


    public function __construct(string $File)
    {
        if(File::Exists($File))
            $this->Image = imagecreatefromstring(File::ReadAllText($File));
        else
            $this->Image = imagecreatefromstring($File);
    }


    public function GetWidth() : int
    {
        $Out = imagesx($this->Image);
        if($Out === false)
            throw new Exception("Get Width Error");
        return empty($Out) ? 0 : $Out;
    }


    public function GetHeight() : int
    {
        $Out = imagesy($this->Image);
        if($Out === false)
            throw new Exception("Get Height Error");
        return empty($Out) ? 0 : $Out;
    }


    public function GetPixel(int $X, int $Y) : array
    {
        if($X < 0 || $Y < 0 || $X > $this->GetWidth() || $Y > $this->GetHeight()) throw new Exception("Coordinates are out of bounds");
        
        $RGB = imagecolorat($this->Image, $X, $Y);
        if($RGB == false) throw new Exception("Unknown error");
        return ["R" => ($RGB >> 16) & 0xFF, "G" => ($RGB >> 8) & 0xFF, "B" => $RGB & 0xFF];
    }


    public function SetPixel(int $X, int $Y, int $R, int $G, int $B)
    {
        if($X < 0 || $Y < 0 || $X > $this->GetWidth() || $Y > $this->GetHeight())       throw new Exception("Coordinates are out of bounds");
        if($R < 0 || $R > 255 || $G < 0 || $G > 255 || $B < 0 || $B > 255)              throw new Exception("Invalid color values");
        
        if(!imagesetpixel($this->Image, $X, $Y, imagecolorallocate($this->Image, $R, $G, $B)))
            throw new Exception("Unknown error");
    }


    public function Resize(int $Width, int $Height)
    {
        $Coefficient1   = $Width / $this->GetWidth();
        $Coefficient2   = $Height / $this->GetHeight();
        $Coefficient    = $Coefficient1 > $Coefficient2 ? $Coefficient2 : $Coefficient1;

        $Width  = intval($this->GetWidth() * $Coefficient);
        $Height = intval($this->GetHeight() * $Coefficient);

        $NewImage = imagecreatetruecolor($Width, $Height);
        if(!imagecopyresampled($NewImage, $this->Image, 0, 0, 0, 0, $Width, $Height, $this->GetWidth(), $this->GetHeight()))
            throw new Exception("Unknown error");

        $this->Image = $NewImage;
    }


    /**
     * @return string|Exception Image
     */
    private function GetImage(string $Type = "JPG")
    {
        ob_start();

        switch($Type)
        {
            case "JPG":
            case "JPEG":
                imagejpeg($this->Image);
                break;

            case "PNG":
                imagepng($this->Image);
                break;

            case "BMP":
                imagebmp($this->Image);
                break;

            case "GIF":
                imagegif($this->Image);
                break;

            default:
                throw new Exception("Unknown type");
            break;
        }

        $Out = ob_get_contents();
        ob_end_clean();
        return $Out;
    }


    private function Save(string $FileName, string $Type)
    {
        File::WriteAllText($FileName, $this->GetImage($Type));
    }


    /**
     * @return string Image PNG
     */
    public function GetImagePng()
    {
        return $this->GetImage("PNG");
    }


    /**
     * @return string Image JPEG
     */
    public function GetImageJpeg()
    {
        return $this->GetImage("JPEG");
    }


    /**
     * @return string Image GIF
     */
    public function GetImageGif()
    {
        return $this->GetImage("GIF");
    }


    /**
     * @return string Image BMP
     */
    public function GetImageBmp()
    {
        return $this->GetImage("BMP");
    }


    public function SaveImagePng(string $FileName)
    {
        return $this->Save($FileName, "PNG");
    }


    public function SaveImageJpeg(string $FileName)
    {
        return $this->Save($FileName, "JPEG");
    }


    public function SaveImageGif(string $FileName)
    {
        return $this->Save($FileName, "GIF");
    }


    public function SaveImageBmp(string $FileName)
    {
        return $this->Save($FileName, "BMP");
    }
}