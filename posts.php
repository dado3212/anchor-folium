<?php theme_include('partial/header'); ?>

<?php if (site_meta('sidebar',1)) { echo "<div class='mainWrapper'>"; } ?>
	<main class="container">
		<?php if(has_posts()): ?>
			<?php while(posts()): ?>
			<article>
				<header>
					<h1><a href="<?php echo article_url(); ?>"><?php echo article_title(); if (article_status() != 'published') { echo " <span class='glyphicon' style='font-size:0.7em;'>&#xe033;</span>"; } ?></a></h1>
					<div class="meta">
						<time datetime="<?php echo date(DATE_W3C, article_time()); ?>"><?php echo date('F j, Y', article_time()); ?></time>
					</div>
				</header>

				<?php echo split_content(article_markdown()); ?>
				<p><a href="<?php echo article_url(); ?>" rel="article">Read More</a></p>
			</article>
			<?php endwhile; ?>
		<?php else: ?>
			<p>Looks like you have some writing to do!</p>
		<?php endif; ?>

		<?php if(has_pagination()): ?>
		<ul class="pager">
			<li class="previous"><?php echo posts_prev(); ?></li>
			<li class="next"><?php echo posts_next(); ?></li>
		</ul>
		<?php endif; ?>
	</main>
<?php if (site_meta('sidebar',1)) { echo "</div>"; } ?>

<?php theme_include('partial/footer'); ?>