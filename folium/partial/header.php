<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">

	<?php
		if (preg_match("/(iPhone|iPod|iPad|Android|BlackBerry|Mobile)/i", $_SERVER['HTTP_USER_AGENT'])) {
			?><meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1"><?php
		}
	?>
	<link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Open+Sans:400,600,700&amp;subset=latin,latin-ext">
	<link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Montserrat:400,700">
	<link rel="stylesheet" type="text/css" href="<?php echo theme_url('css/bootstrap.min.css'); ?>">
	<link rel="stylesheet" type="text/css" href="<?php echo theme_url('css/styles.css'); ?>">

	<!-- Captcha -->
	<script src='https://www.google.com/recaptcha/api.js'></script>

	<meta property="og:title" content="<?php echo site_name(); ?>">
	<meta property="og:type" content="website">
	<meta property="og:url" content="<?php echo current_url(); ?>">
	<meta property="og:image" content="<?php echo theme_url('img/og_image.gif'); ?>">
	<meta property="og:site_name" content="<?php echo site_name(); ?>">
	<meta property="og:description" content="<?php echo site_description(); ?>">

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

	<title><?php echo page_title("Page can't be found."); ?> - <?php echo site_name(); ?></title>
</head>
<body>
	<header id="top">
		<nav class="navbar navbar-default">
			<div class="navbar-header">
				<?php if(has_menu_items()): ?>
				<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse-menu">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<?php endif; ?>
				<a href="<?php echo base_url(); ?>" class="navbar-brand">
					<strong><?php echo site_name(); ?></strong>
				</a>
			</div>

			<?php if(has_menu_items()): ?>
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
			$items = Query::table(Base::table('posts'))
				->where('status', '=', 'published')->get();
			$previousMonth = "";
			$page = Registry::get('posts_page');
		?>
		<div id="previousPosts">
		<?php
			for ($i = count($items) - 1; $i >= 0; $i--) {
				$item = $items[$i];
				$currMonth = date('F Y', strtotime($item->created));
				if ($currMonth != $previousMonth) {

					if ($previousMonth != "") { echo "</ul>"; }
					echo "<p class='month'>{$currMonth}</p>";
					echo "<ul>";
					$previousMonth = $currMonth;
				}
				echo "<li><a href='" . base_url($page->slug . '/' . $item->slug) . "' title='" . $item->title . "'>" . $item->title . "</a></li>";
			}
		?>
		</div>
		
	</aside>
	<?php } ?>