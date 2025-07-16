<?php include './partials/layouts/layoutTop.php' ?>

<style>
    .wizard-content-left {
    background-blend-mode: darken;
    background-color: rgba(0, 0, 0, 0.45);
    background-image: url("https://i.ibb.co/X292hJF/form-wizard-bg-2.jpg");
    background-position: center center;
    background-size: cover;
    height: 100vh;
    padding: 30px;
    }
    .wizard-content-left h1 {
    color: #ffffff;
    font-size: 38px;
    font-weight: 600;
    padding: 12px 20px;
    text-align: center;
    }

    .form-wizard {
    color: #888888;
    padding: 30px;
    }
    .form-wizard .wizard-form-radio {
    display: inline-block;
    margin-left: 5px;
    position: relative;
    }
    .form-wizard .wizard-form-radio input[type="radio"] {
    -webkit-appearance: none;
    -moz-appearance: none;
    -ms-appearance: none;
    -o-appearance: none;
    appearance: none;
    background-color: #dddddd;
    height: 25px;
    width: 25px;
    display: inline-block;
    vertical-align: middle;
    border-radius: 50%;
    position: relative;
    cursor: pointer;
    }
    .form-wizard .wizard-form-radio input[type="radio"]:focus {
    outline: 0;
    }
    .form-wizard .wizard-form-radio input[type="radio"]:checked {
    background-color: #fec700;
    }
    .form-wizard .wizard-form-radio input[type="radio"]:checked::before {
    content: "";
    position: absolute;
    width: 10px;
    height: 10px;
    display: inline-block;
    background-color: #ffffff;
    border-radius: 50%;
    left: 1px;
    right: 0;
    margin: 0 auto;
    top: 8px;
    }
    .form-wizard .wizard-form-radio input[type="radio"]:checked::after {
    content: "";
    display: inline-block;
    webkit-animation: click-radio-wave 0.65s;
    -moz-animation: click-radio-wave 0.65s;
    animation: click-radio-wave 0.65s;
    background: #000000;
    content: '';
    display: block;
    position: relative;
    z-index: 100;
    border-radius: 50%;
    }
    .form-wizard .wizard-form-radio input[type="radio"] ~ label {
    padding-left: 10px;
    cursor: pointer;
    }
    .form-wizard .form-wizard-header {
    text-align: center;
    }
    .form-wizard .form-wizard-next-btn, .form-wizard .form-wizard-previous-btn, .form-wizard .form-wizard-submit {
    background-color: #fec700;
    color: #ffffff;
    display: inline-block;
    min-width: 100px;
    min-width: 120px;
    padding: 10px;
    text-align: center;
    }
    .form-wizard .form-wizard-next-btn:hover, .form-wizard .form-wizard-next-btn:focus, .form-wizard .form-wizard-previous-btn:hover, .form-wizard .form-wizard-previous-btn:focus, .form-wizard .form-wizard-submit:hover, .form-wizard .form-wizard-submit:focus {
    color: #ffffff;
    opacity: 0.6;
    text-decoration: none;
    }
    .form-wizard .wizard-fieldset {
    display: none;
    }
    .form-wizard .wizard-fieldset.show {
    display: block;
    }
    .form-wizard .wizard-form-error {
    display: none;
    /* background-color: #fec700; */
    background-color: transparent;
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 2px;
    width: 100%;
    /* border : 1px solid #fec700; */
    border:none;
    inset-block-start:auto !important;
    }
    .form-wizard .form-wizard-previous-btn {
    background-color: #fec700;
    }
    .form-wizard .form-group {
    position: relative;
    margin: 25px 0;
    }
    .form-wizard .wizard-form-text-label {
    position: absolute;
    left: 10px;
    top: 16px;
    transition: 0.2s linear all;
    }
    .form-wizard .focus-input .wizard-form-text-label {
    color: #fec700;
    top: -18px;
    transition: 0.2s linear all;
    font-size: 12px;
    }
    .form-wizard .form-wizard-steps {
    margin: 30px 0;
    }
    .form-wizard .form-wizard-steps li {
    width: 12%;
    float: left;
    position: relative;
    }
    .form-wizard .form-wizard-steps li::after {
    background-color: #f3f3f3;
    content: "";
    height: 5px;
    left: 0;
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 100%;
    border-bottom: 1px solid #dddddd;
    border-top: 1px solid #dddddd;
    }
    .form-wizard .form-wizard-steps li span {
    background-color: #dddddd;
    border-radius: 50%;
    display: inline-block;
    height: 40px;
    line-height: 40px;
    position: relative;
    text-align: center;
    width: 40px;
    z-index: 1;
    }
    .form-wizard .form-wizard-steps li:last-child::after {
    width: 50%;
    }
    .form-wizard .form-wizard-steps li.active span, .form-wizard .form-wizard-steps li.activated span {
    background-color: #fec700;
    color: #ffffff;
    }
    .form-wizard .form-wizard-steps li.active::after, .form-wizard .form-wizard-steps li.activated::after {
    background-color: #fec700;
    left: 50%;
    width: 50%;
    border-color: #fec700;
    }
    .form-wizard .form-wizard-steps li.activated::after {
    width: 100%;
    border-color: #fec700;
    }
    .form-wizard .form-wizard-steps li:last-child::after {
    left: 0;
    }
    .form-wizard .wizard-password-eye {
    position: absolute;
    right: 32px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    }
    @keyframes click-radio-wave {
    0% {
        width: 25px;
        height: 25px;
        opacity: 0.35;
        position: relative;
    }
    100% {
        width: 60px;
        height: 60px;
        margin-left: -15px;
        margin-top: -15px;
        opacity: 0.0;
    }
    }
    @media screen and (max-width: 767px) {
    .wizard-content-left {
        height: auto;
    }
    }

    .progress {
    height: 30px;
    background-color: #f3f3f3;
    border-radius: 8px;
    overflow: hidden;
}
.progress-bar {
    background-color: #fec700 !important; /* Match your form's primary color */
    color: #ffffff;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: width 0.6s ease;
}
</style>

<?php
    $id = $_SESSION['user_id'];

    // Fetch user data
    $stmt = $conn->prepare("SELECT user_id, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $user_id = $row['user_id'];
    $role = (int)$row['role'];
    $is_admin = ($role === 1 || $role === 7);
    $is_user = ($role === 8);

    $websiteId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    $prefill = [];
    $field_statuses = [];
    $filled_fields = 0;
    $total_fields = 0;

    // Fetch JSON data
    $stmt = $conn->prepare("SELECT name FROM json WHERE website_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $websiteId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $json_data = $result->fetch_assoc();
        $raw_json = json_decode($json_data['name'], true);

        foreach ($raw_json as $key => $info) {
            $total_fields++;
            if (is_array($info) && isset($info['value'])) {
                $prefill[$key] = $info['value'];
                $field_statuses[$key] = $info['status'] ?? 'pending';
                if (!empty($info['value'])) {
                    $filled_fields++;
                }
            } else {
                $prefill[$key] = $info;
                $field_statuses[$key] = 'pending';
                if (!empty($info)) {
                    $filled_fields++;
                }
            }
        }
    }

    // Calculate progress percentage
    $progress_percentage = $total_fields > 0 ? round(($filled_fields / $total_fields) * 100) : 0;



    if (isset($_POST['save'])) {
        $comp_name = $_POST['comp_name'];
        $cont_person = $_POST['cont_person'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $website = $_POST['website'];
        $address = $_POST['address'];
        $maintenance1 = $_POST['maintenance1'];
        $support1 = $_POST['support1'];

        $purpose = $_POST['purpose'];
        $business = $_POST['business'];
        $goals = $_POST['goals'];
        $vision = $_POST['vision'];

        $target = $_POST['target'];
        $expectations = $_POST['expectations'];
        $personas = $_POST['personas'];

        $design = $_POST['design'];
        $like = $_POST['like'];
        $brand = $_POST['brand'];
        $features = $_POST['features'];
        $functionality = $_POST['functionality'];
        $responsive = $_POST['responsive'];
        $technical = $_POST['technical'];

        $timeline = $_POST['timeline'];
        $budget = $_POST['budget'];
        $deadline = $_POST['deadline'];

        $maintenance = $_POST['maintenance'];
        $support = $_POST['support'];

        // Create JSON object
        // $data = json_encode([
        // 'comp_name' => $comp_name,
        // 'cont_person' => $cont_person,
        // 'email' => $email,
        // 'phone' => $phone,
        // 'website' => $website,
        // 'address' => $address,

        // 'purpose' => $purpose,
        // 'business' => $business,
        // 'goals' => $goals,
        // 'vision' => $vision,

        // 'target' => $target,
        // 'expectations' => $expectations,
        // 'personas' => $personas,

        // 'design' => $design,
        // 'like' => $like,
        // 'brand' => $brand,
        // 'features' => $features,
        // 'functionality' => $functionality,
        // 'responsive' => $responsive,
        // 'technical' => $technical,

        // 'timeline' => $timeline,
        // 'budget' => $budget,
        // 'deadline' => $deadline,

        // 'maintenance' => $maintenance,
        // 'support' => $support
        // ]);

        // Insert JSON into database
        // $sql = "INSERT INTO json (name) VALUES (?)";

        // $stmt = $conn->prepare($sql);
        // $stmt->bind_param("s", $data);

        // $fields = [
        //     'comp_name', 'cont_person', 'email', 'phone', 'website', 'address',
        //     'purpose', 'business', 'goals', 'vision',
        //     'target', 'expectations', 'personas',
        //     'design', 'like', 'brand', 'features', 'functionality', 'responsive', 'technical',
        //     'timeline', 'budget', 'deadline',
        //     'maintenance', 'support'
        // ];

        // $data = [];

        // foreach ($fields as $field) {
        //     $value = $_POST[$field] ?? '';
        //     $existing_status = $field_statuses[$field] ?? 'pending';

        //     if ($existing_status === 'rejected' && isset($prefill[$field]) && $prefill[$field] !== $value) {
        //         $existing_status = 'pending';
        //     }

        //     $data[$field] = [
        //         'value' => $value,
        //         'status' => $existing_status
        //     ];
        // }

        // $json_string = json_encode($data);

        // $sql = "INSERT INTO json (user_id, name) VALUES (?, ?)
        // ON DUPLICATE KEY UPDATE name = VALUES(name)";
        // $stmt = $conn->prepare($sql);
        // $stmt->bind_param("ss", $user_id, $json_string);

        // if ($stmt->execute()) {
        //     echo '
        //     <script>
        //         Swal.fire({
        //             icon: "success",
        //             title: "Success!",
        //             text: "Data saved successfully!"
        //         });
        //     </script>';
        // } else {
        // echo "Error: " . $stmt->error;
        // }

        $fields = [
            'comp_name', 'cont_person', 'email', 'phone', 'website', 'address',
            'purpose', 'business', 'goals', 'vision',
            'target', 'expectations', 'personas',
            'design', 'like', 'brand', 'features', 'functionality', 'responsive', 'technical',
            'timeline', 'budget', 'deadline',
            'maintenance1', 'support1'
        ];

        $data = [];
        foreach ($fields as $field) {
            $value = $_POST[$field] ?? '';
            $existing_status = $field_statuses[$field] ?? 'pending';

            if ($existing_status === 'rejected' && isset($prefill[$field]) && $prefill[$field] !== $value) {
                $existing_status = 'pending';
            }

            $data[$field] = [
                'value' => $value,
                'status' => $existing_status
            ];
        }

        $json_string = json_encode($data);
        $website_id = $_GET['id'];

        $sql = "INSERT INTO json (user_id, name, website_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $user_id, $json_string, $website_id);

        if ($stmt->execute()) {
            echo '<script>Swal.fire({icon: "success", title: "Success!", text: "Data saved successfully!"});</script>';
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    function render_field($name, $label, $prefill, $field_statuses, $is_admin, $is_user) {
        $value = htmlspecialchars($prefill[$name] ?? '');
        $status = $field_statuses[$name] ?? 'pending';
        $readonly = '';
        $badge = '';
        $actions = '';

        if ($is_user) {
            if ($status === 'approved') {
                $readonly = 'readonly';
                $badge = "<span class='badge bg-success ms-2'>✅ Approved</span>";
            } elseif ($status === 'rejected') {
                $badge = "<span class='badge bg-danger ms-2'>❌ Rejected — Please edit</span>";
            }
        }

        if ($is_admin) {
            $actions = <<<HTML
                <button type="button" class="btn btn-success btn-sm approve-btn" data-field="$name">✅</button>
                <button type="button" class="btn btn-danger btn-sm reject-btn" data-field="$name">❌</button>
            HTML;
        }

        echo <<<HTML
        <div class="form-group mb-3">
            <label for="$name">$label</label>
            <input type="text" class="form-control" id="$name" name="$name" value="$value" $readonly>
            $badge
            $actions
        </div>
        HTML;
    }
?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Website Registration Form</h6>
        <!-- <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Blank Page</li>
        </ul> -->
    </div>

    <div class="card h-100 p-0 radius-12 overflow-hidden">
                
                <div class="card-body p-40">
                    <!-- Progress Bar -->
    <div class="progress mb-4">
        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $progress_percentage ?>%;" aria-valuenow="<?= $progress_percentage ?>" aria-valuemin="0" aria-valuemax="100">
            <?= $progress_percentage ?>% Complete
        </div>
    </div>
                    <div class="row justify-content-center">
                        <div class="col-xxl-10">
                        <section class="wizard-section">
                            <div class="row no-gutters">
                                <div class="col-lg-12">
                                    <div class="form-wizard">
                                        <form action="" method="post" role="form">
                                            <div class="form-wizard-header">
                                                <!-- <p>Fill all form field to go next step</p> -->
                                                <ul class="list-unstyled form-wizard-steps clearfix">
                                                    <li class="active"><span>1</span></li>
                                                    <li><span>2</span></li>
                                                    <li><span>3</span></li>
                                                    <li><span>4</span></li>
                                                    <li><span>5</span></li>
                                                    <li><span>6</span></li>
                                                    <li><span>7</span></li>
                                                    <li><span>8</span></li>
                                                </ul>
                                            </div>
                                            <!-- <?php if ($is_user): ?> -->
                    <input type="submit" class="form-wizard-submit" name="save" value="Submit" style="float:right">
                <!-- <?php endif; ?> -->
                                            <!-- <input type="submit" name="save" class="form-wizard-submit" value="Submit" style="float:right"> -->
                                            <fieldset class="wizard-fieldset show">
                                                <h5>Personal Information</h5>
                                                <?php
                    render_field('comp_name', 'Company Name*', $prefill, $field_statuses, $is_admin, $is_user);
                    render_field('cont_person', 'Contact Person*', $prefill, $field_statuses, $is_admin, $is_user);
                    render_field('email', 'Email*', $prefill, $field_statuses, $is_admin, $is_user);
                    render_field('phone', 'Phone*', $prefill, $field_statuses, $is_admin, $is_user);
                    render_field('website', 'Website*', $prefill, $field_statuses, $is_admin, $is_user);
                    render_field('address', 'Address*', $prefill, $field_statuses, $is_admin, $is_user);
                    render_field('maintenance1', 'Maintenance?', $prefill, $field_statuses, $is_admin, $is_user);
                    render_field('support1', 'Support?', $prefill, $field_statuses, $is_admin, $is_user);
                ?>
                                                
                                                <!-- <div class="form-group">
                                                    <label for="fname" class="">Company Name*</label>    
                                                        <input type="text" class="form-control wizard-required" id="fname" name="comp_name" value="<?= htmlspecialchars($prefill['comp_name'] ?? '') ?>" autocomplete="off">
                                                        <div class="wizard-form-error text-danger">Fill out company name</div>
                                                </div>
                                                <div class="form-group">
                                                    <label for="lname" class="">Contact Person*</label>
                                                        <input type="text" class="form-control" id="lname" name="cont_person" value="<?= htmlspecialchars($prefill['cont_person'] ?? '') ?>" autocomplete="off">
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group">
                                                <label for="lname" class="">Email*</label>
                                                    <input type="email" class="form-control " id="lname" name="email" value="<?= htmlspecialchars($prefill['email'] ?? '') ?>" autocomplete="off">
                                                    
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group">
                                                <label for="lname" class="">Phone*</label>
                                                    <input type="text" class="form-control " id="lname" name="phone" value="<?= htmlspecialchars($prefill['phone'] ?? '') ?>" autocomplete="off">
                                                    
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group">
                                                <label for="lname" class="">Website*</label>
                                                    <input type="text" class="form-control " id="lname" name="website" value="<?= htmlspecialchars($prefill['website'] ?? '') ?>" autocomplete="off">
                                                    
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group">
                                                <label for="lname" class="">Address*</label>
                                                    <input type="text" class="form-control " id="lname" name="address" value="<?= htmlspecialchars($prefill['address'] ?? '') ?>" autocomplete="off">
                                                    
                                                    <div class="wizard-form-error"></div>
                                                </div> -->
                                                <!-- <div class="form-group"> 
                                                    Gender
                                                    <div class="wizard-form-radio">
                                                        <input name="radio-name" id="radio1" type="radio">
                                                        <label for="radio1">Male</label>
                                                    </div>
                                                    <div class="wizard-form-radio">
                                                        <input name="radio-name" id="radio2" type="radio">
                                                        <label for="radio2">Female</label>
                                                    </div>
                                                </div> -->
                                                
                                                <div class="form-group clearfix">
                                                    <a href="javascript:;" class="form-wizard-next-btn float-right">Next</a>
                                                </div>
                                            </fieldset> 
                                            <fieldset class="wizard-fieldset">
                                                <h5>Project Description</h5>
                                                <div class="form-group"> 
                                                    <label>What is the primary purpose of this website? </label>
                                                    <div class="wizard-form-radio">
                                                        <input name="purpose" id="radio1" type="radio" value="E-commerce">
                                                        <label for="radio1">E-commerce</label>
                                                    </div>
                                                    <div class="wizard-form-radio">
                                                        <input name="purpose" id="radio2" type="radio" value="Portfolio">
                                                        <label for="radio2">Portfolio</label>
                                                    </div>
                                                    <div class="wizard-form-radio">
                                                        <input name="purpose" id="radio3" type="radio" value="Blog">
                                                        <label for="radio3">Blog</label>
                                                    </div>
                                                    <div class="wizard-form-radio">
                                                        <input name="purpose" id="radio4" type="radio" value="Business website">
                                                        <label for="radio4">Business website</label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                <label for="lname" class="">Describe your business, products, or services</label>
                                                    <input type="text" class="form-control " id="lname" name="business">
                                                    
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group">
                                                    <label for="username" class="">What are your goals for this website?</label>
                                                    <select class="form-control" name="goals">
                                                        <option value="Increase sales">Increase sales</option>
                                                        <option value="Generate leads">Generate leads</option>
                                                        <option value="Improve brand awareness">Improve brand awareness</option>
                                                    </select>
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="">Do you have a specific vision or style in mind for the website?</label>
                                                    <textarea  class="form-control" id="lname" name="vision"></textarea>
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group clearfix">
                                                    <a href="javascript:;" class="form-wizard-previous-btn float-left">Previous</a>
                                                    <a href="javascript:;" class="form-wizard-next-btn float-right">Next</a>
                                                </div>
                                            </fieldset> 
                                            <fieldset class="wizard-fieldset">
                                                <h5>Target Audience</h5>
                                                <div class="form-group">
                                                <label for="bname" class="">Who is your target audience? (e.g., age, demographics, interests)</label>
                                                    <input type="text" class="form-control " id="bname" name="target">
                                                    
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="">What are their needs and expectations from your website? </label>
                                                    <textarea  class="form-control" id="lname" name="expectations"></textarea>
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="">Do you have any user personas or customer journey maps?</label>
                                                    <textarea  class="form-control" id="lname" name="personas"></textarea>
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group clearfix">
                                                    <a href="javascript:;" class="form-wizard-previous-btn float-left">Previous</a>
                                                    <a href="javascript:;" class="form-wizard-next-btn float-right">Next</a>
                                                </div>
                                            </fieldset> 
                                            <fieldset class="wizard-fieldset">
                                                <h5>Content</h5>
                                                <div class="form-group">
                                                <label for="fname" class="">Do you have existing content for the website?</label>    
                                                    <input type="text" class="form-control " id="fname" name="ex_content">
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group">
                                                <label for="fname" class="">Will you be providing all the content, or will you need assistance with copywriting or content creation?</label>    
                                                    <input type="text" class="form-control " id="fname" name="copywriting">
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group">
                                                <label for="fname" class="">Do you have a site map or page structure in mind?</label>    
                                                    <input type="text" class="form-control " id="fname" name="sitemap">
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group">
                                                <label for="fname" class="">Are there any specific content requirements?</label>    
                                                    <input type="text" class="form-control " id="fname" name="sp_content">
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group clearfix">
                                                    <a href="javascript:;" class="form-wizard-previous-btn float-left">Previous</a>
                                                    <a href="javascript:;" class="form-wizard-next-btn float-right">Next</a>
                                                </div>
                                            </fieldset> 
                                            <fieldset class="wizard-fieldset">
                                                <h5>Design and Functionality</h5>
                                                <div class="form-group">
                                                <label for="fname" class="">Do you have any design preferences?(e.g., minimalist, modern, classic)</label>    
                                                    <input type="text" class="form-control " id="fname" name="design">
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group">
                                                <label for="fname" class="">Are there any websites you like or dislike? Please provide examples:</label>    
                                                    <input type="text" class="form-control " id="fname" name="like">
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group">
                                                <label for="fname" class="">Do you have a logo and branding guidelines?</label>    
                                                    <input type="text" class="form-control " id="fname" name="brand">
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group">
                                                <label for="fname" class="">What key features and functionalities do you need? (e.g., e-commerce, contact form, social media integration, CMS)</label>    
                                                    <input type="text" class="form-control " id="fname" name="features">
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group">
                                                <label for="fname" class="">Is there any specific functionality you want? </label>    
                                                    <input type="text" class="form-control " id="fname" name="functionality">
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group">
                                                <label for="fname" class="">Do you require the website to be responsive (mobile-friendly)?</label>    
                                                    <input type="text" class="form-control " id="fname" name="responsive">
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group">
                                                <label for="fname" class="">Do you have any specific technical requirements? (e.g., CMS, hosting, domain)</label>    
                                                    <input type="text" class="form-control " id="fname" name="technical">
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group clearfix">
                                                    <a href="javascript:;" class="form-wizard-previous-btn float-left">Previous</a>
                                                    <a href="javascript:;" class="form-wizard-next-btn float-right">Next</a>
                                                    
                                                </div>
                                            </fieldset> 
                                            <fieldset class="wizard-fieldset">
                                                <h5>Technical Requirements</h5>
                                                <div class="form-group">
                                                    <label>Do you have a domain name?</label>

                                                    <div class="wizard-form-radio">
                                                        <input name="domain" id="domain_yes" type="radio" value="yes">
                                                        <label for="domain_yes">Yes</label>
                                                    </div> 
                                                    <div class="wizard-form-radio">
                                                        <input name="domain" id="domain_no" type="radio" value="no">
                                                        <label for="domain_no">No</label>
                                                    </div>    

                                                    <div id="domain-input-wrapper" class="form-group" style="display: none;">
                                                        <label>If yes, please provide</label>
                                                        <input type="text" class="form-control" id="fname" name="domain_yes">
                                                        <div class="wizard-form-error"></div>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label >Do you have hosting?</label>

                                                    <div class="wizard-form-radio">
                                                        <input name="hosting" id="hosting_yes" type="radio" value="yes">
                                                        <label for="hosting_yes">Yes</label>
                                                    </div> 
                                                    <div class="wizard-form-radio">
                                                        <input name="hosting" id="hosting_no" type="radio" value="no">
                                                        <label for="hosting_no">No</label>
                                                    </div>    

                                                    <div id="domain-input-wrapper2" class="form-group" style="display: none;">
                                                        <label>If yes, please provide</label>
                                                        <input type="text" class="form-control" id="hosting" name="hosting_yes">
                                                        <div class="wizard-form-error"></div>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                <label for="fname" class="">Do you have any preferences for a CMS? (e.g., WordPress, Shopify, Webflow, etc.)</label>    
                                                    <input type="text" class="form-control " id="fname">
                                                    <div class="wizard-form-error"></div>
                                                </div>

                                                <div class="form-group">
                                                    <label >Will the website require e-commerce functionality?</label>

                                                    <div class="wizard-form-radio">
                                                        <input name="e_commerce" type="radio" id="e_commerce_yes" value="yes">
                                                        <label>Yes</label>
                                                    </div> 
                                                    <div class="wizard-form-radio">
                                                        <input name="e_commerce" type="radio" id="e_commerce_no" value="no">
                                                        <label>No</label>
                                                    </div>    

                                                    <div id="domain-input-wrapper3" class="form-group" style="display: none;">
                                                        <label>If yes, which payment gateway would you prefer? (e.g., Stripe, PayPal)</label>
                                                        <input type="text" class="form-control" id="e_commerce" name="e_commerce_yes">
                                                        <div class="wizard-form-error"></div>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group">
                                                <label for="fname" class="">Do you have any specific security requirements? </label>    
                                                    <input type="text" class="form-control " id="fname">
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group">
                                                <label for="fname" class="">Do you need any integrations with third-party tools?</label>    
                                                    <input type="text" class="form-control " id="fname">
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group clearfix">
                                                    <a href="javascript:;" class="form-wizard-previous-btn float-left">Previous</a>
                                                    <a href="javascript:;" class="form-wizard-next-btn float-right">Next</a>
                                                    
                                                </div>
                                            </fieldset> 
                                            <fieldset class="wizard-fieldset">
                                                <h5>Timeline and Budget</h5>
                                                <div class="form-group">
                                                <label class="">What is your timeline for the project? When would you like the website to be launched?</label>    
                                                    <input type="text" class="form-control " name="timeline">
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group">
                                                <label class="">What is your budget for the project?</label>    
                                                    <input type="text" class="form-control " name="budget">
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group">
                                                <label class="">Are there any milestones or deadlines we should be aware of?</label>    
                                                    <input type="text" class="form-control " name="deadline">
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group clearfix">
                                                    <a href="javascript:;" class="form-wizard-previous-btn float-left">Previous</a>
                                                    <a href="javascript:;" class="form-wizard-next-btn float-right">Next</a>
                                                    
                                                </div>
                                            </fieldset> 
                                            <fieldset class="wizard-fieldset">
                                                <h5>Maintenance and Support</h5>
                                                <div class="form-group">
                                                <label class="">Will you require ongoing maintenance and support for the website?</label>    
                                                    <input type="text" class="form-control " name="maintenance">
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group">
                                                <label class="">Are there any specific support requirements or service-level agreements (SLAs)?</label>    
                                                    <input type="text" class="form-control " name="support">
                                                    <div class="wizard-form-error"></div>
                                                </div>
                                                <div class="form-group clearfix">
                                                    <a href="javascript:;" class="form-wizard-previous-btn float-left">Previous</a>
                                                    <!-- <a href="javascript:;" class="form-wizard-submit float-right">Submit</a> -->

                                                </div>
                                            </fieldset> 
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </section>
                        </div>
                    </div>
                </div>
            </div>
</div>

<script>
    jQuery(document).ready(function() {
    // click on next button
    jQuery('.form-wizard-next-btn').click(function() {
        var parentFieldset = jQuery(this).parents('.wizard-fieldset');
        var currentActiveStep = jQuery(this).parents('.form-wizard').find('.form-wizard-steps .active');
        var next = jQuery(this);
        var nextWizardStep = true;
        parentFieldset.find('.wizard-required').each(function(){
            var thisValue = jQuery(this).val();

            if( thisValue == "") {
                jQuery(this).siblings(".wizard-form-error").slideDown();
                nextWizardStep = false;
            }
            else {
                jQuery(this).siblings(".wizard-form-error").slideUp();
            }
        });
        if( nextWizardStep) {
            next.parents('.wizard-fieldset').removeClass("show","400");
            currentActiveStep.removeClass('active').addClass('activated').next().addClass('active',"400");
            next.parents('.wizard-fieldset').next('.wizard-fieldset').addClass("show","400");
            jQuery(document).find('.wizard-fieldset').each(function(){
                if(jQuery(this).hasClass('show')){
                    var formAtrr = jQuery(this).attr('data-tab-content');
                    jQuery(document).find('.form-wizard-steps .form-wizard-step-item').each(function(){
                        if(jQuery(this).attr('data-attr') == formAtrr){
                            jQuery(this).addClass('active');
                            var innerWidth = jQuery(this).innerWidth();
                            var position = jQuery(this).position();
                            jQuery(document).find('.form-wizard-step-move').css({"left": position.left, "width": innerWidth});
                        }else{
                            jQuery(this).removeClass('active');
                        }
                    });
                }
            });
        }
    });
    //click on previous button
    jQuery('.form-wizard-previous-btn').click(function() {
        var counter = parseInt(jQuery(".wizard-counter").text());;
        var prev =jQuery(this);
        var currentActiveStep = jQuery(this).parents('.form-wizard').find('.form-wizard-steps .active');
        prev.parents('.wizard-fieldset').removeClass("show","400");
        prev.parents('.wizard-fieldset').prev('.wizard-fieldset').addClass("show","400");
        currentActiveStep.removeClass('active').prev().removeClass('activated').addClass('active',"400");
        jQuery(document).find('.wizard-fieldset').each(function(){
            if(jQuery(this).hasClass('show')){
                var formAtrr = jQuery(this).attr('data-tab-content');
                jQuery(document).find('.form-wizard-steps .form-wizard-step-item').each(function(){
                    if(jQuery(this).attr('data-attr') == formAtrr){
                        jQuery(this).addClass('active');
                        var innerWidth = jQuery(this).innerWidth();
                        var position = jQuery(this).position();
                        jQuery(document).find('.form-wizard-step-move').css({"left": position.left, "width": innerWidth});
                    }else{
                        jQuery(this).removeClass('active');
                    }
                });
            }
        });
    });
    //click on form submit button
    jQuery(document).on("click",".form-wizard .form-wizard-submit" , function(){
        var parentFieldset = jQuery(this).parents('.wizard-fieldset');
        var currentActiveStep = jQuery(this).parents('.form-wizard').find('.form-wizard-steps .active');
        parentFieldset.find('.wizard-required').each(function() {
            var thisValue = jQuery(this).val();
            if( thisValue == "" ) {
                jQuery(this).siblings(".wizard-form-error").slideDown();
            }
            else {
                jQuery(this).siblings(".wizard-form-error").slideUp();
            }
        });
    });
    // focus on input field check empty or not
    jQuery(".form-control").on('focus', function(){
        var tmpThis = jQuery(this).val();
        if(tmpThis == '' ) {
            jQuery(this).parent().addClass("focus-input");
        }
        else if(tmpThis !='' ){
            jQuery(this).parent().addClass("focus-input");
        }
        }).on('blur', function(){
            var tmpThis = jQuery(this).val();
            if(tmpThis == '' ) {
                jQuery(this).parent().removeClass("focus-input");
                jQuery(this).siblings('.wizard-form-error').slideDown("3000");
            }
            else if(tmpThis !='' ){
                jQuery(this).parent().addClass("focus-input");
                jQuery(this).siblings('.wizard-form-error').slideUp("3000");
            }
        });
    });

    // Get the elements
    const domainYes = document.getElementById('domain_yes');
    const domainNo = document.getElementById('domain_no');
    const hostingYes = document.getElementById('hosting_yes');
    const hostingNo = document.getElementById('hosting_no');
    const ecommerceYes = document.getElementById('e_commerce_yes');
    const ecommerceNo = document.getElementById('e_commerce_no');
    const domainInputWrapper = document.getElementById('domain-input-wrapper');
    const domainInputWrapper2 = document.getElementById('domain-input-wrapper2');
    const domainInputWrapper3 = document.getElementById('domain-input-wrapper3');

    // Add event listeners
    domainYes.addEventListener('change', function() {
        if (this.checked) {
            domainInputWrapper.style.display = 'block';
        }
    });

    domainNo.addEventListener('change', function() {
        if (this.checked) {
            domainInputWrapper.style.display = 'none';
        }
    });

    hostingYes.addEventListener('change', function() {
        if (this.checked) {
            domainInputWrapper2.style.display = 'block';
        }
    });

    hostingNo.addEventListener('change', function() {
        if (this.checked) {
            domainInputWrapper2.style.display = 'none';
        }
    });

    ecommerceYes.addEventListener('change', function() {
        if (this.checked) {
            domainInputWrapper3.style.display = 'block';
        }
    });

    ecommerceNo.addEventListener('change', function() {
        if (this.checked) {
            domainInputWrapper3.style.display = 'none';
        }
    });
</script>

<script>
$(document).ready(function(){
    $(".approve-btn, .reject-btn").click(function(){
        const field = $(this).data('field');
        const status = $(this).hasClass('approve-btn') ? 'approved' : 'rejected';

        $.post("json_status_update.php", {
            website_id: <?= json_encode($_GET['id']) ?>,
            field: field,
            status: status
        }, function(res){
            location.reload();
        });
    });
});
</script>
<script>
$(document).ready(function() {
    function updateProgressBar() {
        const totalFields = $('input[type="text"], textarea, select, input[type="radio"]:checked').length;
        const filledFields = $('input[type="text"], textarea, select, input[type="radio"]:checked').filter(function() {
            return $(this).val().trim() !== '';
        }).length;
        const percentage = totalFields > 0 ? Math.round((filledFields / totalFields) * 100) : 0;

        $('.progress-bar').css('width', percentage + '%').attr('aria-valuenow', percentage).text(percentage + '% Complete');
    }

    // Update progress bar on input change
    $('input, textarea, select').on('input change', updateProgressBar);

    // Initial calculation
    updateProgressBar();
});
</script>
<?php include './partials/layouts/layoutBottom.php' ?>

