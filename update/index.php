<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Встановлення/оновлення CombaCart</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <link rel="icon" href="https://comba.com.ua/assets/favicon/favicon.ico" type="image/x-icon">
    <style>
        * {
            margin: 0;
            box-sizing: border-box;
        }
        body {
            display: flex;
            align-items: center;
            height: 100vh;
            background-color: #f0f0f0;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", "Noto Sans", "Liberation Sans", Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"
        }
        .container {
            text-align: left;
            padding: 0 0.2em;
            width: 100%;
        }
        .logo {
            margin-bottom: 0.5em;
            padding-bottom: 0.9em;
            box-shadow: 0 5px 5px 0 #9FB7A7;
        }
        .logo img{
            vertical-align: middle;
            margin-right: 0.1em;
        }
        .box {
            height: 90vh;
            overflow-y: auto;
            padding: 10px;
            word-wrap: break-word;
            white-space: normal;
        }
        .error {color: red;font-weight: bold;}
        .success {color: green;}
        .warning {color:chocolate;}
        .strong {font-weight: bold;}
    </style>
</head>
<body>
<div class="container">
    <div class="logo">
        <h2 class="">
            <img src="https://comba.com.ua/assets/favicon/android-chrome-256x256.png" width="40" alt="logo"> CombaCart
        </h2>
    </div>
    <pre id="output" class="box"></pre>
</div>
<script>
    let retryCount = 0;
    const maxRetries = 3;

    async function fetchData() {
        console.log('Запит до серверу...');
        try {
            const response = await fetch("process.php", { keepalive: true });
            if (!response.ok) {
                console.log(response);
                return;
            }
            const reader = response.body.getReader();
            const decoder = new TextDecoder();

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                const decodedText = decoder.decode(value);
                const formattedText = decodedText.replace(/\n/g, '<br>');
                document.getElementById("output").innerHTML += formattedText;
                document.getElementById("output").scrollTop = document.getElementById("output").scrollHeight;
            }
        } catch (error) {
            console.error('Помилка:', error);
            retryCount++;
            if (retryCount < maxRetries) {
                document.getElementById("output").innerHTML += '<br>';
                console.log('Повторний запит до серверу через 3 секунд...');
                setTimeout(fetchData, 3000);
            } else {
                document.getElementById("output").innerHTML += '<br><div class="warning">Процес завис? <a href="javascript:window.location.reload(true)">Оновіть сторінку</a> для продовження встановлення.</div>';
            }
        }
    }
    fetchData();
</script>
</body>
</html>
