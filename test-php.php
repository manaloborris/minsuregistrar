<?php
echo "PHP is working!<br>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "<br>";
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'NOT SET') . "<br>";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NOT SET') . "<br>";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'NOT SET') . "<br>";
echo "Current working directory: " . getcwd() . "<br>";
echo "index.php exists: " . (file_exists('index.php') ? 'YES' : 'NO') . "<br>";
