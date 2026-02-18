<?php
// delete.php
include '../config/connection.php';

echo '<script src="sweetalert2/dist/sweetalert2.all.min.js"></script>';
echo '<link rel="stylesheet" href="sweetalert2/dist/sweetalert2.min.css">';
echo '<script src="asset/index/js/jquery-3.7.1.js"></script>';

if (isset($_POST['id'])) {
    $id = $_POST['id'];


    $sql = "DELETE FROM " . $database[0] . ".user WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        echo 'success';
    } else {
        echo 'error';
    }

    $stmt->close();
    $conn->close();
}
?>
