<?php
$network_share = 'W:\\Media\\Video\\TV Shows';

if (is_dir($network_share)) {
    echo "Contents of the network share:\n";
    $files = scandir($network_share);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo $file . "\n";
        }
    }
} else {
    echo "Network share not accessible or does not exist.";
}
?>
