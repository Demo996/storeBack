<?php
header("Access-Control-Allow-Origin:*");
require_once('../checkToken/checkToken.php');
require_once('../comFunc.php');
require_once('../header.php');
$sendArr = array();
$getData = null;
$meta = array("state"=>200,"msg"=>'操作成功');
$array_multi = array();
$sql = "INSERT IGNORE INTO property_check VALUES(?,?,?,?,?)";


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

parse_str($getData, $dataArr);

foreach ($dataArr as $x => $x_val) {
    $array_child = array();
    $mean_1 = $x_val["含义_1"];
    $mean_2 = $x_val["含义_2"];
    $mean_3 = $x_val["含义_3"];
    $mean_4 = $x_val["含义_4"];
    $code = $x_val["最终编码"];
    array_push($array_child, $mean_1, $mean_2, $mean_3, $mean_4, $code);
    array_push($array_multi, $array_child);
}

$conn->begin_transaction(true);
try {
    $isOk = true;
    foreach ($array_multi as $key => $child) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssss', $child[0], $child[1], $child[2], $child[3], $child[4]);
        $isOk = $isOk && $stmt->execute();
        if(!$isOk) {
            $conn->rollback();
            $meta["state"] = 202;
            $meta["msg"] = "数据提交失败";
            $sendArr["meta"] = $meta;
            echo json_encode($sendArr, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
            $conn->close();
            return;
        }
    }
    if($isOk) {
        $conn->commit();
        $meta["state"] = 200;
        $meta["msg"] = "提交成功";
    } else {
        $conn->rollback();
        $meta["state"] = 202;
        $meta["msg"] = "数据提交失败";
    }
} catch (Exception $ex) {
    $conn->rollback();
    $meta["state"] = 202;
    $meta["msg"] = "数据提交失败";
}
$sendArr["meta"] = $meta;
echo json_encode($sendArr, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
$conn->close();
?>