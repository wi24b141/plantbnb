<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    var_dump($_FILES);

    echo"<br />";
    echo"<br />";

    echo print_r($_FILES);

    //Bei ordner verschieben immer basename verwenden!!!

    $targetFileName = "uploads/" . basename($_FILES["uploadBild"]["name"]);

    move_uploaded_file($_FILES["uploadBild"]["tmp_name"], $targetFileName);


    // Ordner erstellen
    mkdir("uploads");

    //Datei löschen
    unlink("uploads/todelete.jpg");

    rename("frompath", "topath");

}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload</title>
</head>
<body>
    <h2>Datei Upload</h2>
        <!--enctype !!??-->
    <form method="POST" enctype="multipart/form-data">
        <label for="fileupload">Datei hochladen</label>
        <!--type file !!!! -->
        <input type="file" id="fileupload" name="uploadBild" />

        <br />
        <label for="newnam">Neuer Name</label>
        <input type="text" id="newname" name="newname" />
        <br />
        <button type="submit">Abschicken</button>
    </form>
    <!--<img src="<?php echo $targetFileName ?>" /> -->
    <img src="<?= $targetFileName ?>" />
</body>
</html>