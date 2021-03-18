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
$param1 = $handleData["类型"];
$param2 = $handleData["名称"];
$param3 = $handleData["规格型号"];
$param4 = $handleData["颜色形状"];
$param5 = $handleData["编码"];

$sql = "UPDATE property_check SET 类型='$param1', 名称='$param2',规格型号='$param3',颜色形状='$param4' WHERE 编码='$param5'";
if($conn->query($sql)) {
    echo json_encode($successMsg, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_NUMERIC_CHECK);
} else {
    echo json_encode($failMsg, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_NUMERIC_CHECK);
}
$conn->close();
?>
