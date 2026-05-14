<!-- ========================= -->
<!-- STEP 2 — CREATE error.php -->
<!-- ========================= -->

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>403 Forbidden</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{
            background:#f3f4f8;
            font-family:Arial, sans-serif;
            min-height:100vh;
            overflow-x:hidden;
        }

        /* HEADER */
        .top-header{
            padding:20px 40px;
        }

        .home-btn-top{
            border:2px solid #fec700;
            color:#fec700;
            background:white;
            padding:10px 18px;
            border-radius:8px;
            text-decoration:none;
            font-size:14px;
            font-weight:600;
            transition:0.3s;
        }

        .home-btn-top:hover{
            background:#fec700;
            color:#fff;
        }

        /* MAIN SECTION */
        .error-wrapper{
            display:flex;
            justify-content:center;
            align-items:center;
            flex-direction:column;
            text-align:center;
            padding:20px 20px 60px;
        }

        .error-image{
            width:100%;
            max-width:650px;
            margin-top:10px;
        }

        .error-title{
            font-size:52px;
            font-weight:700;
            color:#111827;
            margin-top:10px;
            margin-bottom:10px;
        }

        .error-description{
            max-width:700px;
            color:#6b7280;
            font-size:16px;
            line-height:1.8;
        }

        .home-btn{
            display:inline-flex;
            align-items:center;
            gap:10px;
            margin-top:30px;
            background:#fec700;
            color:white;
            text-decoration:none;
            padding:14px 30px;
            border-radius:10px;
            font-size:18px;
            font-weight:600;
            transition:0.3s;
        }

        .home-btn:hover{
            background:#e3b300;
            color:white;
        }

        .home-btn i{
            font-size:18px;
        }

        /* RESPONSIVE */
        @media(max-width:768px){

            .top-header{
                padding:20px;
            }

            .error-image{
                max-width:100%;
            }

            .error-title{
                font-size:38px;
            }

            .error-description{
                font-size:15px;
                line-height:1.7;
                padding:0 10px;
            }

            .home-btn{
                font-size:16px;
                padding:12px 24px;
            }

        }

    </style>

</head>

<body>

    <!-- HEADER -->
    <div class="top-header d-flex justify-content-between align-items-center">

        <!-- LOGO -->
        <div>

            <img 
                src="assets/images/lufera-one.png"
                alt="Logo"
                style="height:50px; width:auto;"
            >

        </div>

        <!-- TOP BUTTON -->
        <a href="index.php" class="home-btn-top">
            Go To Home
        </a>

    </div>

    <!-- MAIN -->
    <div class="container-fluid">

        <div class="error-wrapper">

            <!-- ERROR IMAGE -->
            <img 
                src="assets/images/access-denied/access-denied.png"
                class="error-image"
                alt="403 Error"
            >

            <!-- TITLE -->
            <h1 class="error-title">
                Access Denied
            </h1>

            <!-- DESCRIPTION -->
            <p class="error-description">

                You don't have authorization to get to this page.
                If it's not too much trouble, contact your site
                executive to demand access.

            </p>

            <!-- BUTTON -->
            <a href="index.php" class="home-btn">

                <i class="fa-solid fa-house"></i>

                Go Back To Home

            </a>

        </div>

    </div>

</body>

</html>