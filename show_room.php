<?php

$query = "
    SELECT 
        rt.*, 
        (SELECT rti.image_url 
         FROM RoomTypeImages rti 
         WHERE rti.type_id = rt.type_id 
         LIMIT 1) AS main_image_url
    FROM 
        RoomTypes rt
    ORDER BY 
        rt.base_price ASC
";

$result = $conn->query($query);

if (!$result) {
    echo "<p>Error loading rooms: " . $conn->error . "</p>";
    return;
}
?>

<section class="room-section" id = "rooms">
    <div class="container">
        <h2>Our Rooms</h2>
        
        <div class="room-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while($room = $result->fetch_assoc()): ?>
                    <div class="room-card">
                        <img 
                            src="<?php echo htmlspecialchars($room['main_image_url'] ?? '/uploads/rooms/default.jpg'); ?>" 
                            alt="<?php echo htmlspecialchars($room['type_name']); ?>" 
                            class="room-card-image"
                            onerror="this.onerror=null; this.src='https://placehold.co/400x220/0a2342/f0f4f8?text=Image+Not+Found';">

                        <div class="room-card-content">
                            <h3><?php echo htmlspecialchars($room['type_name']); ?></h3>
                            <div class="room-card-price">
                                $<?php echo htmlspecialchars(number_format($room['base_price'], 2)); ?> / night
                            </div>
                            <p class="room-card-description">
                                <?php echo htmlspecialchars($room['description']); ?>
                            </p>
                            
                            <!-- Amenities List -->
                            <?php if (!empty($room['amenities'])): ?>
                                <ul class="room-card-amenities">
                                    <?php 
                                        $amenities = explode(',', $room['amenities']);
                                        foreach ($amenities as $amenity): 
                                    ?>
                                        <li><?php echo htmlspecialchars(trim($amenity)); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <a href="search_results.php?type_id=<?php echo $room['type_id']; ?>" class="btn-primary">
                                Book Now
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center; grid-column: 1 / -1;">No room types are currently available.</p>
            <?php endif; ?>
        </div>
    </div>
</section>
