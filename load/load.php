<?php
header("Access-Control-Allow-Origin:*");
$servername = "localhost";
$username = "root";
$password = "31415926";
$dbname = "mainstorege";

$successMsg = array("status"=>"200","msg"=>"提交成功");
$failMsg = array("status"=>"400", "msg"=>"提交失败");
$databaseErr = array("status"=>"401", "msg"=>"数据库错误");

$data = file_get_contents("php://input");
if(!$data) {
    return;
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode($databaseErr,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
    die();
}

parse_str($data, $getData);
$userName = $getData["username"];
$passWord = $getData["password"];
$getDate = date("Y-m-d H:i:s");

$sql = "SELECT password,token FROM `user_test` WHERE `user_name`='$userName'";
$result = $conn->query($sql);
if($result->num_rows) {
    while($row = $result->fetch_assoc())
    {
        $getPwd = $row["password"];
        $getToken = $row["token"];
        if($getPwd === $passWord) {
            $successMsg["msg"] = "登陆成功";
            //用户无token则创建token并存进数据库
            if(!$getToken) {
                $str = md5(uniqid(md5(microtime(true)),true));
                $token = sha1($str);
                $conn->query("UPDATE user_test SET token='$token' WHERE `user_name`='$username'");
                $successMsg["token"] = $token;
            } else {
                $successMsg["token"] = $getToken;
            }
            echo json_encode($successMsg,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
            // 记录登陆记录
            $sqlIn = "INSERT INTO history VALUES(null,'$userName','$getDate','$getDate') ON DUPLICATE KEY UPDATE `last_login_time`=`login_time`, `login_time`='$getDate'";
            $conn->query($sqlIn);
        } else {
            $failMsg["msg"] = "密码错误";
            echo json_encode($failMsg,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
        }
    }
} else {
    $failMsg["msg"] = "该用户不存在";
    echo json_encode($failMsg,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
}

$conn->close();
?>