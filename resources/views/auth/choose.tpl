<{extends file="extends/main.block.tpl"}>

<{block "head-title"}><title>选择微信公众号</title><{/block}>
<{block "head-styles-plus"}>
<style>
#tools {}
#tools li {line-height: 300%}
</style>
<{/block}>


<{block "body-container"}>
<div class="container" role="main">
	<div class="page-header">
		<h1>选择需要登录的用户组</h1>
	</div>
	<ul id="tools">
	<{foreach $_roles as $v}>
		<li><a href="<{$v->url|url}>"><i class="glyphicon glyphicon-list"> </i> <{$v->display_name}> (<small><{$v->name}></small>) </a></li>
	<{/foreach}>
	</ul>
</div>
<{/block}>

<{block "body-scripts"}>
<script>
(function($){
$().ready(function(){
	<{if $_roles->count() == 1}>
	window.location.href="<{$_roles[0]->url|url}>";
	<{/if}>
});
})(jQuery);
</script>
<{/block}>