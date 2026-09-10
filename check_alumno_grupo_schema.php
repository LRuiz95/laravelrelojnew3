<?php
$link = new mysqli('127.0.0.1','root','JEZVlehxHux7xmE2RY87OYq2ZL7mhXHY','rh_reloj_testing');
$result = $link->query("DESCRIBE alumnos_grupos");
while ($row = $result->fetch_assoc()) {
    echo "Field: " . $row['Field'] . " | Type: " . $row['Type'] . PHP_EOL;
}
$link->close();
?>