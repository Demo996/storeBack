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

if($data = file_get_contents("php://input")) {
    parse_str($data, $getData);
    $username = $getData["username"];
    $delSql = "DELETE FROM a WHERE name='$username'";
    $conn->query($delSql);
    $conn->close();
    echo "del";
    $conn->close();
    return;
} 

$data1 = array();
$data2 = array();
$sql = "SELECT GROUP_CONCAT(groupname) AS usertype,name AS username FROM groupmanager,mastergroup WHERE mastergroup.groupid=groupmanager.groupid GROUP BY name";
$result = $conn->query($sql);
$total = $result->num_rows;
if($total > 0) {
    while($row = $result->fetch_assoc()) {
        array_push($data1, $row);
    }

    $data2["code"] = 0;
    $data2["msg"] = "";
    $data2["count"] = $total;
    $data2["data"] = $data1;
}
echo json_encode($data2, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
$conn->close();
?>