<?php
header("Access-Control-Allow-Origin:*");
require_once('../checkToken/checkToken.php');
require_once('../comFunc.php');
require_once('../header.php');
$sendArr = array();
$data = null;
$meta = array("state"=>200,"msg"=>'操作成功');

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
$getArr = array();
$getObj = $getData['data'];
foreach ($getObj as $key => $value) {
    array_push($getArr,$value);
}
$store = $getArr[14];

//插入到总库
$sql1 = "INSERT INTO productor VALUES('$getArr[0]','$getArr[1]','$getArr[2]','$getArr[3]','$getArr[4]','$getArr[5]','$getArr[6]',$getArr[7],'$getArr[8]',
        '$getArr[9]','$getArr[10]','$getArr[11]','$getArr[12]','$getArr[13]')";
$sql11 = "INSERT INTO entertable VALUES('$getArr[0]','$getArr[1]','$getArr[2]','$getArr[3]','$getArr[4]','$getArr[5]','$getArr[6]',$getArr[7],'$getArr[10]','$getArr[11]')";
$sql111 = "INSERT INTO finaltable VALUES('$getArr[0]','$getArr[1]','$getArr[2]','$getArr[3]','$getArr[4]','$getArr[5]','$getArr[6]',$getArr[7]) ON DUPLICATE KEY UPDATE 库存量=库存量+$getArr[7]";

// 插入到所属库
$sql2 = "INSERT INTO $store.productor VALUES('$getArr[0]','$getArr[1]','$getArr[2]','$getArr[3]','$getArr[4]','$getArr[5]','$getArr[6]',$getArr[7],'$getArr[8]',
        '$getArr[9]','$getArr[10]','$getArr[11]','$getArr[12]','$getArr[13]')";
$sql22 = "INSERT INTO $store.entertable VALUES('$getArr[0]','$getArr[1]','$getArr[2]','$getArr[3]','$getArr[4]','$getArr[5]','$getArr[6]',$getArr[7],'$getArr[10]','$getArr[11]')";
$sql222 = "INSERT INTO $store.finaltable VALUES('$getArr[0]','$getArr[1]','$getArr[2]','$getArr[3]','$getArr[4]','$getArr[5]','$getArr[6]',$getArr[7]) ON DUPLICATE KEY UPDATE 库存量=库存量+$getArr[7]";

$conn->begin_transaction(true);
try {
    $isOk = true;

    $isOk = $isOk && $conn->query($sql1);
    $isOk = $isOk && $conn->query($sql11);
    $isOk = $isOk && $conn->query($sql111);
    $isOk = $isOk && $conn->query($sql2);
    $isOk = $isOk && $conn->query($sql22);
    $isOk = $isOk && $conn->query($sql222);

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