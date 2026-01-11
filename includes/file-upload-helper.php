<?php
function uploadFile($fileInputName, $uploadDirectory, $allowedTypes, $maxSize) {
    // Check if a file was uploaded and if the upload was successful
    // NOTE: Returning null allows calling code to distinguish between "no file uploaded" and "validation failed"
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // Extract file metadata from the superglobal $_FILES array
    $fileName = $_FILES[$fileInputName]['name'];
    $fileSize = $_FILES[$fileInputName]['size'];
    $fileTmpPath = $_FILES[$fileInputName]['tmp_name'];
    $fileMimeType = $_FILES[$fileInputName]['type'];

    // Validate file size to prevent denial-of-service attacks from excessively large files
    if ($fileSize > $maxSize) {
        return "File size exceeds file size limit.";
    }

    // Validate file type to prevent malicious file uploads (e.g., PHP scripts disguised as images)
    // NOTE: MIME type validation is a first line of defense; production systems should also verify file contents
    if (!in_array($fileMimeType, $allowedTypes)) {
        return "File type not allowed.";
    }

    // Ensure the upload directory exists; create it recursively if it doesn't
    if (!is_dir($uploadDirectory)) {
        mkdir($uploadDirectory, 0777, true);
    }

    // Generate a unique filename to prevent file overwrites and path traversal attacks
    // NOTE: uniqid() creates a unique identifier based on the current time in microseconds, ensuring filename uniqueness
    $uniqueFileName = uniqid() . "_" . basename($fileName);

    $destinationPath = $uploadDirectory . '/' . $uniqueFileName;

    // Move the uploaded file from the temporary location to the final destination
    if (move_uploaded_file($fileTmpPath, $destinationPath)) {
        // Return the relative path starting from 'uploads/' for storage in the database
        // This makes file paths portable across different server configurations
        $relativePath = substr($destinationPath, strpos($destinationPath, 'uploads/'));
        return $relativePath;
    } else {
        return "Failed to save the uploaded file.";
    }
}
