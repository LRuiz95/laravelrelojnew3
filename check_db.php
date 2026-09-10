<?php
$link = new mysqli('127.0.0.1','root','JEZVlehxHux7xmE2RY87OYq2ZL7mhXHY');
if ($link->connect_error) {
    die('DB ERROR: ' . $link->connect_error);
} else {
    echo 'DB CONNECTED';
}
$result = $link->query('SHOW DATABASES');
while ($row = $result->fetch_row()) {
    echo $row[0] . PHP_EOL;
}
$link->close();
?>