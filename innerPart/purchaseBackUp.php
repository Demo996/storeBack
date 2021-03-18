<?php
header('Access-Control-Allow-Origin:*');
require_once('../checkToken/checkToken.php');
require_once('../comFunc.php');
require_once('../header.php');

$sqlOne = "";
$sqlTwo = "";
$sqlThree = "";
$detail = array();
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

$getId = $getData["typeId"];
$getCode = $getData["code"];
$getNumb = $getData["numb"];
$getPrice = $getData["price"];
$getFee = $getData["fee"];
$getMoney = $getData["money"];
$getRowMoney = $getData["rowMoney"];
$getDate = $getData["currDate"];
$getNote = $getData["note"];
$detailObj = $getData["detail"];

foreach ($detailObj as $key => $value) {
    array_push($detail,$value);
}

$handleMoney = $getRowMoney-$getMoney;

//提前获取总表单信息
$mainArr = array();
$sqlMain = "SELECT *FROM buylist_one WHERE 申请单编号='$getCode'";
//提前获取详细表信息
$detArr = array();
$sqlDet = "SELECT *FROM buylist_thr WHERE id=$detail[8]";

$result = $conn->query($sqlMain);
if($result->num_rows > 0) {
    $mainArr = $result->fetch_array();
} else {
    $meta["state"] = 202;
    $meta["msg"] = "数据提交失败";
    echo json_encode($sendArr, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
    die();
}

$result2 = $conn->query($sqlDet);
if($result2->num_rows > 0) {
    $detArr = $result2->fetch_array();
} else {
    $meta["state"] = 202;
    $meta["msg"] = "数据提交失败";
    echo json_encode($sendArr, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
    die();
}
//退货详细表
$sql = "INSERT INTO already_back_test VALUES('$detail[0]','$detail[1]','$detail[2]','$detail[3]','$detail[4]','$detail[5]','$detail[6]',
        '$detail[7]',$getNumb,$getPrice,$getFee,$getMoney,'$getDate','$getNote') ON DUPLICATE KEY UPDATE 退货数量=退货数量+$getNumb,
        退货金额=退货金额+$getMoney";

//更新待返回处理的单量，并且全部返回后，表中清除此数据
$sqlCurrBack = "UPDATE backtable SET 数量=数量-$getNumb,合计金额=合计金额-$getRowMoney WHERE id=$detail[8]";
$sqlClear = "DELETE FROM backtable WHERE 数量=0";


$calMoney = $getRowMoney+$getFee; // 原始单价得到的金额 + 退货运费
$calMoney2 = $getRowMoney+$detail[10];  //原始单价得到的金额 + 原始运费

// 退换方式下的sql语句
$sqlOne = "INSERT INTO waitfor_check_one VALUES('$mainArr[0]', $getNumb, $calMoney, $calMoney, '$mainArr[4]', '$mainArr[5]', '$mainArr[6]', '$mainArr[7]', '$mainArr[8]', '$mainArr[9]')
           ON DUPLICATE KEY UPDATE 待审核总数=待审核总数+$getNumb,应付金额=应付金额+$handleMoney,实付金额=实付金额+$handleMoney";

$sqlTwo = "INSERT IGNORE INTO waitfor_check_two SELECT *FROM buyList_two WHERE buyList_two.id=$detail[8]";

$sqlThr = "INSERT INTO waitfor_check_thr VALUES($detail[8], $getNumb, $detail[10], $getFee, $calMoney, '$detArr[5]', '$detArr[6]', '$detArr[7]', '$detArr[8]', '$detArr[9]')
           ON DUPLICATE KEY UPDATE 数量=数量+$getNumb,运费=运费+$getFee,合计金额=数量*单价+运费";

$sqlThr2 = "INSERT INTO waitfor_check_thr VALUES($detail[8], $getNumb, $detail[10], $detail[11], $calMoney2, '$detArr[5]', '$detArr[6]', '$detArr[7]', '$detArr[8]', '$detArr[9]')
           ON DUPLICATE KEY UPDATE 数量=数量+$getNumb,合计金额=数量*单价+运费";

// 不同退货方式对应不同表的更新
// 1:退换 : 更新待入库表。 不存在价格的变化，只存在运费的支出
// 2：退购  3：仅退 ：这两种方式本质是都是退货，不考虑更新待入库表，如果再购那么按采购入库重新操作即可，如果不再购买当然什么操作都不要
// 4: 取消退还：当发现退还不了，或者不想退还的时候，可以返回到待审核库
$isOk = true;
$conn->begin_transaction(true);
try {
    switch ($getId) {
        case 1:
            $isOk = $isOk && $conn->query($sqlCurrBack);
            $isOk = $isOk && $conn->query($sqlClear);
            $isOk = $isOk && $conn->query($sqlTwo);
            
            $isOk = $isOk && $conn->query($sql);

            $isOk = $isOk && $conn->query($sqlOne); 

            $isOk = $isOk && $conn->query($sqlThr);  
            //事务操作，上面的表的更新都是同步的，若某个表的数据更新出错，那么所有表的更新回滚到更新    
            if($isOk) {
                $conn->commit();
                $meta["state"] = 200;
                $meta["msg"] = "提交成功";
            } else {
                $conn->rollback();
                $meta["state"] = 202;
                $meta["msg"] = "数据提交失败";
            }
            break;
        case 2:
        case 3:
            $isOk = $isOk && $conn->query($sql);
            if($isOk) {
                $conn->commit();
                $meta["state"] = 200;
                $meta["msg"] = "提交成功";
            } else {
                $conn->rollback();
                $meta["state"] = 202;
                $meta["msg"] = "数据提交失败";
            }
            break;
        case 4:
            $isOk = $isOk && $conn->query($sqlCurrBack);
            $isOk = $isOk && $conn->query($sqlClear);
            $isOk = $isOk && $conn->query($sqlTwo);

            $sqlOne = "INSERT INTO waitfor_check_one VALUES('$mainArr[0]', $getNumb, $calMoney2, $calMoney2, '$mainArr[4]', '$mainArr[5]', '$mainArr[6]', '$mainArr[7]', '$mainArr[8]', '$mainArr[9]')
                       ON DUPLICATE KEY UPDATE 待审核总数=待审核总数+$getNumb,应付金额=应付金额+$handleMoney,实付金额=实付金额+$handleMoney";

            $isOk = $isOk && $conn->query($sqlOne);
            $isOk = $isOk && $conn->query($sqlThr2);

            //事务操作，上面的表的更新都是同步的，若某个表的数据更新出错，那么所有表的更新回滚到更新前
            if($isOk) {
                $conn->commit();
                $meta["state"] = 200;
                $meta["msg"] = "提交成功";
            } else {
                $conn->rollback();
                $meta["state"] = 202;
                $meta["msg"] = "数据提交失败";
            }
            break;
            default:break;
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