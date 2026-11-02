<?php
header('Content-Type: application/json');

$host = "localhost";
$username = "root";
$password = "";
$dbname = "gps_tracking";

$conn = new mysqli($host, $username, $password, $dbname);

// Cek Koneksi
if ($conn->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Koneksi database gagal: " . $conn->connect_error
    ]);
    exit();
}

// Memastikan permintaan menggunakan metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $inputData = $_POST;
    if (empty($inputData)) {
        $rawInput = file_get_contents('php://input');
        $inputData = json_decode($rawInput, true) ?? [];
    }
    
    // Ambil data yang dikirim dari AJAX JS
    $latitude  = $inputData['latitude'] ?? $inputData['lat'] ?? null;
    $longitude = $inputData['longitude'] ?? $inputData['lng'] ?? null;
    $speed     = $inputData['speed'] ?? 0;
    $accuracy  = $inputData['accuracy'] ?? null;

    error_log("Data diterima PHP: " . print_r($inputData, true));

    if (!empty($latitude) && !empty($longitude)) {

        // Konversi ke float agar aman masuk ke kolom DECIMAL MySQL
        $lat= floatval($latitude);
        $lng = floatval($longitude);
        $spd = floatval($speed);
        $acc = ($accuracy !== null && $accuracy !== '') ? (string) $accuracy : null;
        
        // Buat waktu WIB
        $waktuWIB = (new DateTime('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO location_logs (latitude, longitude, speed, accuracy, waktu) VALUES (?, ?, ?, ?, ?)");
        
        $stmt->bind_param("dddss", $lat, $lng, $spd, $acc, $waktuWIB);

        if ($stmt->execute()) {
            echo json_encode([
                "status" => "success",
                "message" => "Lokasi berhasil disimpan ke database SQLyog!",
                "data" => [
                    "lat" => $lat,
                    "lng" => $lng,
                    "speed" => $spd,
                    "accuracy" => $acc,
                    "waktu" => $waktuWIB
                ]
            ]);
        } else {
            // Ini akan menampilkan pesan error asli dari MySQL jika masih gagal
            echo json_encode([
                "status" => "error",
                "message" => "Gagal menyimpan ke database: " . $stmt->error
            ]);
        }

        $stmt->close();
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Data koordinat latitude/longitude bernilai 0 atau kosong.",
            "data_yang_diterima_php" => $inputData
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Metode HTTP tidak diizinkan."
    ]);
}

$conn->close();
?>