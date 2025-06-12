<?php
session_start();
$_SESSION['ALB_TEST'] = time();
echo "当前 Session ID: " . session_id() . "<br>";
echo "存储的值: " . $_SESSION['ALB_TEST'];
