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
        <h2>Marketing Wizard</h2>
        <form action="save-campaign.php" method="post">
            <div class="form-group">
                <label for="campaign_name">Campaign Name</label>
                <input type="text" id="campaign_name" name="campaign_name" placeholder="e.g., Summer Sale Campaign" required>
            </div>

            <div class="form-group">
                <label for="audience">Target Audience</label>
                <input type="text" id="audience" name="audience" placeholder="e.g., B2B, Millennials, Developers" required>
            </div>

            <div class="form-group">
                <label for="platform">Platform</label>
                <select id="platform" name="platform" required>
                    <option value="">-- Select Platform --</option>
                    <option value="facebook">Facebook</option>
                    <option value="instagram">Instagram</option>
                    <option value="google">Google Ads</option>
                    <option value="linkedin">LinkedIn</option>
                </select>
            </div>

            <div class="form-group">
                <label for="budget">Budget ($)</label>
                <input type="number" id="budget" name="budget" step="0.01" placeholder="e.g., 5000" required>
            </div>

            <div class="form-group">
                <label for="duration">Duration (Days)</label>
                <input type="number" id="duration" name="duration" placeholder="e.g., 30" required>
            </div>

            <div class="form-group">
                <label for="goals">Campaign Goals</label>
                <textarea id="goals" name="goals" placeholder="e.g., Increase website traffic, generate leads" required></textarea>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="draft">Draft</option>
                    <option value="scheduled">Scheduled</option>
                    <option value="active">Active</option>
                </select>
            </div>

            <button class="form-submit-btn" type="submit">Launch Campaign</button>
        </form>
    </div>
</div>

<?php include './partials/layouts/layoutBottom.php' ?>
