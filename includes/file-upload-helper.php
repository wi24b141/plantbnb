<?php
// ==================================================================
// FILE UPLOAD HELPER FUNCTION
// ==================================================================
// This function handles file uploads to avoid repeating the same code
// It checks file size, file type, creates directories, and moves files
// ==================================================================

function uploadFile($fileInputName, $uploadDirectory, $allowedTypes, $maxSize) {
    // Check if a file was uploaded
    // $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK means no errors (0 = success)
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        // No file uploaded or upload error occurred
        return null;
    }

    // Get file information
    $fileName = $_FILES[$fileInputName]['name'];
    $fileSize = $_FILES[$fileInputName]['size'];
    $fileTmpPath = $_FILES[$fileInputName]['tmp_name'];
    $fileMimeType = $_FILES[$fileInputName]['type'];

    // VALIDATION STEP 1: Check file size
    // File must be smaller than the maximum allowed size
    if ($fileSize > $maxSize) {
        return "File size exceeds file size limit.";
    }

    // VALIDATION STEP 2: Check file type
    // File must be one of the allowed MIME types
    if (!in_array($fileMimeType, $allowedTypes)) {
        return "File type not allowed.";
    }

    // Create the upload directory if it doesn't exist
    // This ensures the directory is ready to receive the file
    if (!is_dir($uploadDirectory)) {
        // Directory doesn't exist, so create it
        // 0777 = full permissions, true = create parent directories if needed
        mkdir($uploadDirectory, 0777, true);
    }

    // Generate unique filename to prevent overwriting existing files
    // uniqid() creates a unique ID based on current timestamp
    $uniqueFileName = uniqid() . "_" . basename($fileName);

    // Build the full destination path (absolute file system path for moving the file)
    $destinationPath = $uploadDirectory . '/' . $uniqueFileName;

    // Move uploaded file from temporary location to permanent location
    // move_uploaded_file() is the secure way to handle uploads
    if (move_uploaded_file($fileTmpPath, $destinationPath)) {
        // Success! Return ONLY the relative path for database storage
        // WHY: The browser needs a relative path, not the full file system path
        // Extract just the uploads/folder/filename part
        // We look for 'uploads/' in the path and return everything from there
        $relativePath = substr($destinationPath, strpos($destinationPath, 'uploads/'));
        return $relativePath;
    } else {
        // File move failed
        return "Failed to save the uploaded file.";
    }
}
