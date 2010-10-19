<!DOCTYPE html
PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
<?=$this['includes']->render()?>
<style type="text/css">
#layout_wrapper
{
	width: 800px;
	margin-left: auto;
	margin-right: auto;
}
#layout_header
{
	height: 90px;
	border: 1px solid red;
}
#layout_left_menu
{
	float: left;
	width: 146px;
	min-height: 300px;
	height: 100%;
	border: 1px solid purple;
}
#layout_content
{
	width: 650px;
	min-height: 300px;
	float: left;
	border: 1px solid blue;
}
#layout_footer
{
	clear: both;
	height: 90px;
	border: 1px solid green;
}
</style>
<title>Title</title>
</head>
<body>
<div id="layout_wrapper">
  <div id="layout_header">
  	Look Ma, No Output Buffering
  </div><!--layout_header-->
	<?=$this['navigation']->render()?>
  <div id="layout_left_menu">
  </div><!--layout_left_menu-->
  <div id="layout_content">
    <?=$this['content']->render()?>
  </div><!--layout_content-->
  <div id="layout_footer">
  </div><!--layout_footer-->
</div><!--layout_wrapper-->
<?=$this['DefaultFooter']->render()?>
<?=$this['clouds']->render()?>
</body>
</html>
