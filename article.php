<?php theme_include('partial/header'); ?>
<?php theme_include('partial/allowlisted') ?>
<?php if (site_meta('sidebar',1)) { echo "<div class='mainWrapper'>"; } ?>
<?php if (article_status() == "published" || admin() || isArticlePublicWithCode()):
	$suffix = "";
	if (article_status() != 'published') {
		$suffix = " <span class='glyphicon' style='font-size:0.7em;'>&#xe033;</span>";
	}
?>
<?php if (admin()) { ?>
	<div id="branchWrapper">
		<canvas id="progressBranch" aria-label="Branch rendering A"></canvas>
	</div>
	<script src="/themes/folium/branch/index.js"></script>
	<style>
		:root {
			/* My gut, unadjusted */
			--text: black;
			--meta-text: #636a66;
			--highlight: rgb(68, 121, 68);
			--link: rgb(105 118 68);
			--visited-link: rgb(37, 65, 37);
			--code-text: rgb(46, 82, 46);
			--code-background: rgb(224, 245, 230);
			--border: #ddd;
			--bg: rgb(253 253 250);
			--secondary-text: rgb(64, 64, 64);
			--header: var(--text);

			/* ROBOT COLORS */
			/* --text: #2f2f1f;
    --meta-text: #6f7357;
    --highlight: #878753;
    --link: #7f8450;
    --visited-link: #5f6338;
    --code-text: #4a4f2f;
    --code-background: #ece6c2;
    --bg: #f4f2e6;
    --secondary-text: #54563f;
    --header: var(--text); */
		}
		#branchWrapper {
			overflow: hidden;
			width: 100%;
			height: 68px;
			display: flex;
			align-items: center;
			position: absolute;
			margin-top: -35px;
			z-index: 1;
		}
		#branchWrapper.fixed {
			position: fixed;
			margin-top: -117px;
		}
		#branchWrapper canvas {
			margin-left: -35px;
		}
	</style>
	<script>
		// Inline this code higher up to enable scroll listening
		// before the full document has loaded for smoother execution

		const branchWrapper = document.getElementById("branchWrapper");
		const branch = window.BranchSceneLibrary.mount(document.getElementById("progressBranch"), {
			sceneWidth: document.documentElement.clientWidth,
			sceneHeight: 300,
			rotationDeg: 90,
			trunkWaviness: 0,
			scale: 2,
			// leafColorStart: "#254125",
			// leafColorEnd: "#447944",

			// leafColorStart: "#7a8b41",
    	// leafColorEnd: "#c2dc8d",

			leafColorStart: "#d73333",
			leafColorEnd: "#c4af38",
			
			// leafColorStart: "#6c6d3b",
      //   leafColorEnd: "#ece6c2",
			branches: [],
		});

		// Listen for scrolling to do the secondary sticky progress bar
		// (do this immediately so it's responsive)
		let isTop = true;
		new IntersectionObserver(([entry]) => {
			isTop = entry.isIntersecting;
			branchWrapper.classList.toggle('fixed', !isTop);
			if (isTop) {
				branch.setTrunkLeafMaxPercent(100);
			}
		}).observe(document.querySelector('nav'));

		function setProgress(p) {
			if (isTop) {
				return;
			}
			p = Math.max(0, Math.min(1, p));
			branch.setTrunkLeafMaxPercent(p * 100);
		}

		function onScroll() {
			const el = document.querySelector('article');
			const footnotes = document.querySelector('.footnotes');
			if (!el) {
				return;
			}
			// Add in a little more padding (80px) because of top height in the window.
			// We're ignoring the footnotes
			const max = el.scrollHeight - window.innerHeight - (footnotes?.scrollHeight ?? 0) + 80;
			const y = Math.max(0, Math.min(max, window.scrollY));
			setProgress(max ? y / max : 0);
		}

		window.addEventListener('scroll', onScroll, { passive:true });
		let resizeRaf = null;
		function onResize() {
			if (resizeRaf != null) {
				return;
			}
			resizeRaf = requestAnimationFrame(() => {
				resizeRaf = null;
				branch.setSceneSize(document.documentElement.clientWidth, 300);
				onScroll();
			});
		}
		window.addEventListener('resize', onResize, { passive:true });
		onScroll();
	</script>
<?php } ?>
	<main class="container">
		<article id="article-<?php echo article_id(); ?>">
			<header>
				<h1><?php echo article_title() . $suffix; ?></h1>
				<div class="meta">
					<time datetime="<?php echo date(DATE_W3C, article_time()); ?>"><?php echo date('F j, Y', article_time()); ?></time>
					<?php if (admin()) {
						$category = article_category();
						echo '<a class="tag" href="'. article_category_url() . '">' . $category . '</a>';
					} ?>
				</div>
			</header>
			<?php
			libxml_use_internal_errors(true); 
			$dom = new DOMDocument();
			@$dom->loadHTML('<!DOCTYPE html><meta charset="UTF-8">' . article_markdown(), LIBXML_NOERROR | LIBXML_NOWARNING);
			libxml_clear_errors();
			$xpath = new DOMXpath($dom);
			$headers = $xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6');

			if ($headers->length > 1) {
				$button = '<button class="trigger" aria-label="Table of Contents" onclick="(function(){$(\'.table-of-contents\').toggle();})();">';
				$contents = '<div class="table-of-contents" style="display: none;"><div><span class="contents">CONTENTS</span><button id="top-jump" aria-label="Jump to top of article">&#10548;&#xFE0E;</button></div>';
				
				for ($i = 0; $i < $headers->length; $i++) {
					$header = $headers->item($i);
					$level = substr($header->nodeName, 1); // Extract the number from "h1", "h2", etc.
					$innerHTML = '';
					foreach ($header->childNodes as $child) {
						$innerHTML .= $header->ownerDocument->saveHTML($child);
					}

					$id = $header->getAttribute('id');

					if ($i == 0) {
						$button .= '<span class="active header' . $level . '" attr="' . $id . '"></span>';
					} else {
						$button .= '<span class="header' . $level . '" attr="' . $id . '"></span>';
					}
					
					$contents .= '<div class="link header' . $level . '" data-id="' . $id . '">' . $innerHTML . '</div>';
				}

				echo $button . '</button>' . $contents . '</div>';
				?>
				<script>
					$(document).ready(function() {
						// Click to link
						$('.table-of-contents .link').click(function() {
							const id = '#' + $(this).data('id');
							$(id).get(0).scrollIntoView({ behavior: 'smooth' });
							history.replaceState(null, '', id);
						});
						// Click to go to top of article
						$('.table-of-contents #top-jump').click(function() {
							$('header').get(0).scrollIntoView({ behavior: 'smooth' });
							history.replaceState(null, '', '');
						});
						// Automatically close it if the window is open
						document.body.addEventListener('click', function (event) {
							const tableOfContents = document.querySelector('.table-of-contents');
							if (
								window.getComputedStyle(tableOfContents).display !== 'none' &&
								!tableOfContents.contains(event.target) &&
								!document.querySelector('button.trigger').contains(event.target)
							) {
								tableOfContents.style.display = 'none';
							}
						});
						// Handle header updating
						const observer = new IntersectionObserver(
							(entries) => {
								entries.forEach((entry) => {
									if (entry.isIntersecting) {
										// Mark it
										$('.trigger span').removeClass('active');
										$('.trigger span[attr=' + entry.target.id + ']').addClass('active');
										
										$('.table-of-contents .link').removeClass('active');
										$('.table-of-contents .link[data-id=' + entry.target.id + ']').addClass('active');
									}
								});
							},
							{ threshold: 0 } // Trigger when the entire thing changes on/off screen
						);
						document.querySelectorAll('article h2, article h3, article h4, article h5, article h6').forEach((i) => {
							if (i) {
								observer.observe(i);
							}
						});
						const mobileToc = document.querySelector('button.trigger');
						(new IntersectionObserver(
							([entry]) => {
								mobileToc.classList.toggle('mobile-show', !entry.isIntersecting);
							},
							{ threshold: 0 }
						)).observe(document.querySelector('body header'));
					});
				</script>
				<?php
			}
		?>
			<?php echo article_markdown(); ?>
			<?php if (admin()) { 
				echo "<a href='/admin/posts/edit/" . article_id() . "' target='_blank'>Edit Article</a>"; 
			} ?>
		</article>

		<?php if(has_comments()): ?>
		<section id="comments">
			<h2><?php echo total_comments(article_id()); ?> comments</h2>
			<ul class="commentlist">
				<?php $i = 0; while(comments()): $i++; ?>
				<li>
					<header>
						<h2 data-id="<?php echo $i; ?>"><?php echo comment_name(); ?></h2>
						<time datetime="<?php echo date(DATE_W3C, comment_time()); ?>"><?php echo date('M j, Y - g:i a', comment_time()); ?></time>
					</header>

					<p><?php echo comment_text(); ?></p>
				</li>
				<?php endwhile; ?>
			</ul>
		</section>
		<?php endif; ?>

		<?php if(comments_open()): ?>
		<form id="comment" class="form-horizontal" role="form" method="post" action="<?php echo comment_form_url(); ?>#comment">
			<?php echo comment_form_notifications(); ?>

			<?php echo comment_form_input_name('placeholder="Your name" class="form-control"'); ?>
			<?php echo comment_form_input_email('placeholder="Your email (won’t be published)" class="form-control"'); ?>
			<?php echo comment_form_input_text('placeholder="Your comment" class="form-control"'); ?><br>
			<!-- Captcha (site key must be modified for your uses) -->
			<div class="g-recaptcha" data-sitekey="6Lcg7iIUAAAAAPLDHLmj5YgjkJfH_RJz4h4ZyAAB"></div>
			<button class="btn btn-default">Post Comment</button>
		</form>
		<!-- Captcha -->
		<script>
		let recaptchaLoaded = false;

		function loadRecaptcha() {
			if (recaptchaLoaded) return;
			recaptchaLoaded = true;

			const script = document.createElement('script');
			script.src = 'https://www.google.com/recaptcha/api.js';
			script.async = true;
			script.defer = true;
			document.head.appendChild(script);
		}

		document.querySelectorAll('#comment input').forEach(input => {
			input.addEventListener('focus', loadRecaptcha, { once: true });
		});
		</script>
		<?php endif;
		if (article_status() == 'published') {
		$is_snippet = article_custom_field('is_snippet');
		$posts = Query::table(Base::table('posts'));
		if ($is_snippet) {
			$posts = $posts->left_join(Base::table('post_meta'), Base::table('post_meta.post'), '=', Base::table('posts.id'))
				->where(Base::table('post_meta.extend'), '=', '4') // this is "is_snippet"
				->where(Base::table('post_meta.data'), '=', '{"boolean":true}');
		} else {
			$posts = $posts->left_join(Base::table('post_meta'), 'anchor_post_meta.extend` = "4" and `anchor_post_meta.post', '=', Base::table('posts.id'))
				->where('anchor_post_meta.data` IS NULL OR `anchor_post_meta.data', '=', '{"boolean":false}');
		}
		if (!admin()) {
			$posts = $posts->where('status', '=', 'published');
		}
		$posts = $posts->sort(Base::table('posts.created'), 'desc')
			->get(array(Base::table('posts.*')));
		$page = Registry::get('posts_page');
		?>
		<div id="previousPosts" class="page">
		<?php
		$previousPost = null;
		$nextPost = null;
		$recentPosts = [];
		$foundCurrentPost = false;
		$currentArticleId = article_id();
		
		foreach ($posts as $post) {
			if (count($recentPosts) < 5) {
				$recentPosts[] = $post; // Collect the 5 most recent posts
			}

			if ($post->id === $currentArticleId) {
				$foundCurrentPost = true;
				continue;
			}
		
			if (!$foundCurrentPost) {
				$nextPost = $post; // The last post before the current one in descending order
			} elseif ($foundCurrentPost && !$previousPost) {
				$previousPost = $post; // The first post after the current one in descending order
			}
		}
		if ($nextPost) {
			echo $is_snippet ? "<h3 class='year'>Next snippet</h3>" : "<h3 class='year'>Next</h3>";
			renderArticleLink($page, $nextPost);
		}
		if ($previousPost) {
			echo $is_snippet ? "<h3 class='year'>Previous snippet</h3>" : "<h3 class='year'>Previous</h3>";
			renderArticleLink($page, $previousPost);
		}
		if (count($recentPosts) > 0) {
			echo $is_snippet ? "<h3 class='year'>Recent snippets</h3>" : "<h3 class='year'>Recent</h3>";
			foreach ($recentPosts as $recentPost) {
				renderArticleLink($page, $recentPost);
			}
			if ($is_snippet) {
				echo "<a class='articleLink' href='/snippets' title='View all snippets'><b>View all snippets</b></a><a class='articleLink' href='/list' title='View all posts'><b>View all posts</b></a>";
			} else {
				echo "<a class='articleLink' href='/list' title='View all'><b>View all</b></a>";
			}
		}
		?>
		</div>
		<?php } ?>
	</main>
<?php else: ?>
	<main class="container">
		<article>
			<header>
				<h1>Sorry...</h1>
			</header>

			<p>Unfortunately, there's no page for that slug. Did you spell everything correctly?</p>
		</article>
	</main>
<?php endif; ?>
<?php if (site_meta('sidebar',1)) { echo "</div>"; } ?>

<script type="text/javascript" id="MathJax-script" defer src="https://cdn.jsdelivr.net/npm/mathjax@4/tex-mml-chtml.js"></script>
<style>
	/** Fallback inline styles for prism.js */
	pre:not([class]) > code[class^="language-"] {
		display: block;
		background: #272822;
		color: #f8f8f2;
		text-shadow: 0 1px rgba(0, 0, 0, .3);
		font-family: Consolas,Monaco,'Andale Mono','Ubuntu Mono',monospace;
		font-size: 0.75em;
		line-height: 1.5;
		white-space: pre;
		overflow-x: auto;

		padding: 38.5px 18px 13.5px 18px;
    margin: 20px -10px;
    border-radius: 7px;
	}

	pre:not([class])[data-line] {
		padding: 0;
	}
</style>
<script src="<?php echo theme_url('js/prism.js'); ?>"></script>
<script>
	// Used for https://blog.alexbeals.com/posts/debugging-fitness-sf-qr for Cherri code 
	Prism.languages.cherri = Prism.languages.javascript;
</script>

<?php theme_include('partial/footer'); ?>
