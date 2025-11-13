<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            margin: 0;
            padding: 0;
        }

        .header {
            width: 100%;
            text-align: center;
            /* border-bottom: 2px solid #31bd9c; */
            padding: 5px 0 10px;
            position: relative;
        }

        .header img {
            position: absolute;
            left: 10px;
            top: 0;
            width: 40px;
        }

        .header h2 {
            margin: 0;
            font-size: 14px;
            font-weight: bold;
        }

        .header h4 {
            margin: 2px 0;
            font-size: 11px;
            font-weight: normal;
        }
    </style>
</head>

<body>
    <div class="header">
        <img src="{{ public_path('images/rupali_cash.png') }}" alt="Logo">
        <h2>{{ $institute_name }} </h2>
        <h4>{{ $report_title }}</h4>
    </div>
</body>

</html>
