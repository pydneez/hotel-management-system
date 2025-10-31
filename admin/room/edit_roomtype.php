<?php
    require_once(__DIR__ . '/../auth_check.php');
    
    $error_message = "";
    $success_message = "";
    $roomType = null;
    $roomImages = []; // To store existing images
    $type_id = null;

    // --- Get the Type ID (from GET or POST) ---
    // Make sure the DB connection is still available or re-establish if needed
    if (!isset($conn) || !$conn->ping()) {
        // NOTE: Adjust this path if your connect.php is elsewhere relative to edit_roomtype.php
        require(__DIR__ . '/../../connect.php'); 
    }
    
    if (isset($_GET['type_id'])) {
        $type_id = filter_input(INPUT_GET, 'type_id', FILTER_SANITIZE_NUMBER_INT);
    } elseif (isset($_POST['type_id'])) {
        $type_id = filter_input(INPUT_POST, 'type_id', FILTER_SANITIZE_NUMBER_INT);
    } else {
        header("Location: room_types.php?error=noID"); // No ID, redirect
        exit;
    }

    // --- Step 1: Handle Form Submission (UPDATE + UPLOAD) ---
    if (isset($_POST['submit'])) {
        // --- A. Handle Text Data Update ---
        $type_name = trim(filter_input(INPUT_POST, 'type_name', FILTER_SANITIZE_SPECIAL_CHARS));
        $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS));
        $base_price = filter_input(INPUT_POST, 'base_price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $capacity = filter_input(INPUT_POST, 'capacity', FILTER_SANITIZE_NUMBER_INT);
        $amenities = trim(filter_input(INPUT_POST, 'amenities', FILTER_SANITIZE_SPECIAL_CHARS));

        if (empty($type_name) || empty($base_price) || empty($capacity)) {
            $error_message = "Please fill in all required fields (Name, Price, Capacity).";
        } else {
            $query = "UPDATE RoomTypes
                      SET type_name = ?, description = ?, base_price = ?, capacity = ?, amenities = ?
                      WHERE type_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssdisi", $type_name, $description, $base_price, $capacity, $amenities, $type_id);

            if ($stmt->execute()) {
                $success_message = "Room Type details updated successfully!";
            } else {
                $error_message = "Text update failed. Error: " . $stmt->error;
            }
            $stmt->close();
        }

        // --- B. Handle NEW Image Uploads ---
        // Check specifically if files were uploaded in this submission using the 'images' name
        if (isset($_FILES['images']) && is_array($_FILES['images']['error']) && $_FILES['images']['error'][0] != UPLOAD_ERR_NO_FILE && empty($error_message)) {

            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/rooms/';
            if (!is_writable($upload_dir)) {
                $error_message .= " <strong>CRITICAL ERROR: The upload directory is not writable.</strong> Please check server permissions for: " . htmlspecialchars($upload_dir);
            } else {
                // Get current image count before adding new ones
                $stmt_count = $conn->prepare("SELECT COUNT(*) as img_count FROM RoomTypeImages WHERE type_id = ?");
                $stmt_count->bind_param("i", $type_id);
                $stmt_count->execute();
                $count_result = $stmt_count->get_result()->fetch_assoc();
                $current_image_count = $count_result['img_count'];
                $stmt_count->close();
                
                $files = $_FILES['images'];
                $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
                $upload_limit = 5;
                $files_to_upload = count($files['name']);
                
                for ($i = 0; $i < $files_to_upload; $i++) {
                    if ($files['error'][$i] == UPLOAD_ERR_OK) {
                        if ($current_image_count >= $upload_limit) {
                            $error_message .= " Upload limit of 5 images reached. Some new images were not uploaded.";
                            break; // Stop uploading
                        }

                        $file_tmp_name = $files['tmp_name'][$i];
                        $file_ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));

                        if (in_array($file_ext, $allowed_ext) && $files['size'][$i] < 5000000) {
                            $new_file_name = uniqid('room_', true) . '.' . $file_ext;
                            $upload_path = $upload_dir . $new_file_name;
                            
                            if (move_uploaded_file($file_tmp_name, $upload_path)) {
                                $image_url_for_db = '/uploads/rooms/' . $new_file_name;
                                
                                // Insert into images table
                                $stmt_insert = $conn->prepare("INSERT INTO RoomTypeImages (type_id, image_url) VALUES (?, ?)");
                                $stmt_insert->bind_param("is", $type_id, $image_url_for_db);
                                $stmt_insert->execute();
                                $stmt_insert->close();
                                
                                $current_image_count++; // Increment count *after* successful insert
                                //$success_message .= " New image uploaded successfully.";
                            } else {
                                $error_message .= " <strong>Upload Failed:</strong> Could not move new file " . ($i+1) . ".";
                            }
                        } else {
                            $error_message .= " Invalid file type or size for new image " . ($i+1) . ".";
                        }
                    } elseif ($files['error'][$i] != UPLOAD_ERR_NO_FILE) {
                         // Report other upload errors
                         $error_message .= " Error uploading new file " . ($i+1) . ": Error code " . $files['error'][$i] . ".";
                    }
                }
                $success_message .= " New image uploaded successfully.";
            }
        }

        header("Location: room_types.php?status=success2");
        exit();
    }

    // --- Step 2: Fetch Data for Form (SELECT) ---
    // We always fetch fresh data *after* an update attempt to show current state
    $query_type = "SELECT * FROM RoomTypes WHERE type_id = ?";
    $stmt_type = $conn->prepare($query_type);
    $stmt_type->bind_param("i", $type_id);
    $stmt_type->execute();
    $roomType = $stmt_type->get_result()->fetch_assoc();
    $stmt_type->close();

    if (!$roomType) {
        // Redirect if the room type was somehow deleted between page load and now
        header("Location: room_types.php?error=notfound");
        exit;
    }

    // Fetch all existing images for this room type AFTER potential uploads/deletes
    $query_images = "SELECT * FROM RoomTypeImages WHERE type_id = ?";
    $stmt_images = $conn->prepare($query_images);
    $stmt_images->bind_param("i", $type_id);
    $stmt_images->execute();
    $images_result = $stmt_images->get_result();
    // Clear the array before repopulating
    $roomImages = []; 
    while ($row = $images_result->fetch_assoc()) {
        $roomImages[] = $row; // Populate $roomImages array with current images
    }
    $stmt_images->close();
    
    // Close connection only if it's still open
    if (isset($conn)) {
         $conn->close();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Room Type</title>
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
            <h1>Edit Room Type</h1>
            <div class="header-actions">
                <a href="room_types.php" class="btn btn-secondary">← Back to Room Types</a>
            </div>
        </div>
            
            <!-- ADDED: Wrapper div for AJAX messages -->
            <div id="ajax-message-area"> 
                <?php if (!empty($error_message)): ?>
                    <div class="form-message error"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                    <div class="form-message success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
            </div>

            <?php if ($roomType): // Only show the form if we found a room type ?>
            <!-- Added enctype for file uploads -->
            <form action="edit_roomtype.php?type_id=<?php echo htmlspecialchars($roomType['type_id']); ?>" method="post" enctype="multipart/form-data">
                
                <!-- Grid layout wrapper -->
                <div class="form-layout-grid">

                    <!-- Column 1: Room Info -->
                    <div class="background-card">
                        <input type="hidden" name="type_id" value="<?php echo htmlspecialchars($roomType['type_id']); ?>">

                        <label for="type_name">Type Name<span>*</span></label>
                        <input type="text" id="type_name" name="type_name" 
                               value="<?php echo htmlspecialchars($roomType['type_name']); ?>" required>

                        <label for="description">Description</label>
                        <textarea id="description" name="description" maxlength="255" rows="5"><?php echo htmlspecialchars($roomType['description']); ?></textarea>

                        <label for="base_price">Base Price ($)<span>*</span></label>
                        <input type="number" id="base_price" name="base_price"  min='0' step="0.01" 
                               value="<?php echo htmlspecialchars($roomType['base_price']); ?>" required>

                        <label for="capacity">Capacity (Guests)<span>*</span></label>
                        <input type="number" id="capacity" name="capacity" min='0' step="1" 
                               value="<?php echo htmlspecialchars($roomType['capacity']); ?>" required>
                        
                        <label for="amenities">Amenities (comma-separated)</label>
                        <input type="text" id="amenities" name="amenities" 
                               value="<?php echo htmlspecialchars($roomType['amenities']); ?>">
                    </div>

                    <!-- Column 2: Image Manager -->
                    <div class="background-card">
                        <div class="image-manager">
                            <label>Manage Images (Optional, Max 5)</label>
                            
                            <!-- 1. Image Preview Grid (Existing + New) -->
                            <div class="image-preview-grid" id="image-grid">
                                <!-- Existing Images (Loaded by PHP) -->
                                <?php foreach ($roomImages as $image): ?>
                                    <div class="image-preview-item existing-image" id="image-<?php echo $image['image_id']; ?>">
                                        <img src="<?php echo htmlspecialchars($image['image_url']); ?>" 
                                             onerror="this.style.display='none'; this.parentElement.style.display='none';" 
                                             class="image-preview-img" alt="Room Image">
                                        <button type="button" class="delete-image-btn" 
                                                data-image-id="<?php echo $image['image_id']; ?>" 
                                                title="Delete Image Permanently">&times;</button>
                                    </div>
                                <?php endforeach; ?>
                                
                                <!-- Placeholder for New Images (Added by JS) -->
                            </div>
                             <?php if (empty($roomImages)): ?>
                                <!-- This text will be hidden by JS if new images are added -->
                                <p id="no-images-text" style="color: #555;">No images uploaded for this room type yet.</p>
                             <?php endif; ?>
                            
                            <!-- 2. Image Upload Input Section -->
                            <div id="image-upload-section" style="margin-top: 1rem;">
                                <?php 
                                    $current_count_php = count($roomImages); // Get PHP count
                                    $can_upload_more = $current_count_php < 5;
                                ?>
                                <?php if ($can_upload_more): ?>
                                    <input type="file" id="images" name="images[]" multiple accept="image/png, image/jpeg, image/jpg, image/webp"> 
                                    <br>
                                    <small>Max 5MB each. Allowed types: JPG, PNG, WEBP.</small>
                                <?php else: ?>
                                    <p style="font-weight: 600; color: var(--navy-blue);">You have reached the 5 image limit.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div> 

                <div class="form-submit-row">
                    <input type="submit" name="submit" value="Save All Changes" class="btn btn-primary">
                </div>

            </form>
            <?php else: ?>
                <!-- This message shows if the initial fetch failed -->
                <p>Room Type not found. Please go back and select a valid room type to edit.</p> 
            <?php endif; ?>

        
    </main>
</div>

<!-- 
=====================================================
=== JAVASCRIPT FOR IMAGE PREVIEW & DELETE (COMBINED) ===
=====================================================
-->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const imageGrid = document.getElementById('image-grid');
    const imageInput = document.getElementById('images'); 
    const noImagesText = document.getElementById('no-images-text');
    const uploadSection = document.getElementById('image-upload-section');
    const ajaxMessageArea = document.getElementById('ajax-message-area'); // Area for AJAX messages

    let newFileStore = []; // Holds NEW File objects selected by the user
    const maxImages = 5;
    // Get initial count from PHP - number of images currently saved in DB
    let existingImageCount = <?php echo count($roomImages); ?>; 

    // --- Helper function to display messages ---
    function displayAjaxMessage(message, type = 'success') {
        if (!ajaxMessageArea) return; 
        ajaxMessageArea.innerHTML = ''; 
        const messageDiv = document.createElement('div');
        messageDiv.className = `form-message ${type}`; 
        messageDiv.textContent = message; 
        ajaxMessageArea.appendChild(messageDiv);
        ajaxMessageArea.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    
    // --- Function to clear AJAX messages ---
    function clearAjaxMessages() {
         if (ajaxMessageArea) ajaxMessageArea.innerHTML = '';
    }

    // --- Function to update the Add button visibility ---
    function updateCounterAndButton() {
        const totalImages = existingImageCount + newFileStore.length;
        
        if (uploadSection) {
            if (totalImages >= maxImages) {
                uploadSection.style.display = 'none'; // Hide if limit reached
            } else {
                uploadSection.style.display = ''; // Show otherwise
            }
        }
        
        if (noImagesText) {
             noImagesText.style.display = (totalImages === 0) ? '' : 'none';
        }
    }
    
    // --- Initial setup ---
    updateCounterAndButton(); // Run once on page load to set initial state


    // --- 1. Handle selection of NEW files via the VISIBLE file input ---
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            clearAjaxMessages(); 
            const newFiles = Array.from(e.target.files);
            const currentTotal = existingImageCount; // We are REPLACING newFileStore
            const slotsRemaining = maxImages - currentTotal; 

            // Clear any old *new* previews, since this input REPLACES the selection
            newFileStore = [];
            const oldPreviews = imageGrid.querySelectorAll('.image-preview-item.new-image');
            oldPreviews.forEach(preview => preview.remove());
            
            // --- UPDATED: Limit check and Alert Message ---
            if (newFiles.length > slotsRemaining) {
                 const currentSelectedCount = 0; // Resetting on change
                 let message = `You already have ${existingImageCount} saved images.\n`;
                 message += `You tried to select ${newFiles.length}, but you only have ${slotsRemaining} slot(s) left.\n\n`;
                 message += `Please select ${slotsRemaining} or fewer images.`;

                 alert(message); // Show the detailed alert
                 e.target.value = null; // Clear the file input
                 updateRealFileInput(); // Sync the empty list
                 return; // Stop processing further
            }
            // --- END OF UPDATED CHECK ---

            // Add new valid files to the store and create previews
            newFiles.forEach(file => {
                 if (existingImageCount + newFileStore.length >= maxImages) return; 

                 if (['image/jpeg', 'image/png', 'image/webp', 'image/jpg'].includes(file.type)) {
                     if (file.size < 5000000) { 
                         if (!newFileStore.some(f => f.name === file.name && f.size === file.size)) {
                             newFileStore.push(file); 
                             createPreview(file, newFileStore.length - 1); 
                         } else {
                              console.log(`File "${file.name}" is already selected.`); 
                         }
                     } else {
                          displayAjaxMessage(`File "${file.name}" is too large (Max 5MB).`, 'error');
                     }
                 } else {
                      displayAjaxMessage(`File "${file.name}" is not a valid image type (JPG, PNG, WEBP).`, 'error');
                 }
             });
            
            // NOTE: updateRealFileInput() is not needed here, as 'imageInput'
            // is the real input and already holds the files.
            // We ONLY need it for deletion.
            updateCounterAndButton(); 

            // DO NOT clear e.target.value, as it holds the selected files
        });
    }

    // --- 2. Handle clicks within the image grid ---
    if (imageGrid) {
        imageGrid.addEventListener('click', function(e) {
            clearAjaxMessages(); 
            const button = e.target; 

            // A. Clicked delete button for an EXISTING image (needs AJAX)
            if (button.classList.contains('delete-image-btn') && button.dataset.imageId) {
                handleDeleteExisting(button);
            }
            // B. Clicked delete button for a NEW image preview (only JS removal)
            else if (button.classList.contains('delete-image-btn')) { 
                 const itemToRemove = button.closest('.image-preview-item.new-image');
                 if (itemToRemove) {
                     const allNewPreviews = Array.from(imageGrid.querySelectorAll('.image-preview-item.new-image'));
                     const indexToRemove = allNewPreviews.indexOf(itemToRemove);
                     
                     if (indexToRemove > -1) { 
                         handleDeleteNew(indexToRemove, itemToRemove); 
                     } else {
                          console.error("Could not find the index of the new preview item to delete.");
                     }
                 }
            }
        });
    }

    // --- Function to create a preview element for a NEWLY selected file ---
    function createPreview(file, index) {
        const reader = new FileReader();
        reader.onload = function(event) {
            const item = document.createElement('div');
            item.className = 'image-preview-item new-image'; 
            item.id = `new-image-${index}`; 
            
            const img = document.createElement('img');
            img.src = event.target.result;
            img.className = 'image-preview-img';
            img.alt = 'New image preview';
            
            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'delete-image-btn'; 
            deleteBtn.title = 'Remove New Image';
            deleteBtn.innerHTML = '&times;'; 
            
            item.appendChild(img);
            item.appendChild(deleteBtn);
            imageGrid.appendChild(item); 
            
            if (noImagesText) noImagesText.style.display = 'none';
        }
        reader.readAsDataURL(file); 
    }
    
     // --- Function to handle removing a NEW image preview ---
    function handleDeleteNew(indexToRemove, itemToRemove) {
        if (isNaN(indexToRemove) || indexToRemove < 0 || indexToRemove >= newFileStore.length) {
             console.error("handleDeleteNew: Invalid index provided for deletion:", indexToRemove, " FileStore length:", newFileStore.length);
             return;
        }

        console.log(`Attempting to remove new preview at index: ${indexToRemove}.`); // DEBUG
        
        // Remove from fileStore array *using the provided index*
        newFileStore.splice(indexToRemove, 1);

        // Remove preview element from the DOM
        if (itemToRemove) {
            itemToRemove.remove();
        } else {
             console.warn("Could not find preview item to remove for index:", indexToRemove);
        }

        updateRealFileInput(); // Update the hidden input
        updateCounterAndButton(); // Update button visibility
    }


    // --- Function to handle deleting an EXISTING image (uses AJAX) ---
    function handleDeleteExisting(button) {
        const imageId = button.dataset.imageId;
        const imageItem = document.getElementById('image-' + imageId);

        if (!confirm('Are you sure you want to PERMANENTLY delete this image?')) {
            return; // User cancelled
        }

        const formData = new FormData();
        formData.append('image_id', imageId);

        fetch('delete_image.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
             if (!response.ok) { 
                 throw new Error(`HTTP error! status: ${response.status}`);
             }
             return response.json();
        })
        .then(data => {
            if (data.success) {
                if (imageItem) {
                    imageItem.remove();
                }
                existingImageCount--; 
                updateCounterAndButton(); 
                displayAjaxMessage('Image deleted successfully.', 'success'); 
            } else {
                 displayAjaxMessage('Error: ' + data.message, 'error'); 
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            displayAjaxMessage('An error occurred while deleting the image. Please check the console.', 'error'); 
        });
    }

    // --- Function to update the hidden file input ('realInput') ---
    function updateRealFileInput() {
        console.log(`Updating realInput. Files in newFileStore: ${newFileStore.length}`); // DEBUG
        const dataTransfer = new DataTransfer();
        newFileStore.forEach(file => {
             if (file instanceof File) {
                 dataTransfer.items.add(file);
             }
        });
        // Assign the updated file list to the hidden input
        if (imageInput) { // Use the main input
             imageInput.files = dataTransfer.files; 
             console.log(`Assigned ${imageInput.files.length} files to realInput.`); // DEBUG
        } else {
             console.error("Could not find the 'imageInput' element (id='images') to update.");
        }
    }
});
</script>

</body>
</html>

