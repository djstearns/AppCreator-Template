<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title><?php echo $title_for_layout; ?> &raquo; <?php echo Configure::read('Site.title'); ?></title>
	<?php
		echo $this->Layout->meta();
		echo $this->Html->css(array(
			'bootstrap',
			'theme',
			'bootstrap-responsive',
			'chosen',
			'select2'
		));    

        echo $this->Layout->js();
		echo $this->Html->script(array(
			'jquery.min',
			'bootstrap',
			'bootstrap-editable',
			'select2',
			'chosen.jquery'
			
		));
		echo $scripts_for_layout;
    ?>

    <!-- Should replace the following with your icons -->
    <link rel="shortcut icon" href="/img/favicon.ico">
    <link rel="apple-touch-icon" href="images/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="72x72" href="images/apple-touch-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="114x114" href="images/apple-touch-icon-114x114.png">
    <link href="//cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.0/bootstrap-editable/css/bootstrap-editable.css" rel="stylesheet"/>
	<!-- <script src="//cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.0/bootstrap-editable/js/bootstrap-editable.js"></script>-->
    <link href="//www.fuelcdn.com/fuelux/2.4.1/css/fuelux.min.css" rel="stylesheet" type="text/css">
	<link href="//www.fuelcdn.com/fuelux/2.4.1/css/fuelux-responsive.css" rel="stylesheet" type="text/css">
    <script src="http://www.fuelcdn.com/fuelux/2.4.1/loader.min.js" type="text/javascript"></script>
  </head>

  <body>

	<?php echo $this->element('header'); ?>

	<div class="container">

		<?php
			if($this->here == '/'){
			//Home page layout
		?>
		<!-- Main hero unit for a primary marketing message or call to action -->
		<div class="hero-unit">
			<h1><?php echo Configure::read('Site.tagline'); ?></h1>
			<p>&nbsp;</p>
			<p><a class="btn btn-large"><b>Call Us To Get Started &raquo;</b></a></p>
		</div>

		<div class="row">
			<div class="span12">
				<?php 
				  echo $this->Layout->sessionFlash();
				?>
			</div>
		</div>

		<?php 
		  echo $content_for_layout;
		?>
		<?php
			} else {
			//Layout for all other pages
		?>
		<div class="hero-unit subpage">
			<h1><?php echo $title_for_layout; ?></h1>
		</div>
		
		<div class="row">
			<div class="span8">
				<?php 
				echo $this->Layout->sessionFlash();
				echo $content_for_layout; 
				?>
			</div>
			<div class="span4">
				<div class="well sidebar-nav">
					<?php echo $layout->blocks('right'); ?>
				</div>
			</div>
		</div>
		<?php
			}
		?>
		<hr />
		
		<?php echo $this->element('footer'); ?>
		
	</div>

  </body>
</html>
<script type="text/javascript">
$(".chosen-select").chosen()
</script>
