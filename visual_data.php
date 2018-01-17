<?php
require_once 'functions.php';
$conn        = connectDB();
$danmu       = analysisDanmu($conn, $_GET['room_id']);
$danmuKeys   = implode('","', array_keys($danmu));
$danmuValues = implode('","', array_values($danmu));

$gifts      = analysisGifts($conn, $_GET['room_id']);
$giftKeys   = implode('","', array_keys($gifts));
$giftValues = implode('","', array_values($gifts));
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <script src="https://cdn.bootcss.com/echarts/3.8.5/echarts.js"></script>
</head>
<body>
<div id="danmu" style="width: 600px;height:400px;">
    <script type="text/javascript">
        var myChart = echarts.init(document.getElementById('danmu'));
        var option = {
            title: {
                text: '弹幕量（条）'
            },
            tooltip: {},
            legend: {
                data: ['弹幕条数']
            },
            xAxis: {
                data: [<?php echo '"' . $danmuKeys . '"';?>]
            },
            yAxis: {},
            series: [{
                name: '弹幕量',
                type: 'bar',
                data: [<?php echo '"' . $danmuValues . '"';?>]
            }]
        };
        myChart.setOption(option);
    </script>
</div>
<div id="gift" style="width: 600px;height:400px;">
    <script type="text/javascript">
        var myChart = echarts.init(document.getElementById('gift'));
        var option = {
            title: {
                text: '礼物价值（元）'
            },
            tooltip: {},
            legend: {
                data: ['礼物真实价值（RMB）']
            },
            xAxis: {
                data: [<?php echo '"' . $giftKeys . '"';?>]
            },
            yAxis: {},
            series: [{
                name: '礼物价值（元）',
                type: 'bar',
                data: [<?php echo '"' . $giftValues . '元"';?>]
            }]
        };
        myChart.setOption(option);
    </script>
</div>
</body>
</html>