<?php
header("Access-Control-Allow-Origin:*");
require_once('../checkToken/checkToken.php');
require_once('../comFunc.php');
require_once('../header.php');
$sql = "";
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

parse_str($getData,$handleData);
$store = $handleData["store"];
$pageNum = intval($handleData["pagenum"]);
$pageSize = intval($handleData["pagesize"]);
$searchDate = $handleData["searchDate"];
$searchCode = $handleData["searchCode"];
$searchClient = $handleData["searchClient"];
$searchDevName = $handleData["searchDevName"];
$limitHead = ($pageNum - 1 ) * $pageSize; // 搜索行数范围之首行

$addStr = "";

if($searchDate || $searchCode || $searchDevName || $searchClient) {
    $addStr = " WHERE ";
    if($searchDate) {
        $addStr .= "出库日期='$searchDate' AND ";
    }
    if($searchCode) {
        $addStr .= "`产品/设备编码`='$searchCode' AND ";
    }
    if($searchDevName) {
        $addStr .= "`产品/设备名称`='$searchDevName' AND ";
    }
    if($searchClient) {
        $addStr .= "`客户名称`='$searchClient' AND ";
    }
    $addStr = substr($addStr,0,strlen($addStr)-4);
}

if($store) {
    $sql = "SELECT *FROM $store.goodsSale" . $addStr . " LIMIT $limitHead,$pageSize";
    $sqlNum = "SELECT COUNT(*) as number FROM $store.goodsSale" . $addStr ;
} else {
    $sql = "SELECT *FROM goodsSale" . $addStr . " LIMIT $limitHead,$pageSize";
    $sqlNum = "SELECT COUNT(*) as number FROM goodsSale" . $addStr ;
}

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