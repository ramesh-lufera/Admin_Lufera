<?php

/*
|--------------------------------------------------------------------------
| LOAD LATEST SAVED THEME COLORS
|--------------------------------------------------------------------------
*/

$themeQuery = mysqli_query($conn, "

    SELECT *

    FROM theme_colors

    ORDER BY id DESC

    LIMIT 1

");

$themeData = mysqli_fetch_assoc($themeQuery);

/*
|--------------------------------------------------------------------------
| DEFAULT COLORS
|--------------------------------------------------------------------------
*/

$mainColor  = '#fec700';
$focusColor = '#fec700';
$textColor  = '#000000';

/*
|--------------------------------------------------------------------------
| APPLY SAVED THEME COLORS
|--------------------------------------------------------------------------
*/

if($themeData){

    $mainColor  = $themeData['main_color'];
    $focusColor = $themeData['focus_color'];
    $textColor  = $themeData['text_color'];

}

?>

<style>

/*
|--------------------------------------------------------------------------
| ROOT VARIABLES
|--------------------------------------------------------------------------
*/

:root{

    --lufera-main-color:
        <?php echo $mainColor; ?>;

    --lufera-focus-color:
        <?php echo $focusColor; ?>;

    --lufera-text-color:
        <?php echo $textColor; ?>;

}

/*
|--------------------------------------------------------------------------
| MAIN COLORS
|--------------------------------------------------------------------------
|
| Usage:
| class="lufera-bg"
|
*/

.lufera-bg{

    background-color:
        var(--lufera-main-color) !important;

}

/*
|--------------------------------------------------------------------------
| FOCUS COLORS
|--------------------------------------------------------------------------
|
| Usage:
| class="lufera-color"
|
*/

/* .lufera-color{

    color:
        var(--lufera-focus-color) !important;

} */

.lufera-color{
  color: var(--lufera-main-color) !important;
}

/*
|--------------------------------------------------------------------------
| TEXT COLORS
|--------------------------------------------------------------------------
|
| Usage:
| class="lufera-text"
|
*/

.lufera-text{

    color:
        var(--lufera-text-color) !important;

}

/* style.css file css */

.form-select:focus, .form-select:active, .form-control:focus, .form-control:active, textarea:focus, textarea:active {
  border-color: var(--lufera-main-color) !important;
}

.form-check.style-check .form-check-input:checked {
  border-color: var(--lufera-main-color) !important;
}

.form-check-input:checked {
    background-color:
        var(--lufera-main-color) !important;
}

.text-warning-600 {
  color: var(--lufera-main-color) !important;
}

.sidebar-menu li > a.active-page {
  background-color: var(--lufera-focus-color);
}

.sidebar-menu .sidebar-submenu li.active-page a {
  background-color: var(--lufera-focus-color);
}

.bg-primary-50 {
  background-color: var(--lufera-focus-color) !important;
}

.text-primary-600 {
  color: var(--lufera-main-color) !important;
}

.hover-text-primary:hover, .btn.hover-text-primary:hover {
  color: var(--lufera-main-color) !important;
}

.button-tab .nav-link.active{
  background-color: var(--lufera-main-color) !important;
  border-color: var(--lufera-main-color) !important;
}

/* Pagination */

div.dt-container .dt-paging .dt-paging-button:hover, div.dt-container .dt-paging .dt-paging-button.current {
  background: var(--lufera-main-color) !important;
}

/* Sweet Alert */

.swal2-confirm{
  background-color: var(--lufera-main-color) !important;
  color: var(--lufera-text-color) !important;
}

.switch-primary .form-check-input:checked {
  background-color: var(--lufera-main-color) !important;
}

</style>
