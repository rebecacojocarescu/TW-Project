<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

try {
    $conn = getConnection();
    
    $query = "SELECT 
        af.id as form_id,
        af.status,
        af.first_name,
        af.last_name,
        p.id as pet_id,
        p.name as pet_name,
        p.species,
        NVL(
            (
                SELECT url 
                FROM (
                    SELECT url
                    FROM media 
                    WHERE pet_id = p.id 
                    AND type = 'photo'
                    ORDER BY upload_date ASC
                ) 
                WHERE ROWNUM = 1
            ),
            NULL
        ) as pet_image
    FROM adoption_form af
    JOIN pets p ON af.pet_id = p.id
    WHERE af.status = 'submitted'
    AND p.owner_id = :user_id
    ORDER BY af.form_submitted_date DESC";

    $stmt = oci_parse($conn, $query);
    oci_bind_by_name($stmt, ":user_id", $_SESSION['user_id']);
    oci_execute($stmt);

    // Count how many requests we have
    $has_requests = false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adoption Requests - Pow</title>
    <link rel="stylesheet" href="../stiluri/adoption-requests.css">
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">
    <style>
    /* ... existing code ... */
    </style>
</head>
<body>
    

    <div class="overlay"></div>

    <header class="navbar">
        <div class="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <div class="logo">Pow</div>
        <a href="profile.php" class="profile-icon">
            <img src="../stiluri/imagini/profileicon.png" alt="Profile">
        </a>
    </header>

    <div class="title-banner">
        <h1>ADOPTION REQUESTS</h1>
    </div>

    <div class="requests-container">
        <?php 
        $has_requests = false;
        while ($row = oci_fetch_assoc($stmt)) { 
            $has_requests = true;
        ?>
            <div class="request-card">
                <div class="pet-info">
                    <?php
                    $image_path = '';
                    // Debug information
                    error_log("Pet Image from DB: " . print_r($row['PET_IMAGE'], true));
                    error_log("Species: " . $row['SPECIES']);
                    
                    if ($row['PET_IMAGE'] !== null) {
                        $image_path = $row['PET_IMAGE'];
                    } else {
                        // Folosim imaginea speciei ca fallback
                        $species = strtolower($row['SPECIES']);
                        $image_path = 'stiluri/imagini/' . $species . '.png';
                    }
                    error_log("Final image path: " . $image_path);
                    ?>
                    <img src="../<?php echo htmlspecialchars($image_path); ?>" 
                         alt="<?php echo htmlspecialchars($row['PET_NAME']); ?>" 
                         class="pet-image">
                    <!-- Debug display -->
                    <div style="display: none;">
                        Debug: Image path = <?php echo htmlspecialchars($image_path); ?><br>
                        Raw image from DB = <?php echo htmlspecialchars(print_r($row['PET_IMAGE'], true)); ?>
                    </div>
                    <div class="pet-details">
                        <h2><?php echo htmlspecialchars($row['PET_NAME']); ?></h2>
                        <p>Adoption request<br>coming from <?php echo htmlspecialchars($row['FIRST_NAME'] . ' ' . $row['LAST_NAME']); ?></p>
                    </div>
                </div>
                <div class="action-buttons">
                    <button class="see-form-btn" onclick="window.location.href='view-form.php?id=<?php echo $row['FORM_ID']; ?>'">
                        See form
                    </button>
                    <button class="accept-btn" onclick="updateStatus(<?php echo $row['FORM_ID']; ?>, 'approved')">
                        Accept
                    </button>
                    <button class="decline-btn" onclick="updateStatus(<?php echo $row['FORM_ID']; ?>, 'rejected')">
                        Decline
                    </button>
                </div>
            </div>
        <?php } 
        
        if (!$has_requests) {
            echo '<div class="no-requests">
                    <p>You don\'t have any adoption requests at the moment.</p>
                  </div>';
        }
        ?>
    </div>

    <script>
    function updateStatus(formId, status) {
        if (confirm('Are you sure you want to ' + status + ' this adoption request?')) {
            fetch('update-adoption-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'form_id=' + formId + '&status=' + status
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to show updated status
                    location.reload();
                } else {
                    alert('Error updating status: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating status');
            });
        }
    }

    // Add sidebar functionality
    document.addEventListener('DOMContentLoaded', function() {
        const hamburger = document.querySelector('.hamburger');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.overlay');
        const closeButton = document.querySelector('.close-sidebar');

        function openSidebar() {
            sidebar.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        hamburger.addEventListener('click', openSidebar);
        overlay.addEventListener('click', closeSidebar);
        closeButton.addEventListener('click', closeSidebar);
    });
    </script>
</body>
</html>

<?php
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} finally {
    if (isset($stmt)) {
        oci_free_statement($stmt);
    }
    if (isset($conn)) {
        oci_close($conn);
    }
}
?> 