<?php
header("Content-type: text/plain; charset=UTF-8");

if (!(file_exists("output.csv"))){
    echo "output file not exixt";
} else {
    $fs = fopen("output.csv","r");
    $line = fgets($fs);
    $line = fgets($fs);
    echo $line;
}

fclose($fs);
?>