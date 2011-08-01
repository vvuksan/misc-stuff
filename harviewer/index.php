<html>
<head>
<title>Page performance</title>
<link type="text/css" href="css/flick/jquery-ui-1.8.14.custom.css" rel="stylesheet" />
<script type="text/javascript" src="js/jquery-1.6.2.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.14.custom.min.js"></script>
<style>
body{ font: 62.5% "Trebuchet MS", sans-serif; margin: 10px;}
.bar {
  width: 600px;
  background: white;
  display: block;
}
.fill {
  float: left;
}
.harview {
  font-size: 12px;
}
</style>
<script>
function getTimings() {
    $("#results").html('<img src="img/spinner.gif">');
    $.get('waterfall.php', $("#query_form").serialize(), function(data) {
	$("#results").html(data);
     });
}
</script>
</head>
<body>
<div id=header>
<form id="query_form">
URL <input name="url" size=60>
<button onclick="getTimings(); return false;">Get timings</button>
</form>
</div>
<div id=results>
</div>
</body>
</html>