<?php

include './partials/connection.php';

$selectedThemeData = null;

$getThemeQuery = mysqli_query($conn, "

    SELECT *

    FROM theme_colors

    ORDER BY id DESC

    LIMIT 1

");

if(mysqli_num_rows($getThemeQuery) > 0){

    $selectedThemeData =
        mysqli_fetch_assoc($getThemeQuery);

}

$themeSaved = false;

$customThemeSaved = false;

if (
    isset($_POST['theme_color_name']) &&
    trim($_POST['theme_color_name']) != ''
) {

    $themeName  = mysqli_real_escape_string(
        $conn,
        trim($_POST['theme_color_name'])
    );

    $mainColor = mysqli_real_escape_string(
        $conn,
        $_POST['theme_main_color']
    );

    $focusColor = mysqli_real_escape_string(
        $conn,
        $_POST['theme_focus_color']
    );

    $textColor = mysqli_real_escape_string(
        $conn,
        $_POST['theme_text_color']
    );

    mysqli_query($conn, "

        INSERT INTO theme_colors
        (
            main_color,
            focus_color,
            text_color,
            selected_theme
        )

        VALUES
        (
            '$mainColor',
            '$focusColor',
            '$textColor',
            '$themeName'
        )

    ");

    $customThemeSaved = true;

}

if(isset($_POST['selected_theme'])){

    $mainColor     = $_POST['main_color'];
    $focusColor    = $_POST['focus_color'];
    $textColor     = $_POST['text_color'];
    $selectedTheme = $_POST['selected_theme'];

    mysqli_query($conn, "

        INSERT INTO theme_colors
        (
            main_color,
            focus_color,
            text_color,
            selected_theme
        )

        VALUES
        (
            '$mainColor',
            '$focusColor',
            '$textColor',
            '$selectedTheme'
        )

    ");

    /*
    |--------------------------------------------------------------------------
    | IMMEDIATELY UPDATE CURRENT THEME VARIABLES
    |--------------------------------------------------------------------------
    |
    | This fixes selector showing old selected theme
    | until manual refresh.
    |
    */

    $selectedThemeData = [

        'main_color'     => $mainColor,

        'focus_color'    => $focusColor,

        'text_color'     => $textColor,

        'selected_theme' => $selectedTheme

    ];

    $themeSaved = true;

}

?>

<?php include './partials/layouts/layoutTop.php' ?>

<style>

    .theme-color-box{
        width: 33.33%;
        display: flex;
        flex-direction: column;
    }

    .theme-color-box .h-72-px{
        width: 100%;
        display: block;
    }

    .custom-theme-picker{
        width: 100%;
        height: 72px;
        border: none;
        border-radius: 8px;
        overflow: hidden;
        cursor: pointer;
        padding: 0;
        background: transparent;
        appearance: none;
        -webkit-appearance: none;
        display: block;
    }

    .custom-theme-picker::-webkit-color-swatch-wrapper{
        padding: 0;
    }

    .custom-theme-picker::-webkit-color-swatch{
        border: none;
        border-radius: 8px;
    }

    .custom-theme-picker::-moz-color-swatch{
        border: none;
        border-radius: 8px;
    }

</style>

<div class="dashboard-main-body">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">

        <h6 class="fw-semibold mb-0">Theme</h6>

        <ul class="d-flex align-items-center gap-2">

            <li class="fw-medium">

                <a href="index.php"
                   class="d-flex align-items-center gap-1 hover-text-primary">

                    <iconify-icon icon="solar:home-smile-angle-outline"
                                  class="icon text-lg"></iconify-icon>

                    Dashboard

                </a>

            </li>

            <li>-</li>

            <li class="fw-medium">Settings - Theme</li>

        </ul>

    </div>

    <div class="card h-100 p-0 radius-12">

        <div class="card-body p-24">

            <form action="" method="POST">

                <div class="mt-32">

                    <!-- <h6 class="text-xl mb-16">Theme Colors</h6> -->

                    <div class="d-flex align-items-center justify-content-between mb-16">

                        <h6 class="text-xl mb-0">
                            Theme Colors
                        </h6>

                        <button
                            type="button"
                            class="btn lufera-bg lufera-text text-sm px-20 py-10 radius-8"
                            data-bs-toggle="modal"
                            data-bs-target="#addThemeColorModal">

                            + Add New Color

                        </button>

                    </div>

                    <div class="row gy-4">

                        <!-- Blue -->
                        <div class="col-xxl-4 col-md-6 col-sm-12">

                            <input
                                class="form-check-input payment-gateway-input"
                                name="payment-gateway"
                                type="radio"
                                id="blue"
                                hidden
                                value="blue"
                                data-main="#487fff"
                                data-focus="#dbe9ff"
                                data-text="#000000"

                                <?php
                                    if(
                                        $selectedThemeData &&
                                        $selectedThemeData['selected_theme'] == 'blue'
                                    ){
                                        echo 'checked';
                                    }
                                ?>>

                            <label for="blue"
                                   class="payment-gateway-label border radius-8 p-12 w-100">

                                <span class="d-flex align-items-start justify-content-between gap-3">

                                    <span class="theme-color-box text-center">

                                        <span class="h-72-px bg-primary-600 radius-4 d-block"></span>

                                        <span class="text-secondary-light text-md fw-semibold mt-8 d-block">
                                            Blue
                                        </span>

                                    </span>

                                    <span class="theme-color-box text-center">

                                        <span class="h-72-px bg-primary-100 radius-4 d-block"></span>

                                        <span class="text-secondary-light text-md fw-semibold mt-8 d-block">
                                            Focus
                                        </span>

                                    </span>

                                    <span class="theme-color-box text-center">

                                        <span class="h-72-px radius-4 border d-block"
                                              style="background:#000000;"></span>

                                        <span class="text-secondary-light text-md fw-semibold mt-8 d-block">
                                            Text
                                        </span>

                                    </span>

                                </span>

                            </label>

                        </div>

                        <!-- Magenta -->
                        <div class="col-xxl-4 col-md-6 col-sm-12">

                            <input
                                class="form-check-input payment-gateway-input"
                                name="payment-gateway"
                                type="radio"
                                id="magenta"
                                hidden
                                value="magenta"
                                data-main="#8252e9"
                                data-focus="#ece0ff"
                                data-text="#000000"

                                <?php
                                    if(
                                        $selectedThemeData &&
                                        $selectedThemeData['selected_theme'] == 'magenta'
                                    ){
                                        echo 'checked';
                                    }
                                ?>>

                            <label for="magenta"
                                   class="payment-gateway-label border radius-8 p-12 w-100">

                                <span class="d-flex align-items-start justify-content-between gap-3">

                                    <span class="theme-color-box text-center">

                                        <span class="h-72-px bg-lilac-600 radius-4 d-block"></span>

                                        <span class="text-lilac-light text-md fw-semibold mt-8 d-block">
                                            Magenta
                                        </span>

                                    </span>

                                    <span class="theme-color-box text-center">

                                        <span class="h-72-px bg-lilac-100 radius-4 d-block"></span>

                                        <span class="text-lilac-light text-md fw-semibold mt-8 d-block">
                                            Focus
                                        </span>

                                    </span>

                                    <span class="theme-color-box text-center">

                                        <span class="h-72-px radius-4 border d-block"
                                              style="background:#000000;"></span>

                                        <span class="text-secondary-light text-md fw-semibold mt-8 d-block">
                                            Text
                                        </span>

                                    </span>

                                </span>

                            </label>

                        </div>

                        <!-- Orange -->
                        <div class="col-xxl-4 col-md-6 col-sm-12">

                            <input
                                class="form-check-input payment-gateway-input"
                                name="payment-gateway"
                                type="radio"
                                id="orange"
                                hidden
                                value="orange"
                                data-main="#ff9f29"
                                data-focus="#fff3e0"
                                data-text="#000000"

                                <?php
                                    if(
                                        $selectedThemeData &&
                                        $selectedThemeData['selected_theme'] == 'orange'
                                    ){
                                        echo 'checked';
                                    }
                                ?>>

                            <label for="orange"
                                   class="payment-gateway-label border radius-8 p-12 w-100">

                                <span class="d-flex align-items-start justify-content-between gap-3">

                                    <span class="theme-color-box text-center">

                                        <span class="h-72-px bg-warning-600 radius-4 d-block"></span>

                                        <span class="text-secondary-light text-md fw-semibold mt-8 d-block">
                                            Orange
                                        </span>

                                    </span>

                                    <span class="theme-color-box text-center">

                                        <span class="h-72-px bg-warning-100 radius-4 d-block"></span>

                                        <span class="text-secondary-light text-md fw-semibold mt-8 d-block">
                                            Focus
                                        </span>

                                    </span>

                                    <span class="theme-color-box text-center">

                                        <span class="h-72-px radius-4 border d-block"
                                              style="background:#000000;"></span>

                                        <span class="text-secondary-light text-md fw-semibold mt-8 d-block">
                                            Text
                                        </span>

                                    </span>

                                </span>

                            </label>

                        </div>

                        <!-- Green -->
                        <div class="col-xxl-4 col-md-6 col-sm-12">

                            <input
                                class="form-check-input payment-gateway-input"
                                name="payment-gateway"
                                type="radio"
                                id="green"
                                hidden
                                value="green"
                                data-main="#45b369"
                                data-focus="#d9f8e5"
                                data-text="#000000"

                                <?php
                                    if(
                                        $selectedThemeData &&
                                        $selectedThemeData['selected_theme'] == 'green'
                                    ){
                                        echo 'checked';
                                    }
                                ?>>

                            <label for="green"
                                   class="payment-gateway-label border radius-8 p-12 w-100">

                                <span class="d-flex align-items-start justify-content-between gap-3">

                                    <span class="theme-color-box text-center">

                                        <span class="h-72-px bg-success-600 radius-4 d-block"></span>

                                        <span class="text-secondary-light text-md fw-semibold mt-8 d-block">
                                            Green
                                        </span>

                                    </span>

                                    <span class="theme-color-box text-center">

                                        <span class="h-72-px bg-success-100 radius-4 d-block"></span>

                                        <span class="text-secondary-light text-md fw-semibold mt-8 d-block">
                                            Focus
                                        </span>

                                    </span>

                                    <span class="theme-color-box text-center">

                                        <span class="h-72-px radius-4 border d-block"
                                              style="background:#000000;"></span>

                                        <span class="text-secondary-light text-md fw-semibold mt-8 d-block">
                                            Text
                                        </span>

                                    </span>

                                </span>

                            </label>

                        </div>

                        <!-- Red -->
                        <div class="col-xxl-4 col-md-6 col-sm-12">

                            <input
                                class="form-check-input payment-gateway-input"
                                name="payment-gateway"
                                type="radio"
                                id="red"
                                hidden
                                value="red"
                                data-main="#ef4a4a"
                                data-focus="#ffe2e2"
                                data-text="#000000"

                                <?php
                                    if(
                                        $selectedThemeData &&
                                        $selectedThemeData['selected_theme'] == 'red'
                                    ){
                                        echo 'checked';
                                    }
                                ?>>

                            <label for="red"
                                   class="payment-gateway-label border radius-8 p-12 w-100">

                                <span class="d-flex align-items-start justify-content-between gap-3">

                                    <span class="theme-color-box text-center">

                                        <span class="h-72-px bg-danger-600 radius-4 d-block"></span>

                                        <span class="text-secondary-light text-md fw-semibold mt-8 d-block">
                                            Red
                                        </span>

                                    </span>

                                    <span class="theme-color-box text-center">

                                        <span class="h-72-px bg-danger-100 radius-4 d-block"></span>

                                        <span class="text-secondary-light text-md fw-semibold mt-8 d-block">
                                            Focus
                                        </span>

                                    </span>

                                    <span class="theme-color-box text-center">

                                        <span class="h-72-px radius-4 border d-block"
                                              style="background:#000000;"></span>

                                        <span class="text-secondary-light text-md fw-semibold mt-8 d-block">
                                            Text
                                        </span>

                                    </span>

                                </span>

                            </label>

                        </div>

                        <!-- Blue Dark -->
                        <div class="col-xxl-4 col-md-6 col-sm-12">

                            <input
                                class="form-check-input payment-gateway-input"
                                name="payment-gateway"
                                type="radio"
                                id="blueDark"
                                hidden
                                value="blueDark"
                                data-main="#0ea5e9"
                                data-focus="#d9f1ff"
                                data-text="#000000"

                                <?php
                                    if(
                                        $selectedThemeData &&
                                        $selectedThemeData['selected_theme'] == 'blueDark'
                                    ){
                                        echo 'checked';
                                    }
                                ?>>

                            <label for="blueDark"
                                   class="payment-gateway-label border radius-8 p-12 w-100">

                                <span class="d-flex align-items-start justify-content-between gap-3">

                                    <span class="theme-color-box text-center">

                                        <span class="h-72-px bg-info-600 radius-4 d-block"></span>

                                        <span class="text-secondary-light text-md fw-semibold mt-8 d-block">
                                            Blue Dark
                                        </span>

                                    </span>

                                    <span class="theme-color-box text-center">

                                        <span class="h-72-px bg-info-100 radius-4 d-block"></span>

                                        <span class="text-secondary-light text-md fw-semibold mt-8 d-block">
                                            Focus
                                        </span>

                                    </span>

                                    <span class="theme-color-box text-center">

                                        <span class="h-72-px radius-4 border d-block"
                                              style="background:#000000;"></span>

                                        <span class="text-secondary-light text-md fw-semibold mt-8 d-block">
                                            Text
                                        </span>

                                    </span>

                                </span>

                            </label>

                        </div>

                        <!-- Lufera Infotech -->
                        <div class="col-xxl-4 col-md-6 col-sm-12">

                            <input
                                class="form-check-input payment-gateway-input"
                                name="payment-gateway"
                                type="radio"
                                id="luferaInfotech"
                                hidden
                                value="luferaInfotech"

                                data-main="#1e8a8a"
                                data-focus="#d8e5e5"
                                data-text="#0c1e21"

                                <?php

                                if(

                                    $selectedThemeData &&

                                    $selectedThemeData['selected_theme'] == 'luferaInfotech'

                                ){

                                    echo 'checked';

                                }

                                ?>

                            >

                            <label
                                for="luferaInfotech"
                                class="payment-gateway-label border radius-8 p-12 w-100">

                                <span class="d-flex align-items-start justify-content-between gap-3">

                                    <!-- Main -->

                                    <span class="theme-color-box text-center">

                                        <span
                                            class="h-72-px radius-4 d-block"
                                            style="background:#1e8a8a;">
                                        </span>

                                        <span class="text-secondary-light text-md fw-semibold mt-8 d-block">

                                            Lufera Infotech

                                        </span>

                                    </span>

                                    <!-- Focus -->

                                    <span class="theme-color-box text-center">

                                        <span
                                            class="h-72-px radius-4 d-block"
                                            style="background:#d8e5e5;">
                                        </span>

                                        <span class="text-secondary-light text-md fw-semibold mt-8 d-block">

                                            Focus

                                        </span>

                                    </span>

                                    <!-- Text -->

                                    <span class="theme-color-box text-center">

                                        <span
                                            class="h-72-px radius-4 border d-block"
                                            style="background:#0c1e21;">
                                        </span>

                                        <span class="text-secondary-light text-md fw-semibold mt-8 d-block">

                                            Text

                                        </span>

                                    </span>

                                </span>

                            </label>

                        </div>

                        <?php

                        $userThemes = mysqli_query($conn, "

                        SELECT *

                        FROM theme_colors

                        WHERE selected_theme NOT IN (

                        'blue',

                        'magenta',

                        'orange',

                        'green',

                        'red',

                        'blueDark',

                        'luferaInfotech',

                        'custom'

                        )

                        ORDER BY id ASC

                        ");

                        while($theme = mysqli_fetch_assoc($userThemes)){

                        ?>

                        <div class="col-xxl-4 col-md-6 col-sm-12">

                            <input

                                class="form-check-input payment-gateway-input"

                                name="payment-gateway"

                                type="radio"

                                hidden

                                id="theme_<?php echo $theme['id']; ?>"

                                value="<?php echo htmlspecialchars($theme['selected_theme']); ?>"

                                data-main="<?php echo $theme['main_color']; ?>"

                                data-focus="<?php echo $theme['focus_color']; ?>"

                                data-text="<?php echo $theme['text_color']; ?>"

                                <?php

                                if(

                                    $selectedThemeData &&

                                    $selectedThemeData['selected_theme'] == $theme['selected_theme']

                                ){

                                    echo "checked";

                                }

                                ?>

                            >

                            <label

                                for="theme_<?php echo $theme['id']; ?>"

                                class="payment-gateway-label border radius-8 p-12 w-100">

                                <span class="d-flex align-items-start justify-content-between gap-3">

                                    <!-- Main -->

                                    <span class="theme-color-box text-center">

                                        <span

                                            class="h-72-px radius-4 d-block"

                                            style="background:<?php echo $theme['main_color']; ?>;">

                                        </span>

                                        <span

                                            class="text-secondary-light text-md fw-semibold mt-8 d-block">

                                            <?php echo htmlspecialchars($theme['selected_theme']); ?>

                                        </span>

                                    </span>

                                    <!-- Focus -->

                                    <span class="theme-color-box text-center">

                                        <span

                                            class="h-72-px radius-4 d-block"

                                            style="background:<?php echo $theme['focus_color']; ?>;">

                                        </span>

                                        <span

                                            class="text-secondary-light text-md fw-semibold mt-8 d-block">

                                            Focus

                                        </span>

                                    </span>

                                    <!-- Text -->

                                    <span class="theme-color-box text-center">

                                        <span

                                            class="h-72-px radius-4 border d-block"

                                            style="background:<?php echo $theme['text_color']; ?>;">

                                        </span>

                                        <span

                                            class="text-secondary-light text-md fw-semibold mt-8 d-block">

                                            Text

                                        </span>

                                    </span>

                                </span>

                            </label>

                        </div>

                        <?php } ?>

                        <!-- Custom Colors Title -->
                        <div class="col-12 pb-0">

                            <h6 class="text-lg fw-semibold mb-16">
                                Custom Colors
                            </h6>

                        </div>

                        <!-- Custom Colors -->
                        <div class="col-xxl-4 col-md-6 col-sm-12 pt-0 mt-0">

                            <input
                                class="form-check-input payment-gateway-input"
                                name="payment-gateway"
                                type="radio"
                                id="customTheme"
                                hidden
                                value="custom"

                                <?php
                                    if(
                                        $selectedThemeData &&
                                        $selectedThemeData['selected_theme'] == 'custom'
                                    ){
                                        echo 'checked';
                                    }
                                ?>>

                            <label for="customTheme"
                                   class="payment-gateway-label border radius-8 p-12 w-100">

                                <span class="d-flex align-items-start justify-content-between gap-3">

                                    <span class="theme-color-box text-center">

                                        <input type="color"
                                               id="customMainColor"

                                               value="<?php

                                                    if(
                                                        $selectedThemeData &&
                                                        $selectedThemeData['selected_theme'] == 'custom'
                                                    ){

                                                        echo $selectedThemeData['main_color'];

                                                    }else{

                                                        echo '#487fff';

                                                    }

                                               ?>"

                                               class="custom-theme-picker">

                                        <span class="text-secondary-light text-md fw-semibold mt-8 d-block">
                                            Main
                                        </span>

                                    </span>

                                    <span class="theme-color-box text-center">

                                        <input type="color"
                                               id="customFocusColor"

                                               value="<?php

                                                    if(
                                                        $selectedThemeData &&
                                                        $selectedThemeData['selected_theme'] == 'custom'
                                                    ){

                                                        echo $selectedThemeData['focus_color'];

                                                    }else{

                                                        echo '#dbe9ff';

                                                    }

                                               ?>"

                                               class="custom-theme-picker">

                                        <span class="text-secondary-light text-md fw-semibold mt-8 d-block">
                                            Focus
                                        </span>

                                    </span>

                                    <span class="theme-color-box text-center">

                                        <input type="color"
                                               id="customTextColor"

                                               value="<?php

                                                    if(
                                                        $selectedThemeData &&
                                                        $selectedThemeData['selected_theme'] == 'custom'
                                                    ){

                                                        echo $selectedThemeData['text_color'];

                                                    }else{

                                                        echo '#000000';

                                                    }

                                               ?>"

                                               class="custom-theme-picker">

                                        <span class="text-secondary-light text-md fw-semibold mt-8 d-block">
                                            Text
                                        </span>

                                    </span>

                                </span>

                            </label>

                        </div>

                        <input type="hidden"
                               name="main_color"
                               id="mainColorValue">

                        <input type="hidden"
                               name="focus_color"
                               id="focusColorValue">

                        <input type="hidden"
                               name="text_color"
                               id="textColorValue">

                        <input type="hidden"
                               name="selected_theme"
                               id="selectedThemeValue">

                        <div class="d-flex align-items-center justify-content-center gap-3 mt-24">

                            <button type="reset"
                                    class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-40 py-11 radius-8">
                                Reset
                            </button>

                            <button type="submit"
                                    class="btn btn-primary border border-primary-600 text-md px-24 py-12 radius-8">
                                Save Change
                            </button>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

const themeInputs =
    document.querySelectorAll('.payment-gateway-input');

const mainInput =
    document.getElementById('mainColorValue');

const focusInput =
    document.getElementById('focusColorValue');

const textInput =
    document.getElementById('textColorValue');

const themeInput =
    document.getElementById('selectedThemeValue');

themeInputs.forEach(input => {

    input.addEventListener('change', function(){

        /*
        |--------------------------------------------------------------------------
        | CUSTOM THEME
        |--------------------------------------------------------------------------
        */

        if(this.value === 'custom'){

            updateCustomThemeValues();

        }

        /*
        |--------------------------------------------------------------------------
        | STATIC THEMES
        |--------------------------------------------------------------------------
        */

        else{

            mainInput.value =
                this.dataset.main;

            focusInput.value =
                this.dataset.focus;

            textInput.value =
                this.dataset.text;

            themeInput.value =
                this.value;

        }

    });

});

/*
|--------------------------------------------------------------------------
| UPDATE CUSTOM THEME VALUES
|--------------------------------------------------------------------------
|
| This function synchronizes hidden
| inputs with current color picker values.
|
*/

function updateCustomThemeValues(){

    mainInput.value =
        document.getElementById('customMainColor').value;

    focusInput.value =
        document.getElementById('customFocusColor').value;

    textInput.value =
        document.getElementById('customTextColor').value;

    themeInput.value = 'custom';

}

/*
|--------------------------------------------------------------------------
| CUSTOM COLOR PICKER EVENTS
|--------------------------------------------------------------------------
|
| Update hidden values immediately
| whenever colors change.
|
*/

document
    .getElementById('customMainColor')
    .addEventListener('input', updateCustomThemeValues);

document
    .getElementById('customFocusColor')
    .addEventListener('input', updateCustomThemeValues);

document
    .getElementById('customTextColor')
    .addEventListener('input', updateCustomThemeValues);

window.addEventListener('load', () => {

    const checkedTheme =
        document.querySelector('.payment-gateway-input:checked');

    if(checkedTheme){

        checkedTheme.dispatchEvent(new Event('change'));

    }

});

</script>

<?php if($themeSaved){ ?>

<script>

    Swal.fire({

        icon: 'success',

        title: 'Theme Saved',

        text: 'Your dashboard theme colors saved successfully.',

        confirmButtonColor: '#487fff',

        confirmButtonText: 'Okay'

    });

</script>

<?php } ?>

<?php if($customThemeSaved){ ?>

<script>

Swal.fire({

icon:'success',

title:'Theme Added',

text:'Theme added successfully.',

confirmButtonColor:'#1e8a8a'

}).then(()=>{

window.location='theme_colors.php';

});

</script>

<?php } ?>

<!-- ==========================================================
     ADD NEW THEME COLOR MODAL
========================================================== -->

<div class="modal fade"
     id="addThemeColorModal"
     tabindex="-1"
     aria-labelledby="addThemeColorModalLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <form
                id="addThemeColorForm"
                method="POST"
                action="">

                <!-- Header -->
                <div class="modal-header">

                    <h5 class="modal-title"
                        id="addThemeColorModalLabel">

                        Add New Theme Color

                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close">
                    </button>

                </div>

                <!-- Body -->
                <div class="modal-body">

                    <!-- Color Name -->
                    <div class="mb-20">

                        <label class="form-label">

                            Color Name

                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="themeColorName"
                            name="theme_color_name"
                            placeholder="Enter Color Name"
                            required>

                    </div>

                    <!-- Main Color -->
                    <div class="mb-20">

                        <label class="form-label">

                            Main Color

                        </label>

                        <input
                            type="color"
                            class="form-control form-control-color w-100"
                            id="themeMainColor"
                            name="theme_main_color"
                            required>

                    </div>

                    <!-- Focus Color -->
                    <div class="mb-20">

                        <label class="form-label">

                            Focus Color

                        </label>

                        <input
                            type="color"
                            class="form-control form-control-color w-100"
                            id="themeFocusColor"
                            name="theme_focus_color"
                            required>

                    </div>

                    <!-- Text Color -->
                    <div class="mb-20">

                        <label class="form-label">

                            Text Color

                        </label>

                        <input
                            type="color"
                            class="form-control form-control-color w-100"
                            id="themeTextColor"
                            name="theme_text_color"
                            required>

                    </div>

                </div>

                <!-- Footer -->
                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">

                        Close

                    </button>

                    <button type="submit"
                            class="btn lufera-bg lufera-text">

                        Save

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php include './partials/layouts/layoutBottom.php' ?>