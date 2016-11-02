<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Tips - <{$_data.result}></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link rel="stylesheet" href="<{'static/css/bootstrap3/bootstrap.min.css'|url nofilter}>" />
</head>
<body>
<div style="margin:100px auto 0 auto;">
	<div class="container">
		<div class="panel <{if $_data.result == 'failure'}>panel-warning<{elseif $_data.result == 'error'}>panel-danger<{elseif $_data.result == 'notice'}>panel-info<{else}>panel-<{$_data.result}><{/if}>">
			<div class="panel-heading">
				<h3 class="panel-title"><span class="glyphicon glyphicon-comment"></span> <strong>提示</strong> <{$_data.result|escape}></h3>
			</div>
  			<div class="panel-body">
				<h2><{$_data.message.title|escape:'html' nofilter}></h2>
				<p><{$_data.message.content|escape:'noscript' nofilter}></p>
			</div>
			<div class="panel-footer">
				<{if !is_bool($_data.url) }>
					<a href="<{$_data.url nofilter}>" target="_self">如果页面没有跳转，请点击这里</a>
					<script type="text/javascript">
					<{if !empty($_data.url)}>
					setTimeout(function(){
						self.location.href = '<{$_data.url|@addslashes nofilter}>';
					},1500);
					<{/if}>
					</script>
				<{else}>
					<a href="<{$url.previous}>" target="_self">请后退页面</a>
				<{/if}>
			</div>
		</div>
	</div>
</div>
<script>
	var DATA = <{$_data.data|json_encode nofilter}>
</script>
</body>
</html>

