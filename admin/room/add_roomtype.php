<?php
    require_once(__DIR__ . '/../auth_check.php');
    
    $error_message = "";
    $success_message = "";
    $form_data = []; // Holds sticky form data

    // --- Handle Form Submission (INSERT) ---
    if (isset($_POST['submit'])) {
        // Sanitize all TEXT inputs and store them
        $form_data = [
            'type_name'   => trim(filter_input(INPUT_POST, 'type_name', FILTER_SANITIZE_SPECIAL_CHARS)),
            'description' => trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS)),
            'base_price'  => filter_input(INPUT_POST, 'base_price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            'capacity'    => filter_input(INPUT_POST, 'capacity', FILTER_SANITIZE_NUMBER_INT),
            'amenities'   => trim(filter_input(INPUT_POST, 'amenities', FILTER_SANITIZE_SPECIAL_CHARS))
        ];

        // --- Validation for text fields ---
        if (empty($form_data['type_name']) || empty($form_data['base_price']) || empty($form_data['capacity'])) {
            $error_message = "Please fill in all required fields (Name, Price, Capacity).";
        } else {
            // --- Step 1: Insert the Text Data into RoomTypes ---
            $query = "INSERT INTO RoomTypes (type_name, description, base_price, capacity, amenities) 
                      VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($query);
            // s = string, d = double, i = integer
            $stmt->bind_param("ssdis", 
                $form_data['type_name'], 
                $form_data['description'], 
                $form_data['base_price'], 
                $form_data['capacity'], 
                $form_data['amenities']
            );

            if ($stmt->execute()) {
                // --- Step 2: Get the new type_id that was just created ---
                $new_type_id = $conn->insert_id;
                $success_message = "New room type added successfully! ";
                $stmt->close(); // Close the first statement

                // --- Step 3: Handle NEW Image Uploads (Loop) ---
                if (isset($_FILES['images'])) {
                    $files = $_FILES['images'];
                    $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
                    $upload_limit = 5;
                    $files_to_upload = count($files['name']);

                    for ($i = 0; $i < $files_to_upload; $i++) {
                        if ($files['error'][$i] == UPLOAD_ERR_OK) {
                            if ($i >= $upload_limit) {
                                $error_message .= " Upload limit of 5 images reached. Some images were not uploaded.";
                                break; // Stop uploading
                            }

                            $file_tmp_name = $files['tmp_name'][$i];
                            $file_ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));

                            if (in_array($file_ext, $allowed_ext) && $files['size'][$i] < 5000000) {
                                $new_file_name = uniqid('room_', true) . '.' . $file_ext;
                                $upload_path = $_SERVER['DOCUMENT_ROOT'] . '/uploads/rooms/' . $new_file_name;
                                
                                if (move_uploaded_file($file_tmp_name, $upload_path)) {
                                    $image_url_for_db = '/uploads/rooms/' . $new_file_name;
                                    
                                    // --- Step 4: Insert image path into RoomTypeImages table ---
                                    $stmt_insert = $conn->prepare("INSERT INTO RoomTypeImages (type_id, image_url) VALUES (?, ?)");
                                    $stmt_insert->bind_param("is", $new_type_id, $image_url_for_db);
                                    $stmt_insert->execute();
                                    $stmt_insert->close();
                                    
                                    //$success_message .= " Image " . ($i+1) . " uploaded.";
                                } else {
                                    $error_message .= " <strong>Upload Failed:</strong> Could not move file " . ($i+1) . ".";
                                }
                            } else {
                                $error_message .= " Invalid file type or size (Image " . ($i+1) . ").";
                            }
                        }
                    }
                    $success_message .= " Images uploaded.";
                }
                
                $form_data = []; // Clear the form data on success
            } else {
                // Handle text data insert failure
                if ($stmt->errno == 1062) {
                    $error_message = "A room type with this name already exists.";
                } else {
                    $error_message = "Insert failed. Error: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
    
    $conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Room Type</title>
    <link rel="stylesheet" href="../admin.css">
</head>
<body>

<?php 
    include "../component/navbar.php"; 
?>

<div class="dashboard-container">
    <?php 
        include "../component/sidebar.php"; 
    ?>

    <main class="content">
        <div class="content-header-row">
            <h1>Add New Room Type</h1>
            
            <div class="header-actions">
                <a href="room_types.php" class="btn btn-secondary">← Back to Room Types</a>
            </div>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="form-message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="form-message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <!-- Form now wraps the grid and submit button -->
        <form action="add_roomtype.php" method="post" enctype="multipart/form-data">
            
            <div class="form-layout-grid">

                <!-- Column 1: Room Info -->
                <div class="background-card">
                    <label for="type_name">Type Name<span>*</span></label>
                    <input type="text" id="type_name" name="type_name" 
                           value="<?php echo htmlspecialchars($form_data['type_name'] ?? ''); ?>" required>

                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>

                    <label for="base_price">Base Price ($)<span>*</span></label>
                    <input type="number" id="base_price" name="base_price" min='0' step="0.01" 
                           value="<?php echo htmlspecialchars($form_data['base_price'] ?? ''); ?>" required>

                    <label for="capacity">Capacity (Guests)<span>*</span></label>
                    <input type="number" id="capacity" name="capacity" min='0' step="1" 
                           value="<?php echo htmlspecialchars($form_data['capacity'] ?? ''); ?>" required>
                    
                    <label for="amenities">Amenities (comma-separated)</label>
                    <input type="text" id="amenities" name="amenities" 
                           value="<?php echo htmlspecialchars($form_data['amenities'] ?? ''); ?>">
                </div>
                
                <!-- Column 2: Image Upload -->
                <div class="background-card">
                    <div class="image-manager">
                        <label for="images">Room Images (Optional, Max 5)</label>
                        <!-- NEW: Preview grid for new images -->
                        <div class="image-preview-grid" id="add-image-preview" style="margin-top: 1rem;">
                            <p style="grid-column: 1 / -1; color: #555;">New image previews will appear here.</p>
                        </div>

                         <div id="image-upload-section" style="margin-top: 1rem;">
                            <input type="file" id="images" name="images[]" multiple accept="image/png, image/jpeg, image/jpg, image/webp">
                            <br>
                            <small>Max 5MB each. Allowed types: JPG, PNG, WEBP.</small>
                         </div>
                    </div>
                </div>

            </div> 

            <div class="form-submit-row">
                <input type="submit" name="submit" value="Add Room Type" class="btn btn-primary">
            </div>

        </form>

    </main>
</div>

<!-- === UPDATED JAVASCRIPT FOR PREVIEWING & DELETING === -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const imageInput = document.getElementById('images');
    const previewGrid = document.getElementById('add-image-preview');
    
    // Get the UI elements
    const uploadSection = document.getElementById('image-upload-section');
    const limitMessage = document.getElementById('image-limit-message');
    
    let fileStore = []; // This will hold our File objects
    const maxImages = 5; // Define the limit

    // 1. Listen for new files being selected
    imageInput.addEventListener('change', function(e) {
        const newFiles = Array.from(e.target.files);
        const currentCount = fileStore.length;
        const newFileCount = newFiles.length;
        const potentialTotal = currentCount + newFileCount;
        
        // Check if the *potential* total exceeds the limit
        if (potentialTotal > maxImages) {
            const slotsRemaining = maxImages - currentCount;
            let message;
            
            if (slotsRemaining > 0) {
                // e.g., "You have 2 images. You tried to add 4, but you only have 3 slots left."
                message = `You already have ${currentCount} images selected.\n` +
                          `You tried to add ${newFileCount}, but you only have ${slotsRemaining} slot(s) left.\n\n` +
                          `Please select ${slotsRemaining} or fewer images.`;
            } else {
                // e.g., "You already have 5 images. You cannot add more."
                message = `You already have ${currentCount} (the maximum) images selected.\n` +
                          `You cannot add any more.`;
            }

            alert(message);
            
            // Clear the file input's value
            e.target.value = null; 
            
            // STOP. Do not add *any* of the selected files.
            return; 
        }
        // --- END OF VALIDATION LOGIC ---

        // If we are here, the *number* of files is valid (<= 5).
        // Now, we loop through and validate each file for type and size.
        let filesAdded = 0;
        newFiles.forEach(file => {
            if (['image/jpeg', 'image/png', 'image/webp', 'image/jpg'].includes(file.type)) {
                if (file.size < 5000000) { // 5MB
                    // Check for duplicates
                    if (!fileStore.some(f => f.name === file.name && f.size === file.size)) {
                        fileStore.push(file);
                        filesAdded++;
                    } else {
                        console.log(`File "${file.name}" is already selected.`);
                    }
                } else {
                    alert(`File "${file.name}" is too large (Max 5MB) and was not added.`);
                }
            } else {
                alert(`File "${file.name}" is not a valid image type and was not added.`);
            }
        });
        
        // Only update if we actually added files
        if (filesAdded > 0) {
            // Update the file input's internal list
            updateFileInput();
            
            // Re-draw the preview grid
            updatePreviewGrid();
            
            // Update the button visibility
            updateUIVisibility();
        }

        // Clear the file input's value so 'change' fires again
        e.target.value = null;
    });

    // 2. Listen for clicks on the delete buttons
    previewGrid.addEventListener('click', function(e) {
        const deleteButton = e.target.closest('.delete-image-btn');
        if (deleteButton) {
            const index = deleteButton.dataset.index;
            if (index === undefined) return; 

            // Remove the file from our store
            fileStore.splice(index, 1);
            
            // Update the file input's internal list
            updateFileInput();
            
            // Re-draw the preview grid
            updatePreviewGrid();
            
            // Update the button visibility
            updateUIVisibility();
        }
    });

    // 3. This function draws the preview grid based on the fileStore
    function updatePreviewGrid() {
        previewGrid.innerHTML = ''; 

        if (fileStore.length === 0) {
            previewGrid.innerHTML = '<p style="grid-column: 1 / -1; color: #555;">New image previews will appear here.</p>';
            return;
        }

        fileStore.forEach((file, index) => {
            const reader = new FileReader();
            
            reader.onload = function(event) {
                const item = document.createElement('div');
                item.className = 'image-preview-item new-image'; // Added 'new-image' for consistency
                
                const img = document.createElement('img');
                img.src = event.target.result;
                img.className = 'image-preview-img';
                img.alt = 'New image preview';
                
                const deleteBtn = document.createElement('button');
                deleteBtn.type = 'button';
                deleteBtn.className = 'delete-image-btn';
                deleteBtn.dataset.index = index; 
                deleteBtn.title = 'Remove Image';
                deleteBtn.innerHTML = '&times;'; 
                
                item.appendChild(img);
                item.appendChild(deleteBtn);
                previewGrid.appendChild(item);
            }
            
            reader.readAsDataURL(file); 
        });
    }

    // 4. This magic function updates the *actual* file input
    function updateFileInput() {
        const dataTransfer = new DataTransfer();
        fileStore.forEach(file => {
            dataTransfer.items.add(file);
        });
        imageInput.files = dataTransfer.files;
    }
    
    // 5. This function hides/shows the upload button
    function updateUIVisibility() {
        if (fileStore.length >= maxImages) {
            uploadSection.style.display = 'none';
            limitMessage.style.display = 'block';
        } else {
            uploadSection.style.display = 'block';
            limitMessage.style.display = 'none';
        }
    }
    
    // Run once on load
    updateUIVisibility();
});
</script>

</body>
</html>

