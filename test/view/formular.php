<?php
require_once '../utils/auth_middleware.php';
$user = checkAuth();

session_start();
require_once '../controllers/AdoptionFormController.php';

$controller = new AdoptionFormController();
$controller->index();

$errors = [];
$success = false;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = $controller->validateForm($_POST);
    
    if (empty($errors)) {
        $pet_id = isset($_GET['pet_id']) ? (int)$_GET['pet_id'] : 0;
        $result = $controller->processForm($_POST, $pet_id, $_SESSION['user_id']);
        
        if (isset($result['error'])) {
            $errors[] = $result['error'];
        } elseif (isset($result['success']) && $result['success']) {
            $success = true;
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

    <?php if (!empty($errors)): ?>
        <div class="error-messages">
            <?php foreach ($errors as $error): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <section class="section-title">
        <h1>New Owner Information</h1>
    </section>

    <form class="adoption-form" method="POST" action="<?php echo $_SERVER['PHP_SELF'] . '?pet_id=' . (isset($_GET['pet_id']) ? $_GET['pet_id'] : ''); ?>">
        <div class="form-group two-col">
            <label>First Name
                <input type="text" name="first_name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" />
            </label>
            <label>Last Name
                <input type="text" name="last_name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" />
            </label>
        </div>

        <div class="form-group two-col">
            <label>Email
                <input type="email" name="email" placeholder="example@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" />
            </label>
            <label>Phone Number
                <input type="tel" name="phone" placeholder="(0000) 000 000" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" />
            </label>
        </div>

        <div class="address-group">
            <label>Address
                <input type="text" name="street_address" placeholder="Street Address" value="<?php echo isset($_POST['street_address']) ? htmlspecialchars($_POST['street_address']) : ''; ?>" />
            </label>
            <div class="city-country-row">
                <label>City
                    <input type="text" name="city" value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>" />
                </label>
                <label>Country
                    <input type="text" name="country" value="<?php echo isset($_POST['country']) ? htmlspecialchars($_POST['country']) : ''; ?>" />
                </label>
            </div>
            <label>Postal/Zip Code
                <input type="text" name="postal_code" value="<?php echo isset($_POST['postal_code']) ? htmlspecialchars($_POST['postal_code']) : ''; ?>" />
            </label>
        </div>

        <div class="form-group">
            <label>Name of Pet You Wish to Adopt
                <input type="text" name="pet_name" value="<?php echo isset($_POST['pet_name']) ? htmlspecialchars($_POST['pet_name']) : ''; ?>" />
            </label>
        </div>

        <div class="form-group">
            <label class="question-label">Do you have a yard?</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="yard" value="yes" <?php echo (isset($_POST['yard']) && $_POST['yard'] === 'yes') ? 'checked' : ''; ?> />
                    <span class="radio-text">Yes</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="yard" value="no" <?php echo (isset($_POST['yard']) && $_POST['yard'] === 'no') ? 'checked' : ''; ?> />
                    <span class="radio-text">No</span>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label class="question-label">Do you own or rent your place?</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="housing" value="own" <?php echo (isset($_POST['housing']) && $_POST['housing'] === 'own') ? 'checked' : ''; ?> />
                    <span class="radio-text">Own</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="housing" value="rent" <?php echo (isset($_POST['housing']) && $_POST['housing'] === 'rent') ? 'checked' : ''; ?> />
                    <span class="radio-text">Rent</span>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label class="question-label">If renting, do you have the landlord's permission to have a pet?</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="landlord_permission" value="yes" <?php echo (isset($_POST['landlord_permission']) && $_POST['landlord_permission'] === 'yes') ? 'checked' : ''; ?> />
                    <span class="radio-text">Yes</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="landlord_permission" value="no" <?php echo (isset($_POST['landlord_permission']) && $_POST['landlord_permission'] === 'no') ? 'checked' : ''; ?> />
                    <span class="radio-text">No</span>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label class="question-label">Are there any children in the home?</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="children" value="yes" <?php echo (isset($_POST['children']) && $_POST['children'] === 'yes') ? 'checked' : ''; ?> />
                    <span class="radio-text">Yes</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="children" value="no" <?php echo (isset($_POST['children']) && $_POST['children'] === 'no') ? 'checked' : ''; ?> />
                    <span class="radio-text">No</span>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label>If yes, how many and how old?
                <input type="text" name="children_details" value="<?php echo isset($_POST['children_details']) ? htmlspecialchars($_POST['children_details']) : ''; ?>" />
            </label>
        </div>

        <div class="form-group">
            <label class="question-label">Do you own any pets?</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="current_pets" value="yes" <?php echo (isset($_POST['current_pets']) && $_POST['current_pets'] === 'yes') ? 'checked' : ''; ?> />
                    <span class="radio-text">Yes</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="current_pets" value="no" <?php echo (isset($_POST['current_pets']) && $_POST['current_pets'] === 'no') ? 'checked' : ''; ?> />
                    <span class="radio-text">No</span>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label>If yes, what pets?
                <input type="text" name="pet_details" value="<?php echo isset($_POST['pet_details']) ? htmlspecialchars($_POST['pet_details']) : ''; ?>" />
            </label>
        </div>

        <div class="form-group">
            <label>How many hours per day would the pet be alone and why?
                <textarea name="pet_alone_time" placeholder="Type here... (Please include the number of hours)"><?php echo isset($_POST['pet_alone_time']) ? htmlspecialchars($_POST['pet_alone_time']) : ''; ?></textarea>
            </label>
        </div>

        <div class="form-group">
            <label>Why do you want to adopt this pet?
                <textarea name="adoption_reason" placeholder="Type here..."><?php echo isset($_POST['adoption_reason']) ? htmlspecialchars($_POST['adoption_reason']) : ''; ?></textarea>
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
        <?php if ($success): ?>
            <div class="success-message" style="margin-top: 20px; color: green; font-weight: bold; text-align: center;">Submitted with success!</div>
        <?php endif; ?>
    </form>
</body>
</html> 