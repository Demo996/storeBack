<?php
header("Access-Control-Allow-Origin:*");
require_once('../checkToken/checkToken.php');
require_once('../comFunc.php');
require_once('../header.php');

$sendArr = array();
$data = null;
$role_name = "";
$auth = array();
$meta = array("state"=>200,"msg"=>'操作成功');

if(!($data = file_get_contents("php://input"))) {
    return;
}

$jwt = isset($_SERVER['HTTP_TOKEN']) ? $_SERVER['HTTP_TOKEN'] : '';
echo $jwt;
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

parse_str($data,$getData);
$uname = $getData["uname"];
$sqlRole = "SELECT `role_name`,`roles_auth_son` AS auth FROM `roles_test`,`user_test` WHERE `user_test`.`user_name`='$uname' AND `user_test`.`role_pid`=`roles_test`.`role_id`";
$result = $conn->query($sqlRole);
if(!$result) {
    $meta["state"] = 202;
    $meta["msg"] = "查询有误";
    $sendArr["meta"] = $meta;
    echo json_encode($sendArr,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
}
if($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $role_name = $row["role_name"];
    $auth = explode(',', $row["auth"]);
}else {
    $meta["state"] = 202;
    $meta["msg"] = "查询有误";
    $sendArr["meta"] = $meta;
    echo json_encode($sendArr,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
}

$sqlStr = "";
foreach ($auth as $key => $value) {
    $sqlStr .= "auth_id=$value" . " or ";
}
$sqlStr = substr($sqlStr, 0, strlen($sqlStr)-3);

$sql = "SELECT *,(SELECT CONCAT('[',GROUP_CONCAT(JSON_OBJECT('auth_id',auth_id,'auth_pid',auth_pid,'auth_name',auth_name,'url',url)),']') FROM auth_child WHERE auth_pid=auth_parent.auth_id and ("
. $sqlStr . ")) AS children FROM auth_parent";

$result = $conn->query($sql);
if($result->num_rows > 0) {
    $tmp = array();
    while($row = $result->fetch_assoc()) {
        if(!$row["children"]) continue;
        $row["children"] = json_decode($row["children"],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        array_push($tmp, $row);
    }
    $sendArr["data"] = $tmp;
}else {
    $meta["state"] = 202;
    $meta["msg"] = "查询有误";
}
$sendArr["meta"] = $meta;
echo json_encode($sendArr,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
$conn->close();
?>
