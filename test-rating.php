<?php
require_once 'php/config.php';

echo "<h2>CEK DATA RATING</h2>";

// Cek tabel planner_ratings
$cek_tabel = mysqli_query($conn, "SHOW TABLES LIKE 'planner_ratings'");
if(mysqli_num_rows($cek_tabel) > 0) {
    echo "✅ Tabel planner_ratings ADA<br><br>";
    
    $query = mysqli_query($conn, "SELECT * FROM planner_ratings");
    if(mysqli_num_rows($query) > 0) {
        echo "<table border='1' cellpadding='8'>";
        echo "<tr><th>ID</th><th>Planner ID</th><th>User ID</th><th>Rating</th><th>Created At</th></tr>";
        while($row = mysqli_fetch_assoc($query)) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['planner_id']}</td>";
            echo "<td>{$row['user_id']}</td>";
            echo "<td>{$row['rating']}</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ Tabel planner_ratings KOSONG. Belum ada rating dari user.<br>";
        echo "Silakan login sebagai user dan beri rating bintang ke Wedding Planner.";
    }
} else {
    echo "❌ Tabel planner_ratings TIDAK ADA! Jalankan SQL di atas terlebih dahulu.";
}

// Cek data wedding_planners
echo "<br><br>";
$planners = mysqli_query($conn, "SELECT id, name, total_ratings, ratings_count FROM wedding_planners");
echo "<table border='1' cellpadding='8'>";
echo "<tr><th>ID</th><th>Planner Name</th><th>Total Ratings</th><th>Ratings Count</th></tr>";
while($p = mysqli_fetch_assoc($planners)) {
    echo "<tr>";
    echo "<td>{$p['id']}</td>";
    echo "<td>{$p['name']}</td>";
    echo "<td>{$p['total_ratings']}</td>";
    echo "<td>{$p['ratings_count']}</td>";
    echo "</tr>";
}
echo "</table>";
?>