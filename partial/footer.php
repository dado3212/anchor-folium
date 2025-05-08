<footer>
	<div class="container-wide padding-container">
		<div class="row">
			<div class="col-md-7">
				<p><?php echo " © " . date("Y") . " " . site_name() . " - All rights reserved."; ?></p>
			</div>

			<div class="col-md-5">
				<nav>
					<ul class="nav navbar-nav navbar-right">
						<li><a href="<?php echo rss_url(); ?>">RSS</a></li>
						<li class="active"><a href="<?php echo base_url('admin'); ?>" title="Manage your site!">Admin area</a></li>
						<li class="active"><a href="<?php echo base_url(); ?>" title="Return to my website!">Home</a></li>
					</ul>
				</nav>
			</div>
		</div>
	</div>
</footer>

<section id="search">
	<div class="cell">
		<div class="cell-inner">
			<form action="<?php echo search_url(); ?>" method="post">
				<input type="text" name="term" class="input-lg col-md-12" placeholder="Type here to search...">
			</form>
		</div>
	</div>

	<button class="btn btn-default close">&times;</button>
</section>

<script src="<?php echo theme_url('js/bootstrap.min.js'); ?>"></script>

<!-- Lightbox -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.0.47/jquery.fancybox.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.0.47/jquery.fancybox.min.js"></script>

<script>
$(document).ready(function(){
	// Disable hash module
	$.fancybox.defaults.hash = false;
	// Set up searching
	var searchWindow = $('#search');
	$('a[data-target=#search]').click(function(event){
		event.preventDefault();
		searchWindow.css('display', 'table');
	});

	$('button.close').click(function() {
		$('#search').css('display', 'none');
	});

	// Handle clickable headers
	$("article h2, article h3, article h4, article h5, article h6, section#comments > h2").click(function() {
		// When hovering over a header
		var header = $(this);
		var headerId = header.attr('id');
		if (headerId) {
			header.get(0).scrollIntoView({ behavior: 'smooth' });
			history.replaceState(null, '', `#${headerId}`);
		}
	});

	function debounce(func, wait) {
		let timeout;
		return function (...args) {
			clearTimeout(timeout);
			timeout = setTimeout(() => func.apply(this, args), wait);
		};
	}
	function adjustStuff() {
		$('pre[data-line]').each(function () {
			const preElement = $(this);
			const lineHighlights = preElement.find('.line-highlight');
			lineHighlights.each(function () {
				$(this).css('width', '');
			});
			const preScrollWidth = preElement[0].scrollWidth;
			lineHighlights.each(function () {
				$(this).css('width', preScrollWidth + 'px');
			});
		});
	}
	// Attach resize event listener with debounce
	const debouncedResize = debounce(adjustStuff, 200); // Adjust debounce time as needed
	window.addEventListener('resize', debouncedResize);

	// Handle lazy load smaller images
	$('img[data-high-res]').each(function () {
		const image = $(this);
		const highResSrc = image.attr('data-high-res');
		if (highResSrc) {
			const highResImage = new Image();
			highResImage.src = highResSrc;
			highResImage.onload = () => {
				image.attr('src', highResSrc);
			};
		}
	});

	// Handle footnotes in place
	$('.sidenote-indicator').each(function () {
		var sidenoteIndicator = $(this);
		sidenoteIndicator.on('click', function() {
			// If the screen is too big just ignore it
			if (window.innerWidth > 790) {
				return;
			}
			// Toggle visibility
			sidenoteIndicator.parent().find('.sidenote').toggleClass('visible');
		});
	});

	// Double click on <code> will select the whole thing
	document.querySelectorAll('code').forEach(code => {
		console.log(code.className, code.parentElement.tagName);
		// Inline
		if (code.className === '' && code.parentElement.tagName !== 'PRE') {
			code.addEventListener('dblclick', e => {
				e.preventDefault();
				const range = document.createRange();
				range.selectNodeContents(code);
				const selection = window.getSelection();
				selection.removeAllRanges();
				selection.addRange(range);
			});
		}
	});
});
</script>
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-70781506-1', 'auto');
  ga('send', 'pageview');

</script>
</body>
</html>