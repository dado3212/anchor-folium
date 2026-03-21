<?php theme_include('partial/header'); ?>

	<main class="container padding-container">

    <div class="listHeading">
      <h2>Snippets</h2>
      <p>For bite-sized posts that don't warrant a full writeup.</p>
			<hr class="fleuron" />
    </div>

		<?php
			function renderPostLink($page, $item) {
				$itemDate = date('F j, Y', strtotime($item->created));
				$suffix = "";
				$suffixClass = '';
				if ($item->status != 'published') {
					$suffixClass = ' unpublished';
				}
				echo "<div class='post'>
          <div class='title{$suffixClass}'><a class='articleLink' href='" . base_url($page->slug . '/' . $item->slug) . "' title='" . $item->title . "'>" . $item->title . "$suffix</a></div>
          <div class='date'>" . $itemDate . "</div>
        </div>";
			}
			if (admin()) {
        $items = Query::table(Base::table('posts'))
          ->left_join(Base::table('post_meta'), Base::table('post_meta.post'), '=', Base::table('posts.id'))
          ->where(Base::table('post_meta.extend'), '=', '4') // this is "is_snippet"
          ->where(Base::table('post_meta.data'), '=', '{"boolean":true}')
          ->sort('created')
          ->get();
			} else {
				$items = Query::table(Base::table('posts'))
          ->left_join(Base::table('post_meta'), Base::table('post_meta.post'), '=', Base::table('posts.id'))
          ->where(Base::table('post_meta.extend'), '=', '4') // this is "is_snippet"
          ->where(Base::table('post_meta.data'), '=', '{"boolean":true}')
          ->where(Base::table('posts.status'), '=', 'published')
          ->sort('created')
          ->get();
			}
			$page = Registry::get('posts_page');
		?>
		<div id="previousPosts">
		<?php
			for ($i = count($items) - 1; $i >= 0; $i--) {
				$item = $items[$i];
				renderPostLink($page, $item);
			}
		?>
		</div>
		<style>
			#previousPosts .year {
				display: flex;
				align-items: center;
				padding-left: 0px;
			}
			#previousPosts .year .line {
				height: 0;
				border-top: 1px solid color-mix(in srgb, var(--link), white 60%);
				flex: 1;
			}
			#previousPosts .post {
				display: flex;
				flex-direction: column;
				margin-bottom: 4px;
			}
			#previousPosts .post .date {
				font-size: 0.75em;
				font-style: italic;
				color:  var(--secondary-text);
				margin-top: -7px;
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
	</main>

<?php theme_include('partial/footer'); ?>