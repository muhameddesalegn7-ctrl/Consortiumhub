<?php
session_start();
echo "Session data:\n";
print_r($_SESSION);

echo "\n\nUser cluster: " . ($_SESSION['cluster_name'] ?? 'Not set');
echo "\nUser role: " . ($_SESSION['role'] ?? 'Not set');
?>