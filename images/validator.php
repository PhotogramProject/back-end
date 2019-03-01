<?php
function getGps($exifCoord, $hemi) {

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

$file = $_FILES["imageToValidate"]["tmp_name"];
$exif = exif_read_data($file);
$filesize = filesize($file);

if (array_key_exists('Make', $exif) == true && array_key_exists('Model', $exif) == true && array_key_exists('DateTimeOriginal', $exif) == true) {

    $location = [];
    if (array_key_exists('GPSLongitude', $exif) == true && array_key_exists('GPSLatitude', $exif) == true) {
        $location = [
            getGps($exif['GPSLatitude'], $exif['GPSLatitudeRef']),
            getGps($exif['GPSLongitude'], $exif['GPSLongitudeRef'])
        ];
    }

    echo json_encode([
        'success' => true,
        'msg' => 'Снимката съдържа EXIF данни.',
        'data' => [
            'hasExif' => true,
            'location' => $location,
            'dateTaken' => $exif['DateTimeOriginal'],
            'size' => $filesize
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'success' => true,
        'msg' => 'Снимката не съдържа EXIF данни.',
        'data' => [
            'hasExif' => false,
            'location' => [],
            'size' => $filesize
        ]
    ], JSON_UNESCAPED_UNICODE);
}