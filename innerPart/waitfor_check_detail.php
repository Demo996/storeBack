<?php
header('Access-Control-Allow-Origin:*');
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
require_once('../checkToken/checkToken.php');
require_once('../comFunc.php');
require_once('../header.php');
$sendArr = array();
$getData = null;
$meta = array("state"=>200,"msg"=>'操作成功');

if(!($getData = file_get_contents("php://input"))) {
    return;
}

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
// 数据库没有token则返回对应消息
if($tmp = checkToken($jwt)) {
    $meta = $tmp;
    $sendArr["meta"] = $meta;
    echo json_encode($sendArr, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
    $conn->close();
    return;
}

parse_str($getData,$handleData);
$pageNum = intval($handleData["pagenum"]);
$pageSize = intval($handleData["pagesize"]);
$limitHead = ($pageNum - 1 ) * $pageSize; // 搜索行数范围之首行

$sql = "SELECT waitfor_check_one.申请单编号, `产品/设备编码`, `产品/设备名称`, 类型, 型号, 规格, `颜色/形状`, 单位, waitfor_check_thr.*
        FROM waitfor_check_one LEFT JOIN waitfor_check_two ON waitfor_check_one.申请单编号 = waitfor_check_two.申请单编号 LEFT JOIN waitfor_check_thr
        ON waitfor_check_two.id = waitfor_check_thr.id LIMIT $limitHead,$pageSize";

$sqlNum = "SELECT COUNT(*) AS number FROM waitfor_check_one LEFT JOIN waitfor_check_two ON waitfor_check_one.申请单编号 = waitfor_check_two.申请单编号 LEFT JOIN waitfor_check_thr
        ON waitfor_check_two.id = waitfor_check_thr.id";


$getNum = $conn->query($sqlNum);
if($getNum->num_rows > 0) {
    $row = $getNum->fetch_assoc();
    $pagetotal = $row['number'];
    $sendArr["pagetotal"] = $pagetotal;
}else {
    $meta["state"] = 202;
    $meta["msg"] = "查询有误";
}

$result = $conn->query($sql);
if($result->num_rows > 0) {
    $tmpArr = array();
    while($row = $result->fetch_assoc()) {
        array_push($tmpArr, $row);
    }
    $sendArr["data"] = $tmpArr;
}else {
    $meta["state"] = 202;
    $meta["msg"] = "查询有误";
}
$sendArr["meta"] = $meta;
echo json_encode($sendArr,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
$conn->close();
?>