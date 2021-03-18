<?php
header("Access-Control-Allow-Origin:*");
$servername = "localhost";
$username = "root";
$password = "31415926";
$dbname = "mainstorege";
$successMsg = array("status"=>"200","msg"=>"提交成功");
$failMsg = array("status"=>"400", "msg"=>"提交失败");
$databaseErr = array("status"=>"401", "msg"=>"数据库错误");

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode($databaseErr, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_NUMERIC_CHECK);
    die();
}

$getData = file_get_contents("php://input");
parse_str($getData, $handleData);
$code = $handleData["code"];

$sql = "DELETE FROM property_check WHERE 编码='$code'";
if($conn->query($sql)) {
    echo json_encode($successMsg, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_NUMERIC_CHECK);
} else {
    echo json_encode($failMsg, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_NUMERIC_CHECK);
}
$conn->close();
?>
