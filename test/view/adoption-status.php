<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

try {
    $conn = getConnection();
    
    // Create the ref cursor
    $cursor = oci_new_cursor($conn);
    
    // Prepare the call to the function
    $stmt = oci_parse($conn, "BEGIN :result := get_user_adoption_status(:user_id); END;");
    
    // Bind the parameters
    oci_bind_by_name($stmt, ":result", $cursor, -1, SQLT_RSET);
    oci_bind_by_name($stmt, ":user_id", $_SESSION['user_id']);
    
    // Execute the statement
    oci_execute($stmt);
    oci_execute($cursor);

    $has_requests = false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Adoption Status - Pow</title>
    <link rel="stylesheet" href="../stiluri/adoption-requests.css">
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">
    <style>
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
        }
        
        .status-submitted {
            background-color: #ffc107;
            color: black;
        }
        
        .status-approved {
            background-color: #4CAF50;
            color: white;
        }
        
        .status-rejected {
            background-color: #f44336;
            color: white;
        }

        .request-date {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
            margin-top: 5px;
        }
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
        <div class="nav-right">
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
            <a href="post-pet.php" class="add-pet-btn">
                <span class="plus-icon">+</span>
                <span>Add</span>
            </a>
            <a href="adoption-requests.php" class="requests-btn">
                <span>Requests</span>
            </a>
            <?php else: ?>
           
            <?php endif; ?>
            <a href="profile.php" class="profile-icon">
                <img src="../stiluri/imagini/profileicon.png" alt="Profile">
            </a>
        </div>
    </header>

    <div class="title-banner">
        <h1>MY ADOPTION REQUESTS</h1>
    </div>

    <div class="requests-container">
        <?php 
        $has_requests = false;
        while ($row = oci_fetch_assoc($cursor)) { 
            $has_requests = true;
            
            // Determine status class
            $statusClass = 'status-' . strtolower($row['STATUS']);
            
            // Format date
            $submittedDate = new DateTime($row['FORM_SUBMITTED_DATE']);
            $formattedDate = $submittedDate->format('F j, Y');
        ?>
            <div class="request-card">
                <div class="pet-info">
                    <?php
                    $image_path = '';
                    if ($row['PET_IMAGE'] !== null) {
                        $image_path = $row['PET_IMAGE'];
                    } else {
                        // Folosim imaginea speciei ca fallback
                        $species = strtolower($row['SPECIES']);
                        $image_path = 'stiluri/imagini/' . $species . '.png';
                    }
                    ?>
                    <img src="../<?php echo htmlspecialchars($image_path); ?>" 
                         alt="<?php echo htmlspecialchars($row['PET_NAME']); ?>" 
                         class="pet-image">
                    <div class="pet-details">
                        <h2><?php echo htmlspecialchars($row['PET_NAME']); ?></h2>
                        <p>
                            Status: <span class="status-badge <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($row['STATUS']); ?>
                            </span>
                        </p>
                        <p class="request-date">Submitted on <?php echo $formattedDate; ?></p>
                    </div>
                </div>
                <div class="action-buttons">
                    <button class="see-form-btn" onclick="window.location.href='view-form.php?id=<?php echo $row['FORM_ID']; ?>'">
                        View Details
                    </button>
                </div>
            </div>
        <?php } 
        
        if (!$has_requests) {
            echo '<div class="no-requests">
                    <p>You haven\'t submitted any adoption requests yet.</p>
                  </div>';
        }
        ?>
    </div>

    <script>
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
        if (closeButton) {
            closeButton.addEventListener('click', closeSidebar);
        }
    });
    </script>
</body>
</html>

<?php
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} finally {
    if (isset($cursor)) {
        oci_free_statement($cursor);
    }
    if (isset($conn)) {
        oci_close($conn);
    }
}
?> 