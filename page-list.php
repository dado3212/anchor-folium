<?php theme_include('partial/header'); ?>

	<main class="container padding-container">
		<div class="listHeading">
      <h2>Posts</h2>
    </div>

		<?php
			$items = Query::table(Base::table('posts'))
				->left_join(Base::table('post_meta'), 'anchor_post_meta.extend` = "4" and `anchor_post_meta.post', '=', Base::table('posts.id'))
				->where('anchor_post_meta.data` IS NULL OR `anchor_post_meta.data', '=', '{"boolean":false}');
			if (!admin()) {
				$items = $items->where('status', '=', 'published');
			}
			$items = $items->sort('created')->get(array(Base::table('posts.*')));
			$previousYear = "";
			$page = Registry::get('posts_page');
		?>
		<div id="previousPosts">
		<?php
			function renderPostLink($page, $item) {
				$itemDate = date('F j', Date::format($item->created, 'U'));
				$suffixClass = '';
				if ($item->status != 'published') {
					$suffixClass = ' unpublished';
				}
				$categoriesHtml = '';
				if (admin()) {
					$cats = Post::categories($item->id);
					// Remove uncategorized
					$cats = array_filter($cats, function ($cat) { return $cat->id !== 1; });
					if ($cats) {
						$strings = [];
						foreach ($cats as $cat) {
							$strings[] = '<a class="tag" href="' . base_url('category/' . $cat->slug) . '">' . $cat->title . '</a>';
						}
						$categoriesHtml = '<span class="delimiter">𐫱&lrm;</span>' . implode(' · ', $strings);
					}
					echo "<div class='post'>
						<div class='title'><a class='articleLink{$suffixClass}' href='" . base_url($page->slug . '/' . $item->slug) . "' title='" . $item->title . "'>" . $item->title . "</a></div>
						<div class='meta'><span class='date'>" . $itemDate . '</span>' . $categoriesHtml . "</div>
					</div>";
				} else {
					echo "<div class='post'>
						<div class='title'><a class='articleLink{$suffixClass}' href='" . base_url($page->slug . '/' . $item->slug) . "' title='" . $item->title . "'>" . $item->title . "</a></div>
						<div class='meta'><span class='date'>" . $itemDate . '</span>' . $categoriesHtml . "</div>
					</div>";
				}
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
			if (admin()) { ?>
				<style>
					/* .post {
						position: relative;
					}
					.post .date {
						position: absolute;
						top: 14px;
						left: -110px;
						width: 100px;
						text-align: right;
						font-size: 0.7em;
						color: var(--secondary-text);
						font-style: italic;
					} */
					.post .tag {
						color: var(--secondary-text);
						font-variant: small-caps;
						letter-spacing: 1px;

						font-size: 1em;
						margin-top: -2px;

						text-decoration: none;
					}
				</style>
			<?php }
		?>
		</div>
	</main>
	<!-- TODO: Move this into the main.css file -->
	<style>
		#previousPosts .year {
			display: flex;
    	align-items: center;
			padding-left: 0px;
		}
		#previousPosts .yearText {
			font-size: 0.7em;
			font-weight: 500;
			letter-spacing: 0.25em;
			padding: 0 16px;
		}
		#previousPosts .year .line {
			height: 0;
			border-top: 1px solid var(--border);
			flex: 1;
		}
		#previousPosts .post {
			display: flex;
			flex-direction: column;
			margin-bottom: 4px;
		}
		#previousPosts .post .meta {
			font-size: 0.75em;
			color: var(--secondary-text);
			margin-top: -7px;
		}
		#previousPosts .post .meta .date {
			font-style: italic;
		}
		.articleLink {
			font-size: 1em;
			font-weight: 400;
			color: var(--text);
			cursor: pointer;
			transition: color 0.2s;
			align-items: center;
			padding: 10px 0;
		}
		@media (hover: hover) and (pointer: fine) {
			.articleLink:hover {
					background-color: transparent;
					text-decoration: inherit;
			}
		}
		</style>

<?php theme_include('partial/footer'); ?>