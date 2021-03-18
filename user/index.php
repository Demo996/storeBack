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

parse_str($getData, $handleData);
$pageNum = intval($handleData["pagenum"]);
$pageSize = intval($handleData["pagesize"]);
$getName = $handleData["uname"];
$stime = $handleData["start_time"];
$etime = $handleData["end_time"];
$limitHead = ($pageNum - 1 ) * $pageSize; // 搜索行数范围之首行

if($getName) {
    if($stime && $etime) {
        $sql = "SELECT user_test.*,role_name FROM user_test,roles_test WHERE `user_name`='$getName' AND (`create_time` BETWEEN '$stime' AND '$etime') 
        AND role_pid=role_id LIMIT $limitHead,$pageSize";

    $sqlNum = "SELECT COUNT(*) as number FROM user_test WHERE `user_name`='$getName' AND (`create_time` BETWEEN '$stime' AND '$etime')";
    } else {
        $sql = "SELECT user_test.*,role_name FROM user_test,roles_test WHERE `user_name`='$getName' AND role_pid=role_id LIMIT $limitHead,$pageSize";
        $sqlNum = "SELECT COUNT(*) as number FROM user_test WHERE `user_name`='$getName'";
    }
} else {
    if($stime && $etime) {
        $sql = "SELECT user_test.*,role_name FROM user_test,roles_test WHERE (`create_time` BETWEEN '$stime' AND '$etime') AND role_pid=role_id LIMIT $limitHead,$pageSize";
        $sqlNum = "SELECT COUNT(*) as number FROM user_test WHERE (`create_time` BETWEEN '$stime' AND '$etime')";
    } else {
        $sql = "SELECT user_test.*,role_name FROM user_test,roles_test WHERE role_pid=role_id LIMIT $limitHead,$pageSize";
        $sqlNum = "SELECT COUNT(*) as number FROM user_test";
    }
}

$getNum = $conn->query($sqlNum);
if($getNum->num_rows > 0) {
    $row = $getNum->fetch_assoc();
    $pagetotal = $row['number'];
    $sendArr["pagetotal"] = $pagetotal;
} else {
    $meta["state"] = 202;
    $meta["msg"] = "操作失败";
}

$result = $conn->query($sql);
if($result->num_rows > 0) {
    $tmpArr = array();
    while($row = $result->fetch_assoc()) {
        array_push($tmpArr, $row);
    }
    $sendArr["data"] = $tmpArr;
} else {
    $meta["state"] = 202;
    $meta["msg"] = "操作失败";
}
$sendArr["meta"] = $meta;
echo json_encode($sendArr,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
$conn->close();
?>