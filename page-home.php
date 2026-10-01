<?php
if (Uri::current() !== '/') {
  header('Location: /', true, 301); exit;
}
?>
<?php 
// Load up all of the posts information
$per_page = Config::meta('home_posts_per_page');
list($total, $posts) = Post::listing(null, 1, $per_page);
$posts = new Items($posts);

Registry::set('posts', $posts);
Registry::set('total_posts', $total);
Registry::set('page_offset', 1);

theme_include('partial/header');
?>

<main class="container">

<?php if (has_posts()): ?>
	<?php while (posts()): ?>
		<article>
			<header>
				<h1><a <?php if (article_status() != 'published') { echo " class='unpublished'"; } ?> href="<?php echo article_url(); ?>"><?php echo article_title(); ?></a></h1>
				<div class="meta">
					<time datetime="<?php echo date(DATE_W3C, article_time()); ?>"><?php echo date('F j, Y', article_time()); ?></time>
				</div>
			</header>

			<?php 
				$article_description = article_description();
				if ($article_description) {
					echo parse($article_description);
				} else {
					echo get_description(article_markdown());
				}
			?>
			<p><a href="<?php echo article_url(); ?>" rel="article">Read More</a></p>
		</article>
		<?php endwhile; ?>
	<?php else: ?>
		<p>Looks like you have some writing to do!</p>
	<?php endif; ?>

	<?php if($total > $per_page): ?>
	<?php $posts_page_obj = Registry::get('posts_page'); ?>
	<div class="pagination">
		<div class="count"><span>—</span><span class="num">1</span><span>—</span></div>
		<span class="older"><a href="<?php echo base_url($posts_page_obj->slug . '/2?home=1'); ?>">Older posts</a></span>
	</div>
	<?php endif; ?>
	<?php theme_include('partial/snippets'); ?>
</main>

<?php theme_include('partial/footer'); ?>
