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
			function renderPostLink($page, $item) {
				$itemDate = date('F j', strtotime($item->created));
				$suffix = "";
				$suffixClass = '';
				if ($item->status != 'published') {
					$suffixClass = ' unpublished';
				}
				echo "<div class='post'>
          <div class='title${suffixClass}'><a class='articleLink' href='" . base_url($page->slug . '/' . $item->slug) . "' title='" . $item->title . "'>" . $item->title . "$suffix</a></div>
          <div class='date'>" . $itemDate . "</div>
        </div>";
			}

			for ($i = count($items) - 1; $i >= 0; $i--) {
				$item = $items[$i];
				$currYear = date('Y', strtotime($item->created));
				if ($currYear !== $previousYear) {
					echo "<h3 id='{$currYear}' class='year'><span class='line'></span><span class='yearText'>{$currYear}</span><span class='line'></span></h3>";
					$previousYear = $currYear;
				}
				renderPostLink($page, $item);
			}
		?>
		</div>
	</main>
	<!-- TODO: Move this into the main.css file -->
	<style>
		#previousPosts .year {
			display: flex;
    	align-items: center;
		}
		#previousPosts .yearText {
			font-size: 0.7em;
			font-weight: 500;
			letter-spacing: 0.25em;
			color: var(--bk-muted);
			padding: 0 16px;
		}
		#previousPosts .year .line {
			height: 0;
			border-top: 1px solid color-mix(in srgb, var(--link), white 60%);
			flex: 1;
		}
		#previousPosts .post {
			display: flex;
			flex-direction: column;
			align-items: center;
			margin-bottom: 4px;
		}
		#previousPosts .post .date {
			font-size: 0.75em;
			font-style: italic;
			color: #888;
			margin-top: -8px;
		}
		#previousPosts .post .title.unpublished {
			text-decoration: line-through;
		}
		.articleLink {
			font-size: 1em;
			font-weight: 400;
			color: var(--bk-text);
			cursor: pointer;
			transition: color 0.2s;
			align-items: center;
			text-align: center;
		}
		@media (hover: hover) and (pointer: fine) {
			.articleLink:hover {
					background-color: transparent;
					text-decoration: inherit;
			}
		}
		</style>

<?php theme_include('partial/footer'); ?>