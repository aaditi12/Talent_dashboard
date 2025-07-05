<?php  

$sName = "localhost"; // No need for :3306 unless non-default port
$uName = "etms_user";  
$pass  = "Aaditi@1810123";  
$db_name = "etms_db";

try {
    $conn = new PDO("mysql:host=$sName;dbname=$db_name;charset=utf8", $uName, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected to database successfully!";
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
    exit;
}
?>
