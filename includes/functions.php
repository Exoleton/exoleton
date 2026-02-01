<?php
function uploadProductMedia($file, $product_id) {
    $targetDir = "uploads/products/" . $product_id . "/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $newFileName = uniqid('prod_') . ".webp"; // On force le WebP
    $targetPath = $targetDir . $newFileName;

    // Conversion et Redimensionnement simple (Max 1200px)
    list($width, $height) = getimagesize($file['tmp_name']);
    $newWidth = 1200;
    $newHeight = ($height / $width) * $newWidth;

    $src = ($extension == 'png') ? imagecreatefrompng($file['tmp_name']) : imagecreatefromjpeg($file['tmp_name']);
    $dst = imagecreatetruecolor($newWidth, $newHeight);

    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    imagewebp($dst, $targetPath, 80); // Sauvegarde en WebP qualité 80%

    return $newFileName;
}
?>
