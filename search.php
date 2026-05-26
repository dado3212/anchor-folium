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

			<ul class="search-results">
				<?php foreach ($searchResults as $item) { ?>
				<li>
					<h2>
						<a href="<?php echo $item['url']; ?>" title="<?php echo htmlspecialchars($item['title'], ENT_QUOTES); ?>">
							<?php echo $item['title_html'] . ($item['status'] != 'published' ? " <span class='glyphicon' style='font-size:0.7em;'>&#xe033;</span>" : ""); ?>
						</a>
					</h2>
					<div class="meta">
						<time datetime="<?php echo date(DATE_W3C, strtotime($item['date'])); ?>"><?php echo date('F j, Y', strtotime($item['date'])); ?></time>
					</div>
					<?php if ($item['snippet'] !== ''): ?>
					<p class="snippet"><?php echo $item['snippet']; ?></p>
					<?php endif; ?>
				</li>
				<?php } ?>
			</ul>
		</article>

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