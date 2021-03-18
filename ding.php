<?php
$servername = "localhost";
$username = "root";
$password = "31415926";
$dbname = "qqq";
$sendArr = array();
$sql = "SELECT *FROM waitfor_check_thr";

mysqli_report(MYSQLI_REPORT_ALL);
// $conn = mysqli_connect($servername,$username,$password,$dbname);
try {
    $conn = mysqli_connect($servername,$username,$password,$dbname);
    echo "hello";
} catch (mysqli_sql_exception $e) {
    echo "error";
    // throw $e;
}
echo "are you OK";
// if($conn->connect_error) {
//     die($conn->error);
// }
// $result = $conn->query($sql);
// if($result->num_rows > 0) {
//     $row = $result->fetch_array();
//     $sendArr = $row;
// }
// print_r($sendArr);
// echo $sendArr[4];
$conn->close();
// try {
//     $test = [1,2,3];
//     echo $test[5];
//     echo "hello";
// } catch (Exception $ex) {
//     echo "error";
// }
?>