<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">

	<?php
		if (preg_match("/(iPhone|iPod|iPad|Android|BlackBerry|Mobile)/i", $_SERVER['HTTP_USER_AGENT'])) {
			?><meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, viewport-fit=cover"><?php

		}
	?>
	<link rel="stylesheet" type="text/css" href="<?php echo theme_url('css/bootstrap.min.css'); ?>">
	<link rel="stylesheet" type="text/css" href="<?php echo theme_url('css/prism.css'); ?>">
	<link rel="stylesheet" type="text/css" href="<?php echo theme_url('css/styles.css'); ?>">

	<!-- Captcha -->
	<link rel="preconnect" href="https://www.google.com">
	<link rel="preconnect" href="https://www.gstatic.com" crossorigin>
	<script async src='https://www.google.com/recaptcha/api.js'></script>

	<?php
		$page_title = page_title("Page can't be found.");
		if ($page_title === 'Main Posts') {
			$page_title = site_name();
		} else {
			$page_title = $page_title . ' - ' . site_name();
		}
	?>

	<meta property="og:title" content="<?php echo $page_title; ?>">
	<meta property="og:type" content="website">
	<meta property="og:url" content="<?php echo current_url(); ?>">
	<meta property="og:image" content="<?php echo theme_url('img/og_image.gif'); ?>">
	<meta property="og:site_name" content="<?php echo site_name(); ?>">
	<meta property="og:description" content="<?php echo site_description(); ?>">
	<script type="application/ld+json">
		{
		"@context" : "https://schema.org",
		"@type" : "WebSite",
		"name" : "Vox Silva",
		"url" : "https://blog.alexbeals.com/"
		}
	</script>

	<link rel="apple-touch-icon" sizes="57x57" href="<?php echo theme_url('img/favicon/apple-touch-icon-57x57.png'); ?>">
	<link rel="apple-touch-icon" sizes="60x60" href="<?php echo theme_url('img/favicon/apple-touch-icon-60x60.png'); ?>">
	<link rel="apple-touch-icon" sizes="72x72" href="<?php echo theme_url('img/favicon/apple-touch-icon-72x72.png'); ?>">
	<link rel="apple-touch-icon" sizes="76x76" href="<?php echo theme_url('img/favicon/apple-touch-icon-76x76.png'); ?>">
	<link rel="apple-touch-icon" sizes="114x114" href="<?php echo theme_url('img/favicon/apple-touch-icon-114x114.png'); ?>">
	<link rel="apple-touch-icon" sizes="120x120" href="<?php echo theme_url('img/favicon/apple-touch-icon-120x120.png'); ?>">
	<link rel="apple-touch-icon" sizes="144x144" href="<?php echo theme_url('img/favicon/apple-touch-icon-144x144.png'); ?>">
	<link rel="apple-touch-icon" sizes="152x152" href="<?php echo theme_url('img/favicon/apple-touch-icon-152x152.png'); ?>">
	<link rel="apple-touch-icon" sizes="180x180" href="<?php echo theme_url('img/favicon/apple-touch-icon-180x180.png'); ?>">
	<link rel="icon" type="image/png" href="<?php echo theme_url('img/favicon/favicon-32x32.png'); ?>" sizes="32x32">
	<link rel="icon" type="image/png" href="<?php echo theme_url('img/favicon/android-chrome-192x192.png'); ?>" sizes="192x192">
	<link rel="icon" type="image/png" href="<?php echo theme_url('img/favicon/favicon-96x96.png'); ?>" sizes="96x96">
	<link rel="icon" type="image/png" href="<?php echo theme_url('img/favicon/favicon-16x16.png'); ?>" sizes="16x16">
	<link rel="manifest" href="<?php echo theme_url('img/favicon/manifest.json'); ?>">
	<link rel="shortcut icon" href="<?php echo theme_url('img/favicon/favicon.ico'); ?>">
	<meta name="msapplication-TileColor" content="#000000">
	<meta name="msapplication-TileImage" content="<?php echo theme_url('img/favicon/mstile-144x144.png'); ?>">
	<meta name="msapplication-config" content="<?php echo theme_url('img/favicon/browserconfig.xml'); ?>">
	<meta name="theme-color" content="#006841">

	<script src="<?php echo theme_url('js/jquery.min.js'); ?>"></script>
	<script type="text/javascript" id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>

	<title><?php echo $page_title; ?></title>
</head>
<body>
	<header id="top">
		<nav class="navbar navbar-default padding-container">
			<div class="navbar-header">
				<a href="<?php echo base_url(); ?>" class="navbar-brand">
					<img src="/themes/folium/tree_small.png" data-high-res="/themes/folium/tree.png" alt="Lone Redwood" />
					<span><?php echo site_name(); ?></span>
					<?php
					if (admin()) {
						echo '<img src="/themes/folium/tree_small.png" data-high-res="/themes/folium/tree.png" alt="Lone Redwood" style="margin-left: 7px;" />';
					}
					?>
				</a>
			</div>

			<?php if(has_menu_items()): ?>
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse-menu">
				<span class="sr-only">Toggle navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<div class="collapse navbar-collapse" id="navbar-collapse-menu">
				<ul class="nav navbar-nav navbar-right">
					<?php while(menu_items()): ?>
					<li<?php echo (menu_active() ? ' class="active"' : ''); ?>><a href="<?php echo menu_url(); ?>" title="<?php echo menu_title(); ?>"><?php echo menu_name(); ?></a></li>
					<?php endwhile; ?>
					<li><a href="#" title="Search..." data-target="#search"><span class="glyphicon glyphicon-search"></span></a></li>
				</ul>
			</div>
			<?php endif; ?>
		</nav>
	</header>
	<?php if (site_meta('sidebar',1) && page_title("") != "List") { ?>
	<aside>
		<p class="description">
			<?php echo site_description(); ?>
		</p>
		<?php
			if (admin()) {
				$items = Query::table(Base::table('posts'))
					->sort('created')
					->get();
			} else {
				$items = Query::table(Base::table('posts'))
					->where('status', '=', 'published')
					->sort('created')
					->get();
			}
			$previousYear = "";
			$page = Registry::get('posts_page');
		?>
		<div id="previousPosts">
		<?php
			for ($i = count($items) - 1; $i >= 0; $i--) {
				$item = $items[$i];
				$currYear = date('Y', strtotime($item->created));
				if ($currYear !== $previousYear) {
					if ($previousYear != "") {
						echo "</ul>";
					}
					echo "<p class='year'>{$currYear}</p>";
					echo "<ul>";
					$previousYear = $currYear;
				}
				$suffix = "";
				if ($item->status != 'published') {
					$suffix = " <span class='glyphicon' style='font-size:0.7em;'>&#xe033;</span>";
				}
				echo "<li><a href='" . base_url($page->slug . '/' . $item->slug) . "' title='" . $item->title . "'>" . $item->title . "$suffix</a></li>";
			}
		?>
		</div>
		
	</aside>
	<?php } ?>