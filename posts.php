<?php theme_include('partial/header'); ?>

<?php if (admin()) { ?>
	<div id="topBranch">
		<canvas id="progressBranch"></canvas>
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
		#topBranch.fixed {
			position: fixed;
			margin-top: -121px;
		}
		#branchSidebar {
			position: absolute;
			bottom: -5px;
    	left: -300px;
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
				return el ? el.offsetHeight + 140 : 0; // + 45 : 0;
			}

			const branch = window.BranchSceneLibrary.mount(progressCanvas, {
				sceneWidth: document.documentElement.clientWidth * 0.6,
				sceneHeight: 300,
				rotationDeg: 90,
				trunkWaviness: Math.random() * 0.3,
				scale: 2,
				leafColorStart: getComputedStyle(document.documentElement).getPropertyValue('--leafStart').trim(),
				leafColorEnd: getComputedStyle(document.documentElement).getPropertyValue('--leafEnd').trim(),
				branches: [],
			});

			const sidebarBranch = window.BranchSceneLibrary.mount(sidebarCanvas, {
				sceneWidth: 350,
				sceneHeight: mainHeight,
				rotationDeg: 0,
				trunkWaviness: 1.2,
				scale: 2,
				leafColorStart: getComputedStyle(document.documentElement).getPropertyValue('--leafStart').trim(),
				leafColorEnd: getComputedStyle(document.documentElement).getPropertyValue('--leafEnd').trim(),
				branches: [],
			});

			function updateSidebarBranchesFromHeadings() {
				const el = document.querySelector('main');
				if (!el || !sidebarBranch || !sidebarBranch.setBranches) {
					return;
				}
				const h1s = Array.from(el.querySelectorAll('article h1'));
				const totalHeight = Math.max(1, el.scrollHeight);
				const branchSpecs = h1s.map((h1, idx) => {
					const y = h1.offsetTop;
					return {
						percent: (totalHeight - y) / (totalHeight + 140) - 0.014,
						direction: "right",
						lengthFactor: 0.35,
						waviness: 1.2,
					};
				});
				// branchSpecs.push({
				// 	percent: 0.95,
				// 	direction: "right",
				// 	lengthFactor: 3,
				// 	waviness: 1.2,
				// 	thickness: 1.2,
				// });
				// for (var i = 1; i <= 30; i++) {
				// 	branchSpecs.push({
				// 		percent: 0.9 + (i / 30) ** 0.4 * 0.1,
				// 		direction: i % 2 === 0 ? "right" : "left",
				// 		lengthFactor: 0.8 * (i / 30),
				// 		waviness: 1.2,
				// 		thickness: 4,
				// 	});
				// }
				
				sidebarBranch.setBranches(branchSpecs);
			}

			updateSidebarBranchesFromHeadings();

			let resizeRaf = null;
			function onResize() {
				if (resizeRaf != null) {
					return;
				}
				resizeRaf = requestAnimationFrame(() => {
					resizeRaf = null;
					branch.setSceneSize(420, 300);
					sidebarBranch.setSceneSize(300, mainHeight());
					updateSidebarBranchesFromHeadings();
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
