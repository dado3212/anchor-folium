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
	<?php
		// Snippets
		function renderPostLink($page, $item) {
			$itemDate = date('F j, Y', strtotime($item->created));
			$suffixClass = '';
			if ($item->status != 'published') {
				$suffixClass = ' unpublished';
			}
			echo "<div class='snippet-item'><div class='snippet-wrapper'>
      <span class='snippet-bullet'>❧</span>
      <div>
        <div class='title'><a class='articleLink{$suffixClass}' href='" . base_url($page->slug . '/' . $item->slug) . "' title='" . $item->title . "'>" . $item->title . "</a></div>
        <div class='date'>" . $itemDate . "</div>
      </div>
			</div>
    </div>";
		}
		if (admin()) {
			$items = Query::table(Base::table('posts'))
				->left_join(Base::table('post_meta'), Base::table('post_meta.post'), '=', Base::table('posts.id'))
				->where(Base::table('post_meta.extend'), '=', '4') // this is "is_snippet"
				->where(Base::table('post_meta.data'), '=', '{"boolean":true}')
				->sort('created', 'desc')
				->take(8)
				->get();
		} else {
			$items = Query::table(Base::table('posts'))
				->left_join(Base::table('post_meta'), Base::table('post_meta.post'), '=', Base::table('posts.id'))
				->where(Base::table('post_meta.extend'), '=', '4') // this is "is_snippet"
				->where(Base::table('post_meta.data'), '=', '{"boolean":true}')
				->where(Base::table('posts.status'), '=', 'published')
				->sort('created', 'desc')
				->take(8)
				->get();
		}
		$page = Registry::get('posts_page');
		
		?>
		<hr class="fleuron alt" style="margin: 40px 0;" />
		<div class="listHeading">
      <h2>Snippets</h2>
    </div>
		<div class="snippets-grid">
		<?php
			for ($i = 0; $i < count($items); $i++) {
				$item = $items[$i];
				renderPostLink($page, $item);
			}
		?>
		</div>
		<a class='articleLink viewAllSnippets' href='<?php echo base_url('snippets') ?>' title=''>— view all snippets —</a>
		<style>
			.snippets-grid {
				display: grid;
				grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
				gap: 0;
			}
			.snippet-bullet {
				font-size: 1em;
				color: var(--link);
				flex-shrink: 0;
				line-height: 1;
				position: relative;
				top: -1px;
			}
			.snippets-grid .year {
				display: flex;
				align-items: center;
				padding-left: 0px;
			}
			.snippets-grid .year .line {
				height: 0;
				border-top: 1px solid color-mix(in srgb, var(--link), white 60%);
				flex: 1;
			}
			.snippet-item {
				padding: 0.55rem 0.75rem;
				border-bottom: 1px solid var(--border);
				display: flex;
    		align-items: center;
			}

			.snippet-item:nth-child(odd) { border-right: 1px solid var(--border); padding-left: 0; }
			.snippet-item:nth-child(even) { padding-right: 0; }
			.snippet-item:nth-last-child(-n+2) { border-bottom: none; }

			@media (max-width: 600px) {
				.container {
					grid-template-columns: 1fr;
				}
			}

			.snippet-wrapper {
				display: flex;
				align-items: baseline;
				gap: 8px;
			}
				
			.snippets-grid .snippet-item .date {
				font-size: 0.75em;
				font-style: italic;
				color:  var(--secondary-text);
				margin-top: 2px;
			}
			.articleLink {
				font-size: 1em;
				font-weight: 400;
				color: var(--text);
				cursor: pointer;
				transition: color 0.2s;
				align-items: center;
				padding: 0;
			}
			@media (hover: hover) and (pointer: fine) {
				.articleLink:hover {
						background-color: transparent;
						text-decoration: inherit;
				}
			}
			.viewAllSnippets {
				margin: 20px 0 50px 0;
				display: flex;
				justify-content: center;
				font-size: 0.9em;
				color: var(--secondary-text);
			}
			main article:last-of-type {
				border-bottom: none;
				padding-bottom: 0px;
			}
			.listHeading h2 {
				font-size: 1.5em;
			}
		</style>
</main>

<?php theme_include('partial/footer'); ?>
