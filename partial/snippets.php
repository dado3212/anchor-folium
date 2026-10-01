<?php
	// Snippets grid. On a category page, shows every snippet in that category;
	// otherwise (home) shows the 8 most recent with a link to /snippets.
	$category = Registry::get('post_category');

	$query = Query::table(Base::table('posts'))
		->left_join(Base::table('post_meta'), Base::table('post_meta.post'), '=', Base::table('posts.id'))
		->where(Base::table('post_meta.extend'), '=', '4') // this is "is_snippet"
		->where(Base::table('post_meta.data'), '=', '{"boolean":true}');
	if (!admin()) {
		$query->where(Base::table('posts.status'), '=', 'published');
	}
	if ($category) {
		$query->join(Base::table('post_categories'), Base::table('post_categories.post'), '=', Base::table('posts.id'))
			->where(Base::table('post_categories.category'), '=', $category->id);
	} else {
		$query->take(8);
	}
	$items = $query->sort(Base::table('posts.created'), 'desc')->get(array(Base::table('posts.*')));

	if (count($items) == 0) {
		return;
	}
	$page = Registry::get('posts_page');
?>
		<hr class="fleuron alt" style="margin: 40px 0;">
		<div class="listHeading">
      <h2>Snippets</h2>
    </div>
		<div class="snippets-grid<?php if (count($items) < 3) { echo ' single'; } ?>">
		<?php foreach ($items as $item) {
			$itemDate = date('F j, Y', strtotime($item->created));
			$suffixClass = '';
			if ($item->status != 'published') {
				$suffixClass = ' unpublished';
			}
			echo "<div class='snippet-item'><div class='snippet-wrapper'>
      <span class='snippet-bullet'>❧</span>
      <div>
        <div class='title'><a class=\"articleLink{$suffixClass}\" href=\"" . base_url($page->slug . '/' . $item->slug) . "\" title=\"" . $item->title . "\">" . $item->title . "</a></div>
        <div class='date'>" . $itemDate . "</div>
      </div>
			</div>
    </div>";
		} ?>
		</div>
		<?php if (!$category): ?>
		<a class='articleLink viewAllSnippets' href='<?php echo base_url('snippets') ?>' title=''>— view all snippets —</a>
		<?php else: ?>
		<div class="snippetsEnd"></div>
		<?php endif; ?>
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
			/* Last row: the final item, plus the one before it if they share a row */
			.snippet-item:last-child,
			.snippet-item:nth-child(odd):nth-last-child(2) { border-bottom: none; }

			.snippets-grid.single { grid-template-columns: minmax(0, 1fr); }
			.snippets-grid.single .snippet-item { border-right: none; padding-left: 0; padding-right: 0; }
			.snippets-grid.single .snippet-item:not(:last-child) { border-bottom: 1px solid var(--border); }

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
			main article:last-of-type {
				padding-bottom: 0px;
			}
			.viewAllSnippets {
				margin: 20px 0 50px 0;
				display: flex;
				justify-content: center;
				font-size: 0.9em;
				color: var(--secondary-text);
			}
			.snippetsEnd {
				margin-bottom: 50px;
			}
			.listHeading h2 {
				font-size: 1.5em;
			}
		</style>
