<?php
$servername = "localhost";
$username = "root";
$password = "31415926";
$dbname = "power";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("数据库连接出错");
}
$val1 =1;
$val2=2;
$conn->query("INSERT INTO test VALUES($val1, $val2)");
$conn->close();
?>