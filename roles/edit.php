<?php
header("Access-Control-Allow-Origin:*");
require_once('../checkToken/checkToken.php');
require_once('../comFunc.php');
require_once('../header.php');

$sendArr = array();
$getData = null;
$meta = array("state"=>200,"msg"=>'操作成功');

$jwt = isset($_SERVER['HTTP_TOKEN']) ? $_SERVER['HTTP_TOKEN'] : '';
if(!$jwt) {
    $meta["state"] = 201;
    $meta["msg"] = "Token有误，请重新登录验证";
    $sendArr["meta"] = $meta;
    echo json_encode($sendArr, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
    return;
}  else {
    substr($jwt,0,strlen($jwt)-5);
}

$conn = connectDB();
mysqli_select_db($conn, MYSQL_DB1);

// 数据库没有token
if($tmp = checkToken($jwt)) {
    $meta = $tmp;
    $sendArr["meta"] = $meta;
    echo json_encode($sendArr, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
    $conn->close();
    return;
}

if(!($getData = file_get_contents("php://input"))) {
    return;
}

parse_str($getData, $handleData);
$handleData = $handleData["edit"];
$roleid = intval($handleData["roleId"]);
$rolename = $handleData["roleName"];
$roledesc = $handleData["roleDesc"];

$sql = "UPDATE roles_test SET `role_name`='$rolename',`role_desc`='$roledesc' WHERE `role_id`='$roleid'";
if($conn->query($sql)) {
    $meta["state"] = 200;
    $meta["msg"] = "操作成功";
} else {
    $meta["state"] = 203;
    $meta["msg"] = "操作失败";
}
$sendArr["meta"] = $meta;
echo json_encode($sendArr,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
$conn->close();
?>
