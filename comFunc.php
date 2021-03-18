<?php
require_once('header.php');
function connectDB() {
    $conn = mysqli_connect(MYSQL_SER,MYSQL_USER,MYSQL_PW);
    if($conn->connect_error) {
        die();
    } else {
        return $conn;
    }
}
?>