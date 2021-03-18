<?php
// 领用退还之正常退还
// 本界面执行了正常退还操作数据更新 及 正常退还记录
header("Access-Control-Allow-Origin:*");
require_once('../checkToken/checkToken.php');
require_once('../comFunc.php');
require_once('../header.php');
$sendArr = array();
$data = null;
$meta = array("state"=>200,"msg"=>'操作成功');

$dataArr = array();
if(!($data = file_get_contents("php://input"))) {
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

parse_str($data, $getData);
foreach ($getData as $key => $value) {
    array_push($dataArr, $value);
}

$store = $dataArr[13];

//所属库
$sql1 = "INSERT INTO $store.depart_return VALUES('$dataArr[0]','$dataArr[1]','$dataArr[2]','$dataArr[3]','$dataArr[4]','$dataArr[5]',
        '$dataArr[6]',$dataArr[7],'$dataArr[8]','$dataArr[9]','$dataArr[10]','$dataArr[11]','$dataArr[12]')";
$sql11 = "UPDATE  $store.finalTable SET 库存量=库存量+$dataArr[7] WHERE `产品/设备编码`='$dataArr[0]'";

//总库
$sql2 = "INSERT INTO depart_return VALUES('$dataArr[0]','$dataArr[1]','$dataArr[2]','$dataArr[3]','$dataArr[4]','$dataArr[5]',
        '$dataArr[6]',$dataArr[7],'$dataArr[8]','$dataArr[9]','$dataArr[10]','$dataArr[11]','$dataArr[12]')";
$sql22 = "UPDATE finalTable SET 库存量=库存量+$dataArr[7] WHERE `产品/设备编码`='$dataArr[0]'";

$sqlUdp = "UPDATE externalstorege.now_return SET 数量=数量-$dataArr[7] WHERE `产品/设备编码`='$dataArr[0]' AND 领用人='$dataArr[8]'";
$sqlClear = "DELETE FROM externalstorege.now_return WHERE 数量=0";

$conn->begin_transaction(true);
try {
    $isOk = true;
    $isOk = $isOk && $conn->query($sql1);
    $isOk = $isOk && $conn->query($sql11);
    $isOk = $isOk && $conn->query($sql2);
    $isOk = $isOk && $conn->query($sql22);
    $isOk = $isOk && $conn->query($sqlUdp);
    $isOk = $isOk && $conn->query($sqlClear);
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