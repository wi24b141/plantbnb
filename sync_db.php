<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DatabaseSync</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <form method="POST" action="#">
        <input type="radio" id="import" name="import_export" value="import">
        <label for="import">Import</label><br>  
        <input type="radio" id="export" name="import_export" value="export">
        <label for="export">Export</label><br><br>
        <input type="submit" value="Submit">
    </form>
</body>
</html>




<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    $dbhost = "localhost";
    $dbuser = "root";
    $dbpassword = "";
    $dbdatabase = "plantbnb_db";

if(isset($_POST["import_export"])){
    if($_POST["import_export"] == "import"){
        importDatabase();
    }elseif($_POST["import_export"] == "export"){
        exportDatabase();
    }
}

function importDatabase():void{
    global $dbhost, $dbuser, $dbpassword;
    $statement = "
    CREATE DATABASE IF NOT EXISTS plantbnb_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
    USE plantbnb_db;

    DROP TABLE IF EXISTS `applications`;
    DROP TABLE IF EXISTS `favorites`;
    DROP TABLE IF EXISTS `listings`;
    DROP TABLE IF EXISTS `listing_photos`;
    DROP TABLE IF EXISTS `messages`;
    DROP TABLE IF EXISTS `plants`;
    DROP TABLE IF EXISTS `ratings`;
    DROP TABLE IF EXISTS `users`;
    DROP TABLE IF EXISTS `user_tokens`;
    DROP TABLE IF EXISTS `verifications`;
    ";
    $file_contents = file_get_contents("plantbnb_db.sql");
    if ($file_contents === false) {
        echo "SQL file 'plantbnb_db.sql' not found or not readable.";
        return;
    }
    $statement .= $file_contents;

    $db_obj = new mysqli($dbhost, $dbuser, $dbpassword);
    if ($db_obj->connect_error) {
        echo "Connection Error: " . $db_obj->connect_error;
        exit();
    }

    if (!$db_obj->multi_query($statement)) {
        echo "Import failed to start: " . $db_obj->error;
        return;
    }

    do {
        if ($result = $db_obj->store_result()) {
            $result->free();
        }
        if ($db_obj->error) {
            echo "Import error: " . $db_obj->error;
            return;
        }
    } while ($db_obj->more_results() && $db_obj->next_result());

    echo "Import Erfolgreich";
}
function exportDatabase():void{ 
    global $dbhost, $dbuser, $dbpassword, $dbdatabase;
    
    $connection = mysqli_connect($dbhost, $dbuser, $dbpassword, $dbdatabase);
    $backupAlert = '';
    $tables = array();
    $result = mysqli_query($connection, "SHOW TABLES");
    if (!$result) {
        $backupAlert = 'Error found.<br/>ERROR : ' . mysqli_error($connection) . 'ERROR NO :' . mysqli_errno($connection);
    } else {
        while ($row = mysqli_fetch_row($result)) {
            $tables[] = $row[0];
        }
        mysqli_free_result($result);

        $return = '';
        foreach ($tables as $table) {

            $result = mysqli_query($connection, "SELECT * FROM " . $table);
            if (!$result) {
                $backupAlert = 'Error found.<br/>ERROR : ' . mysqli_error($connection) . 'ERROR NO :' . mysqli_errno($connection);
            } else {
                $num_fields = mysqli_num_fields($result);
                if (!$num_fields) {
                    $backupAlert = 'Error found.<br/>ERROR : ' . mysqli_error($connection) . 'ERROR NO :' . mysqli_errno($connection);
                } else {
                    $return .= 'DROP TABLE IF EXISTS ' . $table . ';';
                    $row2 = mysqli_fetch_row(mysqli_query($connection, 'SHOW CREATE TABLE ' . $table));
                    if (!$row2) {
                        $backupAlert = 'Error found.<br/>ERROR : ' . mysqli_error($connection) . 'ERROR NO :' . mysqli_errno($connection);
                    } else {
                        $return .= "\n\n" . $row2[1] . ";\n\n";
                        for ($i = 0; $i < $num_fields; $i++) {
                            while ($row = mysqli_fetch_row($result)) {
                                $return .= 'INSERT INTO ' . $table . ' VALUES(';
                                for ($j = 0; $j < $num_fields; $j++) {
                                    $row[$j] = addslashes($row[$j]);
                                    if (isset($row[$j])) {
                                        $return .= '"' . $row[$j] . '"';
                                    } else {
                                        $return .= '""';
                                    }
                                    if ($j < $num_fields - 1) {
                                        $return .= ',';
                                    }
                                }
                                $return .= ");\n";
                            }
                        }
                        $return .= "\n\n\n";
                    }

                    $backup_file = $dbdatabase . '.sql';
                    $handle = fopen("{$backup_file}", 'w+');
                    fwrite($handle, $return);
                    fclose($handle);
                    $backupAlert = 'Export erfolgreich';
                }
            }
        }
    }
    echo $backupAlert;
}
?>