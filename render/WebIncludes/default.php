<base href="<?=$this['base_path']?>" />
<link rel="shortcut icon" href="<?=$this['base_path']?>/favicon.ico" />
<?php foreach($this['css'] as $media => $css_files): ?>
	<?php foreach($css_files as $css):?>
		<link href="<?=Url::get($css)?>" rel="stylesheet" media="<?=$media?>" type="text/css" />
	<?php endforeach; ?>
<?php endforeach; ?>
<?php foreach($this['js'] as $js): ?>
	<script type="text/javascript" src="<?=Url::Get($js);?>"></script>
<?php endforeach; ?>

