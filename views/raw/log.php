<a href="javascript:void(0);" onclick="clearLogFile()" style="
    padding: 10px;
    background-color: red;
    color: white;
    cursor: pointer;
    display: block;
    text-align: center;
    text-decoration: none;
">Стереть лог</a>

<script>
    function clearLogFile() {
        let c = confirm('Стереть файл логов?');
        if (c) {
            let response = fetch('/raw/clear-log')
                .then(response => response.text())
                .then(data => {
                    console.log(data);
                })
                .catch(error => {
                    console.error('Error fetching log file:', error);
                });
        }
    }
</script>

<br>
<pre style="
    word-wrap: break-word;
    white-space: pre-wrap;
    word-break: break-all;
"><?= $logs; ?></pre>