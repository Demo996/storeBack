<?php
//审核入库数据保存与更新
header('Access-Control-Allow-Origin:*');
require_once('../checkToken/checkToken.php');
require_once('../comFunc.php');
require_once('../header.php');
$sendArr = array();
$meta = array("state"=>200,"msg"=>'操作成功');
$data = null;

//空请求则不进行操作
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
// 数据库没有token则返回对应消息
if($tmp = checkToken($jwt)) {
    $meta = $tmp;
    $sendArr["meta"] = $meta;
    echo json_encode($sendArr, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
    return;
}

parse_str($data, $getData);
$getTitle = $getData["title"];
$getNumb = intval($getData["numb"]); // 剩余数量
$getCheckNum = intval($getData["checkNum"]); //审核数量
$getMoney = $getData["money"];  // 剩余总额
$store = $getData["store"]; // 所选仓库
$noPassNote = $getData["notes"]; // 审核不通过备注
$curr_operator = $getData["operator"]; // 当前操作的操作员

$mainObj = $getData["mainMsg"];
$detailArr = $getData["detailMsg"];

$applyNum = $mainObj["申请单编号"];
$applyMan = $mainObj["申报人"];
$useDept = $mainObj["使用部门"];
$applyDate = $mainObj["申请日期"];
$purchaser = $mainObj["采购人"];
$note = $mainObj["备注"];
$operator = $mainObj["操作员"];

$getId = $detailArr["id"];
$getCode = $detailArr["产品/设备编码"];
$getName = $detailArr['产品/设备名称'];
$getType = $detailArr['类型'];
$getModel = $detailArr['型号'];
$getSpecify = $detailArr['规格'];
$getColSha = $detailArr['颜色/形状'];
$getUnit = $detailArr['单位'];
$getPrice = $detailArr['单价'];
$getFee = $detailArr['运费'];
$getFeeType = $detailArr['票据类型'];
$getFeeState = $detailArr["票据签收"];
$getBuyDate = $detailArr['采购日期'];
$getUseFunc = $detailArr['用途'];
$getNote = $detailArr['备注']; // 原始采购单备注

$getDate = date("Y-m-d"); // 审核日期

$conn = connectDB();
mysqli_select_db($conn, MYSQL_DB1);

//审核入库产品的应付和实付金额
$checkInMoney = intval($getPrice) * intval($getCheckNum);

if($getTitle) {
    // 更新待入库产品的数据
    $sql_wait_upd1 = "UPDATE waitfor_check_thr SET 数量=$getNumb, 合计金额=数量*单价, 票据签收='$getFee' WHERE id=$getId";
    $sql_wait_upd2 = "UPDATE waitfor_check_one SET 待审核总数 = 待审核总数 - $getCheckNum WHERE 申请单编号 = '$applyNum'";

    // 删除全部审核通过且发票全部收到的数据信息
    $sql_wait_del1 = "DELETE FROM waitfor_check_thr WHERE 数量 = 0 AND 票据签收 = '是'";
    $sql_wait_del2 = "DELETE FROM waitfor_check_two WHERE waitfor_check_two.id NOT IN (SELECT id FROM waitfor_check_thr)";
    $sql_wait_del3 = "DELETE FROM waitfor_check_one WHERE waitfor_check_one.申请单编号 NOT IN (SELECT 申请单编号 FROM waitfor_check_two)";

    // 审核通过添加的数据库信息
    // 插入到所属库
    $sql_belong_ins1 = "INSERT INTO $store.already_check_one VALUES('$applyNum',$getCheckNum, $checkInMoney, $checkInMoney,'$applyMan','$useDept','$applyDate','$purchaser','$note',
    '$operator') ON DUPLICATE KEY UPDATE 入库总数=入库总数+$getCheckNum,应付金额=应付金额+'$checkInMoney',实付金额=实付金额+'$checkInMoney'";

    $sql_belong_ins2 = "INSERT INTO $store.already_check_thr VALUES('$applyNum', '$getCode', '$getName','$getType','$getModel','$getSpecify','$getColSha','$getUnit', $getCheckNum,$getPrice,$getFee,$checkInMoney,
    '$getFeeType', '$getFeeState','$getBuyDate','$getUseFunc','$getNote', '$curr_operator','$getDate')";

    $sql_belong_ins3 = "INSERT INTO $store.enterTable VALUES('$getCode', '$getName','$getType','$getModel','$getSpecify','$getColSha','$getUnit', $getCheckNum, '$curr_operator','$getDate')";

    $sql_belong_ins4 = "INSERT INTO $store.finalTable VALUES('$getCode', '$getName','$getType','$getModel','$getSpecify','$getColSha','$getUnit', $getCheckNum) ON DUPLICATE KEY UPDATE 库存量=库存量+$getCheckNum";

    // 插入到总库
    $sql_main_ins1 = "INSERT INTO already_check_one VALUES('$applyNum',$getCheckNum, $checkInMoney, $checkInMoney,'$applyMan','$useDept','$applyDate','$purchaser','$note',
    '$operator') ON DUPLICATE KEY UPDATE 入库总数=入库总数+$getCheckNum,应付金额=应付金额+'$checkInMoney',实付金额=实付金额+'$checkInMoney'";

    $sql_main_ins2 = "INSERT INTO already_check_thr VALUES('$applyNum', '$getCode', '$getName','$getType','$getModel','$getSpecify','$getColSha','$getUnit', $getCheckNum,$getPrice,$getFee,$checkInMoney,
    '$getFeeType', '$getFeeState','$getBuyDate','$getUseFunc','$getNote', '$curr_operator','$getDate')";

    $sql_main_ins3 = "INSERT INTO enterTable VALUES('$getCode', '$getName','$getType','$getModel','$getSpecify','$getColSha','$getUnit', $getCheckNum, '$curr_operator','$getDate')";

    $sql_main_ins4 = "INSERT INTO finalTable VALUES('$getCode', '$getName','$getType','$getModel','$getSpecify','$getColSha','$getUnit', $getCheckNum) ON DUPLICATE KEY UPDATE 库存量=库存量+$getCheckNum";

    $conn->begin_transaction(true);
    try {
        $isOk = true;
        $isOk = $isOk && $conn->query($sql_wait_upd1);
        $isOk = $isOk && $conn->query($sql_wait_upd2);
        $isOk = $isOk && $conn->query($sql_wait_del1);
        $isOk = $isOk && $conn->query($sql_wait_del2);
        $isOk = $isOk && $conn->query($sql_wait_del3);
        $isOk = $isOk && $conn->query($sql_belong_ins1);
        $isOk = $isOk && $conn->query($sql_belong_ins2);
        $isOk = $isOk && $conn->query($sql_belong_ins3);
        $isOk = $isOk && $conn->query($sql_belong_ins4);
        $isOk = $isOk && $conn->query($sql_main_ins1);
        $isOk = $isOk && $conn->query($sql_main_ins2);
        $isOk = $isOk && $conn->query($sql_main_ins3);
        $isOk = $isOk && $conn->query($sql_main_ins4);

        echo $conn->error;
        if($isOk) {
            $conn->commit();
            $meta["state"] = 200;
            $meta["msg"] = "提交成功";
            $sendArr["meta"] = $meta;
            echo json_encode($sendArr, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
        } else {
            $conn->rollback();
            $meta["state"] = 202;
            $meta["msg"] = "数据提交失败";
            $sendArr["meta"] = $meta;
            echo json_encode($sendArr, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
        }
    } catch (Exception $ex) {
        $conn->rollback();
        $meta["state"] = 202;
        $meta["msg"] = "数据提交失败";
        $sendArr["meta"] = $meta;
        echo json_encode($sendArr, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
    }
}else{
    $isOk = true;
    $insSql = "INSERT INTO backtable VALUES('$applyNum','$getCode','$getName','$getType','$getModel','$getSpecify',
              '$getColSha','$getUnit', $getId,$getCheckNum,$getPrice,$getFee,$checkInMoney,'$noPassNote')";
    $udpSql1 = "UPDATE waitfor_check_thr SET 数量=$getNumb,合计金额=数量*单价+运费 WHERE id=$getId";
    $udpSql2 = "UPDATE waitfor_check_one SET 待审核总数=待审核总数-$getCheckNum,应付金额=应付金额-$checkInMoney, 实付金额=应付金额 WHERE 申请单编号='$applyNum'";

   try {
    $isOk = $isOk && $conn->query($insSql);
    $isOk = $isOk && $conn->query($udpSql1);
    $isOk = $isOk && $conn->query($udpSql2);

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
}
$sendArr["meta"] = $meta;
echo json_encode($sendArr, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
$conn->close();
?>