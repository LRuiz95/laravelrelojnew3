<?php
$link = new mysqli('127.0.0.1','root','JEZVlehxHux7xmE2RY87OYq2ZL7mhXHY','rh_reloj_testing');
$result = $link->query('SELECT * FROM ciclos');
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo 'inicial: ' . $row['inicial'] . ', final: ' . $row['final'] . ', periodo: ' . $row['periodo'] . PHP_EOL;
    }
} else {
    echo 'No rows' . PHP_EOL;
}
$link->close();
?>