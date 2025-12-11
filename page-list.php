<?php theme_include('partial/header'); ?>

	<main class="container padding-container">
		<?php
			$items = Query::table(Base::table('posts'))
				->left_join(Base::table('post_meta'), 'anchor_post_meta.extend` = "4" and `anchor_post_meta.post', '=', Base::table('posts.id'))
				->where('anchor_post_meta.data` IS NULL OR `anchor_post_meta.data', '=', '{"boolean":false}');
			if (!admin()) {
				$items = $items->where('status', '=', 'published');
			}
			$items = $items->sort('created')->get();
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