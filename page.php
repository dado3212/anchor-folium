<?php theme_include('partial/header'); ?>

<main class="container">
	<article>
		<header>
			<h1><?php echo page_title(); ?></h1>
		</header>
		<?php if (page_slug() == 'subscribe') { theme_include('partial/subscribe'); }?>
		<?php echo page_content(); ?>
	</article>
</main>

<?php theme_include('partial/footer'); ?>