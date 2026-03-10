<?php

class Webp{

    private $files = [];

    public function seek_dir($dir, $notdeep=false){
        foreach(glob($dir . "/*") as $file){
            if(is_dir($file)){
                if($notdeep === false){
                
                }else{
                    $this->seek_dir($file, $notdeep);
                }
            }elseif(is_file($file) && preg_match('/(?i)(.*)(\.jpe?g|\.png)/', $file)){
                $this->files[] = $file;
            }
        }
    }

    public function create_webp($resize=false){

        foreach($this->files as $file){
            $input = $file;
            $output = $input . ".webp";

            if(file_exists($output)){
                // exists 
                echo "exists -> ";
                $size = filesize($output);
                list($width, $height, $type, $attr) = getimagesize($output);
                
                if($resize > 0 && max($width, $height) > $resize){
                    $baseImage = imageCreateFromWebp($output);
                    $alpha = $resize / max($width, $height);
                    $w = $width  * $alpha;
                    $h = $height * $alpha;
                    $image = imagecreatetruecolor($w, $h);
                    imagecopyresampled($image, $baseImage, 0, 0, 0, 0, $w, $h, $width, $height);
                    imageWebp($image, $output);
                    imagedestroy($image);


                    $size2 = filesize($output);
                    list($width2, $height2, $type2, $attr2) = getimagesize($output);
                    echo $output . " size " . $this->FileSizeConvert($size) . " (w,h)=(" . $width . ",".$height .") -> resize " . $this->FileSizeConvert($size2) . " (w,h)=(" . $width2 . ",".$height2 .") " .PHP_EOL;

                }else{
                    echo $output . " size " . $this->FileSizeConvert($size) . " (w,h)=(" . $width . ",".$height .")" .PHP_EOL;
                }

            }else{
                $im = null;
                $pi = pathinfo($input);
                list($width, $height, $type, $attr) = getimagesize($input);
                if(in_array( strtolower($pi["extension"]),["jpeg","jpg"] )){
                    echo "JPEG -> ";
                    $im = imageCreateFromJpeg($input);

                    //$image = imagecreatetruecolor(100, 100); // サイズを指定して新しい画像のキャンバスを作成
                    // 画像のコピーと伸縮
                    //imagecopyresampled($image, $baseImage, 0, 0, 0, 0, 100, 100, $width, $hight);


                    imageWebp($im, $output);
                    imagedestroy($im);
                }elseif(in_array( strtolower($pi["extension"]),["png"] ) ){
                    echo "PNG -> ";
                    $im = imageCreateFromPng($input);
                    imagepalettetotruecolor($im);
                    imagealphablending($im, true);
                    imageWebp($im, $output);
                    imagedestroy($im);                
                }

                if($im){
                    echo $output . PHP_EOL;
                }
            }

        }
    }

    function FileSizeConvert($bytes)
    {
        $bytes = floatval($bytes);
            $arBytes = array(
                0 => array(
                    "UNIT" => "TB",
                    "VALUE" => pow(1024, 4)
                ),
                1 => array(
                    "UNIT" => "GB",
                    "VALUE" => pow(1024, 3)
                ),
                2 => array(
                    "UNIT" => "MB",
                    "VALUE" => pow(1024, 2)
                ),
                3 => array(
                    "UNIT" => "KB",
                    "VALUE" => 1024
                ),
                4 => array(
                    "UNIT" => "B",
                    "VALUE" => 1
                ),
            );

        foreach($arBytes as $arItem)
        {
            if($bytes >= $arItem["VALUE"])
            {
                $result = $bytes / $arItem["VALUE"];
                $result = str_replace(".", "," , strval(round($result, 2)))." ".$arItem["UNIT"];
                break;
            }
        }
        return $result;
    }

    public static function main($args){
        var_dump($args);
        $seek_dir = $args[1];
        $resize = isset($args[2]) ? $args[2] : false;
        $notdeep =  isset($args[3]) ? $args[3] : false;

        $inst = new self;
        $inst->seek_dir($seek_dir, $notdeep);
        $inst->create_webp($resize);

        //var_dump($inst->files);
    }
}
# php7.3 webp.php ./html/template/default/assets/img
$args = $_SERVER["argv"];
Webp::main($args);