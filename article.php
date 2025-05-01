<?php theme_include('partial/header'); ?>
<?php if (site_meta('sidebar',1)) { echo "<div class='mainWrapper'>"; } ?>
<?php if (article_status() == "published" || admin()):
	$suffix = "";
	if (article_status() != 'published') {
		$suffix = " <span class='glyphicon' style='font-size:0.7em;'>&#xe033;</span>";
	}
?>
	<main class="container">
		<article id="article-<?php echo article_id(); ?>">
			<header>
				<h1><?php echo article_title() . $suffix; ?></h1>
				<div class="meta">
					<time datetime="<?php echo date(DATE_W3C, article_time()); ?>"><?php echo date('F j, Y', article_time()); ?></time>
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
				$button = '<button class="trigger" onclick="(function(){$(\'.table-of-contents\').toggle();})();">';
				$contents = '<div class="table-of-contents" style="display: none;"><span class="contents">CONTENTS</span>';
				
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
		if (admin()) {
			$posts = Query::table(Base::table('posts'))
				->sort('created', 'desc')
				->get();
		} else {
			$posts = Query::table(Base::table('posts'))
				->where('status', '=', 'published')
				->sort('created', 'desc')
				->get();
		}
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
			echo "<h3 class='year'>Next</h3>";
			renderArticleLink($page, $nextPost);
		}
		if ($previousPost) {
			echo "<h3 class='year'>Previous</h3>";
			renderArticleLink($page, $previousPost);
		}
		if (count($recentPosts) > 0) {
			echo "<h3 class='year'>Recent</h3>";
			foreach ($recentPosts as $recentPost) {
				renderArticleLink($page, $recentPost);
			}
			echo "<a class='articleLink' href='/list' title='View all'><b>View all</b></a>";
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

<?php theme_include('partial/footer'); ?>