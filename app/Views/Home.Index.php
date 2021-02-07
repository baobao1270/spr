<h1>SPR Example Home Page</h1>
<p>
    Controller: <?=$__controller?>, Action: <?=$__action?>
</p>
<pre style="background: #eee" id="ajax-result"></pre>
<script>
function ajax() {
    var ajax = new XMLHttpRequest();
    ajax.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
        document.getElementById("ajax-result").innerText = this.responseText;
    }
    };
    ajax.open("GET", "/api", true);
    ajax.send();
}
</script>
<button onclick="ajax()">Click Me to Load API!</button>
