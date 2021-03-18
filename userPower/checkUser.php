<?php
header("Access-Control-Allow-Origin:*");
$servername = "localhost";
$username = "root";
$password = "31415926";
$dbname = "power";
$sendArr = array();
$data = "";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("数据库连接出错");
}
if($data = $_GET["userName"]) {
    $sql = "SELECT id FROM master WHERE name='$data'";
    $result = $conn->query($sql);
    if($result->num_rows > 0) {
        echo 1;
    } else {
        echo 0;
    }
}
$conn->close();
?>