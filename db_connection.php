<?php
$conn = new mysqli("localhost", "root", "", "cineclick_db");
if ($conn->connect_error) {
    die("Database connection failed");
}
