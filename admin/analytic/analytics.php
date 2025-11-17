<?php
    require_once(__DIR__ . '/../auth_check.php');


    try {
        
        
        
    } catch (Exception $e) {
       
    }

    function formatCurrencyShort($number) {
        $prefix = $number < 0 ? '-$' : '$';
        $number = abs($number);
        if ($number >= 1000000) return $prefix . number_format($number / 1000000, 1) . 'M';
        if ($number >= 1000) return $prefix . number_format($number / 1000, 0) . 'K';
        return $prefix . number_format($number, 2);
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Dashboard | Financial Analytics</title>
    <link rel="stylesheet" href="../admin.css">
</head>
<body>

<?php include "../component/navbar.php"; ?>

<div class="dashboard-container">
    <?php include "../component/sidebar.php"; ?>

    <main class="content">
        <div class="content-header-row">
            <h1>Financial Analytics</h1>
        </div>
        
        
        <!-- === 1. Filter Form === -->
        <form method="GET" action="analytics.php" class="search-form card">
            <div class="form-layout-grid" style="grid-template-columns: 1fr 1fr 1fr;width:100%;">
                <div>
                    <label for="month">Month</label>
                    <select id="month" name="month">
                        <option value="0" <?php echo ($selected_month == 0) ? 'selected' : ''; ?>>All Year</option>
                        <?php for ($m = 1; $m <= 12; $m++): 
                            $month_name = date('F', mktime(0, 0, 0, $m, 10));
                        ?>
                            <option value="<?php echo $m; ?>" <?php echo ($selected_month == $m) ? 'selected' : ''; ?>>
                                <?php echo $month_name; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div>     
                    <label for="year">Year</label>
                    <select id="year" name="year">
                        <?php 
                        $current_year = date('Y');
                        for ($y = $current_year; $y >= $current_year - 3; $y--): 
                        ?>
                            <option value="<?php echo $y; ?>" <?php echo ($selected_year == $y) ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary" style="width:100%;padding: 12px 16px;">Filter</button>
                </div>
            </div>
        </form>

        <!-- === 2. KPI Cards === -->
        <h2 style="margin-top: 2rem;">
            Analytics for: 
            <?php 
                echo ($selected_month == 0) ? "All of $selected_year" : date('F Y', mktime(0, 0, 0, $selected_month, 1, $selected_year));
            ?>
        </h2>
        <div class="dashboard-widgets">
            <div class="card kpi-card">
                Net Revenue
                <span><?php echo formatCurrencyShort($kpi_data['net_revenue'] ?? 0); ?></span>
            </div>
            <div class="card kpi-card">
                Total Bookings
                <span><?php echo number_format($kpi_data['total_bookings'] ?? 0); ?></span>
            </div>
            <div class="card kpi-card">
                Avg. Booking Value
                <span><?php echo formatCurrencyShort($kpi_data['avg_booking_value'] ?? 0); ?></span>
            </div>
            <div class="card">
                Total Cancellations
                <span class="status-occupied"><?php echo number_format($kpi_data['total_cancellations'] ?? 0); ?></span>
            </div>
        </div>

        <!-- === 3. Top Rooms Table === -->
        <h2 style="margin-top: 2rem;">Top Rooms by Revenue</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Room Type</th>
                    <th>Total Bookings</th>
                    <th>Total Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($table_data)): ?>
                    <tr>
                        <td colspan="3" style="text-align: center;">No paid bookings found for this period.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($table_data as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['type_name']); ?></td>
                            <td><?php echo number_format($row['total_bookings']); ?></td>
                            <td>$<?php echo htmlspecialchars(number_format($row['total_revenue'], 2)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    </main>
</div>

</body>
</html>