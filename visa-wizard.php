<?php include './partials/layouts/layoutTop.php'; ?>

<style>
    body {
        background-color: #f3f4f9 !important;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .full-width-page {
        width: 100% !important;
        padding: 40px 20px !important;
        box-sizing: border-box !important;
    }

    .wizard-form-container {
        max-width: 800px !important;
        margin: 0 auto !important;
        background-color: #ffffff !important;
        padding: 40px 50px !important;
        border-radius: 12px !important;
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.06) !important;
    }

    .wizard-form-container h2 {
        font-size: 32px !important;
        font-weight: 700 !important;
        color: #222 !important;
        margin-bottom: 35px !important;
        text-align: center !important;
    }

    .form-group {
        margin-bottom: 25px !important;
    }

    .form-group label {
        display: block !important;
        margin-bottom: 8px !important;
        font-weight: 600 !important;
        color: #333 !important;
        font-size: 15px !important;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100% !important;
        padding: 14px 18px !important;
        border: 1px solid #ccc !important;
        border-radius: 8px !important;
        background-color: #f9fafc !important;
        font-size: 16px !important;
        transition: border-color 0.2s ease !important;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: #ffcc00 !important;
        outline: none !important;
        background-color: #fffbe6 !important;
    }

    .form-group textarea {
        resize: vertical !important;
        min-height: 100px !important;
    }

    .form-submit-btn {
        background-color: #ffcc00 !important;
        color: #000 !important;
        font-weight: 700 !important;
        padding: 14px 20px !important;
        border: none !important;
        border-radius: 8px !important;
        font-size: 16px !important;
        width: 100% !important;
        cursor: pointer !important;
        transition: background 0.3s ease !important;
    }

    .form-submit-btn:hover {
        background-color: #e6b800 !important;
    }
</style>

<div class="full-width-page">
    <div class="wizard-form-container">
        <h2>Visa Application Wizard</h2>
        <form action="submit-visa.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" placeholder="Your full legal name" required>
            </div>

            <div class="form-group">
                <label for="dob">Date of Birth</label>
                <input type="date" id="dob" name="dob" required>
            </div>

            <div class="form-group">
                <label for="passport_number">Passport Number</label>
                <input type="text" id="passport_number" name="passport_number" required>
            </div>

            <div class="form-group">
                <label for="nationality">Nationality</label>
                <input type="text" id="nationality" name="nationality" required>
            </div>

            <div class="form-group">
                <label for="visa_type">Visa Type</label>
                <select id="visa_type" name="visa_type" required>
                    <option value="">-- Select Type --</option>
                    <option value="tourist">Tourist</option>
                    <option value="business">Business</option>
                    <option value="student">Student</option>
                    <option value="work">Work</option>
                </select>
            </div>

            <div class="form-group">
                <label for="travel_purpose">Purpose of Travel</label>
                <textarea id="travel_purpose" name="travel_purpose" required placeholder="Explain purpose of your trip..."></textarea>
            </div>

            <div class="form-group">
                <label for="arrival_date">Expected Arrival Date</label>
                <input type="date" id="arrival_date" name="arrival_date" required>
            </div>

            <div class="form-group">
                <label for="departure_date">Expected Return Date</label>
                <input type="date" id="departure_date" name="departure_date" required>
            </div>

            <div class="form-group">
                <label for="passport_copy">Upload Passport Copy (PDF or JPG)</label>
                <input type="file" id="passport_copy" name="passport_copy" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>

            <button type="submit" class="form-submit-btn">Submit Visa Application</button>
        </form>
    </div>
</div>

<?php include './partials/layouts/layoutBottom.php' ?>
