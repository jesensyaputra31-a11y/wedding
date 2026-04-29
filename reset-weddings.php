<?php
session_start();
require_once 'php/config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Wedding Data</title>
    <style>
        body { font-family: Arial; padding: 20px; text-align: center; }
        button { padding: 10px 20px; background: #D4AF37; border: none; border-radius: 5px; cursor: pointer; }
        .success { color: green; }
    </style>
</head>
<body>
    <h2>Wedding Data Reset Tool</h2>
    <button onclick="resetData()">Reset Wedding Data ke Default</button>
    <p id="message"></p>

    <script>
        function resetData() {
            const defaultWeddings = [
                {id: 1, couple: 'Sarah & David', date: '2025-12-25', guests: 250, status: 'Planning'},
                {id: 2, couple: 'Amanda & Budi', date: '2026-01-15', guests: 180, status: 'Pending'},
                {id: 3, couple: 'Jessica & Rizki', date: '2026-02-10', guests: 320, status: 'Planning'},
                {id: 4, couple: 'Putri & Andi', date: '2026-03-20', guests: 210, status: 'Confirmed'},
                {id: 5, couple: 'Maya & Dimas', date: '2026-04-05', guests: 150, status: 'Planning'}
            ];
            localStorage.setItem('weddings_data', JSON.stringify(defaultWeddings));
            document.getElementById('message').innerHTML = '<span class="success">✅ Data berhasil direset! <a href="admin-dashboard.php">Kembali ke Dashboard</a></span>';
        }
    </script>
</body>
</html>