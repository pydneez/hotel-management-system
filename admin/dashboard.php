<?php
    require_once(__DIR__ . '/auth_check.php');

    function formatCountShort($number) {
        if ($number >= 1000000) {
            return number_format($number / 1000000, 1) . 'M'; 
        }
        if ($number >= 1000) {
            return number_format($number / 1000, 1) . 'k'; 
        }
        return number_format($number, 0); 
    }

    function formatCurrencyShort($number) {
        $prefix = $number < 0 ? '-$' : '$';
        $number = abs($number);
        if ($number >= 1000000) {
            return $prefix . number_format($number / 1000000, 1) . 'M'; 
        }
        if ($number >= 1000) {
            return $prefix . number_format($number / 1000, 1) . 'k'; 
        }
        return $prefix . number_format($number, 2); 
    }

    // Call the Stored Procedure ---
    $data = [];
    try {
        $sql = "CALL sp_GetDashboardAnalytics()";
        $result = $conn->query($sql);
        
        if ($result === false) {
            throw new Exception("Database query failed: " . $conn->error);
        }
        $data = $result->fetch_assoc();
        $result->close();
        
        // Clear any 'more results' so we can run other queries if needed
        while($conn->more_results()) {
            $conn->next_result();
            if($res = $conn->store_result()) {
                $res->free();
            }
        }
    } catch (Exception $e) {
        die("<strong>Fatal Dashboard Error:</strong> " . $e->getMessage());
    }

    $arrivals_count         = $data['arrivals_count'] ?? 0;
    $departures_count       = $data['departures_count'] ?? 0;
    $checked_in_count       = $data['checked_in_count'] ?? 0;
    $walkins_today          = $data['walkins_today'] ?? 0;
    
    $rooms_clean            = $data['rooms_clean'] ?? 0;
    $rooms_occupied         = $data['rooms_occupied'] ?? 0;
    $rooms_cleaning         = $data['rooms_cleaning'] ?? 0;
    $rooms_maintenance      = $data['rooms_maintenance'] ?? 0;
    $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<?php include "component/navbar.php"; ?>

<div class="dashboard-container">
    <?php include "component/sidebar.php"; ?>

    <main class="content">
        <h1>Dashboard</h1>
        
        <h2>Front Desk & Operations</h2>
        <div class="dashboard-widgets">
            <div class="card">
                Arrivals Expected
                <span><?= formatCountShort($arrivals_count) ?></span>
            </div>
            <div class="card">
                Departures Expected
                <span><?= formatCountShort($departures_count) ?></span>
            </div>
            <div class="card">
                Guests In-House
                <span><?= formatCountShort($checked_in_count) ?></span>
            </div>
            <div class="card">
                Walk-ins Today
                <span><?= formatCountShort($walkins_today) ?></span>
            </div>
        </div>
        
        <h2 style="margin-top: 2rem;">Housekeeping & Room Status</h2>
        <div class="dashboard-widgets">
            <div class="card">
                Available / Clean
                <span class="status-clean"><?= formatCountShort($rooms_clean) ?></span>
            </div>
            <div class="card">
                Occupied
                <span class="status-occupied"><?= formatCountShort($rooms_occupied) ?></span>
            </div>
            <div class="card">
                Needs Cleaning
                <span class="status-cleaning"><?= formatCountShort($rooms_cleaning) ?></span>
            </div>
            <div class="card">
                Out of Order
                <span class="status-maintenance"><?= formatCountShort($rooms_maintenance) ?></span>
            </div>
        </div>
    </main>
</div>

</body>
</html>