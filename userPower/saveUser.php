<?php
header("Access-Control-Allow-Origin:*");
$servername = "localhost";
$username = "root";
$password = "31415926";
$dbname = "power";
$data = "";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("数据库连接出错");
}

$data = file_get_contents("php://input");
parse_str($data, $getData);
$type = $getData["type"];
$username = $getData["username"];
$password = $getData["password"];
$dept = $getData["dept"];
$power = $getData["power"];
$cnt = count($power);
$id = 0;
$groupid = 0;
$conn->autocommit(false); // 开始事务操作
try {
    $time = date("Y-m-d H:i:s");
    //插入新注册用户的信息
    if($type == 1) {
        $inSql1 = "INSERT IGNORE INTO master VALUES(null,'$username','$password','$dept', '$time', '$time', '$time')";
        $conn->query($inSql1);
    } else if($type == 2) {
        // 修改后的用户信息保存
        $udpSql1 = "UPDATE master SET password='$password',dept='$dept',mdtime='$time',uptime='$time' WHERE name='$username'";
        $udpSql2 = "DELETE FROM mastergroup WHERE name='$username'";
        $conn->query($udpSql1);
        $conn->query($udpSql2);
    }

    //获取用户被关联的id
    $querySql = "SELECT id FROM master WHERE name='$username'";
    $myres = $conn->query($querySql);
    if($myres->num_rows > 0) {
        while($row = $myres->fetch_assoc()) {
            $id = intval($row["id"]);
        }
    }
    //循环插入用户所有权限
    for($i=0; $i<$cnt; $i++) {
        $inCirSql= "INSERT INTO mastergroup VALUES(null,'$username',$id,$power[$i])";
        $conn->query($inCirSql);
    }
    if(!$conn->errno) {
        $conn->commit();
        echo "提交成功";
    } else {
        $conn->rollback();
        echo "提交失败";
    }
} catch (Exception $ex) {
    $conn->rollback();
    echo "提交失败";
}
$conn->close();
?>