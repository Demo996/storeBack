<?php
function checkToken($token) {
    $servername = "localhost";
    $username = "root";
    $password = "31415926";
    $dbname = "mainstorege";
    
    $meta = array();
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die();
    }

    $result = $conn->query("SELECT token FROM `user_test` WHERE token='$token'");
    if($result->num_rows > 0) {
        $conn->close();
         return null;
    } else {
        $meta["msg"] = "Token有误，请重新登录";
        $meta["state"] = 201;
        $conn->close();
    }
    return $meta;
}
?>