<?php
header("Access-Control-Allow-Origin:*");
header('Access-Control-Allow-Methods:OPTIONS, GET, POST'); // 允许option，get，post请求
header('Access-Control-Allow-Headers:x-requested-with, content-type'); // 允许x-requested-with请求头
$servername = "localhost";
$username = "root";
$password = "31415926";
$dbname = "mainstorege";

$sql = "";
$getData = null;
$sendArr = array();

if(!($getData = file_get_contents("php://input"))) {
    return;
}
parse_str($getData, $handleData);

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die();
}

$pageNum = intval($handleData["pagenum"]);
$pageSize = intval($handleData["pagesize"]);
$code = $handleData["filterCode"];
$type = $handleData["filterType"];
$name = $handleData["filterName"];
$model = $handleData["filterModel"];
$colorShape = $handleData["filterColorShape"];
$limitHead = ($pageNum - 1) * $pageSize; // 搜索行数范围之首行

$addStr = "";

if($code) {
    $addStr = " WHERE 编码 LIKE '$code%'";
} else {
    if($type || $name || $model || $colorShape) {
        $addStr = " WHERE ";
        if($type) {
            $addStr .= "类型 LIKE '$type%' AND ";
        }
        if($name) {
            $addStr .= "`名称` LIKE '$name%' AND ";
        }
        if($model) {
            $addStr .= "`规格型号` LIKE '$model%' AND ";
        }
        if($colorShape) {
            $addStr .= "`颜色形状` LIKE '$colorShape%' AND ";
        }
        $addStr = substr($addStr,0,strlen($addStr)-4);
    }
}

$sql = "SELECT *FROM property_check" . $addStr . " LIMIT $limitHead,$pageSize";
$sqlNum = "SELECT COUNT(*) as number FROM property_check" . $addStr;

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
