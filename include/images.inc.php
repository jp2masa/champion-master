<?php
    class Images
    {
        static function GetImage($image_name)
        {
            $image = file_get_contents("img/$image_name.png");
            $base64 = base64_encode($image);

            return "data:image/png;base64,$base64";
        }

        static function GetImageByURL($url)
        {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $image = curl_exec($ch);
            $base64 = base64_encode($image);

            curl_close($ch);

            return "data:image/png;base64,$base64";
        }
    }
?>
