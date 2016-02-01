<?php theme_include('partial/header'); ?>

	<main class="container">
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
	</main>

<?php theme_include('partial/footer'); ?>