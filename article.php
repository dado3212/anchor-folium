<?php theme_include('partial/header'); ?>
<?php if (site_meta('sidebar',1)) { echo "<div class='mainWrapper'>"; } ?>
<?php if (article_status() == "published" || (user_authed() && user_authed_role() == 'administrator')):
	$suffix = "";
	if (article_status() != 'published') {
		$suffix = " <span class='glyphicon' style='font-size:0.7em;'>&#xe033;</span>";
	}
?>
	<main class="container">
		<article id="article-<?php echo article_id(); ?>">
			<header>
				<h1><?php echo article_title() . $suffix; ?></h1>
				<div class="meta">
						<time datetime="<?php echo date(DATE_W3C, article_time()); ?>"><?php echo date('F j, Y', article_time()); ?></time>
					</div>
			</header>

			<?php echo article_markdown(); ?>
			<?php if (user_authed() && user_authed_role() == 'administrator') { 
				echo "<a href='/admin/posts/edit/" . article_id() . "' target='_blank'>Edit Article</a>"; 
			} ?>
		</article>

		<?php if(has_comments()): ?>
		<section id="comments">
			<h2><?php echo total_comments(article_id()); ?> comments</h2>
			<ul class="commentlist">
				<?php $i = 0; while(comments()): $i++; ?>
				<li>
					<header>
						<h2 data-id="<?php echo $i; ?>"><?php echo comment_name(); ?></h2>
						<time datetime="<?php echo date(DATE_W3C, comment_time()); ?>"><?php echo date('M j, Y - g:i a', comment_time()); ?></time>
					</header>

					<p><?php echo comment_text(); ?></p>
				</li>
				<?php endwhile; ?>
			</ul>
		</section>
		<?php endif; ?>

		<?php if(comments_open()): ?>
		<form id="comment" class="form-horizontal" role="form" method="post" action="<?php echo comment_form_url(); ?>#comment">
			<?php echo comment_form_notifications(); ?>

			<?php echo comment_form_input_name('placeholder="Your name" class="form-control"'); ?>
			<?php echo comment_form_input_email('placeholder="Your email (won’t be published)" class="form-control"'); ?>
			<?php echo comment_form_input_text('placeholder="Your comment" class="form-control"'); ?><br>
			<!-- Captcha (site key must be modified for your uses) -->
			<div class="g-recaptcha" data-sitekey="6Lcg7iIUAAAAAPLDHLmj5YgjkJfH_RJz4h4ZyAAB"></div>
			<button class="btn btn-default">Post Comment</button>
		</form>
		<?php endif; ?>
	</main>
<?php else: ?>
	<main class="container">
		<article>
			<header>
				<h1>Sorry...</h1>
			</header>

			<p>Unfortunately, there's no page for that slug. Did you spell everything correctly?</p>
		</article>
	</main>
<?php endif; ?>
<?php if (site_meta('sidebar',1)) { echo "</div>"; } ?>

<?php theme_include('partial/footer'); ?>