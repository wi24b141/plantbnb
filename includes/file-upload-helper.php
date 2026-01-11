<?php
function uploadFile($fileInputName, $uploadDirectory, $allowedTypes, $maxSize) {
    
    
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    
    $fileName = $_FILES[$fileInputName]['name'];
    $fileSize = $_FILES[$fileInputName]['size'];
    $fileTmpPath = $_FILES[$fileInputName]['tmp_name'];
    $fileMimeType = $_FILES[$fileInputName]['type'];

    
    if ($fileSize > $maxSize) {
        return "File size exceeds file size limit.";
    }

    
    
    if (!in_array($fileMimeType, $allowedTypes)) {
        return "File type not allowed.";
    }

    
    if (!is_dir($uploadDirectory)) {
        mkdir($uploadDirectory, 0777, true);
    }

    
    
    $uniqueFileName = uniqid() . "_" . basename($fileName);

    $destinationPath = $uploadDirectory . '/' . $uniqueFileName;

    
    if (move_uploaded_file($fileTmpPath, $destinationPath)) {
        
        
        $relativePath = substr($destinationPath, strpos($destinationPath, 'uploads/'));
        return $relativePath;
    } else {
        return "Failed to save the uploaded file.";
    }
}
