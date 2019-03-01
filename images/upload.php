<?php
function getFocalLength($focal_len)
{
    $focal = explode("/", $focal_len);
    return floatval($focal[0]) / floatval($focal[1]);
}

function getFileName($dateTaken)
{
    $tmp = explode(" ", $dateTaken)[0];
    $t = time() + mt_rand(0, 1000000);
    return [str_replace(":", "/", $tmp) . "/" . $t . "/", $t];
}

function getGps($exifCoord, $hemi)
{
    $degrees = count($exifCoord) > 0 ? gps2Num($exifCoord[0]) : 0;
    $minutes = count($exifCoord) > 1 ? gps2Num($exifCoord[1]) : 0;
    $seconds = count($exifCoord) > 2 ? gps2Num($exifCoord[2]) : 0;

    $flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;

    return round($flip * ($degrees + $minutes / 60 + $seconds / 3600), 5);
}

function gps2Num($coordPart)
{
    $parts = explode('/', $coordPart);

    if (count($parts) <= 0)
        return 0;

    if (count($parts) == 1)
        return $parts[0];

    return floatval($parts[0]) / floatval($parts[1]);
}

function resizeImage($file_name) {
    $dir = __DIR__ . "/../../uploads/images/";
    $sizes = [
        '_s.jpg' => 400,
        '_m.jpg' => 600,
        '_l.jpg' => 950,
    ];

    $photo = imagecreatefromjpeg($dir . $file_name . '_o.jpg');
    $orig_width = imagesx($photo);
    $orig_height = imagesy($photo);

    foreach ($sizes as $size => $newwidth) {
        $w = $newwidth;
        $newheight = ($newwidth * $orig_height) / $orig_width;
        $new_photo = imagecreatetruecolor($newwidth, $newheight);

        if ($size == '_m.jpg') {
            if ($newheight < 400) {
                $w = (600 * 400) / $newheight;
                $newheight = 400;
            }
        }

        imagecopyresampled($new_photo, $photo,
            0, 0, 0, 0,
            $w, $newheight,
            $orig_width, $orig_height);
        imagejpeg($new_photo, $dir . $file_name . $size, 90);
    }
}

$flash_arr = [
    '0' => 'No Flash',
    '1' => 'Fired',
    '5' => 'Fired, Return not detected',
    '7' => 'Fired, Return detected',
    '8' => 'On, Did not fire',
    '9' => 'On, Fired',
    'd' => 'On, Return not detected',
    'f' => 'On, Return detected',
    '10' => 'Off, Did not fire',
    '14' => 'Off, Did not fire, Return not detected',
    '18' => 'Auto, Did not fire',
    '19' => 'Auto, Fired',
    '1d' => 'Auto, Fired, Return not detected',
    '1f' => 'Auto, Fired, Return detected',
    '20' => 'No flash function',
    '30' => 'Off, No flash function',
    '41' => 'Fired, Red-eye reduction',
    '45' => 'Fired, Red-eye reduction, Return not detected',
    '47' => 'Fired, Red-eye reduction, Return detected',
    '49' => 'On, Red-eye reduction',
    '4d' => 'On, Red-eye reduction, Return not detected',
    '4f' => 'On, Red-eye reduction, Return detected',
    '50' => 'Off, Red-eye reduction',
    '58' => 'Auto, Did not fire, Red-eye reduction',
    '59' => 'Auto, Fired, Red-eye reduction',
    '5d' => 'Auto, Fired, Red-eye reduction, Return not detected',
    '5f' => 'Auto, Fired, Red-eye reduction, Return detected',
];

$image_data = json_decode($_POST['imageDetails']);
$image = $_FILES["imageToUpload"];
$exif = exif_read_data($image["tmp_name"]);

if (array_key_exists('Make', $exif) == true && array_key_exists('Model', $exif) == true && array_key_exists('DateTimeOriginal', $exif) == true) {
    $query = "INSERT INTO `images`(`journey_id`, `make`, `model`, `date_taken`, `lat`, `lon`, `resolution_w`, `resolution_h`, `flash`, `iso`, `focal_length`, `file_name`, `size`, `comment`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    if ($stmt = $mysqli->prepare($query)) {
        $stmt->bind_param('isssddiisidsds', $journey_id, $make, $model, $date_taken, $lat, $lon, $res_w, $res_h, $flash, $iso, $focal_length, $file_name, $size, $image_comment);

        $date_taken = $exif['DateTimeOriginal'];
        $file_name_arr = getFileName($date_taken);
        $image_dir = $file_name_arr[0];
        $image_filename = $file_name_arr[1] . "_o.jpg";
        $file_name = $image_dir . $file_name_arr[1];

        $dir = __DIR__ . "/../../uploads/images/";
        if (!file_exists($dir . $image_dir)) {
            mkdir($dir . $image_dir, 0777, true);
        }
        move_uploaded_file($image['tmp_name'], $dir . $image_dir . $image_filename);
        resizeImage($file_name);

        $make = trim($exif['Make']);
        $model = trim($exif['Model']);
        $lat = (array_key_exists('GPSLatitude', $exif) == true && array_key_exists('GPSLatitudeRef', $exif) == true) ? getGps($exif['GPSLatitude'], $exif['GPSLatitudeRef']) : 0;
        $lon = (array_key_exists('GPSLongitude', $exif) == true && array_key_exists('GPSLongitudeRef', $exif) == true) ? getGps($exif['GPSLongitude'], $exif['GPSLongitudeRef']) : 0;
        if (!array_key_exists('COMPUTED', $exif)) {
            $res_w = 0;
            $res_h = 0;
        } else {
            $res_w = floatval($exif['COMPUTED']['Width']);
            $res_h = floatval($exif['COMPUTED']['Height']);
        }
        $flash = (array_key_exists('Flash', $exif) == true) ? $flash_arr[dechex($exif['Flash'])] : "";
        $iso = (array_key_exists('ISOSpeedRatings', $exif) == true) ? intval($exif['ISOSpeedRatings']) : 0;
        $focal_length = (array_key_exists('FocalLength', $exif) == true) ? getFocalLength($exif['FocalLength']) : 0;
        $size = (array_key_exists('FileSize', $exif)) ? floatval($exif['FileSize'] / 1024.0) : 0;

        $image_comment = $image_data->comment;
        $journey_id = $image_data->journeyID;

        $stmt->execute();
        $stmt->free_result();

        echo json_encode([
            'success' => true,
//            'image_data' => json_decode($image_data)
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode([
        'success' => false,
        'msg' => 'Възникна грешка при обработването на снимката.'
    ], JSON_UNESCAPED_UNICODE);
}