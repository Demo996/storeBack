<?php
header("Access-Control-Allow-Origin:*");
require_once('../checkToken/checkToken.php');
require_once('../comFunc.php');
require_once('../header.php');
$data = null;
$meta = array("state"=>200,"msg"=>'操作成功');
$sendArr = array();

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
$operator = $getData['operator'];
$main = $getData['main'];
$detail = $getData['detail'];

$tableNumber = $main["tableNumber"];
$applyMan = $main["applyMan"];
$purchaser = $main["purchaser"];
$currDept = $main["currDept"];
$applyDate = $main["applyDate"];
$totalNum = $main["totalNum"];
$payMoney = $main["payMoney"];
$note = $main["note"];

handleId('buylist_two');
handleId('buylist_thr');
handleId('waitfor_check_two');
handleId('waitfor_check_thr');
function handleId($tbName) {
    global $conn;
    $sql = "ALTER TABLE $tbName AUTO_INCREMENT=1";
    $conn->query($sql);
}

$conn->begin_transaction(true);
try {
    $isOk = true;
    $sqlMain1 = "INSERT INTO buylist_one VALUES('$tableNumber',$totalNum,$payMoney,$payMoney,'$applyMan','$currDept','$applyDate','$purchaser','$note','$operator')";
    $sqlMain2 = "INSERT INTO waitfor_check_one VALUES('$tableNumber',$totalNum,$payMoney,$payMoney,'$applyMan','$currDept','$applyDate','$purchaser','$note','$operator')";

    $isOk = $isOk && $conn->query($sqlMain1);
    $isOk = $isOk && $conn->query($sqlMain2);

    foreach ($detail as  $key => $value) {
        $val = array();
        foreach ($value as $key2 => $value2) {
            array_push($val, $value2);
        }
        $sqlDetail1 = "INSERT INTO buylist_two VALUES('$tableNumber','$val[0]','$val[1]','$val[2]','$val[3]','$val[4]','$val[5]','$val[6]',Null)";
        $sqlDetail2 = "INSERT INTO buylist_thr VALUES(Null,'$val[7]','$val[8]','$val[9]','$val[10]','$val[11]', '否','$val[12]' ,'$val[13]', '$val[14]')";
        $sqlDetail21 = "INSERT INTO waitfor_check_two VALUES('$tableNumber','$val[0]','$val[1]','$val[2]','$val[3]','$val[4]','$val[5]','$val[6]',Null)";
        $sqlDetail22 = "INSERT INTO waitfor_check_thr VALUES(Null,'$val[7]','$val[8]','$val[9]','$val[10]','$val[11]', '否' ,'$val[12]','$val[13]', '$val[14]')";
        $isOk = $isOk && $conn->query($sqlDetail1);
        $isOk = $isOk && $conn->query($sqlDetail2);
        $isOk = $isOk && $conn->query($sqlDetail21);
        $isOk = $isOk && $conn->query($sqlDetail22);
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