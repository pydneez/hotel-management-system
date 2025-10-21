<?php
$conn = new mysqli('localhost','root','root','hotel_management_system');
   if($conn->connect_errno){
      echo $conn->connect_errno.": ".$conn->connect_error;
   }
?>

