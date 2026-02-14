<?php theme_include('partial/header'); ?>

<?php if (admin()) { ?>
	<div id="branchWrapper">
		<canvas id="progressBranch" aria-label="Branch rendering A"></canvas>
	</div>
	<script src="/themes/folium/branch/index.js"></script>
	<style>

		header#top {
			border-bottom: 0px;
			margin-bottom: 8px;
			padding-bottom: 7px;
			box-shadow: none;
		}
		header#top nav {
			border: none;
		}
		main {
			margin-top: 40px;
			position: relative;
		}
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
			margin-top: -121px;
		}
		#branchWrapper canvas {
			margin-left: -35px;
		}
		#branchSidebar {
			position: absolute;
			top: -55px;
    	left: -150px;
		}
	</style>
	<script>
		function initBranches() {
			const main = document.querySelector('main');
			const progressCanvas = document.getElementById("progressBranch");
			const sidebarCanvas = document.getElementById("sidebar");
			if (!main || !progressCanvas || !sidebarCanvas || !window.BranchSceneLibrary) {
				return;
			}

			function mainHeight() {
				const el = document.querySelector('main');
				return el ? el.scrollHeight : 0;
			}

			const branch = window.BranchSceneLibrary.mount(progressCanvas, {
				sceneWidth: document.documentElement.clientWidth,
				sceneHeight: 300,
				rotationDeg: 90,
				trunkWaviness: 0,
				scale: 2,
				leafColorStart: "#d73333",
				leafColorEnd: "#c4af38",
				branches: [],
			});

			const sidebarBranch = window.BranchSceneLibrary.mount(sidebarCanvas, {
				sceneWidth: 300,
				sceneHeight: mainHeight,
				rotationDeg: 0,
				trunkWaviness: 0.5,
				scale: 2,
				leafColorStart: "#d73333",
				leafColorEnd: "#c4af38",
				branches: [],
			});

			let resizeRaf = null;
			function onResize() {
				if (resizeRaf != null) {
					return;
				}
				resizeRaf = requestAnimationFrame(() => {
					resizeRaf = null;
					branch.setSceneSize(document.documentElement.clientWidth, 300);
					sidebarBranch.setSceneSize(300, mainHeight());
				});
			}

			window.addEventListener('resize', onResize, { passive:true });
		}

		if (document.readyState === "loading") {
			document.addEventListener("DOMContentLoaded", initBranches, { once: true });
		} else {
			initBranches();
		}
	</script>
<?php } ?>
<?php if (site_meta('sidebar',1)) { echo "<div class='mainWrapper'>"; } ?>
	<main class="container">
		<?php if (admin()) { ?>
		<div id="branchSidebar">
			<canvas id="sidebar" aria-label="Branch rendering B"></canvas>
		</div>
		<?php } ?>
		<?php if(has_posts()): ?>
			<?php while(posts()): ?>
			<article>
				<?php
				// if (admin()) {
				// 	$og_image = article_custom_field('og_image') ?? "";
				// 	if ($og_image !== "") {
				// 		echo '<img src="' . URI::full($og_image, true) . '" class="post-preview" />';
				// 	}
				// }
				?>
				<header>
					<h1><a href="<?php echo article_url(); ?>"><?php echo article_title(); if (article_status() != 'published') { echo " <span class='glyphicon' style='font-size:0.7em;'>&#xe033;</span>"; } ?></a></h1>
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

		<?php if(has_pagination()): ?>
		<ul class="pager">
			<li class="previous"><?php echo posts_prev(); ?></li>
			<li class="next"><?php echo posts_next(); ?></li>
		</ul>
		<?php endif; ?>
	</main>
<?php if (site_meta('sidebar',1)) { echo "</div>"; } ?>

<?php theme_include('partial/footer'); ?>
