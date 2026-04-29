<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);


function connectToDatabase() {
    $host = 'localhost';
    $port = '5432';
    $dbname = 'greenwich_ap_master';
    $user = 'postgres';
    $password = '1';

    $conn_str = "host={$host} port={$port} dbname={$dbname} user={$user} password={$password}";
    $conn = pg_connect($conn_str);
    if (!$conn) {
        die("Connection failed: Unable to connect to PostgreSQL database.");
    }

    return $conn;
}