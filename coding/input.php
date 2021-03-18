<?php
header("Access-Control-Allow-Origin:*");
$servername = "localhost";
$username = "root";
$password = "31415926";
$dbname = "mainstorege";
$array_multi = array();

$successMsg = array("status"=>"200","msg"=>"提交成功");
$failMsg = array("status"=>"400", "msg"=>"提交失败");
$databaseErr = array("status"=>"401", "msg"=>"数据库错误");

$sql = "INSERT IGNORE INTO property_check VALUES(?,?,?,?,?)";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {    
    echo json_encode($databaseErr, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
    die();
}

$data = file_get_contents("php://input");
parse_str($data, $dataArr);

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
            echo json_encode($failMsg, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
            $conn->close();
            return;
        }
    }
    if($isOk) {
        $conn->commit();
        echo json_encode($successMsg, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
    } else {
        $conn->rollback();
        echo json_encode($failMsg, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
    }
} catch (Exception $ex) {
    $conn->rollback();
    echo json_encode($failMsg, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
}

$conn->close();
?>