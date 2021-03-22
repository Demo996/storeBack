<?php
header("Access-Control-Allow-Origin:*");
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

parse_str($data, $getData);
$userName = $getData["username"];
$passWord = $getData["password"];
$getDate = date("Y-m-d H:i:s");

$conn = connectDB();
mysqli_select_db($conn, MYSQL_DB1);

$sql = "SELECT password,token,`role_name` FROM `user_test`,`roles_test` WHERE `user_name`='$userName' AND `role_pid`=`role_id`";
$result = $conn->query($sql);
if($result->num_rows) {
    while($row = $result->fetch_assoc())
    {
        $getPwd = $row["password"];
        $getToken = $row["token"];
        $getRole = $row["role_name"];
        $sendArr["role"] = $getRole;
        if($getPwd === $passWord) {
            $meta["state"] = 200;
            $meta["msg"] = "登陆成功";
            //用户无token则创建token并存进数据库
            if(!$getToken) {
                $str = md5(uniqid(md5(microtime(true)),true));
                $token = sha1($str);
                $conn->query("UPDATE user_test SET token='$token' WHERE `user_name`='$username'");
                $sendArr["token"] = $token;
            } else {
                $sendArr["token"] = $getToken;
            }
            // 记录登陆记录
            $sqlIn = "INSERT INTO history VALUES(null,'$userName','$getDate','$getDate') ON DUPLICATE KEY UPDATE `last_login_time`=`login_time`, `login_time`='$getDate'";
            $conn->query($sqlIn);
        } else {
            $meta["state"] = 202;
            $meta["msg"] = "密码错误";
        }
    }
} else {
    $meta["state"] = 202;
    $meta["msg"] = "该用户不存在";
}
$sendArr["meta"] = $meta;
$sendArr["user"] = $userName;
echo json_encode($sendArr, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
$conn->close();
?>