<?php theme_include('partial/header'); ?>

	<main class="container padding-container">
		<?php
			if (user_authed() && user_authed_role() == 'administrator') {
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
					echo "<h3 id='{$currYear}' class='year'>{$currYear}</h3>";
					$previousYear = $currYear;
				}
				renderArticleLink($page, $item);
			}
		?>
		</div>
	</main>

<?php theme_include('partial/footer'); ?>