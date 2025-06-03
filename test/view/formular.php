<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $conn = getConnection();
        if (!$conn) {
            throw new Exception("Database connection failed");
        }

        // Get the pet_id from URL and user_id from session
        $pet_id = isset($_GET['pet_id']) ? (int)$_GET['pet_id'] : 0;
        $user_id = $_SESSION['user_id'];

        // Prepare the SQL statement
        $query = "INSERT INTO adoption_form (
            pet_id, user_id, first_name, last_name, email, phone, 
            street_address, city, country, postal_code,
            pet_name_desired, has_yard, housing_status, has_pet_permission,
            has_children, children_ages,
            has_other_pets, other_pets_description,
            hours_alone_per_day, reason_for_alone_time, adoption_reason,
            form_submitted_date
        ) VALUES (
            :pet_id, :user_id, :first_name, :last_name, :email, :phone,
            :street_address, :city, :country, :postal_code,
            :pet_name_desired, :has_yard, :housing_status, :has_pet_permission,
            :has_children, :children_ages,
            :has_other_pets, :other_pets_description,
            :hours_alone_per_day, :reason_for_alone_time, :adoption_reason,
            SYSDATE
        )";

        $stmt = oci_parse($conn, $query);
        
        // Convert radio button values to numbers for boolean fields
        $has_yard = $_POST['yard'] === 'yes' ? 1 : 0;
        $has_pet_permission = $_POST['landlord_permission'] === 'yes' ? 1 : 0;
        $has_children = $_POST['children'] === 'yes' ? 1 : 0;
        $has_other_pets = $_POST['current_pets'] === 'yes' ? 1 : 0;
        
        // Extract numeric value for hours_alone_per_day using regex
        $hours_alone = 0;
        if (preg_match('/(\d+)/', $_POST['pet_alone_time'], $matches)) {
            $hours_alone = intval($matches[1]);
        }
        
        // Store the full text as the reason
        $reason_for_alone = $_POST['pet_alone_time'];
        
        // Bind all parameters
        oci_bind_by_name($stmt, ":pet_id", $pet_id);
        oci_bind_by_name($stmt, ":user_id", $user_id);
        oci_bind_by_name($stmt, ":first_name", $_POST['first_name']);
        oci_bind_by_name($stmt, ":last_name", $_POST['last_name']);
        oci_bind_by_name($stmt, ":email", $_POST['email']);
        oci_bind_by_name($stmt, ":phone", $_POST['phone']);
        oci_bind_by_name($stmt, ":street_address", $_POST['street_address']);
        oci_bind_by_name($stmt, ":city", $_POST['city']);
        oci_bind_by_name($stmt, ":country", $_POST['country']);
        oci_bind_by_name($stmt, ":postal_code", $_POST['postal_code']);
        oci_bind_by_name($stmt, ":pet_name_desired", $_POST['pet_name']);
        oci_bind_by_name($stmt, ":has_yard", $has_yard);
        oci_bind_by_name($stmt, ":housing_status", $_POST['housing']);
        oci_bind_by_name($stmt, ":has_pet_permission", $has_pet_permission);
        oci_bind_by_name($stmt, ":has_children", $has_children);
        oci_bind_by_name($stmt, ":children_ages", $_POST['children_details']);
        oci_bind_by_name($stmt, ":has_other_pets", $has_other_pets);
        oci_bind_by_name($stmt, ":other_pets_description", $_POST['pet_details']);
        oci_bind_by_name($stmt, ":hours_alone_per_day", $hours_alone);
        oci_bind_by_name($stmt, ":reason_for_alone_time", $reason_for_alone);
        oci_bind_by_name($stmt, ":adoption_reason", $_POST['adoption_reason']);

        $execute = oci_execute($stmt);
        if (!$execute) {
            $e = oci_error($stmt);
            throw new Exception("Error saving form: " . $e['message']);
        }

        // Commit the transaction
        oci_commit($conn);
        
        // Redirect to a success page or show success message
        header("Location: adoption-success.php");
        exit;

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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
     <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pet Adoption Form - Pow</title>
    <link rel="stylesheet" href="../stiluri/formular.css" />
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">
</head>
<body>
     <header class="navbar">
        <div class="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <div class="logo">Pow</div>
        <a href="homepage.php" class="profile-icon">
            <img src="../stiluri/imagini/profileicon.png" alt="Profile" />
        </a>
    </header>
    <section class="formular-banner">
        <h1>PET ADOPTION FORM</h1>
    </section>
    <section class="section-title">
        <h1>New Owner Information</h1>
    </section>
    <form class="adoption-form" method="POST" action="<?php echo $_SERVER['PHP_SELF'] . '?pet_id=' . (isset($_GET['pet_id']) ? $_GET['pet_id'] : ''); ?>">
        <div class="form-group two-col">
             <label>First Name
                 <input type="text" name="first_name" />
             </label>
             <label>Last Name
                 <input type="text" name="last_name" />
             </label>
        </div>
        <div class="form-group two-col">
             <label>Email
                 <input type="email" name="email" placeholder="example@example.com" />
             </label>
             <label>Phone Number
                 <input type="tel" name="phone" placeholder="(0000) 000 000" />
             </label>
        </div>
        <div class="address-group">
            <label>Address
                <input type="text" name="street_address" placeholder="Street Address" />
            </label>
            <div class="city-country-row">
                <label>City
                    <input type="text" name="city" />
                </label>
                <label>Country
                    <input type="text" name="country" />
                </label>
            </div>
            <label>Postal/Zip Code
                <input type="text" name="postal_code" />
            </label>
        </div>

        <div class="form-group">
            <label>Name of Pet You Wish to Adopt
                <input type="text" name="pet_name" />
            </label>
        </div>

        <div class="form-group">
            <label class="question-label">Do you have a yard?</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="yard" value="yes" />
                    <span class="radio-text">Yes</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="yard" value="no" />
                    <span class="radio-text">No</span>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label class="question-label">Do you own or rent your place?</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="housing" value="own" />
                    <span class="radio-text">Own</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="housing" value="rent" />
                    <span class="radio-text">Rent</span>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label class="question-label">If renting, do you have the landlord's permission to have a pet?</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="landlord_permission" value="yes" />
                    <span class="radio-text">Yes</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="landlord_permission" value="no" />
                    <span class="radio-text">No</span>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label class="question-label">Are there any children in the home?</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="children" value="yes" />
                    <span class="radio-text">Yes</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="children" value="no" />
                    <span class="radio-text">No</span>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label>If yes, how many and how old?
                <input type="text" name="children_details" />
            </label>
        </div>

        <div class="form-group">
            <label class="question-label">Do you own any pets?</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="current_pets" value="yes" />
                    <span class="radio-text">Yes</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="current_pets" value="no" />
                    <span class="radio-text">No</span>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label>If yes, what pets?
                <input type="text" name="pet_details" />
            </label>
        </div>

        <div class="form-group">
            <label>How many hours per day would the pet be alone and why?
                <textarea name="pet_alone_time" placeholder="Type here... (Please include the number of hours)"></textarea>
            </label>
        </div>

        <div class="form-group">
            <label>Why do you want to adopt this pet?
                <textarea name="adoption_reason" placeholder="Type here..."></textarea>
            </label>
        </div>

        <section class="terms-section">
            <ul class="terms-list">
                <li>By clicking the submit button, I agree to go adoption process will undergo thoroughly and check all above-mentioned information.</li>
                <li>By clicking the submit button, I understand my references will be checked including veterinary and personal.</li>
                <li>By clicking the submit button, I understand there is an adoption donation and this donation will support all of your local no kill rescue centers.</li>
                <li>By clicking the submit button, I understand and agree that all pets from the Rescue Center are spayed or neutered prior to adoption.</li>
                <li>By clicking the submit button, I understand there is no "cooling off" period, and that if the pet should not and will not leave Rescue Center until all above Rescue Center to make arrangements for my pet to be taken back into care.</li>
                <li>By clicking the submit button, I agree to return the pet to the rescue center against any losses, forwards, clients, injury, damages incurred that may affect any persons as property by our adopted pet after custody transfer.</li>
                <li>By clicking the submit button, I understand that Rescue Center will follow up on the pet's health or behaviour problems known by the above rescue center are disclosed at the time of adoption.</li>
                <li>By clicking the submit button, I understand that if I no longer want my pet, or am no longer able to provide a good home that I will return my pet to the Rescue Center where I initially adopted my pet from. Rescue Center deems appropriate.</li>
                <li>By clicking the submit button, I certify all of the above information is true and complete.</li>
            </ul>
        </section>

        <button type="submit" class="submit-btn">Submit</button>
    </form>

</body>
</html>