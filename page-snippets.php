<?php theme_include('partial/header'); ?>

	<main class="container padding-container">

    <div style="padding: 0 25px 10px 25px;">
      <h2>Snippets</h2>
      <p style="line-height: 28px; margin-bottom: 40px;">For bite-sized posts that don't warrant a full writeup.</p>
			<hr />
    </div>

		<?php
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
				renderArticleLink($page, $item);
			}
		?>
		</div>
	</main>

<?php theme_include('partial/footer'); ?>