<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <style>
        body {
            background: #000000;
            text-align: center;
        }

        .bg {
            background-image: url("/assets/website/img/bg.jpg");
            background-size: cover;
            background-position: center;
            width: 100%;
            height: 100%;
            display: block;
            position: absolute;
            opacity: 0.4;
            z-index: 0;
            top: 0;
        }

        .center {
            z-index: 999;
            padding-top: 20vw;
            text-align: center;
            opacity: 1;
            color: #ffffff;
            position: relative;
        }
    </style>
</head>

<body>
    <div class="bg"></div>
    <div class="center">
        <h1 style="margin-bottom: 40px;">{{ trans('panel.site_title') }}</h1>
        @auth
        <a href="/admin" class="btn btn-large btn-outline-light">DASHBOARD</a>
        @else
        <a href="/login" class="btn btn-large btn-outline-light">LOGIN</a>
        @endauth
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous">
    </script>
</body>

</html>