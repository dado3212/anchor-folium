<footer>
	<div class="container-wide padding-container">
		<div class="row">
			<div class="col-md-7">
				<p><?php echo " © " . date("Y") . " " . site_name() . " - All rights reserved."; ?> Theme by <a href="http://alexbeals.com">Alex Beals</a>.</p>
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

<script src="<?php echo theme_url('js/jquery.min.js'); ?>"></script>
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
    		history.pushState(null, null, `#${headerId}`);
		}
	});

	// Handle copyable text blocks
	$('code[copyable]').each(function() {
		var codeBlock = $(this);

		var copyButton = $('<button class="copy-button">⧉</button>');
		codeBlock.parent().append(copyButton);
		copyButton.on('click', function() {
			// Create a temporary textarea element to copy the text
			var tempTextarea = $('<textarea>')
				.val(codeBlock.text())
				.attr('readonly', true) // Make it readonly to prevent keyboard opening
				.css({
					position: 'absolute', // Absolute positioning
					top: '-9999px', // Move it off-screen
					left: '-9999px',
				})
				.appendTo('body')
				.select();
			try {
				document.execCommand('copy');

				// Animate success
				copyButton.addClass('success');
				setTimeout(function() {
					copyButton.removeClass('success');
				}, 500);
			} catch (err) {
				// Animate failure
				copyButton.addClass('failure');
				setTimeout(function() {
					copyButton.removeClass('failure');
				}, 500);
			}
			// Remove the temporary textarea element
			tempTextarea.remove();
		});
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