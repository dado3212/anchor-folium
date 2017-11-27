<?php theme_include('partial/header'); ?>

<div class="mainWrapper">
	<main class="container">
		<article>
			<?php
				$searchResults = search_string(search_term());
			?>
			<?php if(count($searchResults) > 0): ?>
			<header>
				<h1>Results for <b>&ldquo;<?php echo search_term(); ?>&rdquo;</b></h1>
			</header>

			<ul class="list-unstyled">
				<?php for ($i = count($searchResults) - 1; $i >= 0; $i--) { 
					$item = $searchResults[$i];
				?>
				<li>
					<h2>
						<a href="<?php echo $item['url']; ?>" title="<?php echo $item['title']; ?>">
							<?php echo $item['title']; ?>
						</a>
					</h2>
				</li>
				<?php } ?>
			</ul>
		</article>

		<?php if(has_pagination()): ?>
		<nav class="pagination">
			<div class="wrap">
				<?php echo search_prev(); ?>
				<?php echo search_next(); ?>
			</div>
		</nav>
		<?php endif; ?>

		<?php else: ?>
			<article>
				<header>
					<h1>Sorry...</h1>
				</header>

				<p>Unfortunately, there's no results for &ldquo;<?php echo search_term(); ?>&rdquo;. Did you spell everything correctly?</p>
			</article>
		<?php endif; ?>
	</main>
</div>

<?php theme_include('partial/footer'); ?>