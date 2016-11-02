<{extends file="extends/main.block.tpl"}>

<{block "head-scripts"}><{/block}>
<{block "head-styles-plus"}>
<style>
html, body {
  -moz-user-select: none;
  -webkit-user-select: none;
  -khtml-user-select: none;
  user-select: none;
  background-color: #1CCAA9;
}
</style>
<{/block}>

<{block "body-container"}>
<div class="container">
	<div class="row text-center">
		<div style="-webkit-transform: translate(-50%,-50%);-ms-transform: translate(-50%,-50%);transform: translate(-50%,-50%);position: absolute;top: 45%;left: 50%;width: 100%;">
			<h1 class="" >
				404 
			</h1>
			<h3 class="">Page not found.</h3>
			<h3 class="">頁面不存在</h3>
		</div>
		<div style="position: absolute;bottom: 10px;left: 0;right:0;">&copy; 2013-<{'Y'|date}> Fly studio, all rights reserved.</div>
	</div>
</div>
<{/block}>