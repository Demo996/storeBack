<?php
header("Access-Control-Allow-Origin:*");
$servername = "localhost";
$username = "root";
$password = "31415926";
$dbname = "mainstorege";

$sql = "";
$sendArr = array();

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die();
}

$getData = file_get_contents("php://input");
parse_str($getData, $handleData);

$pageNum = intval($handleData["pagenum"]);
$pageSize = intval($handleData["pagesize"]);
$limitHead = ($pageNum - 1) * $pageSize; // 搜索行数范围之首行

$sql = "SELECT *FROM property_check LIMIT $limitHead,$pageSize";
$sqlNum = "SELECT COUNT(*) as number FROM property_check";

$getNum = $conn->query($sqlNum);
if ($getNum->num_rows > 0) {
    $row = $getNum->fetch_assoc();
    $pagetotal = $row['number'];
    $sendArr["pagetotal"] = $pagetotal;
}

$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $tmpArr = array();
    while ($row = $result->fetch_assoc()) {
        array_push($tmpArr, $row);
    }
    $sendArr["data"] = $tmpArr;
}
echo json_encode($sendArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
$conn->close();
